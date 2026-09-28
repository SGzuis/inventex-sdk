<?php

return [

    /*
    |--------------------------------------------------------------------------
    | URL base da API
    |--------------------------------------------------------------------------
    |
    | Inclua o prefixo /api, ex.: https://sua-instancia.inventex.com.br/api
    |
    */
    'base_url' => env('INVENTEX_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Token do aplicativo
    |--------------------------------------------------------------------------
    |
    | Gerado em Workspace → Integração → Aplicativos no Inventex.
    |
    */
    'token' => env('INVENTEX_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Chave de assinatura HMAC
    |--------------------------------------------------------------------------
    |
    | A API exige assinatura HMAC em toda requisição autenticada por token de
    | aplicativo, inclusive leituras — informe a chave para que o SDK assine
    | cada requisição automaticamente.
    |
    */
    'signing_secret' => env('INVENTEX_SIGNING_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Timeout (segundos)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('INVENTEX_TIMEOUT', 15),

];
