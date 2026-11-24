<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Http;

use ArrayAccess;

/**
 * Envelope de resposta da API ({success, message, data, meta, links}) com
 * acesso fluente — `$response->data()` traz o payload já desembrulhado,
 * `$response['campo']` e `$response->get('campo')` leem direto de dentro de
 * `data` (funciona tanto para um recurso único quanto para uma lista paginada).
 *
 * @implements ArrayAccess<string, mixed>
 */
final class ApiResponse implements ArrayAccess
{
    /** @var array<string, mixed> */
    private $payload;

    /** @var int */
    public $status;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(array $payload, int $status)
    {
        $this->payload = $payload;
        $this->status = $status;
    }

    public function message(): string
    {
        return (string) (isset($this->payload['message']) ? $this->payload['message'] : '');
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function data(): array
    {
        return isset($this->payload['data']) ? $this->payload['data'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return isset($this->payload['meta']) ? $this->payload['meta'] : [];
    }

    /**
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $data = $this->data();

        // array_key_exists (não isset): um campo presente e null (ex.:
        // "webhook_url": null) precisa continuar sendo null aqui, não cair
        // pro $default — isset() trata null como "não existe" e mascararia isso.
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * @param  mixed  $offset
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data());
    }

    /**
     * Sem tipo de retorno de propósito: `mixed` (PHP 8.0+) quebraria o
     * suporte a PHP 7.2. O atributo abaixo silencia o aviso do PHP 8.1+ e,
     * sozinho na linha, é só um comentário (`#`) para o PHP 7.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $data = $this->data();

        return isset($data[$offset]) ? $data[$offset] : null;
    }

    /**
     * @param  mixed  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value): void
    {
        throw new \LogicException('ApiResponse é somente leitura.');
    }

    /**
     * @param  mixed  $offset
     */
    public function offsetUnset($offset): void
    {
        throw new \LogicException('ApiResponse é somente leitura.');
    }
}
