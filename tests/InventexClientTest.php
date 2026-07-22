<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\InventexClient;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * fromArray() é a ponte usada pelo InventexServiceProvider do Laravel (que
 * monta esse array a partir de config/inventex-sdk.php) — testado aqui de
 * forma isolada, sem depender do framework.
 */
class InventexClientTest extends TestCase
{
    public function test_builds_a_client_from_a_config_array(): void
    {
        $client = InventexClient::fromArray([
            'base_url' => 'https://app.inventex.test/api',
            'token' => 'token-123',
            'signing_secret' => 'secret',
            'timeout' => 30,
        ]);

        $this->assertInstanceOf(InventexClient::class, $client);
    }

    public function test_requires_base_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InventexClient::fromArray(['token' => 'token-123']);
    }

    public function test_requires_token(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InventexClient::fromArray(['base_url' => 'https://app.inventex.test/api']);
    }

    public function test_signing_secret_and_timeout_are_optional(): void
    {
        $client = InventexClient::fromArray([
            'base_url' => 'https://app.inventex.test/api',
            'token' => 'token-123',
        ]);

        $this->assertInstanceOf(InventexClient::class, $client);
    }
}
