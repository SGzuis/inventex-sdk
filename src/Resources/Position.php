<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;
use Bootstech\InventexSdk\Support\Payload;

/**
 * Referência fluente a uma posição já identificada:
 *
 *   $position->start();
 *   $position->products()->search('7891000000001');
 *   $position->products()->item($itemUuid)->count()->quantity(10)->send();
 */
final class Position
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

    /**
     * ATENÇÃO: diferente de interrupt(), o endpoint `positions/{position}/start`
     * da API não lê nenhum campo do corpo — ele sempre inicia a posição em
     * nome do usuário do token autenticado, ignorando silenciosamente
     * qualquer $userId informado aqui. O parâmetro existe só por simetria
     * com interrupt() e compatibilidade futura; hoje não há como iniciar uma
     * posição em nome de outro usuário via API.
     */
    public function start(?int $userId = null): ApiResponse
    {
        return $this->connector->post($this->path()->append('start')->toString(), Payload::withoutNulls(['user_id' => $userId]));
    }

    public function interrupt(?int $userId = null): ApiResponse
    {
        return $this->connector->post($this->path()->append('interrupt')->toString(), Payload::withoutNulls(['user_id' => $userId]));
    }

    public function finish(): ApiResponse
    {
        return $this->connector->post($this->path()->append('finish')->toString());
    }

    public function products(): PositionProductResource
    {
        return new PositionProductResource($this->connector, $this->inventoryUuid, $this->uuid);
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->inventoryUuid)->append('positions')->segment($this->uuid);
    }
}
