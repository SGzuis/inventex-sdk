<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Referência fluente a um item do catálogo já identificado:
 *
 *   $item->update('A1', '7891000000001', null, '20');
 *   $item->delete();
 *
 * Assinatura de update(): (position, product, productDescription, quantity).
 */
final class Item
{
    /** @var Connector */
    private $connector;

    /** @var string */
    private $inventoryUuid;

    /** @var string */
    public $uuid;

    public function __construct(Connector $connector, string $inventoryUuid, string $uuid)
    {
        $this->connector = $connector;
        $this->inventoryUuid = $inventoryUuid;
        $this->uuid = $uuid;
    }

    public function update(string $position, string $product, ?string $productDescription = null, string $quantity = '0'): ApiResponse
    {
        return $this->connector->put($this->path()->toString(), [
            'position' => $position,
            'product' => $product,
            'product_description' => $productDescription,
            'product_quantity' => $quantity,
        ]);
    }

    public function delete(): ApiResponse
    {
        return $this->connector->delete($this->path()->toString());
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->inventoryUuid)->append('items')->segment($this->uuid);
    }
}
