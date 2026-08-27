<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

/**
 * Ponto de entrada para inventários:
 *
 *   $client->inventories()->create()->name(...)->send();
 *   $client->inventories()->find($uuid)->show();
 *   $client->inventories()->list()->status('pending')->get();
 */
final class InventoryResource extends AbstractResource
{
    public function create(): InventoryBuilder
    {
        return new InventoryBuilder($this->connector);
    }

    public function list(): InventoryQuery
    {
        return new InventoryQuery($this->connector);
    }

    public function find(string $uuid): Inventory
    {
        return new Inventory($this->connector, $uuid);
    }
}
