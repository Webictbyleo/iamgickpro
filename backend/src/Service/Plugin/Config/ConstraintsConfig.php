<?php

declare(strict_types=1);

namespace App\Service\Plugin\Config;

/**
 * Constraints Configuration
 * 
 * Defines request constraints like timeout and redirects
 */
readonly class ConstraintsConfig
{
    public function __construct(
        public ?int $timeout = null,
        public ?int $maxRedirects = null,
        public ?int $maxResponseSize = null,
        public array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'],
        public array $requiredHeaders = []
    ) {}

    /**
     * Create from array configuration
     */
    public static function fromArray(array $config): self
    {
        return new self(
            timeout: $config['timeout'] ?? null,
            maxRedirects: $config['max_redirects'] ?? null,
            maxResponseSize: $config['max_response_size'] ?? null,
            allowedMethods: $config['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'DELETE'],
            requiredHeaders: $config['required_headers'] ?? []
        );
    }

    /**
     * Apply constraints to HTTP options
     */
    public function apply(array $httpOptions): array
    {
        // Apply timeout
        if ($this->timeout !== null && $this->timeout > 0) {
            $httpOptions['timeout'] = $this->timeout;
        }

        // Apply max redirects
        if ($this->maxRedirects !== null) {
            $httpOptions['max_redirects'] = max(0, $this->maxRedirects);
        }

        // Apply max response size
        if ($this->maxResponseSize !== null && $this->maxResponseSize > 0) {
            $httpOptions['max_duration'] = $this->maxResponseSize;
        }

        // Apply required headers
        if (!empty($this->requiredHeaders)) {
            $httpOptions['headers'] = array_merge($httpOptions['headers'] ?? [], $this->requiredHeaders);
        }

        // Add User-Agent if not specified
        if (!isset($httpOptions['headers']['User-Agent']) && !isset($httpOptions['headers']['user-agent'])) {
            $httpOptions['headers']['User-Agent'] = 'IAmGickPro-Plugin/2.0';
        }

        return $httpOptions;
    }

    /**
     * Validate HTTP method is allowed
     */
    public function isMethodAllowed(string $method): bool
    {
        return in_array(strtoupper($method), array_map('strtoupper', $this->allowedMethods), true);
    }

    /**
     * Get timeout value
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    /**
     * Get max redirects value
     */
    public function getMaxRedirects(): ?int
    {
        return $this->maxRedirects;
    }
}
