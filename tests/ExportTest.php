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

class ExportTest extends TestCase
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
        return new Response($status, [], json_encode(['success' => true, 'message' => 'ok', 'data' => $data]));
    }

    public function test_request_export_posts_format_and_returns_reference(): void
    {
        $inventory = $this->inventory([$this->json(202, ['id' => 'exp-1'])]);

        $export = $inventory->requestExport('csv');

        $this->assertSame('exp-1', $export->id);
        $this->assertSame('POST', $this->history[0]['request']->getMethod());
        $this->assertStringEndsWith('inventories/inv-uuid/exports', $this->history[0]['request']->getUri()->getPath());
        $this->assertSame(['format' => 'csv'], json_decode((string) $this->history[0]['request']->getBody(), true));
    }

    public function test_status_reads_export_job(): void
    {
        $inventory = $this->inventory([$this->json(200, ['attributes' => ['status' => 'completed']])]);

        $status = $inventory->findExport('exp-1')->status();

        $this->assertSame('completed', $status->get('attributes')['status']);
        $this->assertStringEndsWith('inventories/inv-uuid/exports/exp-1', $this->history[0]['request']->getUri()->getPath());
    }

    public function test_download_returns_raw_bytes_and_save_to_writes_file(): void
    {
        $inventory = $this->inventory([new Response(200, [], "a;b\n1;2\n"), new Response(200, [], "a;b\n1;2\n")]);
        $export = $inventory->findExport('exp-1');

        $this->assertSame("a;b\n1;2\n", $export->download());
        $this->assertStringEndsWith('inventories/inv-uuid/exports/exp-1/download', $this->history[0]['request']->getUri()->getPath());

        $path = tempnam(sys_get_temp_dir(), 'exp');
        $export->saveTo($path);
        $this->assertSame("a;b\n1;2\n", file_get_contents($path));
        unlink($path);
    }

    public function test_download_before_completion_throws(): void
    {
        $inventory = $this->inventory([new Response(409, [], json_encode(['success' => false, 'message' => 'A exportação ainda não está pronta para download.']))]);

        $this->expectException(InventexException::class);
        $this->expectExceptionMessage('ainda não está pronta');

        $inventory->findExport('exp-1')->download();
    }
}
