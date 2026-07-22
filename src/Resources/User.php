<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

final class User
{
    /** @var Connector */
    private $connector;

    /** @var string */
    public $uuid;

    public function __construct(Connector $connector, string $uuid)
    {
        $this->connector = $connector;
        $this->uuid = $uuid;
    }

    public function show(): ApiResponse
    {
        return $this->connector->get($this->path()->toString());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): ApiResponse
    {
        return $this->connector->put($this->path()->toString(), $data);
    }

    public function delete(): ApiResponse
    {
        return $this->connector->delete($this->path()->toString());
    }

    private function path(): ApiPath
    {
        return ApiPath::make('users')->segment($this->uuid);
    }
}
