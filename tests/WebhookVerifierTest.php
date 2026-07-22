<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Exceptions\InvalidWebhookSignatureException;
use Bootstech\InventexSdk\Webhooks\WebhookVerifier;
use PHPUnit\Framework\TestCase;

class WebhookVerifierTest extends TestCase
{
    private const SECRET = 'super-secret-signing-key';

    public function test_verifies_a_correctly_signed_webhook(): void
    {
        $body = json_encode(['event' => 'inventory.started', 'event_id' => 'evt-1', 'data' => ['inventory_uuid' => 'abc']]);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", self::SECRET);

        $event = (new WebhookVerifier(self::SECRET))->verify($signature, $timestamp, 'evt-1', $body);

        $this->assertSame('inventory.started', $event->name);
        $this->assertSame('evt-1', $event->eventId);
        $this->assertSame('abc', $event->data['inventory_uuid']);
        $this->assertTrue($event->is('inventory.started'));
    }

    public function test_rejects_an_invalid_signature(): void
    {
        $body = json_encode(['event' => 'inventory.started', 'event_id' => 'evt-1', 'data' => []]);
        $timestamp = (string) time();

        $this->expectException(InvalidWebhookSignatureException::class);

        (new WebhookVerifier(self::SECRET))->verify('wrong-signature', $timestamp, 'evt-1', $body);
    }

    public function test_rejects_an_expired_timestamp(): void
    {
        $body = json_encode(['event' => 'inventory.started', 'event_id' => 'evt-1', 'data' => []]);
        $timestamp = (string) (time() - 3600);
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", self::SECRET);

        $this->expectException(InvalidWebhookSignatureException::class);

        (new WebhookVerifier(self::SECRET))->verify($signature, $timestamp, 'evt-1', $body);
    }

    public function test_rejects_a_malformed_body(): void
    {
        $body = 'not json';
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", self::SECRET);

        $this->expectException(InvalidWebhookSignatureException::class);

        (new WebhookVerifier(self::SECRET))->verify($signature, $timestamp, 'evt-1', $body);
    }
}
