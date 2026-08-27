<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Resources;

use Bootstech\InventexSdk\Http\ApiPath;
use Bootstech\InventexSdk\Http\ApiResponse;

/**
 * Autenticação de usuário (Sanctum) — distinta do Bearer token/HMAC do
 * `InventexClient::make()`, que autentica o aplicativo/workspace, não uma
 * pessoa. Use quando o consumidor do SDK precisa logar um usuário final
 * (ex.: app mobile) em vez de operar só com um token de integração fixo:
 *
 *   $response = $client->auth()->login('bruno@example.com', 'senha-forte');
 *   $token = $response->get('token');
 *   // monte um novo InventexClient com esse $token para as chamadas seguintes
 *
 *   $client->auth()->user();
 *   $client->auth()->logout();
 */
final class AuthResource extends AbstractResource
{
    public function login(string $email, string $password): ApiResponse
    {
        return $this->connector->post('auth/login', ['email' => $email, 'password' => $password]);
    }

    /**
     * @param  array{name: string, email: string, password: string, password_confirmation: string, workspace_name?: string}  $data
     */
    public function register(array $data): ApiResponse
    {
        return $this->connector->post('auth/register', $data);
    }

    public function logout(): ApiResponse
    {
        return $this->connector->post('auth/logout');
    }

    public function user(): ApiResponse
    {
        return $this->connector->get('auth/user');
    }

    public function updateProfile(string $name, string $email): ApiResponse
    {
        return $this->connector->put('auth/profile', ['name' => $name, 'email' => $email]);
    }

    public function updatePassword(string $currentPassword, string $newPassword): ApiResponse
    {
        return $this->connector->put('auth/password', [
            'current_password' => $currentPassword,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);
    }

    public function sessions(): ApiResponse
    {
        return $this->connector->get('auth/sessions');
    }

    public function revokeSession(string $tokenId): ApiResponse
    {
        return $this->connector->delete(ApiPath::make('auth')->append('sessions')->segment($tokenId)->toString());
    }

    /**
     * Atividades da própria conta do usuário logado — diferente de
     * `Inventory::activities()` (histórico de um inventário específico).
     */
    public function activities(): ApiResponse
    {
        return $this->connector->get('auth/activities');
    }

    public function deleteAccount(): ApiResponse
    {
        return $this->connector->delete('auth/account');
    }

    public function switchWorkspace(string $workspaceUuid): ApiResponse
    {
        return $this->connector->post(ApiPath::make('auth')->append('switch-workspace')->segment($workspaceUuid)->toString());
    }
}
