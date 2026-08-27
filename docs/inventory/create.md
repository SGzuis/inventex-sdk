# Criar inventário

`POST /inventories` — `$client->inventories()->create()`

Requer contexto de tenant (token do aplicativo do workspace, ver
[`README`](../../README.md#configuração)) e papel **owner**, **administrator**
ou **member** no workspace.

## Parâmetros

Métodos do `InventoryBuilder`, todos encadeáveis, chame só os que precisar:

| Método | Campo na API | Obrigatório | Descrição |
|---|---|---|---|
| `name(string)` | `name` | ✅ | Único por workspace |
| `description(string)` | `description` | | |
| `location(array)` | `location` | | Pares chave/valor livres (ex.: `['warehouse' => 'CD-01']`) |
| `date(string)` | `date` | ✅ | Formato `Y-m-d`, não pode ser no passado |
| `countType(string)` | `count_type` | | Rótulo livre (ex.: "Geral", "Cíclico") |
| `countingNumber(int)` | `counting_number` | | 1 a 3 rodadas de contagem (padrão do servidor: 1) |
| `showBalance(bool = true)` | `show_balance` | ✅ | Exibe quantidade esperada em tela pro operador |
| `requireFullRecount(bool = true)` | `require_full_recount` | | Padrão desligado: item que bateu não volta a ser pedido nas próximas rodadas. Ligue para forçar recontagem completa sempre |
| `secondTotalCount(bool = true)` | `second_total_count` | | Força recontagem de tudo especificamente na 2ª rodada, independente de `requireFullRecount` |
| `allowMultipleUsersPerPosition(bool = true)` | `allow_multiple_users_per_position` | | |
| `interruptRevertsPosition(bool = true)` | `interrupt_reverts_position` | | Ao interromper, posição volta para "Pendente" em vez de continuar ocupada |
| `variations(array)` | `variations` | | Lista de `{name, type, validate_item, options?}` — ver tabela abaixo |
| `item(position, product?, quantity?, variations?)` | `items[]` | | Adiciona um item; pode chamar várias vezes |
| `addMany(array)` | `items[]` | | Adiciona vários itens de uma vez (array pronto); combina com `item()` |

`show_balance` é o único booleano obrigatório na criação (servidor exige o
campo, mesmo que seja `false`) — os demais têm default no servidor se
omitidos.

### `variations` — schema de variação por item

| Campo | Tipo | Obrigatório | Observação |
|---|---|---|---|
| `name` | string | ✅ | Chave usada depois no `variations` de cada item |
| `type` | `text\|number\|date\|boolean\|list` | ✅ | |
| `validate_item` | bool | ✅ | Se `true`, mesmo produto com variação diferente conta como item distinto |
| `options` | list\<string\> | Obrigatório se `type = list` | Valores distintos aceitos |

### `items` — catálogo inicial (opcional)

Alternativa a cadastrar depois via [`items.md`](./items.md) — mesmas regras
de validação, com um limite menor aqui: **até 1000 itens por chamada de
criação** (para volumes maiores, crie o inventário vazio e use o endpoint de
itens em lote, que aceita o mesmo limite mas por chamada, então múltiplas
chamadas).

| Campo | Obrigatório |
|---|---|
| `position` | ✅ |
| `product` | |
| `product_description` | |
| `product_quantity` | numérico, mínimo 0 |
| `variations` | array, chaves = `name` de cada variação declarada acima |

## Exemplo

```php
$response = $client->inventories()->create()
    ->name('Inventário Loja Centro')
    ->date('2026-08-01')
    ->countingNumber(2)
    ->showBalance()
    ->secondTotalCount()
    ->variations([
        ['name' => 'Lote', 'type' => 'text', 'validate_item' => false],
    ])
    ->item('A1', '7891000000001', '10', ['Lote' => 'L2026-01'])
    ->item('A2', '7891000000002', '5')
    ->send();

$response->get('id');                              // uuid do inventário criado
$response->data()['attributes']['status'];          // "pending"
```

## Resposta

`201 Created` — inventário recém-criado, no formato descrito em
[`../README.md#formato-de-resposta`](../README.md#formato-de-resposta),
`status` sempre `"pending"`.

## Erros (`ValidationException`, 422)

- `name`: obrigatório, já existe outro inventário com o mesmo nome no workspace.
- `date`: obrigatório, formato inválido, ou data no passado.
- `counting_number`: fora do intervalo 1–3.
- `variations.*.options`: ausente quando `type = list`.
- Item com `position` vazia, ou `product_quantity` negativo/maior que o limite.
- Mesma combinação posição + produto + variação repetida entre os itens enviados.
