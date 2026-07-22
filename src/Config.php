<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk;

/**
 * Configuração imutável de uma conexão com a API do Inventex. `$signingSecret`
 * é opcional: sem ele o SDK só envia o Bearer token — se o aplicativo exigir
 * assinatura HMAC (padrão, ver VerifyWorkspaceSignature na API), informe-o
 * para que o Connector assine cada requisição automaticamente.
 *
 * Compatível com PHP 7.2+ de propósito (sem readonly/promoted properties,
 * exclusivos do PHP 8, e sem property type declarations, exclusivas do
 * PHP 7.4) — trate como imutável por convenção.
 */
final class Config
{
    /** @var string */
    public $baseUrl;

    /** @var string */
    public $token;

    /** @var string|null */
    public $signingSecret;

    /** @var int */
    public $timeoutSeconds;

    public function __construct(string $baseUrl, string $token, ?string $signingSecret = null, int $timeoutSeconds = 15)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
        $this->signingSecret = $signingSecret;
        $this->timeoutSeconds = $timeoutSeconds;
    }
}
