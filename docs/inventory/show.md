# Consultar um inventário

`GET /inventories/{uuid}` — `$client->inventories()->find($uuid)->show()`

Requer autenticação + contexto de tenant. Mesma visibilidade de
[`list.md`](./list.md): operador com acesso restrito só consegue ver os
inventários permitidos para ele.

## Exemplo

```php
$inventory = $client->inventories()->find($uuid);
$response = $inventory->show();

$response->data()['attributes']['name'];
$response->data()['attributes']['status'];

// depois do show(), o objeto $inventory também guarda os atributos —
// $inventory->get('id') funciona (chave de nível raiz), mas campos de
// negócio (name, status, ...) só existem dentro de attributes, então use
// $inventory->toArray()['attributes']['...'] pelo mesmo motivo do get()
// explicado em ../README.md#formato-de-resposta.
```

## Resposta

`200 OK` — objeto completo do inventário, incluindo os campos agregados
(`positions_count`, `completed_positions_count`, `items_count`) e, quando
carregados pelo servidor, `relationships.items`/`relationships.countings`.
Formato completo: [`../README.md#formato-de-resposta`](../README.md#formato-de-resposta).

## Erros

- `NotFoundException` (404) — uuid inexistente **ou** sem permissão de
  visualizar esse inventário (a API não diferencia os dois casos de
  propósito, para não vazar a existência de um recurso que você não pode ver).

## Ver também

- [`activities.md`](./activities.md) — histórico de eventos deste inventário.
