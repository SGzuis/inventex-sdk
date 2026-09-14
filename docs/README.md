# Documentação do Inventex SDK

Índice da documentação técnica. Para uma visão geral rápida de cada
recurso, veja também o [`README`](../README.md) na raiz do pacote — aqui vai
o detalhe fino de cada ação: parâmetros aceitos, regras de negócio do
servidor, formato exato de resposta e erros possíveis.

## Primeiros passos

| | |
|---|---|
| [`getting-started.md`](./getting-started.md) | Contextualização — o que é o SDK, o que resolver antes de instalar (conta/workspace, aplicativo de integração, token) |
| [`installation.md`](./installation.md) | Instalação num projeto PHP standalone (sem framework) |
| [`laravel.md`](./laravel.md) | Setup e diretrizes específicas para Laravel (config, container, facade, múltiplos workspaces) |

## Inventário (CRUD)

| Ação | Endpoint | Documentação |
|---|---|---|
| Criar | `POST /inventories` | [`inventory/create.md`](./inventory/create.md) |
| Listar | `GET /inventories` | [`inventory/list.md`](./inventory/list.md) |
| Consultar um | `GET /inventories/{uuid}` | [`inventory/show.md`](./inventory/show.md) |
| Atualizar (e mudar status) | `PUT /inventories/{uuid}` | [`inventory/update.md`](./inventory/update.md) |
| Excluir | `DELETE /inventories/{uuid}` | [`inventory/delete.md`](./inventory/delete.md) |
| Itens do catálogo (lote) | `GET/POST/PUT/DELETE /inventories/{uuid}/items` | [`inventory/items.md`](./inventory/items.md) |
| Atividades | `GET /inventories/{uuid}/activities` | [`inventory/activities.md`](./inventory/activities.md) |

## Outros tópicos

- [`webhooks.md`](./webhooks.md) — formato do payload, assinatura HMAC,
  catálogo de eventos, reentrega/idempotência.
- Autenticação (token de aplicativo do workspace) e tratamento de erros:
  seções próprias no [`README`](../README.md) da raiz.

## Formato de resposta (leia isso antes do resto)

Toda resposta bem-sucedida da API segue o mesmo envelope:

```json
{
    "success": true,
    "message": "Inventário criado com sucesso.",
    "data": { "...": "..." }
}
```

`ApiResponse::message()` lê `message`; `ApiResponse::data()` lê `data` cru,
sem tratamento nenhum.

**Um recurso único** (inventário, item) não vem "achatado" dentro de `data`
— segue um formato tipo JSON:API, com os campos de verdade dentro de
`attributes`:

```json
{
    "type": "inventory",
    "id": "97ab9bc7-b372-4486-a5bf-586044e59de9",
    "attributes": {
        "name": "Inventário Loja Centro",
        "status": "pending",
        "...": "..."
    },
    "relationships": { "items": [], "countings": [] }
}
```

Isso importa na prática porque `ApiResponse::get($key)` (e `Inventory::get($key)`
depois de um `show()`) lê a chave **direto de `data`**, sem descer em
`attributes` sozinho:

```php
$response = $client->inventories()->find($uuid)->show();

$response->get('id');       // funciona — "id" está no nível raiz de data
$response->get('name');     // não funciona — "name" está dentro de attributes, get() devolve null aqui

$response->data()['attributes']['name'];   // forma correta de ler campos de negócio
```

Prefira sempre `$response->data()['attributes'][...]` para qualquer campo
que não seja `id`/`type`/`relationships`. Os exemplos de cada ação abaixo já
seguem essa convenção.

**Uma lista** (`list()->get()`) tem `data` como array desses objetos, mais
`links`/`meta` de paginação no nível raiz da resposta (fora de `data`):

```json
{
    "success": true,
    "message": "...",
    "data": [ { "type": "inventory", "id": "...", "attributes": {...} } ],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 }
}
```

`ApiResponse::meta()` lê esse `meta` de paginação.

## Erros

Qualquer resposta com status HTTP ≥ 400 vira exceção tipada — nunca um
retorno silencioso. Ver seção **Tratamento de erros** no
[`README`](../README.md) da raiz para o catálogo completo
(`ValidationException`, `NotFoundException`, `AuthenticationException`,
`InventexException`). Cada arquivo de ação abaixo lista os casos específicos
que geram cada uma.
