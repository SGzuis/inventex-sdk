<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Exceptions\AuthenticationException;
use Bootstech\InventexSdk\Exceptions\InventexException;
use Bootstech\InventexSdk\Exceptions\NotFoundException;
use Bootstech\InventexSdk\Exceptions\ValidationException;
use Bootstech\InventexSdk\Http\ErrorResponseMapper;
use PHPUnit\Framework\TestCase;

/**
 * Testa o mapeamento status->exceção isoladamente, sem precisar montar um
 * Connector/MockHandler/Guzzle inteiro — só foi possível separar assim
 * porque ErrorResponseMapper não depende de nada de transporte HTTP.
 */
class ErrorResponseMapperTest extends TestCase
{
    public function test_401_and_403_map_to_authentication_exception(): void
    {
        $this->expectException(AuthenticationException::class);
        ErrorResponseMapper::throwForStatus(401, []);
    }

    public function test_404_maps_to_not_found_exception(): void
    {
        $this->expectException(NotFoundException::class);
        ErrorResponseMapper::throwForStatus(404, []);
    }

    public function test_422_maps_to_validation_exception_with_errors(): void
    {
        try {
            ErrorResponseMapper::throwForStatus(422, ['message' => 'Erro.', 'errors' => ['name' => ['obrigatório']]]);
            $this->fail('Esperava ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['name' => ['obrigatório']], $e->errors());
        }
    }

    public function test_unknown_status_maps_to_generic_exception(): void
    {
        $this->expectException(InventexException::class);
        ErrorResponseMapper::throwForStatus(500, []);
    }
}
