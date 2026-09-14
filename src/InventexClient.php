<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk;

use Bootstech\InventexSdk\Http\Connector;
use Bootstech\InventexSdk\Resources\InventoryResource;
use Bootstech\InventexSdk\Resources\RawResource;
use Bootstech\InventexSdk\Webhooks\WebhookVerifier;
use GuzzleHttp\Client;
use InvalidArgumentException;

/**
 * Ponto de entrada único do SDK — uma instância por token de aplicativo
 * (workspace). Uso típico:
 *
 *   $client = InventexClient::make(
 *       'https://app.inventex.com.br/api',
 *       'SEU_TOKEN_BEARER',
 *       'SUA_CHAVE_HMAC' // opcional, mas recomendado
 *   );
 *
 *   $client->inventories()->create()->name('Loja Centro')->date('2026-08-01')->send();
 *   $client->inventories()->find($uuid)->show();
 *
 * Para verificar webhooks recebidos, use webhookVerifier() com o mesmo
 * segredo (não precisa de InventexClient para isso).
 */
final class InventexClient
{
    /** @var Connector */
    private $connector;

    public function __construct(Config $config, ?Client $httpClient = null)
    {
        $this->connector = new Connector($config, $httpClient);
    }

    public static function make(string $baseUrl, string $token, ?string $signingSecret = null, int $timeoutSeconds = 15): self
    {
        return new self(new Config($baseUrl, $token, $signingSecret, $timeoutSeconds));
    }

    /**
     * Monta o cliente a partir de um array de configuração (chaves
     * `base_url`, `token`, `signing_secret`, `timeout`) — usado pelo
     * InventexServiceProvider do Laravel, mas independente dele: qualquer
     * projeto PHP pode montar esse array na mão (ex.: a partir do próprio
     * `$_ENV`) e chamar isso diretamente.
     *
     * @param  array{base_url?: ?string, token?: ?string, signing_secret?: ?string, timeout?: ?int}  $config
     */
    public static function fromArray(array $config): self
    {
        if (empty($config['base_url']) || empty($config['token'])) {
            throw new InvalidArgumentException('Configuração do Inventex SDK incompleta: "base_url" e "token" são obrigatórios.');
        }

        return self::make(
            $config['base_url'],
            $config['token'],
            isset($config['signing_secret']) ? $config['signing_secret'] : null,
            isset($config['timeout']) ? $config['timeout'] : 15
        );
    }

    public function inventories(): InventoryResource
    {
        return new InventoryResource($this->connector);
    }

    /**
     * Chamadas diretas para endpoints ainda não modelados como builder.
     */
    public function raw(): RawResource
    {
        return new RawResource($this->connector);
    }

    public static function webhookVerifier(string $signingSecret, int $toleranceSeconds = 300): WebhookVerifier
    {
        return new WebhookVerifier($signingSecret, $toleranceSeconds);
    }
}
