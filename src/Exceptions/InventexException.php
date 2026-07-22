<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Exceptions;

use RuntimeException;

/**
 * Exceção base do SDK — capture esta para tratar qualquer erro vindo da
 * integração (rede, autenticação, validação, etc.) de forma genérica.
 */
class InventexException extends RuntimeException {}
