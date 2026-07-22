<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Http;

use Bootstech\InventexSdk\Exceptions\AuthenticationException;
use Bootstech\InventexSdk\Exceptions\InventexException;
use Bootstech\InventexSdk\Exceptions\NotFoundException;
use Bootstech\InventexSdk\Exceptions\ValidationException;

/**
 * Traduz "o que um status HTTP + corpo de erro da API significam" em
 * exceções tipadas — regra de negócio própria, sem nenhuma relação com
 * transporte HTTP em si (por isso não vive dentro de Connector). Sempre
 * lança, nunca retorna.
 */
final class ErrorResponseMapper
{
    /**
     * @param  array<string, mixed>  $decoded
     * @return void
     */
    public static function throwForStatus(int $status, array $decoded)
    {
        if ($status === 401 || $status === 403) {
            throw new AuthenticationException(isset($decoded['message']) ? $decoded['message'] : 'Não autenticado ou não autorizado.');
        }

        if ($status === 404) {
            throw new NotFoundException(isset($decoded['message']) ? $decoded['message'] : 'Recurso não encontrado.');
        }

        if ($status === 422) {
            throw new ValidationException(
                isset($decoded['message']) ? $decoded['message'] : 'Erro de validação.',
                isset($decoded['errors']) ? $decoded['errors'] : []
            );
        }

        throw new InventexException(isset($decoded['message']) ? $decoded['message'] : "Erro inesperado da API (HTTP {$status}).");
    }
}
