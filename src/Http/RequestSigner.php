<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Http;

/**
 * Reproduz exatamente o esquema de assinatura que a API valida em
 * VerifyWorkspaceSignature: HMAC-SHA256 de
 * "{METHOD}\n{PATH}\n{TIMESTAMP}\n{sha256(body)}", enviado nos headers
 * X-Timestamp e X-Signature.
 */
final class RequestSigner
{
    /** @var string */
    private $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @return array{timestamp: string, signature: string}
     */
    public function sign(string $method, string $path, string $body): array
    {
        $timestamp = (string) time();
        $bodyHash = hash('sha256', $body);

        $payload = implode("\n", [
            strtoupper($method),
            $path,
            $timestamp,
            $bodyHash,
        ]);

        return [
            'timestamp' => $timestamp,
            'signature' => hash_hmac('sha256', $payload, $this->secret),
        ];
    }
}
