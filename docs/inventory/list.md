# Listar inventários

`GET /inventories` — `$client->inventories()->list()`

Requer autenticação (token do usuário ou do aplicativo) + contexto de
tenant. Acessível também a operadores — se o token for de um operador com
acesso restrito a inventário(s) específico(s), a lista é automaticamente
filtrada só para eles (sem precisar de nenhum filtro adicional no SDK).

## Filtros

Métodos do `InventoryQuery`, todos opcionais e encadeáveis:

| Método | Campo na API | Descrição |
|---|---|---|
| `status(string\|list<string>)` | `status` | Um status, ou vários (array ou string separada por vírgula) — valores: `pending`, `in_progress`, `completed`, `cancelled`, `recounting` |
| `search(string)` | `search` | Busca em `name` e `description` (case-insensitive, substring) |
| `createdFrom(string)` | `created_from` | Filtra por `created_at >=` |
| `createdTo(string)` | `created_to` | Filtra por `created_at <=` |
| `page(int)` | `page` | Paginação (15 por página) |

## Exemplo

```php
$response = $client->inventories()->list()
    ->status(['in_progress', 'recounting'])
    ->search('centro')
    ->page(2)
    ->get();

foreach ($response->data() as $inventory) {
    $inventory['id'];
    $inventory['attributes']['name'];
    $inventory['attributes']['status'];
}

$response->meta()['total'];
$response->meta()['last_page'];
```

## Resposta

`200 OK` — `data` é uma lista de inventários (mesmo formato de
[`show.md`](./show.md)), com `meta`/`links` de paginação no nível raiz da
resposta. Cada item da lista já vem com os contadores agregados
(`positions_count`, `completed_positions_count`, `items_count`) dentro de
`attributes`, sem precisar de outra chamada.

Não há erro de validação nesta ação — filtros inválidos são simplesmente
ignorados (ex.: `status` com um valor que não existe não dá erro, só não
bate com nada).
