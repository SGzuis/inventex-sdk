<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;
use Bootstech\InventexSdk\Support\Payload;

/**
 * Builder fluente para criar um inventário:
 *
 *   $client->inventories()->create()
 *       ->name('Inventário Loja Centro')
 *       ->date('2026-08-01')
 *       ->countingNumber(1)
 *       ->showBalance()
 *       ->item('A1', '7891000000001', '10')
 *       ->send();
 */
final class InventoryBuilder
{
    /** @var array<string, mixed> */
    private $payload = [];

    /** @var Connector */
    private $connector;

    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    public function name(string $name): self
    {
        $this->payload['name'] = $name;

        return $this;
    }

    public function description(string $description): self
    {
        $this->payload['description'] = $description;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $location
     */
    public function location(array $location): self
    {
        $this->payload['location'] = $location;

        return $this;
    }

    public function date(string $date): self
    {
        $this->payload['date'] = $date;

        return $this;
    }

    public function countType(string $countType): self
    {
        $this->payload['count_type'] = $countType;

        return $this;
    }

    public function countingNumber(int $countingNumber): self
    {
        $this->payload['counting_number'] = $countingNumber;

        return $this;
    }

    public function showBalance(bool $enabled = true): self
    {
        $this->payload['show_balance'] = $enabled;

        return $this;
    }

    public function onlyMentionDifferences(bool $enabled = true): self
    {
        $this->payload['only_mention_differences'] = $enabled;

        return $this;
    }

    public function secondTotalCount(bool $enabled = true): self
    {
        $this->payload['second_total_count'] = $enabled;

        return $this;
    }

    public function allowMultipleUsersPerPosition(bool $enabled = true): self
    {
        $this->payload['allow_multiple_users_per_position'] = $enabled;

        return $this;
    }

    public function interruptRevertsPosition(bool $enabled = true): self
    {
        $this->payload['interrupt_reverts_position'] = $enabled;

        return $this;
    }

    /**
     * @param  list<array{name: string, type: string, validate_item: bool}>  $variations
     */
    public function variations(array $variations): self
    {
        $this->payload['variations'] = $variations;

        return $this;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function items(array $items): self
    {
        $this->payload['items'] = $items;

        return $this;
    }

    /**
     * Adiciona um item individualmente — alternativa a montar o array inteiro
     * via items() quando os itens são conhecidos aos poucos.
     */
    public function item(string $position, ?string $product = null, ?string $quantity = null, ?array $variations = null): self
    {
        if (!isset($this->payload['items'])) {
            $this->payload['items'] = [];
        }

        $this->payload['items'][] = Payload::withoutNulls([
            'position' => $position,
            'product' => $product,
            'product_quantity' => $quantity,
            'variations' => $variations,
        ]);

        return $this;
    }

    public function send(): ApiResponse
    {
        return $this->connector->post('inventories', $this->payload);
    }
}
