<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\Connector;

abstract class AbstractResource
{
    /** @var Connector */
    protected $connector;

    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }
}
