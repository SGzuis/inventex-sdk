<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Laravel;

use Bootstech\InventexSdk\InventexClient;
use Illuminate\Support\ServiceProvider;

/**
 * Integração Laravel do SDK — registrada automaticamente via package
 * discovery (ver "extra.laravel" no composer.json, Laravel 5.5+). Publica
 * um config próprio (`config/inventex-sdk.php`) e resolve InventexClient::class
 * como singleton no container, para ser injetado normalmente:
 *
 *   private $inventex;
 *
 *   public function __construct(InventexClient $inventex)
 *   {
 *       $this->inventex = $inventex;
 *   }
 *
 * ou resolvido pela facade Inventex::inventories()->...
 */
class InventexServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/inventex-sdk.php', 'inventex-sdk');

        $this->app->singleton(InventexClient::class, static function ($app) {
            return InventexClient::fromArray($app['config']->get('inventex-sdk', []));
        });

        $this->app->alias(InventexClient::class, 'inventex');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/inventex-sdk.php' => config_path('inventex-sdk.php'),
            ], 'inventex-sdk-config');
        }
    }

    // Sem provides()/carregamento adiado de propósito: o marcador exigido
    // pra isso (Illuminate\Contracts\Support\DeferrableProvider) só existe a
    // partir do Laravel 8 — como o SDK suporta Laravel 5.5+, declarar
    // provides() sem essa interface seria código morto (o Laravel nunca
    // deferiria o registro), então preferimos não sugerir um comportamento
    // que não acontece.
}
