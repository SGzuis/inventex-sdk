# Inventex SDK (PHP)

SDK oficial para integrar aplicativos parceiros com a API do Inventex —
cliente fluente por recurso, assinatura HMAC automática das requisições e
verificação pronta dos webhooks recebidos.

Compatível com **PHP 7.2+ e PHP 8.x**, e com **Laravel 5.5 em diante** (a
integração usa package auto-discovery, disponível a partir do Laravel 5.5).
Por isso o SDK não usa recursos exclusivos do PHP 8 (readonly/promoted
properties, named arguments, `match`, union types) — todas as chamadas abaixo
são posicionais.

📄 Documentação técnica aprofundada em [`docs/`](./docs):

- [`docs/operacao.md`](./docs/operacao.md) — máquina de estados de
  inventário/posição e o fluxo completo de uma contagem ponta a ponta.
- [`docs/webhooks.md`](./docs/webhooks.md) — formato do payload, assinatura,
  catálogo de eventos, reentrega/idempotência.

## Instalação

```bash
composer require bootstech/inventex-sdk
```

## Configuração

Gere um aplicativo em **Workspace → Integração → Aplicativos** no Inventex.
Você receberá um **token Bearer** e uma **chave de assinatura HMAC** (exibidos
uma única vez).

```php
use Bootstech\InventexSdk\InventexClient;

$client = InventexClient::make(
    'https://sua-instancia.inventex.com.br/api',
    'SEU_TOKEN_BEARER',
    'SUA_CHAVE_HMAC' // opcional, mas assina automaticamente quando presente
);
```

Sem `signingSecret`, o SDK envia só o Bearer token. Se o aplicativo exigir
assinatura (padrão do sistema), informe a chave — o `Connector` assina cada
requisição sozinho, você não precisa calcular nada manualmente.

Isso é tudo que um projeto PHP puro (sem framework) precisa — o restante
deste README funciona igual em qualquer lugar. Se o consumidor for uma
aplicação Laravel, veja a seção **Uso no Laravel** abaixo para configurar via
`.env`/container em vez de instanciar `InventexClient::make()` na mão.

## Uso no Laravel

O pacote é auto-discovered — nada para registrar manualmente em
`bootstrap/providers.php`. Publique o config e preencha o `.env`:

```bash
php artisan vendor:publish --tag=inventex-sdk-config
```

```env
INVENTEX_BASE_URL=https://sua-instancia.inventex.com.br/api
INVENTEX_TOKEN=SEU_TOKEN_BEARER
INVENTEX_SIGNING_SECRET=SUA_CHAVE_HMAC
INVENTEX_TIMEOUT=15
```

`InventexClient` fica disponível como singleton no container — injete via
constructor (recomendado, testável) ou use a facade:

```php
use Bootstech\InventexSdk\InventexClient;

class SincronizaInventarioJob implements ShouldQueue
{
    private $inventex;

    public function __construct(InventexClient $inventex)
    {
        $this->inventex = $inventex;
    }

    public function handle(): void
    {
        $this->inventex->inventories()->create()->name('Loja Centro')->date('2026-08-01')->send();
    }
}
```

```php
use Bootstech\InventexSdk\Laravel\Facades\Inventex;

Inventex::inventories()->find($uuid)->start();
```

Múltiplos workspaces/tokens na mesma aplicação Laravel (ex.: um SaaS que
integra vários clientes Inventex)? O binding do container é só o caso comum
de "um token fixo via `.env`" — para instâncias adicionais, construa via
`InventexClient::make(...)` diretamente com as credenciais de cada cliente,
sem depender do container.

## Inventários

> Fluxo completo (criação → contagem → conclusão) com a máquina de estados
> explicada: [`docs/operacao.md`](./docs/operacao.md).

```php
// Criar
$response = $client->inventories()->create()
    ->name('Inventário Loja Centro')
    ->date('2026-08-01')
    ->countingNumber(1)
    ->showBalance()
    ->item('A1', '7891000000001', '10')
    ->send();

$uuid = $response->get('id');

// Listar com filtros
$client->inventories()->list()
    ->status('in_progress')
    ->search('centro')
    ->get();

// Referenciar um inventário existente e encadear ações
$inventory = $client->inventories()->find($uuid);
$inventory->start();
$inventory->conclude();      // força a conclusão da contagem atual
$inventory->interrupt();     // interrompe as posições ativas do usuário
$inventory->cancel();
$inventory->export();
$inventory->activities();
```

## Posições e contagem

```php
$inventory = $client->inventories()->find($uuid);

$inventory->positions()->list();
$inventory->positions()->search('A1');

$position = $inventory->positions()->find($positionUuid);
$position->start();
$position->finish();

$position->products()->search('7891000000001');

// Cadastrar um produto avulso (fora do catálogo esperado) direto na posição
$position->products()->store('7891000000099');

$position->products()->item($itemUuid)->count()
    ->quantity(10)
    ->lot('L2026-01')
    ->send();
```

## Catálogo de itens

```php
$inventory->items()->list();
$inventory->items()->list('7891000000001'); // busca por texto

// item(position, product, productDescription, quantity, variations)
$inventory->items()->store()
    ->item('A1', '7891000000001', null, '10')
    ->item('A2', '7891000000002', null, '5')
    ->send();

// update(position, product, productDescription, quantity)
$inventory->items()->find($itemUuid)->update('A1', '7891000000001', null, '20');
$inventory->items()->find($itemUuid)->delete();
```

