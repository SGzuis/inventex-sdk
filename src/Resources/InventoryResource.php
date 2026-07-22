<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiResponse;

/**
 * Ponto de entrada para inventários:
 *
 *   $client->inventories()->create()->name(...)->send();
 *   $client->inventories()->find($uuid)->start();
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

    public function export(): ApiResponse
    {
        return $this->connector->get('inventories/export');
    }

    /**
     * Modelo de planilha de importação genérico (sem variações de um
     * inventário específico) — para o template já ajustado a um inventário,
     * use Inventory::import()->template().
     */
    public function importTemplate(): string
    {
        return $this->connector->download('inventories/import/template');
    }
}
