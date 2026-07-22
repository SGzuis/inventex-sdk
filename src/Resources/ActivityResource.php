<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * $client->activities()->list()->from('2026-07-01')->to('2026-07-31')->get();
 */
final class ActivityResource extends AbstractResource
{
    public function list(): ActivityQuery
    {
        return new ActivityQuery($this->connector);
    }
}
