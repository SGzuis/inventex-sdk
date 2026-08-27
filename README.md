# Inventex SDK (PHP)

SDK oficial para integrar aplicativos parceiros com a API do Inventex —
cliente fluente por recurso, assinatura HMAC automática das requisições e
verificação pronta dos webhooks recebidos.

Compatível com **PHP 7.2+ e PHP 8.x**, e com **Laravel 5.5 em diante** (a
integração usa package auto-discovery, disponível a partir do Laravel 5.5).
Por isso o SDK não usa recursos exclusivos do PHP 8 (readonly/promoted
properties, named arguments, `match`, union types) — todas as chamadas abaixo
são posicionais.

> **Escopo atual**: a API do Inventex hoje só expõe autenticação e o CRUD de
> inventários (com o sub-recurso de itens, pensado para lotes grandes —
> 20k+ itens via múltiplas chamadas de até 1000). Posições, contagem,
> operadores externos, importação por planilha, workspace e usuários não
> têm mais endpoint na API — só existem no app Web. Este SDK reflete
> exatamente essa superfície; builders para os recursos que voltarem a ter
> endpoint serão adicionados de volta quando isso acontecer.

📄 Documentação técnica aprofundada em [`docs/`](./docs):

- [`docs/webhooks.md`](./docs/webhooks.md) — formato do payload, assinatura,
  catálogo de eventos, reentrega/idempotência.

## Instalação

Este pacote não está publicado no Packagist — o Composer não encontra
`bootstech/inventex-sdk` sozinho. Aponte para o repositório Git direto no
`composer.json` do seu projeto usando um repositório do tipo `vcs`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/SGzuis/inventex-sdk.git"
        }
    ]
}
```

Depois disso, `composer require` funciona normalmente:

```bash
composer require bootstech/inventex-sdk:^1.0
```

O Composer vai buscar as tags do repositório (ex.: `v1.0.0`) para resolver a
versão — sempre trave numa versão/tag específica em produção (`^1.0`, `~1.0.0`,
ou até a tag exata `dev-main` só para testes locais), já que não há Packagist
mediando releases aqui.

Se preferir sem editar o `composer.json` na mão:

```bash
composer config repositories.inventex-sdk vcs https://github.com/SGzuis/inventex-sdk.git
composer require bootstech/inventex-sdk:^1.0
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

Inventex::inventories()->find($uuid)->show();
```

Múltiplos workspaces/tokens na mesma aplicação Laravel (ex.: um SaaS que
integra vários clientes Inventex)? O binding do container é só o caso comum
de "um token fixo via `.env`" — para instâncias adicionais, construa via
`InventexClient::make(...)` diretamente com as credenciais de cada cliente,
sem depender do container.

## Autenticação

`InventexClient::make()`/config Laravel já autentica o **aplicativo**
(Bearer token + assinatura HMAC do workspace). `auth()` é outra coisa: login
de **pessoa** via Sanctum — útil quando o consumidor do SDK precisa logar um
usuário final (ex.: app mobile) em vez de só operar com o token fixo do
aplicativo.

```php
$response = $client->auth()->login('bruno@example.com', 'senha-forte');
$token = $response->get('token'); // monte um novo client com esse token para as chamadas seguintes

$client->auth()->register([
    'name' => 'Bruno Henrique',
    'email' => 'bruno@example.com',
    'password' => 'senha-forte',
    'password_confirmation' => 'senha-forte',
]);

$client->auth()->user();
$client->auth()->updateProfile('Novo Nome', 'novo@example.com');
$client->auth()->updatePassword('senha-atual', 'senha-nova');
$client->auth()->sessions();
$client->auth()->revokeSession($tokenId);
$client->auth()->activities();          // atividades da própria conta
$client->auth()->switchWorkspace($workspaceUuid);
$client->auth()->deleteAccount();
$client->auth()->logout();
```

## Inventários

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

// Referenciar um inventário existente
$inventory = $client->inventories()->find($uuid);
$inventory->show();
$inventory->update(['name' => 'Novo nome']);
$inventory->delete();
$inventory->activities();
```

Início/conclusão/interrupção/cancelamento de contagem, posições e operadores
externos são operados hoje só pelo app Web — não têm endpoint na API.

## Catálogo de itens

Endpoint dedicado porque um inventário pode ter 20k+ itens — envie em lotes
(a API aceita até 1000 itens por requisição, faça múltiplas chamadas para
volumes maiores):

```php
$inventory->items()->list();
$inventory->items()->list('7891000000001'); // busca por texto

// item(position, product, productDescription, quantity, variations)
$inventory->items()->store()
    ->item('A1', '7891000000001', null, '10')
    ->item('A2', '7891000000002', null, '5')
    ->send();

// ou passando um array já pronto (útil pra lotes grandes/gerados dinamicamente)
$items = [
    ['position' => 'A1', 'product' => '7891000000001', 'product_quantity' => '10'],
    ['position' => 'A2', 'product' => '7891000000002', 'product_quantity' => '5'],
];
$inventory->items()->store()->addMany($items)->send();

// update(position, product, productDescription, quantity)
$inventory->items()->find($itemUuid)->update('A1', '7891000000001', null, '20');
$inventory->items()->find($itemUuid)->delete();
```

## Endpoints ainda não modelados

Todo objeto `ApiResponse` retornado por um builder é a mesma classe usada
internamente — se precisar de um endpoint sem builder dedicado, use o escape
hatch, que já passa pela mesma autenticação/assinatura:

```php
$client->raw()->get('inventories/{inventory_uuid}/activities');
```

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