## Importação por planilha

```php
// Modelo genérico
file_put_contents('template.xlsx', $client->inventories()->importTemplate());

// Modelo já ajustado às variações deste inventário
file_put_contents('template.xlsx', $inventory->import()->template());

// Upload — processamento é assíncrono
$response = $inventory->import()->upload('/caminho/para/planilha.xlsx');
$jobId = $response->get('import_job_id');

// Acompanhar o resultado
$status = $inventory->import()->status($jobId);
$status->get('status'); // pending | processing | completed | failed
$status->get('errors'); // lista de erros por linha, se falhou
```

## Operadores externos (pareamento por QR)

```php
$inventory->operators()->generateQrToken();
$inventory->operators()->approve($operatorUuid);
$inventory->operators()->update($operatorUuid, 'Coletor 1');
$inventory->operators()->revoke($operatorUuid);
```

## Workspace, usuários e atividades

```php
$client->workspace()->get();
$client->workspace()->updateLocationConfig(['zones' => [...]]);

$client->users()->list()->search('bruno')->get();
$client->users()->create()->name('Bruno Henrique')->email('bruno@example.com')->password('senha-forte')->send();
$client->users()->find($uuid)->update(['name' => 'Novo Nome']);

$client->activities()->list()->event('inventory_started')->get();
```

## Endpoints ainda não modelados

Todo objeto `ApiResponse` retornado por um builder é a mesma classe usada
internamente — se precisar de um endpoint sem builder dedicado, use o escape
hatch, que já passa pela mesma autenticação/assinatura:

```php
$client->raw()->get('workspaces/{workspace_uuid}/applications');
$client->raw()->post('invites/{token}/accept');
```

Ainda não modelados como builder (uso via `raw()`): gestão central de
workspaces/membros/aplicativos (`/workspaces/*`), convites (`/invites/*`) e
pareamento do próprio dispositivo do operador (`/operator/register-device`).
Os endpoints de `/auth/*` (login, sessões, perfil do usuário) ficam fora do
escopo do SDK de propósito — são autenticação de pessoa logada via Sanctum,
não de integração de aplicativo parceiro.

## Tratamento de erros

Toda chamada pode lançar uma exceção tipada:

```php
use Bootstech\InventexSdk\Exceptions\{
    AuthenticationException, // 401/403
    NotFoundException,       // 404
    ValidationException,     // 422 — ->errors() traz os erros por campo
    InventexException,       // genérica (rede, 5xx, etc.)
};

try {
    $client->inventories()->create()->send();
} catch (ValidationException $e) {
    foreach ($e->errors() as $field => $messages) {
        // ...
    }
}
```

## Recebendo e verificando webhooks

Se o aplicativo tiver um `webhook_url` configurado, o Inventex envia eventos
assinados (headers `X-Signature`, `X-Timestamp`, `X-Event-Id`) para as
transições de inventário/posição e para cada item contado. Verifique-os no
seu endpoint com o mesmo `signingSecret`:

```php
use Bootstech\InventexSdk\InventexClient;
use Bootstech\InventexSdk\Exceptions\InvalidWebhookSignatureException;

$verifier = InventexClient::webhookVerifier('SUA_CHAVE_HMAC');

try {
    $event = $verifier->verify(
        $_SERVER['HTTP_X_SIGNATURE'],
        $_SERVER['HTTP_X_TIMESTAMP'],
        $_SERVER['HTTP_X_EVENT_ID'],
        file_get_contents('php://input')
    );
} catch (InvalidWebhookSignatureException $e) {
    http_response_code(401);
    exit;
}

switch ($event->name) {
    case 'inventory.started':
        // ...
        break;
    case 'inventory.concluded':
        // ...
        break;
    case 'item.counted':
        // ...
        break;
}

$event->data; // payload do evento (array)
```

Eventos disponíveis: `inventory.started`, `inventory.interrupted`,
`inventory.concluded`, `inventory.cancelled`, `position.started`,
`position.interrupted`, `position.finished`, `item.counted`. Formato
completo do payload, reentrega e idempotência: [`docs/webhooks.md`](./docs/webhooks.md).

## Compatibilidade

| | Suportado |
|---|---|
| PHP | 7.2.5 até 8.x |
| Laravel (opcional) | 5.5 em diante (auto-discovery nativo — nada para registrar manualmente) |
| Guzzle | `^6.3` ou `^7.0` (resolvido automaticamente pelo Composer conforme o restante do seu projeto) |

O `composer.json` só exige `guzzlehttp/guzzle` — `illuminate/support` é
opcional (`suggest`), necessário apenas se você usar a integração Laravel
(`InventexServiceProvider`/facade `Inventex`). Em um projeto PHP puro, nada
do Illuminate é instalado.

**Nota sobre os testes do pacote**: a suíte de testes (`phpunit ^11`) roda em
PHP 8.2+ por exigência do próprio PHPUnit — isso é uma ferramenta de
desenvolvimento do SDK, não afeta o requisito de runtime (`php: ^7.2.5 || ^8.0`)
para quem instala o pacote. O código-fonte evita manualmente qualquer
sintaxe exclusiva do PHP 8 (validado via `php -l` e lido com atenção arquivo
por arquivo), mas não há CI rodando de fato sobre um binário PHP 7.2 neste
momento — se você depende de PHP 7.2/7.3 em produção, vale rodar sua própria
suíte de smoke test antes de subir.

## Desenvolvimento

```bash
composer install
composer test
```
