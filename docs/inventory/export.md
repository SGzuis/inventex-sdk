# Exportar inventário (Excel / CSV)

`POST /inventories/{uuid}/exports` — `$inventory->requestExport()`

Gera a planilha detalhada do inventário (uma linha por item, com as
contagens de cada rodada e a divergência). Requer permissão de **exportar**
o inventário; funciona em qualquer status. A geração é **assíncrona**:
solicite, acompanhe o status e baixe quando concluir. O arquivo é apagado
24 h depois de pronto.

```php
$inventory = $client->inventories()->find($uuid);

$export = $inventory->requestExport('csv'); // 'xlsx' (padrão) ou 'csv'

do {
    sleep(3);
    $status = $export->status()->get('attributes')['status'];
} while (in_array($status, ['pending', 'processing'], true));

if ($status === 'completed') {
    $export->saveTo('/tmp/inventario.csv'); // ou $export->download() para os bytes
}
```

| Parâmetro | Descrição |
|---|---|
| `format` | `xlsx` (padrão) ou `csv`. Outro valor → `ValidationException` (422) |

## Status

`$export->status()` → `GET /inventories/{uuid}/exports/{id}`; `attributes`
traz `status` (`pending`, `processing`, `completed`, `failed`, `expired`),
`format`, `progress` (0–100), `total_rows` e `error` (quando `failed`).
Recupere uma exportação já solicitada com `$inventory->findExport($id)`.

## Download

`$export->download()` / `saveTo($path)` → `GET .../exports/{id}/download`.
Antes de `completed` lança `InventexException` (HTTP 409); `NotFoundException`
se a exportação não pertence ao inventário.
