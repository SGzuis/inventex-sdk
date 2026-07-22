<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Http\RequestSigner;
use PHPUnit\Framework\TestCase;

/**
 * Garante que a assinatura gerada é exatamente a que a API espera —
 * ver App\Http\Middleware\VerifyWorkspaceSignature::verify() no projeto principal:
 * HMAC-SHA256 de "{METHOD}\n{PATH}\n{TIMESTAMP}\n{sha256(body)}".
 */
class RequestSignerTest extends TestCase
{
    public function test_signature_matches_the_api_verification_algorithm(): void
    {
        $secret = 'super-secret-signing-key';
        $method = 'POST';
        $path = '/api/inventories/abc-123/state/start';
        $body = '{"force":true}';

        $signed = (new RequestSigner($secret))->sign($method, $path, $body);

        $expectedPayload = implode("\n", [
            strtoupper($method),
            $path,
            $signed['timestamp'],
            hash('sha256', $body),
        ]);
        $expectedSignature = hash_hmac('sha256', $expectedPayload, $secret);

        $this->assertSame($expectedSignature, $signed['signature']);
        $this->assertEqualsWithDelta(time(), (int) $signed['timestamp'], 2);
    }
}
