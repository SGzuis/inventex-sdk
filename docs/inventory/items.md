# Itens do inventário

Sub-recurso do catálogo de itens esperados — separado do CRUD principal do
inventário porque um inventário pode ter **20 mil ou mais itens**: a API
aceita **até 1000 itens por requisição** no lote de inserção, então volumes
maiores exigem múltiplas chamadas (não existe endpoint de upload de arquivo
via API — para planilha, use o app Web).

Acessado via `$inventory->items()`, que devolve o `ItemResource` com
`list()`/`store()`/`find()`.

## Listar (`GET /inventories/{uuid}/items`)

Requer só permissão de **visualizar** o inventário — funciona em qualquer
status, diferente de criar/editar/excluir item abaixo.

```php
$inventory->items()->list();                    // todos, paginado (15/página)
$inventory->items()->list('7891000000001');      // busca por texto (código/descrição)
```

**Atenção ao formato**: esta é a única ação de inventário cuja resposta
**não** usa o envelope `{success, message, data}` do resto da API — vem no
formato padrão de resource collection do Laravel, direto:

```json
{
    "data": [ { "type": "inventory_item", "id": "...", "attributes": {"position": "A1", "product": "7891000000001", "...": "..."} } ],
    "links": {"...": "..."},
    "meta": {"...": "..."}
}
```

`->data()` e `->meta()` continuam funcionando normalmente (leem `data`/`meta`
do payload cru); só não existe `->message()` aqui (retorna string vazia).

## Adicionar em lote (`POST /inventories/{uuid}/items`)

Requer papel **owner**, **administrator** ou **member**, **e** o inventário
precisar estar `pending` — depois de iniciado, o catálogo trava (mesma regra
de [`update.md`](./update.md#trava-por-status)).

```php
$inventory->items()->store()
    ->item('A1', '7891000000001', null, '10')             // position, product, productDescription, quantity
    ->item('A2', '7891000000002', 'Descrição', '5', ['Lote' => 'L2026-01'])
    ->send();

// ou com um array já pronto (útil pra lotes grandes/gerados dinamicamente)
$items = [
    ['position' => 'A1', 'product' => '7891000000001', 'product_quantity' => '10'],
    ['position' => 'A2', 'product' => '7891000000002', 'product_quantity' => '5'],
];
$inventory->items()->store()->addMany($items)->send();
```

| Campo do item | Obrigatório |
|---|---|
| `position` | ✅ |
| `product` | |
| `product_description` | |
| `product_quantity` | numérico, mínimo 0 |
| `variations` | array, chaves = nome de cada variação declarada na criação do inventário |

### Resposta

`201 Created` — `{"success": true, "message": "Itens inseridos com sucesso.", "data": {"count": N}}`.
Aqui `data` **não** segue o formato `type/id/attributes` (é um objeto solto),
então `$response->get('count')` funciona direto.

### Erros (`ValidationException`, 422)

- Inventário fora de `pending`.
- Mesma combinação posição + produto + variação repetida no lote enviado, ou
  já existente no inventário.
- Mais de 1000 itens numa única chamada.
- `position` ausente em algum item.

## Atualizar um item (`PUT /inventories/{uuid}/items/{item_uuid}`)

Mesma permissão de criar (papel + inventário `pending`).

```php
// update(position, product, productDescription, quantity)
$inventory->items()->find($itemUuid)->update('A1', '7891000000001', null, '20');
```

`position`, `product` e `product_quantity` são obrigatórios nesta ação —
diferente do lote de criação, aqui não há update parcial.

### Resposta

`200 OK` — item atualizado, no formato `type/id/attributes` padrão (ver
[`../README.md#formato-de-resposta`](../README.md#formato-de-resposta)).

## Excluir um item (`DELETE /inventories/{uuid}/items/{item_uuid}`)

Mesma permissão de criar/atualizar.

```php
$inventory->items()->find($itemUuid)->delete();
```

`200 OK` — `{"success": true, "message": "Item removido com sucesso."}`, sem `data`.

## Erros comuns às três ações de escrita

- `ValidationException` (422) — inventário fora de `pending`.
- `NotFoundException` (404) — inventário ou item inexistente/sem permissão.
