<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Exceptions;

/**
 * Erro de validação (HTTP 422) — carrega a lista de erros por campo
 * exatamente como devolvida pela API.
 */
class ValidationException extends InventexException
{
    /** @var array<string, list<string>> */
    private $errors;

    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(string $message, array $errors)
    {
        parent::__construct($message);

        $this->errors = $errors;
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
