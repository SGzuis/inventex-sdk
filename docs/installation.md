# Instalação

> Já tem o token do aplicativo e a chave HMAC? Se não, veja
> [`getting-started.md`](./getting-started.md) primeiro — sem isso o SDK
> instala mas não autentica em nada.

Cobre o caso **projeto PHP puro, sem framework**. Se o consumidor for uma
aplicação Laravel, instale igual abaixo e depois siga
[`laravel.md`](./laravel.md) para a configuração — que é diferente (via
`.env`/container, não `InventexClient::make()` na mão).

## 1. Apontar o Composer pro repositório

Este pacote **não está publicado no Packagist** — `composer require
bootstech/inventex-sdk` sozinho não encontra nada. É preciso declarar o
repositório Git direto no `composer.json` do seu projeto, como um
repositório do tipo `vcs`:

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

Ou, sem editar o arquivo na mão:

```bash
composer config repositories.inventex-sdk vcs https://github.com/SGzuis/inventex-sdk.git
```

## 2. Instalar o pacote

```bash
composer require bootstech/inventex-sdk:^1.0
```

O Composer resolve a versão pelas **tags** do repositório (ex.: `v1.0.0`).
Sempre trave numa versão/tag específica em produção (`^1.0`, `~1.0.0`) —
como não há Packagist mediando releases aqui, um `dev-main` sem trava pode
puxar uma mudança breaking sem aviso. Use `dev-main` só para testes locais.

## 3. Configurar o cliente

```php
use Bootstech\InventexSdk\InventexClient;

$client = InventexClient::make(
    'https://sua-instancia.inventex.com.br/api',
    'SEU_TOKEN_BEARER',
    'SUA_CHAVE_HMAC' // opcional, mas assina automaticamente quando presente
);
```

- **`baseUrl`**: inclua o prefixo `/api`.
- **`token`**: o Bearer gerado no passo de `getting-started.md`.
- **`signingSecret`**: opcional — sem ele o SDK manda só o Bearer token; se
  informado, o `Connector` assina cada requisição sozinho (headers
  `X-Signature`/`X-Timestamp`), você não calcula nada manualmente.

Alternativa via array de config (útil se você já monta isso a partir de
`$_ENV` ou de outro lugar centralizado):

```php
$client = InventexClient::fromArray([
    'base_url' => $_ENV['INVENTEX_BASE_URL'],
    'token' => $_ENV['INVENTEX_TOKEN'],
    'signing_secret' => $_ENV['INVENTEX_SIGNING_SECRET'] ?? null, // opcional
    'timeout' => 15, // opcional, default 15s
]);
```

Pronto — o `$client` está pronto pra uso. Próximo passo:
[`inventory/create.md`](./inventory/create.md), ou o índice completo em
[`README.md`](./README.md).
