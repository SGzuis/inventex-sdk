# Excluir inventário

`DELETE /inventories/{uuid}` — `$inventory->delete()`

Requer papel **owner**, **administrator** ou **member** no workspace.

## Trava por status

**Só é possível excluir um inventário `pending` ou `cancelled`.** Qualquer
outro status (`in_progress`, `completed`, `recounting`) é rejeitado — não
existe exclusão forçada via API, mesmo para quem tem papel de administrador.

```php
$inventory = $client->inventories()->find($uuid);
$inventory->delete();
```

## Resposta

`200 OK` — `{"success": true, "message": "Inventário excluído com sucesso."}`,
sem `data`.

## Erros

- `ValidationException` (422) — status atual não é `pending` nem `cancelled`;
  a mensagem já vem com o status atual por extenso (ex.: *"Não é possível
  excluir um inventário que está Em andamento..."*).
- `NotFoundException` (404) — uuid inexistente ou sem permissão de ver esse
  inventário.
