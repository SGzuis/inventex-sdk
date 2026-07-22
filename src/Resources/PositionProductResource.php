<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

final class PositionProductResource
{
    /** @var Connector */
    private $connector;

    /** @var string */
    private $inventoryUuid;

    /** @var string */
    private $positionUuid;

    public function __construct(Connector $connector, string $inventoryUuid, string $positionUuid)
    {
        $this->connector = $connector;
        $this->inventoryUuid = $inventoryUuid;
        $this->positionUuid = $positionUuid;
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
     * Cadastra um produto avulso (fora do catálogo esperado) diretamente
     * nesta posição, identificado pelo código informado.
     */
    public function store(string $code): ApiResponse
    {
        return $this->connector->post($this->path()->toString(), ['code' => $code]);
    }

    public function item(string $itemUuid): PositionProductItem
    {
        return new PositionProductItem($this->connector, $this->path(), $itemUuid);
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->inventoryUuid)->append('positions')->segment($this->positionUuid)->append('products');
    }
}
