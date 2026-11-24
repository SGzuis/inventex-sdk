<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Config;
use Bootstech\InventexSdk\Exceptions\InventexException;
use Bootstech\InventexSdk\Http\Connector;
use Bootstech\InventexSdk\Resources\Inventory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class OperatorResourceTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private $history = [];

    /**
     * @param  list<Response>  $responses
     */
    private function inventory(array $responses): Inventory
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $connector = new Connector(new Config('https://app.inventex.test/api', 'token', null), new Client(['handler' => $stack]));

        return new Inventory($connector, 'inv-uuid');
    }

    private function json(int $status, array $data): Response
    {
        return new Response($status, [], json_encode(['success' => $status < 400, 'message' => 'ok', 'data' => $data]));
    }

    private function request(int $index = 0): \Psr\Http\Message\RequestInterface
    {
        return $this->history[$index]['request'];
    }

    public function test_qr_code_returns_data_uri_link_and_png_bytes(): void
    {
        $png = "\x89PNG\r\n\x1a\nfake";
        $inventory = $this->inventory([$this->json(201, [
            'pairing_url' => 'https://app.inventex.test/operator/pairing#qr=1%7Cabc&inventory=inv-uuid',
            'qr_code' => 'data:image/png;base64,' . base64_encode($png),
            'expires_at' => '2026-11-25T10:00:00-03:00',
        ])]);

        $qr = $inventory->operators()->qrCode();

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertStringEndsWith('inventories/inv-uuid/operators/qr-code', $this->request()->getUri()->getPath());
        $this->assertStringStartsWith('data:image/png;base64,', $qr->dataUri);
        $this->assertStringContainsString('/operator/pairing#qr=', $qr->pairingUrl);
        $this->assertSame('2026-11-25T10:00:00-03:00', $qr->expiresAt);
        $this->assertSame($png, $qr->png());
    }

    public function test_list_gets_operators_with_optional_page(): void
    {
        $inventory = $this->inventory([
            $this->json(200, [['id' => 'op-1', 'attributes' => ['device_code' => '123-456', 'status' => 'awaiting_approval']]]),
            $this->json(200, []),
        ]);

        $list = $inventory->operators()->list();
        $inventory->operators()->list(2);

        $this->assertSame('GET', $this->request()->getMethod());
        $this->assertStringEndsWith('inventories/inv-uuid/operators', $this->request()->getUri()->getPath());
        $this->assertSame('', $this->request()->getUri()->getQuery());
        $this->assertSame('page=2', $this->request(1)->getUri()->getQuery());
        $this->assertSame('123-456', $list->data()[0]['attributes']['device_code']);
    }

    public function test_approve_and_revoke_hit_operator_paths(): void
    {
        $inventory = $this->inventory([
            $this->json(200, ['id' => 'op-1', 'attributes' => ['status' => 'active']]),
            $this->json(200, ['id' => 'op-1', 'attributes' => ['status' => 'revoked']]),
        ]);

        $approved = $inventory->operators()->approve('op-1');
        $revoked = $inventory->operators()->revoke('op-1');

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertStringEndsWith('inventories/inv-uuid/operators/op-1/approve', $this->request()->getUri()->getPath());
        $this->assertSame('active', $approved->get('attributes')['status']);
        $this->assertSame('DELETE', $this->request(1)->getMethod());
        $this->assertStringEndsWith('inventories/inv-uuid/operators/op-1', $this->request(1)->getUri()->getPath());
        $this->assertSame('revoked', $revoked->get('attributes')['status']);
    }

    public function test_operator_uuid_is_encoded_in_path(): void
    {
        $inventory = $this->inventory([$this->json(200, [])]);

        $inventory->operators()->approve('../../inventories');

        $this->assertStringNotContainsString('/../', $this->request()->getUri()->getPath());
    }

    public function test_approve_conflict_throws(): void
    {
        $inventory = $this->inventory([new Response(409, [], json_encode(['success' => false, 'message' => 'Solicitação de pareamento inválida ou já processada.']))]);

        $this->expectException(InventexException::class);
        $this->expectExceptionMessage('Solicitação de pareamento inválida ou já processada.');

        $inventory->operators()->approve('op-1');
    }
}
