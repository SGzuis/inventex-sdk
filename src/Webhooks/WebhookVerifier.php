<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Webhooks;

use Bootstech\InventexSdk\Exceptions\InvalidWebhookSignatureException;

/**
 * Verifica webhooks recebidos do Inventex: HMAC-SHA256 de
 * "{timestamp}.{body}" (headers X-Signature/X-Timestamp/X-Event-Id), com
 * janela de tolerância contra replay — mesmo esquema descrito no modal "Como
 * usar" da tela de aplicativo, aqui como implementação pronta para uso.
 */
final class WebhookVerifier
{
    /** @var string */
    private $secret;

    /** @var int */
    private $toleranceSeconds;

    public function __construct(string $secret, int $toleranceSeconds = 300)
    {
        $this->secret = $secret;
        $this->toleranceSeconds = $toleranceSeconds;
    }

    /**
     * @throws InvalidWebhookSignatureException quando a assinatura não bate
     *         ou o timestamp está fora da janela de tolerância.
     */
    public function verify(string $signature, string $timestamp, string $eventId, string $body): WebhookEvent
    {
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > $this->toleranceSeconds) {
            throw new InvalidWebhookSignatureException('Timestamp do webhook inválido ou expirado.');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $body, $this->secret);

        if (!hash_equals($expected, $signature)) {
            throw new InvalidWebhookSignatureException('Assinatura do webhook inválida.');
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded) || !isset($decoded['event'])) {
            throw new InvalidWebhookSignatureException('Corpo do webhook malformado.');
        }

        return new WebhookEvent(
            $decoded['event'],
            isset($decoded['event_id']) ? $decoded['event_id'] : $eventId,
            isset($decoded['data']) ? $decoded['data'] : []
        );
    }
}
