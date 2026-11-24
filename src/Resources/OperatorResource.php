<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * Operadores externos (celular sem conta no Inventex) de um inventário —
 * obtido via Inventory::operators(). Fluxo na plataforma integradora:
 *
 *   $qr = $inventory->operators()->qrCode();
 *   echo '<img src="' . $qr->dataUri . '" alt="QR code">'; // operador aponta a câmera
 *
 *   $inventory->operators()->list();                 // pedidos com device_code (código na tela do celular)
 *   $inventory->operators()->approve($operatorUuid); // após conferir o código
 *   $inventory->operators()->revoke($operatorUuid);
 */
final class OperatorResource
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
     * Lista paginada (15 por página), mais recentes primeiro. Status:
     * awaiting_approval|active|revoked|expired.
     */
    public function list(?int $page = null): ApiResponse
    {
        return $this->connector->get($this->path()->toString(), array_filter(['page' => $page]));
    }

    /**
     * Gera um QR de pareamento (vale 24 h e pareia vários celulares).
     * Lança InventexException (HTTP 409) se o inventário estiver encerrado.
     */
    public function qrCode(): OperatorQrCode
    {
        $response = $this->connector->post($this->path()->append('qr-code')->toString());

        return new OperatorQrCode(
            (string) $response->get('pairing_url'),
            (string) $response->get('qr_code'),
            (string) $response->get('expires_at')
        );
    }

    /**
     * Aprova o pedido após conferir o device_code com o código no celular.
     * Lança InventexException (HTTP 409) se já processado ou inventário encerrado.
     */
    public function approve(string $operatorUuid): ApiResponse
    {
        return $this->connector->post($this->path()->segment($operatorUuid)->append('approve')->toString());
    }

    /**
     * Revoga o acesso de um celular ativo ou recusa um pedido pendente.
     */
    public function revoke(string $operatorUuid): ApiResponse
    {
        return $this->connector->delete($this->path()->segment($operatorUuid)->toString());
    }

    private function path(): ApiPath
    {
        return ApiPath::make('inventories')->segment($this->inventoryUuid)->append('operators');
    }
}
