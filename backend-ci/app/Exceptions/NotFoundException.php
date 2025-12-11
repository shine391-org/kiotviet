<?php

namespace App\Exceptions;

/**
 * Exception for resources that are not found.
 * Maps to HTTP 404 responses in controllers.
 */
class NotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Resource not found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
