<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Config;
use Bootstech\InventexSdk\InventexClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Regressão: identificadores dinâmicos (uuid, código, id de job) eram
 * concatenados crus no path da requisição, sem rawurlencode(). Com o
 * base_uri real configurado (não um Client de teste "nu"), um valor como
 * "../../admin/secrets" escapava inteiramente do prefixo /api/ — a
 * resolução de URI do Guzzle segue RFC 3986 e remove os "../" contra o
 * base_uri. Por isso este teste, diferente do resto da suíte, monta o
 * Client com 'base_uri' de verdade: é a única forma de reproduzir o escape.
 */
class UrlSegmentEncodingTest extends TestCase
{
    private function clientWithHandler(MockHandler $mock, array &$history): InventexClient
    {
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $httpClient = new Client(['handler' => $stack, 'base_uri' => 'https://app.inventex.test/api/']);

        return new InventexClient(new Config('https://app.inventex.test/api', 'token'), $httpClient);
    }

    private function okResponse(): Response
    {
        return new Response(200, [], json_encode(['success' => true, 'message' => 'ok', 'data' => []]));
    }

    public function test_a_malicious_inventory_uuid_cannot_escape_the_api_path_prefix(): void
    {
        $history = [];
        $client = $this->clientWithHandler(new MockHandler([$this->okResponse()]), $history);

        $client->inventories()->find('../../admin/secrets')->show();

        $uri = (string) $history[0]['request']->getUri();
        $this->assertStringStartsWith('https://app.inventex.test/api/inventories/', $uri);
        $this->assertStringNotContainsString('/admin/secrets', $uri);
    }

    public function test_a_malicious_position_uuid_cannot_escape_the_api_path_prefix(): void
    {
        $history = [];
        $client = $this->clientWithHandler(new MockHandler([$this->okResponse()]), $history);

        $client->inventories()->find('abc')->positions()->find('../../evil')->start();

        $uri = (string) $history[0]['request']->getUri();
        $this->assertStringStartsWith('https://app.inventex.test/api/inventories/abc/positions/', $uri);
        $this->assertStringContainsString('%2F', $uri);
    }

    public function test_a_malicious_user_uuid_cannot_escape_the_api_path_prefix(): void
    {
        $history = [];
        $client = $this->clientWithHandler(new MockHandler([$this->okResponse()]), $history);

        $client->users()->find('../../workspaces')->show();

        $uri = (string) $history[0]['request']->getUri();
        $this->assertStringStartsWith('https://app.inventex.test/api/users/', $uri);
        $this->assertStringContainsString('%2F', $uri);
    }
}
