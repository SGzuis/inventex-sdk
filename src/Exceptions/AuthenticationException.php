<?php

declare(strict_types=1);

namespace Bootstech\InventexSdk\Exceptions;

/**
 * Token inválido, expirado ou sem permissão (HTTP 401/403).
 */
class AuthenticationException extends InventexException {}
