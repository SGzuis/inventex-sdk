<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

final class ActivityQuery
{
    /** @var array<string, mixed> */
    private $filters = [];

    /** @var Connector */
    private $connector;

    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    public function event(string $event): self
    {
        $this->filters['event'] = $event;

        return $this;
    }

    public function causedByUser(int $userId): self
    {
        $this->filters['causer_id'] = $userId;

        return $this;
    }

    public function page(int $page): self
    {
        $this->filters['page'] = $page;

        return $this;
    }

    public function get(): ApiResponse
    {
        return $this->connector->get('activities', $this->filters);
    }
}
