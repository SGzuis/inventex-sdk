# Operação de inventário — documento técnico

Este documento descreve a máquina de estados do inventário/posições e o
fluxo completo de uma contagem ponta a ponta usando o SDK — da criação até a
exportação do resultado.

## Máquina de estados do inventário

```
PENDING ──start()──► IN_PROGRESS ──conclude()──► COMPLETED
   │                     │
   └──cancel()──►        └──cancel()──►
                    CANCELLED (terminal)
```

- `PENDING`: recém-criado. **Só neste estado o catálogo de itens pode ser
  editado** (`items()->store()`, `import()->upload()`, `find($uuid)->update()/delete()`)
  — depois de iniciado, a Policy do servidor bloqueia alterações no catálogo.
- `IN_PROGRESS`: contagem em andamento — posições podem ser ocupadas,
  produtos contados.
- `COMPLETED` / `CANCELLED`: terminais, sem transição de volta.

`conclude(bool $force = true)` existe no SDK e a Action interna do servidor
(`ConcludeInventoryAction`) de fato suporta as duas semânticas (`force:
true` fecha tudo agora; `force: false` finalizaria só as posições pendentes
da rodada atual e, se restar item não completo, abriria uma nova rodada de
contagem) — **mas o endpoint `state/conclude` da API não lê esse campo do
corpo da requisição**: ele sempre chama a Action com `force: true`, então
`conclude(false)` produz exatamente o mesmo resultado que `conclude(true)`
hoje. Se o seu fluxo depende de uma segunda rodada de contagem
(`secondTotalCount()`), ela precisa ser conduzida manualmente — não existe
hoje, via API, uma forma de encerrar a rodada atual mantendo o inventário em
`IN_PROGRESS`.

## Máquina de estados da posição

```
PENDING ──start()──► IN_PROGRESS ──finish()──► COMPLETED
                          │
                          └──interrupt()──► PENDING (ou mantém IN_PROGRESS
                                             para outro usuário, se
                                             allowMultipleUsersPerPosition)
```

Leitura de posições (`list()`/`search()`) **sempre** funciona, mesmo com o
inventário `PENDING` — só as ações (`start()`/`finish()`/`interrupt()`/criar
posição avulsa/contar produto) exigem `IN_PROGRESS`.

## Fluxo completo, passo a passo

### 1. Criar o inventário e o catálogo de itens

```php
$response = $client->inventories()->create()
    ->name('Inventário Loja Centro')
    ->date('2026-08-01')
    ->countingNumber(1)      // 1 = contagem única; 2/3 habilitam rodadas extras
    ->showBalance()
    ->item('A1', '7891000000001', '10')
    ->item('A2', '7891000000002', '5')
    ->send();

$inventory = $client->inventories()->find($response->get('id'));
```

Alternativas ao `item()` inline, ainda em `PENDING`:

```php
// Em lote, depois de já ter criado o inventário — item(position, product, productDescription, quantity, variations)
$inventory->items()->store()
    ->item('A3', '7891000000003', null, '20')
    ->send();

// Por planilha (assíncrono — ver docs de import abaixo)
$inventory->import()->upload('/caminho/planilha.xlsx');
```

Cada `position` informada em `item()`/`items()` cria a `InventoryPosition`
correspondente automaticamente se ainda não existir — você raramente precisa
criar posições manualmente antes de ter itens.

### 2. Iniciar a contagem

```php
$inventory->start(); // PENDING → IN_PROGRESS — catálogo trava a partir daqui
```

### 3. Operar posições e contar produtos

```php
$position = $inventory->positions()->find($positionUuid);
$position->start(); // ocupa a posição

// localizar o produto esperado
$found = $position->products()->search('7891000000001');
$itemUuid = $found->get('item')['id'] ?? null;

if ($itemUuid) {
    $position->products()->item($itemUuid)->count()
        ->quantity(10)
        ->send();
} else {
    // produto fora do catálogo esperado — cadastra na hora
    $position->products()->store('7891000000099');
}

$position->finish(); // IN_PROGRESS → COMPLETED
```

Uma posição pode receber várias chamadas de `count()` para o mesmo item ao
longo da contagem (retificações) — cada chamada registra uma nova
`CountItem`, a última prevalece para o total ao fechar a posição.

`interrupt()` devolve a posição para conferência posterior sem finalizá-la
(útil se o operador precisa pausar):

```php
$position->interrupt();
```

E no nível do inventário, `interrupt()` interrompe **todas** as posições
ativas do usuário atual de uma vez (ex.: o app perdeu conexão e precisa
liberar tudo que aquele usuário tinha em mãos):

```php
$inventory->interrupt();
```

### 4. Concluir

```php
$inventory->conclude(); // force: true por padrão — fecha agora
```

### 5. Consultar o resultado

```php
$inventory->show();       // atributos atuais, incluindo status
$inventory->export();     // dados agregados para exportação/relatório
$inventory->activities(); // trilha de auditoria (quem fez o quê, quando)
```

### Desvio de fluxo: cancelamento

```php
$inventory->cancel(); // de qualquer estado não-terminal, direto para CANCELLED
```

## O que **não** é necessário para esse fluxo

- **Pareamento de operador via QR** (`/operator/register-device`) — é um
  mecanismo separado para o **próprio dispositivo do conferente de campo**
  se autenticar sozinho com um token de vida curta. Uma integração
  backend-a-backend usa diretamente o token do aplicativo (que já tem
  abilities completas) e não precisa desse fluxo.
- **Gestão de workspace/membros/aplicativos** (`/workspaces/*`) — é
  provisionamento/administração da conta, não parte da operação de um
  inventário específico.

## Tratamento de erros no fluxo

Toda chamada acima pode lançar (ver README, seção "Tratamento de erros"):

- `ValidationException` — ex.: tentar `start()` num inventário já
  `COMPLETED`, ou contar um produto sem quantidade válida. `->errors()` traz
  o erro por campo quando aplicável.
- `NotFoundException` — uuid de inventário/posição/item inexistente, ou sem
  permissão (a API sempre responde 404 em vez de 403 para não vazar
  existência de recursos de outro workspace).
- `AuthenticationException` — token inválido/revogado, ou assinatura HMAC
  incorreta quando o aplicativo exige assinatura.

```php
use Bootstech\InventexSdk\Exceptions\ValidationException;

try {
    $inventory->start();
} catch (ValidationException $e) {
    // "Não é possível iniciar este inventário. Status atual: Concluído."
    report($e->getMessage());
}
```

## Eventos correspondentes (webhooks)

Se o aplicativo tiver `webhook_url` configurado, cada etapa acima dispara um
evento assinado em tempo real — ver [`webhooks.md`](./webhooks.md) para o
formato completo. Mapeamento rápido:

| Ação do SDK | Evento de webhook |
|---|---|
| `$inventory->start()` | `inventory.started` |
| `$inventory->interrupt()` | `inventory.interrupted` |
| `$inventory->conclude()` | `inventory.concluded` |
| `$inventory->cancel()` | `inventory.cancelled` |
| `$position->start()` | `position.started` |
| `$position->interrupt()` | `position.interrupted` |
| `$position->finish()` | `position.finished` |
| `->count()->send()` | `item.counted` |
