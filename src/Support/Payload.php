<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Support;

/**
 * Único lugar responsável por "montar corpo de requisição só com os campos
 * que o caller realmente informou" — usa comparação estrita contra null
 * (não o array_filter() puro, que também removeria "0", "", false e 0,
 * valores legítimos como quantidade "0" ou um id 0).
 */
final class Payload
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withoutNulls(array $data): array
    {
        return array_filter($data, function ($value) {
            return $value !== null;
        });
    }
}
