<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;
use Bootstech\InventexSdk\Support\Payload;

/**
 * Referência fluente a um inventário já identificado (por uuid) — encadeia
 * transições de estado e sub-recursos sem precisar repassar o uuid a cada
 * chamada:
 *
 *   $client->inventories()->find($uuid)->start();
 *   $client->inventories()->find($uuid)->positions()->list()->get();
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

    public function start(): ApiResponse
    {
        return $this->connector->post($this->path()->append('state/start')->toString());
    }

    /**
     * ATENÇÃO: hoje o endpoint `state/conclude` da API ignora completamente
     * o campo `force` — o servidor sempre conclui forçadamente (equivalente
     * a $force = true), mesmo se você passar false. O parâmetro é enviado
     * mesmo assim por compatibilidade futura, mas na prática `conclude(false)`
     * se comporta exatamente igual a `conclude(true)` até que a API passe a
     * respeitar esse campo. Não existe hoje, via API, uma forma de concluir
     * apenas a rodada atual e manter o inventário em IN_PROGRESS para uma
     * segunda contagem.
     */
    public function conclude(bool $force = true): ApiResponse
    {
        return $this->connector->post($this->path()->append('state/conclude')->toString(), ['force' => $force]);
    }

    public function interrupt(?int $userId = null): ApiResponse
    {
        return $this->connector->post($this->path()->append('state/interrupt')->toString(), Payload::withoutNulls(['user_id' => $userId]));
    }

    public function cancel(): ApiResponse
    {
        return $this->connector->post($this->path()->append('state/cancel')->toString());
    }

    public function export(): ApiResponse
    {
        return $this->connector->get($this->path()->append('export')->toString());
    }

    public function activities(): ApiResponse
    {
        return $this->connector->get($this->path()->append('activities')->toString());
    }

    public function positions(): PositionResource
    {
        return new PositionResource($this->connector, $this->uuid);
    }

    public function operators(): OperatorResource
    {
        return new OperatorResource($this->connector, $this->uuid);
    }

    public function items(): ItemResource
    {
        return new ItemResource($this->connector, $this->uuid);
    }

    public function import(): ImportResource
    {
        return new ImportResource($this->connector, $this->uuid);
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->uuid);
    }
}
