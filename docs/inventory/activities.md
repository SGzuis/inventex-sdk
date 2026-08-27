# Atividades do inventário

`GET /inventories/{uuid}/activities` — `$inventory->activities()`

Histórico de eventos deste inventário: criação, mudanças de status,
posições, contagens e itens — tudo que aconteceu dentro dele, mais recente
primeiro. Requer permissão de **visualizar** o inventário (mesma de
[`show.md`](./show.md)), funciona em qualquer status.

## Filtros

Passe como query string (não há builder fluente para esta ação, é uma
chamada direta):

```php
$response = $client->raw()->get(
    "inventories/{$uuid}/activities",
    ['position' => 'A1', 'product' => '7891000000001']
);
```

| Filtro | Descrição |
|---|---|
| `position` | Só atividades relacionadas a essa posição |
| `product` | Só atividades relacionadas a esse código de produto |

Sem filtro, `$inventory->activities()` já traz tudo.

## Resposta

`200 OK`:

```json
{
    "success": true,
    "message": "Atividades do inventário.",
    "data": {
        "activities": {
            "data": [
                {
                    "id": 1234,
                    "log_name": "default",
                    "description": "Inventário criado",
                    "event": "inventory_created",
                    "subject_type": "App\\Models\\Inventory",
                    "subject_id": 42,
                    "causer_type": "App\\Models\\User",
                    "causer_id": 7,
                    "causer_name": "Bruno Henrique",
                    "properties": { "...": "..." },
                    "created_at": "2026-08-27T12:00:00+00:00"
                }
            ],
            "links": {"...": "..."},
            "meta": {"...": "..."}
        }
    }
}
```

Repare duas coisas: a lista paginada fica **aninhada** em `data.activities`
(não em `data` direto — `$response->get('activities')['data']` para a lista
de eventos, `$response->get('activities')['meta']` para a paginação,
15/página); e cada atividade **não** segue o formato `type/id/attributes` do
resto da API — é um objeto simples, com `id`, `event`, `causer_name`,
`properties` etc. direto no nível raiz de cada item.
