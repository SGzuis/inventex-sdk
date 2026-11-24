<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

/**
 * QR de pareamento de operador. $dataUri vai direto num <img src>; $pairingUrl
 * é o link que o QR contém (sensível: carrega o token de registro até $expiresAt).
 */
final class OperatorQrCode
{
    /** @var string */
    public $pairingUrl;

    /** @var string "data:image/png;base64,..." */
    public $dataUri;

    /** @var string ISO 8601 */
    public $expiresAt;

    public function __construct(string $pairingUrl, string $dataUri, string $expiresAt)
    {
        $this->pairingUrl = $pairingUrl;
        $this->dataUri = $dataUri;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Bytes do PNG (para salvar em arquivo ou responder com Content-Type: image/png).
     */
    public function png(): string
    {
        return (string) base64_decode(substr($this->dataUri, (int) strpos($this->dataUri, ',') + 1), true);
    }
}
