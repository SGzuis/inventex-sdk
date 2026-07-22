<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Config;
use Bootstech\InventexSdk\Exceptions\AuthenticationException;
use Bootstech\InventexSdk\Exceptions\NotFoundException;
use Bootstech\InventexSdk\Exceptions\ValidationException;
use Bootstech\InventexSdk\Http\Connector;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ConnectorTest extends TestCase
{
    private function connectorWithHandler(MockHandler $mock, ?string $signingSecret, array &$history): Connector
    {
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new Client(['handler' => $stack]);

        return new Connector(new Config('https://app.inventex.test/api', 'plain-text-token', $signingSecret), $client);
    }

    public function test_sends_bearer_token_and_skips_signature_when_no_secret_configured(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(200, [], json_encode(['success' => true, 'message' => 'ok', 'data' => ['id' => 1]]))]);
        $connector = $this->connectorWithHandler($mock, null, $history);

        $response = $connector->get('workspace');

        $request = $history[0]['request'];
        $this->assertSame('Bearer plain-text-token', $request->getHeaderLine('Authorization'));
        $this->assertFalse($request->hasHeader('X-Signature'));
        $this->assertSame(1, $response->get('id'));
    }

    public function test_signs_the_request_when_a_signing_secret_is_configured(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(200, [], json_encode(['success' => true, 'message' => 'ok', 'data' => []]))]);
        $connector = $this->connectorWithHandler($mock, 'secret', $history);

        $connector->post('inventories/abc/state/start', ['force' => true]);

        $request = $history[0]['request'];
        $this->assertTrue($request->hasHeader('X-Signature'));
        $this->assertTrue($request->hasHeader('X-Timestamp'));

        $body = (string) $request->getBody();
        $timestamp = $request->getHeaderLine('X-Timestamp');
        $expectedPayload = implode("\n", ['POST', '/api/inventories/abc/state/start', $timestamp, hash('sha256', $body)]);
        $expectedSignature = hash_hmac('sha256', $expectedPayload, 'secret');

        $this->assertSame($expectedSignature, $request->getHeaderLine('X-Signature'));
    }

    /**
     * Regressão: json_encode() do PHP escapa "/" e caracteres não-ASCII por
     * padrão, e o Guzzle reencondava o payload sozinho na hora de montar o
     * corpo real — a assinatura era calculada sobre uma codificação
     * diferente da que de fato trafegava sempre que o payload tinha acento
     * ou barra (ex.: nomes em pt-BR, qualquer URL). O corpo assinado agora
     * precisa ser byte-a-byte igual ao corpo enviado.
     */
    public function test_signature_matches_the_exact_body_sent_even_with_accents_and_slashes(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(200, [], json_encode(['success' => true, 'message' => 'ok', 'data' => []]))]);
        $connector = $this->connectorWithHandler($mock, 'secret', $history);

        $connector->post('inventories', [
            'name' => 'Inventário Loja Centro',
            'webhook_url' => 'https://cliente.example.com/webhook',
        ]);

        $request = $history[0]['request'];
        $bodyOnWire = (string) $request->getBody();
        $timestamp = $request->getHeaderLine('X-Timestamp');

        $expectedPayload = implode("\n", ['POST', '/api/inventories', $timestamp, hash('sha256', $bodyOnWire)]);
        $expectedSignature = hash_hmac('sha256', $expectedPayload, 'secret');

        $this->assertSame($expectedSignature, $request->getHeaderLine('X-Signature'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function test_maps_401_to_authentication_exception(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(401, [], json_encode(['success' => false, 'message' => 'Não autenticado.']))]);
        $connector = $this->connectorWithHandler($mock, null, $history);

        $this->expectException(AuthenticationException::class);
        $connector->get('workspace');
    }

    public function test_maps_404_to_not_found_exception(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(404, [], json_encode(['success' => false, 'message' => 'Não encontrado.']))]);
        $connector = $this->connectorWithHandler($mock, null, $history);

        $this->expectException(NotFoundException::class);
        $connector->get('inventories/missing');
    }

    public function test_maps_422_to_validation_exception_with_errors(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(422, [], json_encode([
            'success' => false,
            'message' => 'Erro de validação.',
            'errors' => ['name' => ['O campo nome é obrigatório.']],
        ]))]);
        $connector = $this->connectorWithHandler($mock, null, $history);

        try {
            $connector->post('inventories', []);
            $this->fail('Esperava ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['name' => ['O campo nome é obrigatório.']], $e->errors());
        }
    }

    public function test_postmultipart_sends_a_signed_multipart_body_with_the_file(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(202, [], json_encode(['success' => true, 'message' => 'ok', 'data' => ['import_job_id' => 'job-1']]))]);
        $connector = $this->connectorWithHandler($mock, 'secret', $history);

        $filePath = tempnam(sys_get_temp_dir(), 'sdk-test-');
        file_put_contents($filePath, 'posicao,produto,estoque');

        $response = $connector->postMultipart('inventories/abc/import', files: ['file' => ['path' => $filePath, 'filename' => 'planilha.xlsx']]);
        unlink($filePath);

        $request = $history[0]['request'];
        $contentType = $request->getHeaderLine('Content-Type');
        $this->assertStringStartsWith('multipart/form-data; boundary=', $contentType);
        $this->assertStringContainsString('planilha.xlsx', (string) $request->getBody());
        $this->assertStringContainsString('posicao,produto,estoque', (string) $request->getBody());

        $body = (string) $request->getBody();
        $timestamp = $request->getHeaderLine('X-Timestamp');
        $expectedPayload = implode("\n", ['POST', '/api/inventories/abc/import', $timestamp, hash('sha256', $body)]);
        $this->assertSame(hash_hmac('sha256', $expectedPayload, 'secret'), $request->getHeaderLine('X-Signature'));
        $this->assertSame('job-1', $response->get('import_job_id'));
    }

    public function test_download_returns_raw_bytes_without_json_decoding(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(200, ['Content-Type' => 'application/vnd.ms-excel'], 'binary-xlsx-content')]);
        $connector = $this->connectorWithHandler($mock, null, $history);

        $contents = $connector->download('inventories/abc/import/template');

        $this->assertSame('binary-xlsx-content', $contents);
    }

    public function test_download_maps_error_status_to_typed_exception(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(404, [], json_encode(['success' => false, 'message' => 'Não encontrado.']))]);
        $connector = $this->connectorWithHandler($mock, null, $history);

        $this->expectException(NotFoundException::class);
        $connector->download('inventories/missing/import/template');
    }
}
