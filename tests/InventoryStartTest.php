<?php

namespace Bootstech\InventexSdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Bootstech\InventexSdk\Config;
use Bootstech\InventexSdk\InventexClient;
use PHPUnit\Framework\TestCase;

class InventoryStartTest extends TestCase
{
    public function test_start_sends_put_with_in_progress_status(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['success' => true, 'message' => 'ok', 'data' => []])),
        ]));
        $stack->push(Middleware::history($history));
        $client = new InventexClient(
            new Config('https://app.inventex.test/api', 'token'),
            new Client(['handler' => $stack, 'base_uri' => 'https://app.inventex.test/api/'])
        );

        $client->inventories()->find('abc')->start();

        $request = $history[0]['request'];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertStringEndsWith('/inventories/abc', $request->getUri()->getPath());
        $this->assertSame('in_progress', json_decode((string) $request->getBody(), true)['status']);
    }
}
