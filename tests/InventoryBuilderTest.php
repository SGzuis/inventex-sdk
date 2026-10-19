<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Tests;

use Bootstech\InventexSdk\Config;
use Bootstech\InventexSdk\Http\Connector;
use Bootstech\InventexSdk\Resources\InventoryBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class InventoryBuilderTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function sentPayload(callable $configure): array
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(201, [], json_encode(['success' => true, 'message' => 'ok', 'data' => []]))]));
        $stack->push(Middleware::history($history));
        $connector = new Connector(new Config('https://app.inventex.test/api', 'token', null), new Client(['handler' => $stack]));

        $configure(new InventoryBuilder($connector))->send();

        return json_decode((string) $history[0]['request']->getBody(), true);
    }

    public function test_unit_counting_is_sent_under_settings(): void
    {
        $payload = $this->sentPayload(static function (InventoryBuilder $builder) {
            return $builder->name('Chassis')->unitCounting();
        });

        $this->assertSame(['unit_counting' => ['enabled' => true]], $payload['settings']);
    }

    public function test_unit_counting_can_be_disabled_explicitly(): void
    {
        $payload = $this->sentPayload(static function (InventoryBuilder $builder) {
            return $builder->unitCounting(false);
        });

        $this->assertFalse($payload['settings']['unit_counting']['enabled']);
    }

    public function test_settings_keys_are_merged_not_overwritten(): void
    {
        $payload = $this->sentPayload(static function (InventoryBuilder $builder) {
            return $builder->monetaryControl('valor')->unitCounting();
        });

        $this->assertSame([
            'monetary_control' => ['enabled' => true, 'params' => ['value_field' => 'valor']],
            'unit_counting' => ['enabled' => true],
        ], $payload['settings']);
    }

    public function test_quality_fields_are_top_level(): void
    {
        $payload = $this->sentPayload(static function (InventoryBuilder $builder) {
            return $builder->qualityEnabled()->qualityRequiresPhoto()->qualityTypes(['avaria', 'risco']);
        });

        $this->assertTrue($payload['quality_enabled']);
        $this->assertTrue($payload['quality_requires_photo']);
        $this->assertSame(['avaria', 'risco'], $payload['quality_types']);
        $this->assertArrayNotHasKey('settings', $payload);
    }
}
