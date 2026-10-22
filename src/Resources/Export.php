<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Exceptions\InventexException;
use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Referência a uma exportação (xlsx/csv) já solicitada — a geração é
 * assíncrona, então consulte o status até "completed" antes de baixar:
 *
 *   $export = $inventory->requestExport('csv');
 *   $export->status()->get('attributes')['status']; // pending|processing|completed|failed|expired
 *   $export->saveTo('/tmp/inventario.csv');
 */
final class Export
{
    /** @var Connector */
    private $connector;

    /** @var string */
    private $inventoryUuid;

    /** @var string */
    public $id;

    public function __construct(Connector $connector, string $inventoryUuid, string $id)
    {
        $this->connector = $connector;
        $this->inventoryUuid = $inventoryUuid;
        $this->id = $id;
    }

    public function status(): ApiResponse
    {
        return $this->connector->get($this->path()->toString());
    }

    /**
     * Bytes crus do arquivo. Lança InventexException (HTTP 409) se a
     * exportação ainda não foi concluída.
     */
    public function download(): string
    {
        return $this->connector->download($this->path()->append('download')->toString());
    }

    public function saveTo(string $path): void
    {
        if (file_put_contents($path, $this->download()) === false) {
            throw new InventexException("Não foi possível gravar a exportação em {$path}.");
        }
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->inventoryUuid)->append('exports')->segment($this->id);
    }
}
