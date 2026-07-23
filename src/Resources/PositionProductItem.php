<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Um item de catálogo dentro de uma posição:
 *
 *   $item->show();
 *   $item->count()->quantity(10)->variations(['lote' => 'L123'])->send();
 */
final class PositionProductItem
{
    /** @var Connector */
    private $connector;

    /** @var ApiPath */
    private $productsPath;

    /** @var string */
    private $itemUuid;

    public function __construct(Connector $connector, ApiPath $productsPath, string $itemUuid)
    {
        $this->connector = $connector;
        $this->productsPath = $productsPath;
        $this->itemUuid = $itemUuid;
    }

    public function show(): ApiResponse
    {
        return $this->connector->get($this->path()->toString());
    }

    public function count(): CountBuilder
    {
        return new CountBuilder($this->connector, $this->path()->append('count')->toString());
    }

    private function path(): ApiPath
    {
        return $this->productsPath->segment($this->itemUuid);
    }
}
