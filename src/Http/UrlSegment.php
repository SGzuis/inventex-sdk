<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Http;

/**
 * Codifica um identificador antes de concatená-lo num path de requisição —
 * sem isso, um valor contendo "/", "../" ou "?" (vindo de uma fonte não
 * confiável repassada pela aplicação consumidora, ex.: um parâmetro de URL
 * do usuário final usado direto como uuid) pode escapar do prefixo da API
 * ou alterar qual rota é realmente chamada.
 */
final class UrlSegment
{
    public static function encode(string $value): string
    {
        return rawurlencode($value);
    }
}
