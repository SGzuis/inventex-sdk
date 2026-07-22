<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Catálogo de itens esperados de um inventário — obtido via Inventory::items():
 *
 *   $inventory->items()->list();
 *   $inventory->items()->store()->item('A1', '7891000000001', null, '10')->send();
 *   $inventory->items()->find($itemUuid)->update(...);
 */
final class ItemResource
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

    public function list(?string $search = null): ApiResponse
    {
        return $this->connector->get($this->path()->toString(), array_filter(['search' => $search]));
    }

    public function store(): ItemBuilder
    {
        return new ItemBuilder($this->connector, $this->inventoryUuid);
    }

    public function find(string $itemUuid): Item
    {
        return new Item($this->connector, $this->inventoryUuid, $itemUuid);
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->inventoryUuid)->append('items');
    }
}
