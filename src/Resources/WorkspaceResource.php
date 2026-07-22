<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiResponse;

/**
 * $client->workspace()->get();
 * $client->workspace()->updateLocationConfig(['zones' => [...]]);
 */
final class WorkspaceResource extends AbstractResource
{
    public function get(): ApiResponse
    {
        return $this->connector->get('workspace');
    }

    /**
     * @param  array<string, mixed>  $locationConfig
     */
    public function updateLocationConfig(array $locationConfig): ApiResponse
    {
        return $this->connector->put('workspace', ['location_config' => $locationConfig]);
    }
}
