<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Http;

/**
 * Único lugar responsável por montar um path de API seguro — todo
 * identificador dinâmico (uuid, código, id) passa por segment(), que
 * sempre codifica o valor, então não tem como um "../" ou "/" escapar do
 * prefixo da API (ver reprodução do bug de path traversal corrigido
 * antes desta classe existir). Imutável: cada chamada devolve uma nova
 * instância, então é seguro montar um path e reutilizar em vários lugares.
 *
 *   ApiPath::make('inventories')->segment($uuid)->append('state/start')->toString();
 *   // => "inventories/{uuid-codificado}/state/start"
 */
final class ApiPath
{
    /** @var string */
    private $path;

    private function __construct(string $path)
    {
        $this->path = $path;
    }

    public static function make(string $root): self
    {
        return new self(trim($root, '/'));
    }

    /**
     * Adiciona um identificador dinâmico — sempre codificado.
     */
    public function segment(string $value): self
    {
        return new self($this->path . '/' . UrlSegment::encode($value));
    }

    /**
     * Adiciona um trecho fixo do path (nome de rota conhecido em tempo de
     * desenvolvimento, nunca um dado vindo de fora) — não é codificado, pois
     * pode legitimamente conter "/" separando múltiplos segmentos fixos
     * (ex.: "state/start").
     */
    public function append(string $literal): self
    {
        return new self($this->path . '/' . trim($literal, '/'));
    }

    public function toString(): string
    {
        return $this->path;
    }
}
