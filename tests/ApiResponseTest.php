<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Http\ApiResponse;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    /**
     * Regressão: isset() trata um valor null como "não existe" — um campo
     * que a API devolve como null de propósito (ex.: webhook_url ainda não
     * configurado) não pode fazer get() cair pro $default informado pelo
     * caller.
     */
    public function test_get_returns_actual_null_instead_of_default_when_key_is_present_but_null(): void
    {
        $response = new ApiResponse([
            'success' => true,
            'message' => 'ok',
            'data' => ['webhook_url' => null, 'name' => 'App X'],
        ], 200);

        $this->assertNull($response->get('webhook_url', 'DEFAULT_FALLBACK'));
        $this->assertSame('App X', $response->get('name'));
    }

    public function test_get_returns_default_when_key_is_truly_absent(): void
    {
        $response = new ApiResponse([
            'success' => true,
            'message' => 'ok',
            'data' => ['name' => 'App X'],
        ], 200);

        $this->assertSame('DEFAULT_FALLBACK', $response->get('webhook_url', 'DEFAULT_FALLBACK'));
    }
}
