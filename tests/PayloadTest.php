<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Support\Payload;
use PHPUnit\Framework\TestCase;

class PayloadTest extends TestCase
{
    public function test_removes_only_null_values(): void
    {
        $result = Payload::withoutNulls([
            'name' => 'Produto',
            'quantity' => '0',
            'user_id' => 0,
            'active' => false,
            'search' => '',
            'description' => null,
        ]);

        $this->assertSame([
            'name' => 'Produto',
            'quantity' => '0',
            'user_id' => 0,
            'active' => false,
            'search' => '',
        ], $result);
    }

    public function test_empty_array_stays_empty(): void
    {
        $this->assertSame([], Payload::withoutNulls([]));
    }
}
