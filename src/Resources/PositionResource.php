<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Posições de um inventário — obtido via Inventory::positions():
 *
 *   $inventory->positions()->list();
 *   $inventory->positions()->find($positionUuid)->start();
 */
final class PositionResource
{
    /** @var Connector */
    private $connector;

    /** @var string */
    private $inventoryUuid;

    public function __construct(Connector $connector, string $inventoryUuid)
    {
        $this->connector = $connector;
        $this->inventoryUuid = $inventoryUuid;
    }

    public function list(): ApiResponse
    {
        return $this->connector->get($this->path()->toString());
    }

    public function search(string $code): ApiResponse
    {
        return $this->connector->get($this->path()->append('search')->toString(), ['code' => $code]);
    }

    /**
     * Cria uma posição avulsa (fora do cadastro inicial de itens).
     */
    public function create(string $code): ApiResponse
    {
        return $this->connector->post($this->path()->toString(), ['code' => $code]);
    }

    public function find(string $uuid): Position
    {
        return new Position($this->connector, $this->inventoryUuid, $uuid);
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->inventoryUuid)->append('positions');
    }
}
