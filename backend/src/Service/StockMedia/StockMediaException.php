<?php

declare(strict_types=1);

namespace App\Service\StockMedia;

use Exception;

/**
 * Exception thrown when stock media operations fail.
 * 
 * Provides specific error handling for stock media API integration issues
 * including rate limiting, API errors, and network failures.
 */
class StockMediaException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        private readonly ?string $provider = null,
        private readonly ?array $context = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the stock media provider that caused the exception
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Get additional context about the error
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * Create exception for API rate limiting
     */
    public static function rateLimitExceeded(string $provider, ?array $context = null): self
    {
        return new self(
            "Rate limit exceeded for {$provider} API",
            429,
            null,
            $provider,
            $context
        );
    }

    /**
     * Create exception for API authentication errors
     */
    public static function authenticationFailed(string $provider, ?array $context = null): self
    {
        return new self(
            "Authentication failed for {$provider} API",
            401,
            null,
            $provider,
            $context
        );
    }

    /**
     * Create exception for API quota exceeded
     */
    public static function quotaExceeded(string $provider, ?array $context = null): self
    {
        return new self(
            "API quota exceeded for {$provider}",
            403,
            null,
            $provider,
            $context
        );
    }

    /**
     * Create exception for network timeouts
     */
    public static function timeout(string $provider, ?array $context = null): self
    {
        return new self(
            "Request timeout for {$provider} API",
            408,
            null,
            $provider,
            $context
        );
    }

    /**
     * Create exception for service unavailable
     */
    public static function serviceUnavailable(string $provider, ?array $context = null): self
    {
        return new self(
            "{$provider} API is currently unavailable",
            503,
            null,
            $provider,
            $context
        );
    }
}
