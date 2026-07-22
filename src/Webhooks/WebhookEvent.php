<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Webhooks;

/**
 * Evento de webhook já verificado e decodificado — `$name` é um dos valores
 * de WebhookEventType da API (ex.: "inventory.started", "item.counted").
 */
final class WebhookEvent
{
    /** @var string */
    public $name;

    /** @var string */
    public $eventId;

    /** @var array<string, mixed> */
    public $data;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(string $name, string $eventId, array $data)
    {
        $this->name = $name;
        $this->eventId = $eventId;
        $this->data = $data;
    }

    public function is(string $name): bool
    {
        return $this->name === $name;
    }
}
