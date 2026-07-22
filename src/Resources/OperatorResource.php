<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Operadores externos temporários de um inventário (pareados via QR) —
 * obtido via Inventory::operators():
 *
 *   $inventory->operators()->generateQrToken();
 *   $inventory->operators()->approve($operatorUuid);
 */
final class OperatorResource
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

    /**
     * Gera o token embutido no QR code de pareamento (validade de 24h).
     */
    public function generateQrToken(): ApiResponse
    {
        return $this->connector->post($this->path()->append('qr-token')->toString());
    }

    public function approve(string $operatorUuid): ApiResponse
    {
        return $this->connector->post($this->path()->segment($operatorUuid)->append('approve')->toString());
    }

    public function update(string $operatorUuid, string $label): ApiResponse
    {
        return $this->connector->put($this->path()->segment($operatorUuid)->toString(), ['label' => $label]);
    }

    public function revoke(string $operatorUuid): ApiResponse
    {
        return $this->connector->delete($this->path()->segment($operatorUuid)->toString());
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->inventoryUuid)->append('operators');
    }
}
