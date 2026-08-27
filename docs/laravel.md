# Uso no Laravel

> Instale o pacote primeiro: [`installation.md`](./installation.md), passos
> 1 e 2 (repositório + `composer require`) — só o passo 3 (configurar o
> cliente na mão) é diferente aqui, o Laravel cuida disso via `.env` +
> container.

## Requisito

Laravel **5.5 em diante** — a integração usa package auto-discovery,
disponível a partir dessa versão. Nada para registrar manualmente em
`bootstrap/providers.php` (ou `config/app.php`, nas versões mais antigas).

## 1. Publicar o config

```bash
php artisan vendor:publish --tag=inventex-sdk-config
```

Isso cria `config/inventex-sdk.php`, lido a partir das variáveis de
ambiente abaixo.

## 2. Preencher o `.env`

```env
INVENTEX_BASE_URL=https://sua-instancia.inventex.com.br/api
INVENTEX_TOKEN=SEU_TOKEN_BEARER
INVENTEX_SIGNING_SECRET=SUA_CHAVE_HMAC
INVENTEX_TIMEOUT=15
```

Token e chave são os gerados em **Workspace → Integração → Aplicativos** no
Inventex — ver [`getting-started.md`](./getting-started.md) se ainda não
tem. `INVENTEX_SIGNING_SECRET` e `INVENTEX_TIMEOUT` são opcionais
(`timeout` default 15s).

## 3. Usar

`InventexClient` fica disponível como **singleton no container** — injete
via construtor (recomendado, testável):

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

Ou use a facade, se preferir chamar direto sem injetar:

```php
use Bootstech\InventexSdk\Laravel\Facades\Inventex;

Inventex::inventories()->find($uuid)->show();
```

Os dois falam com a mesma instância registrada no container — a facade só é
um atalho estático em cima dela.

## Múltiplos workspaces/tokens numa mesma aplicação

O binding do container é o caso comum: **um token fixo, via `.env`**. Se sua
aplicação precisa falar com vários workspaces/clientes Inventex ao mesmo
tempo (ex.: um SaaS que integra vários clientes, cada um com seu próprio
aplicativo/token Inventex), o binding do container não serve — construa uma
instância por cliente, direto:

```php
use Bootstech\InventexSdk\InventexClient;

$clientDoWorkspaceA = InventexClient::make($baseUrlA, $tokenA, $secretA);
$clientDoWorkspaceB = InventexClient::make($baseUrlB, $tokenB, $secretB);
```

Sem depender do container/facade nesse caso — armazene as credenciais de
cada cliente onde fizer sentido pro seu domínio (tabela de configuração,
outro secret manager, etc.) e monte o `InventexClient` sob demanda.

## Diretrizes

- **Não** commite `INVENTEX_TOKEN`/`INVENTEX_SIGNING_SECRET` no `.env` de
  verdade — só no `.env.example`, com placeholder.
- `illuminate/support` é `suggest` no `composer.json` do SDK (não é
  dependência obrigatória) — necessário só pra essa integração Laravel
  (`InventexServiceProvider`/facade). Fora de uma app Laravel, nada do
  Illuminate é instalado.
- Erros continuam sendo exceções tipadas normais do PHP (`ValidationException`,
  `NotFoundException`, etc.) — capture como em qualquer outro lugar do seu
  código Laravel, não há tradução especial pra `Response`/`Exception Handler`
  feita pelo SDK. Ver seção **Tratamento de erros** no
  [`README`](../README.md) da raiz.

Próximo passo: [`inventory/create.md`](./inventory/create.md), ou o índice
completo em [`README.md`](./README.md).
