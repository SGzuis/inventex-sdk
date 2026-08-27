# Atualizar inventário (e mudar status)

`PUT /inventories/{uuid}` — `$inventory->update(array $data)`

Requer papel **owner**, **administrator** ou **member** no workspace. Este é
também o único jeito de **iniciar, concluir, cancelar ou recontar** um
inventário via API — o SDK não expõe `start()`/`conclude()`/`cancel()` como
métodos próprios porque a API não tem mais esses endpoints dedicados; tudo
passa pelo campo `status` deste mesmo `update()`.

## Trava por status

- **Só é possível editar campos de negócio (nome, data, regras, variações,
  etc.) enquanto o inventário está `pending`.** Fora disso, qualquer campo
  que não seja `status` é rejeitado com erro de validação, mesmo que o
  valor enviado seja igual ao já salvo.
- **O campo `status`, isoladamente, pode ser alterado em qualquer momento**
  — desde que a transição seja uma das permitidas (tabela abaixo). É assim
  que o inventário avança de estado.

```php
// só permitido com o inventário pending
$inventory->update([
    'name' => 'Novo nome',
    'show_balance' => true,
]);

// transição de estado — sempre permitido, independente do status atual
// (desde que seja uma transição válida)
$inventory->update(['status' => 'in_progress']);
```

Misturar os dois na mesma chamada (`['status' => 'in_progress', 'name' =>
'Novo nome']`) só funciona se o inventário ainda estiver `pending` — depois
disso, envie `status` sozinho.

## Transições de status válidas

| De | Para |
|---|---|
| `pending` | `in_progress`, `cancelled` |
| `in_progress` | `completed`, `cancelled` |
| `completed` | `recounting` |
| `recounting` | `completed` |
| `cancelled` | *(nenhuma — terminal)* |

Transição fora dessa tabela devolve erro de validação, não é ignorada
silenciosamente.

### Concluir (`status => 'completed'`)

Ao mandar `completed` a partir de `in_progress`/`recounting`, o servidor
valida se a contagem está de fato completa (todas as posições/rodadas
esperadas). Se houver pendência, a resposta é um erro de validação cuja
mensagem lista o que falta — não uma lista genérica, o `message` já vem
com o resumo humano-legível da pendência.

## Outros campos aceitáveis (só com `pending`)

Mesmos campos de [`create.md`](./create.md#parâmetros), todos opcionais
aqui (`sometimes`, não reenvie o que não quer mudar):
`name`, `description`, `location`, `date`, `count_type`, `counting_number`,
`require_full_recount`, `second_total_count`, `show_balance`,
`allow_multiple_users_per_position`, `interrupt_reverts_position`,
`variations`, `quality_enabled`, `quality_requires_photo`, `quality_types`.

Alterar o `type` de uma variação que já tem item ou contagem vinculada é
bloqueado (o `name` da variação continua igual, só o tipo é travado nesse
caso).

## Resposta

`200 OK` — inventário atualizado, mesmo formato de [`show.md`](./show.md).
`Inventory::update()` **não** atualiza sozinho o cache interno do objeto —
leia o retorno da chamada ou rode `->show()` de novo se precisar do objeto
`$inventory` refletindo a mudança.

## Erros (`ValidationException`, 422)

- Qualquer campo que não seja `status` enviado com o inventário fora de `pending`.
- Transição de `status` fora da tabela acima.
- Concluir com pendência de contagem (mensagem descreve o que falta).
- Mesmas validações de campo de [`create.md`](./create.md#erros-validationexception-422).
