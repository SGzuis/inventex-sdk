<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Builder fluente para registrar uma contagem de item:
 *
 *   $position->products()->item($itemUuid)->count()
 *       ->quantity(10)
 *       ->variations(['lote' => 'L2026-01', 'cor' => 'azul'])
 *       ->send();
 */
final class CountBuilder
{
    /** @var array<string, mixed> */
    private $payload = [];

    /** @var Connector */
    private $connector;

    /** @var string */
    private $path;

    public function __construct(Connector $connector, string $path)
    {
        $this->connector = $connector;
        $this->path = $path;
    }

    /**
     * @param  int|float|string  $quantity
     */
    public function quantity($quantity): self
    {
        $this->payload['counted_quantity'] = $quantity;

        return $this;
    }

    public function product(string $product): self
    {
        $this->payload['product'] = $product;

        return $this;
    }

    public function productDescription(string $description): self
    {
        $this->payload['product_description'] = $description;

        return $this;
    }

    /**
     * @param  array<string, string>  $variations  Chaves definidas dinamicamente por
     *                                              inventário (ex.: 'lote', 'cor') — não
     *                                              existe campo fixo de lote na API, é
     *                                              só mais uma variação configurável.
     */
    public function variations(array $variations): self
    {
        $this->payload['variations'] = $variations;

        return $this;
    }

    public function send(): ApiResponse
    {
        return $this->connector->post($this->path, $this->payload);
    }
}
