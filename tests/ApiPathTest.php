<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Http\ApiPath;
use PHPUnit\Framework\TestCase;

class ApiPathTest extends TestCase
{
    public function test_builds_a_simple_path(): void
    {
        $path = ApiPath::make('inventories')->segment('abc-123')->append('state/start');

        $this->assertSame('inventories/abc-123/state/start', $path->toString());
    }

    public function test_segment_always_encodes_the_value(): void
    {
        $path = ApiPath::make('inventories')->segment('../../admin/secrets');

        $this->assertSame('inventories/..%2F..%2Fadmin%2Fsecrets', $path->toString());
    }

    public function test_append_does_not_encode_since_it_is_a_fixed_literal(): void
    {
        $path = ApiPath::make('inventories')->segment('abc')->append('positions/search');

        $this->assertSame('inventories/abc/positions/search', $path->toString());
    }

    public function test_is_immutable_each_call_returns_a_new_instance(): void
    {
        $base = ApiPath::make('inventories')->segment('abc');

        $withPositions = $base->append('positions');
        $withItems = $base->append('items');

        $this->assertSame('inventories/abc/positions', $withPositions->toString());
        $this->assertSame('inventories/abc/items', $withItems->toString());
    }
}
