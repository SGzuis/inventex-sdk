<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiResponse;
use Bootstech\InventexSdk\Http\Connector;

/**
 * $client->users()->create()
 *     ->name('Bruno Henrique')
 *     ->email('bruno@example.com')
 *     ->password('senha-forte', true)
 *     ->send();
 *
 * Assinatura de password(): (password, mustResetPassword = false).
 */
final class UserBuilder
{
    /** @var array<string, mixed> */
    private $payload = [];

    /** @var Connector */
    private $connector;

    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    public function name(string $name): self
    {
        $this->payload['name'] = $name;

        return $this;
    }

    public function email(string $email): self
    {
        $this->payload['email'] = $email;

        return $this;
    }

    public function password(string $password, bool $mustResetPassword = false): self
    {
        $this->payload['password'] = $password;
        $this->payload['password_confirmation'] = $password;
        $this->payload['must_reset_password'] = $mustResetPassword;

        return $this;
    }

    public function send(): ApiResponse
    {
        return $this->connector->post('users', $this->payload);
    }
}
