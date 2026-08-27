<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Referência fluente a um inventário já identificado (por uuid) — encadeia
 * ações e sub-recursos sem precisar repassar o uuid a cada chamada:
 *
 *   $client->inventories()->find($uuid)->show();
 *   $client->inventories()->find($uuid)->items()->list();
 */
final class Inventory
{
    /** @var Connector */
    private $connector;

    /** @var string */
    public $uuid;

    /** @var array<string, mixed> */
    private $attributes;

    /**
     * @param  array<string, mixed>  $attributes  Atributos já carregados (via find()/show()), vazio se só a uuid é conhecida.
     */
    public function __construct(Connector $connector, string $uuid, array $attributes = [])
    {
        $this->connector = $connector;
        $this->uuid = $uuid;
        $this->attributes = $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        // array_key_exists (não isset): um atributo presente e null não pode
        // cair pro $default — ver mesma correção em ApiResponse::get().
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : $default;
    }

    public function show(): ApiResponse
    {
        $response = $this->connector->get($this->path()->toString());
        $this->attributes = $response->data();

        return $response;
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

    public function activities(): ApiResponse
    {
        return $this->connector->get($this->path()->append('activities')->toString());
    }

    public function items(): ItemResource
    {
        return new ItemResource($this->connector, $this->uuid);
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->uuid);
    }
}
