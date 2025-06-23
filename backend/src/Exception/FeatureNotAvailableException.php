<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when a user tries to access a feature not available in their plan
 */
class FeatureNotAvailableException extends \RuntimeException
{
    public function __construct(string $message = 'Feature not available in current plan', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
