<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Importação de itens por planilha — obtido via Inventory::import():
 *
 *   file_put_contents('template.xlsx', $inventory->import()->template());
 *
 *   $response = $inventory->import()->upload('/caminho/para/planilha.xlsx');
 *   $jobId = $response->get('import_job_id');
 *
 *   $inventory->import()->status($jobId); // acompanha o processamento assíncrono
 */
final class ImportResource
{
    /** @var Connector */
    private $connector;

    /** @var string */
    private $inventoryUuid;

    public function __construct(Connector $connector, string $inventoryUuid)
    {
        $this->connector = $connector;
        $this->inventoryUuid = $inventoryUuid;
    }

    /**
     * Baixa o modelo de planilha (.xlsx) já ajustado às variações deste
     * inventário — devolve os bytes crus do arquivo.
     */
    public function template(): string
    {
        return $this->connector->download($this->path()->append('template')->toString());
    }

    /**
     * Envia a planilha preenchida — o processamento é assíncrono, use
     * status() com o `import_job_id` retornado para acompanhar o resultado.
     */
    public function upload(string $filePath, ?string $filename = null): ApiResponse
    {
        return $this->connector->postMultipart(
            $this->path()->toString(),
            [],
            ['file' => ['path' => $filePath, 'filename' => $filename ?: basename($filePath)]]
        );
    }

    public function status(string $importJobId): ApiResponse
    {
        return $this->connector->get($this->path()->segment($importJobId)->toString());
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->inventoryUuid)->append('import');
    }
}
