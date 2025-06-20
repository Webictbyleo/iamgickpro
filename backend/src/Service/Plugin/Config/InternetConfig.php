<?php

declare(strict_types=1);

namespace App\Service\Plugin\Config;

/**
 * Internet Configuration for Plugins
 * 
 * Defines how plugins access third-party APIs and manage credentials
 */
readonly class InternetConfig
{
    public function __construct(
        public bool $required = true,
        public array $integrations = [],
        public array $endpoints = [],
        public bool $requiresAuth = true,
        public string $authType = 'api_key',
        public array $permissions = [],
        public array $rateLimit = [],
        public array $metadata = [],
        public array $allowedDomains = [],
        public array $blockedDomains = [],
        public ?int $timeout = null,
        public ?int $maxRedirects = null
    ) {}

    /**
     * Create from array configuration
     */
    public static function fromArray(array $config): self
    {
        return new self(
            required: $config['required'] ?? true,
            integrations: $config['integrations'] ?? [],
            endpoints: $config['endpoints'] ?? [],
            requiresAuth: $config['requires_auth'] ?? true,
            authType: $config['auth_type'] ?? 'api_key',
            permissions: $config['permissions'] ?? [],
            rateLimit: $config['rate_limit'] ?? [],
            metadata: $config['metadata'] ?? [],
            allowedDomains: $config['allowed_domains'] ?? [],
            blockedDomains: $config['blocked_domains'] ?? [],
            timeout: $config['timeout'] ?? null,
            maxRedirects: $config['max_redirects'] ?? null
        );
    }

    /**
     * Check if specific integration is required
     */
    public function requiresIntegration(string $integration): bool
    {
        return in_array($integration, $this->integrations, true);
    }

    /**
     * Get authentication requirements
     */
    public function getAuthRequirements(): array
    {
        return [
            'required' => $this->requiresAuth,
            'type' => $this->authType,
            'integrations' => $this->integrations
        ];
    }

    /**
     * Get rate limiting configuration
     */
    public function getRateLimit(): array
    {
        return $this->rateLimit;
    }

    /**
     * Get allowed domains
     */
    public function getAllowedDomains(): array
    {
        return $this->allowedDomains;
    }

    /**
     * Get blocked domains
     */
    public function getBlockedDomains(): array
    {
        return $this->blockedDomains;
    }

    /**
     * Get timeout
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    /**
     * Get max redirects
     */
    public function getMaxRedirects(): ?int
    {
        return $this->maxRedirects;
    }
}
