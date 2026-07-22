<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Http;

use Bootstech\InventexSdk\Config;
use Bootstech\InventexSdk\Exceptions\InventexException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use Psr\Http\Message\ResponseInterface;

/**
 * Única classe que fala HTTP no SDK — todo Resource/Builder passa por aqui.
 * Cuida só de transporte (autenticação Bearer + assinatura HMAC opcional,
 * montagem de multipart, download binário); a tradução de status HTTP em
 * exceções tipadas é responsabilidade do ErrorResponseMapper, não desta
 * classe.
 */
final class Connector
{
    /** @var Config */
    private $config;

    /** @var Client */
    private $http;

    /** @var RequestSigner|null */
    private $signer;

    public function __construct(Config $config, ?Client $http = null)
    {
        $this->config = $config;

        $this->http = $http ?: new Client([
            'base_uri' => rtrim($config->baseUrl, '/') . '/',
            'timeout' => $config->timeoutSeconds,
        ]);

        $this->signer = $config->signingSecret ? new RequestSigner($config->signingSecret) : null;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): ApiResponse
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function post(string $path, array $body = []): ApiResponse
    {
        return $this->request('POST', $path, ['json' => $body]);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function put(string $path, array $body = []): ApiResponse
    {
        return $this->request('PUT', $path, ['json' => $body]);
    }

    public function delete(string $path): ApiResponse
    {
        return $this->request('DELETE', $path);
    }

    /**
     * Envio multipart (upload de arquivo) — assina o corpo exato (bytes já
     * montados com o boundary) antes de enviar, para que a assinatura bata
     * com o que o servidor recebe.
     *
     * @param  array<string, string>  $fields
     * @param  array<string, array{path: string, filename?: string}>  $files
     */
    public function postMultipart(string $path, array $fields = [], array $files = []): ApiResponse
    {
        $elements = [];

        foreach ($fields as $name => $value) {
            $elements[] = ['name' => $name, 'contents' => (string) $value];
        }

        foreach ($files as $name => $file) {
            $elements[] = [
                'name' => $name,
                'contents' => fopen($file['path'], 'r'),
                'filename' => isset($file['filename']) ? $file['filename'] : basename($file['path']),
            ];
        }

        $multipart = new MultipartStream($elements);
        $body = $multipart->getContents();

        return $this->request('POST', $path, [
            'body' => $body,
            'extra_headers' => ['Content-Type' => 'multipart/form-data; boundary=' . $multipart->getBoundary()],
        ]);
    }

    /**
     * Baixa um recurso binário (ex.: planilha de template de importação) —
     * devolve os bytes crus em vez de decodificar como JSON.
     *
     * @param  array<string, mixed>  $query
     */
    public function download(string $path, array $query = []): string
    {
        $response = $this->send('GET', $path, ['query' => $query]);
        $status = $response->getStatusCode();
        $contents = (string) $response->getBody();

        if ($status >= 400) {
            ErrorResponseMapper::throwForStatus($status, json_decode($contents, true) ?: []);
        }

        return $contents;
    }

    /**
     * @param  array{query?: array<string, mixed>, json?: array<string, mixed>, body?: string, extra_headers?: array<string, string>}  $options
     */
    private function request(string $method, string $path, array $options = []): ApiResponse
    {
        $response = $this->send($method, $path, $options);
        $status = $response->getStatusCode();
        $decoded = json_decode((string) $response->getBody(), true) ?: [];

        if ($status >= 400) {
            ErrorResponseMapper::throwForStatus($status, $decoded);
        }

        return new ApiResponse($decoded, $status);
    }

    /**
     * @param  array{query?: array<string, mixed>, json?: array<string, mixed>, body?: string, extra_headers?: array<string, string>}  $options
     */
    private function send(string $method, string $path, array $options): ResponseInterface
    {
        $path = ltrim($path, '/');

        // Nunca deixamos o Guzzle reencondar 'json' sozinho: ele usa
        // json_encode() puro (escapa "/" e unicode), diferente da codificação
        // que usamos para assinar — os dois precisam ser exatamente os
        // mesmos bytes, ou a assinatura HMAC não bate com o corpo que
        // realmente trafega (o servidor assina os bytes que recebeu).
        if (isset($options['json'])) {
            $options['body'] = json_encode($options['json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            unset($options['json']);

            if (!isset($options['extra_headers'])) {
                $options['extra_headers'] = [];
            }

            if (!isset($options['extra_headers']['Content-Type'])) {
                $options['extra_headers']['Content-Type'] = 'application/json';
            }
        }

        $body = isset($options['body']) ? $options['body'] : '';

        $headers = array_merge([
            'Authorization' => 'Bearer ' . $this->config->token,
            'Accept' => 'application/json',
        ], isset($options['extra_headers']) ? $options['extra_headers'] : []);

        if ($this->signer) {
            $basePath = trim((string) parse_url(rtrim($this->config->baseUrl, '/'), PHP_URL_PATH), '/');
            $absolutePath = '/' . trim($basePath . '/' . $path, '/');
            $signed = $this->signer->sign($method, $absolutePath, $body);
            $headers['X-Timestamp'] = $signed['timestamp'];
            $headers['X-Signature'] = $signed['signature'];
        }

        unset($options['extra_headers']);

        $requestOptions = array_merge($options, [
            'headers' => $headers,
            'http_errors' => false,
        ]);

        try {
            return $this->http->request($method, $path, $requestOptions);
        } catch (ConnectException $e) {
            throw new InventexException('Falha de conexão com a API do Inventex: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new InventexException('Erro ao chamar a API do Inventex: ' . $e->getMessage(), 0, $e);
        }
    }
}
