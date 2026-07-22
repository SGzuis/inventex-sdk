<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Exceptions;

/**
 * Assinatura ausente/incorreta ou timestamp fora da janela de tolerância ao
 * verificar um webhook recebido — ver Webhooks\WebhookVerifier.
 */
class InvalidWebhookSignatureException extends InventexException {}
