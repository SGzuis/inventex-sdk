<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

/**
 * $client->users()->list()->search('bruno')->get();
 * $client->users()->create()->name(...)->email(...)->password(...)->send();
 * $client->users()->find($uuid)->update(['name' => 'Novo Nome']);
 */
final class UserResource extends AbstractResource
{
    public function list(): UserQuery
    {
        return new UserQuery($this->connector);
    }

    public function create(): UserBuilder
    {
        return new UserBuilder($this->connector);
    }

    public function find(string $uuid): User
    {
        return new User($this->connector, $uuid);
    }
}
