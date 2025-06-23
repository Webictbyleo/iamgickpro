<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when a user tries to exceed their subscription limits
 */
class SubscriptionLimitExceededException extends \RuntimeException
{
    public function __construct(string $message = 'Subscription limit exceeded', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
