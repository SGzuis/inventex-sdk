<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Bootstech\InventexSdk\Resources\InventoryResource inventories()
 * @method static \Bootstech\InventexSdk\Resources\WorkspaceResource workspace()
 * @method static \Bootstech\InventexSdk\Resources\UserResource users()
 * @method static \Bootstech\InventexSdk\Resources\ActivityResource activities()
 * @method static \Bootstech\InventexSdk\Resources\RawResource raw()
 *
 * @see \Bootstech\InventexSdk\InventexClient
 */
class Inventex extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'inventex';
    }
}
