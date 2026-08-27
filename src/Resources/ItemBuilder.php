<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;
use Bootstech\InventexSdk\Support\Payload;

/**
 * Builder fluente para inserir itens em lote no catálogo de um inventário:
 *
 *   $inventory->items()->store()
 *       ->item('A1', '7891000000001', null, '10')
 *       ->item('A2', '7891000000002', null, '5')
 *       ->send();
 *
 * Ou passando um array já pronto:
 *
 *   $inventory->items()->store()->addMany($items)->send();
 *
 * Assinatura de item(): (position, product, productDescription, quantity, variations).
 */
final class ItemBuilder
{
    /** @var list<array<string, mixed>> */
    private $items = [];

    /** @var Connector */
    private $connector;

    /** @var string */
    private $inventoryUuid;

    public function __construct(Connector $connector, string $inventoryUuid)
    {
        $this->connector = $connector;
        $this->inventoryUuid = $inventoryUuid;
    }

    public function item(string $position, ?string $product = null, ?string $productDescription = null, ?string $quantity = null, ?array $variations = null): self
    {
        $this->items[] = Payload::withoutNulls([
            'position' => $position,
            'product' => $product,
            'product_description' => $productDescription,
            'product_quantity' => $quantity,
            'variations' => $variations,
        ]);

        return $this;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function addMany(array $items): self
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    public function send(): ApiResponse
    {
        $path = ApiPath::make('inventories')->segment($this->inventoryUuid)->append('items');

        return $this->connector->post($path->toString(), ['items' => $this->items]);
    }
}
