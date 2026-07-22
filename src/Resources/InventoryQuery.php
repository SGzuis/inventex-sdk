<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * $client->inventories()->list()->status('in_progress')->search('centro')->get();
 */
final class InventoryQuery
{
    /** @var array<string, mixed> */
    private $filters = [];

    /** @var Connector */
    private $connector;

    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @param  string|list<string>  $status
     */
    public function status($status): self
    {
        $this->filters['status'] = is_array($status) ? implode(',', $status) : $status;

        return $this;
    }

    public function search(string $term): self
    {
        $this->filters['search'] = $term;

        return $this;
    }

    public function createdFrom(string $date): self
    {
        $this->filters['created_from'] = $date;

        return $this;
    }

    public function createdTo(string $date): self
    {
        $this->filters['created_to'] = $date;

        return $this;
    }

    public function page(int $page): self
    {
        $this->filters['page'] = $page;

        return $this;
    }

    public function get(): ApiResponse
    {
        return $this->connector->get('inventories', $this->filters);
    }
}
