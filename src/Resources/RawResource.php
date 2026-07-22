<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

/**
 * Escape hatch para endpoints ainda não modelados como builder — usa a mesma
 * autenticação/assinatura de todo o SDK:
 *
 *   $client->raw()->get('inventories/import/template');
 */
final class RawResource extends AbstractResource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = [])
    {
        return $this->connector->get($path, $query);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function post(string $path, array $body = [])
    {
        return $this->connector->post($path, $body);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function put(string $path, array $body = [])
    {
        return $this->connector->put($path, $body);
    }

    public function delete(string $path)
    {
        return $this->connector->delete($path);
    }
}
