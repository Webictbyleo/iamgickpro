<?php

declare(strict_types=1);

namespace App\Service\Plugin\Config;

/**
 * Integration Configuration
 * 
 * Defines configuration for a specific third-party integration
 */
readonly class IntegrationConfig
{
    public function __construct(
        public string $name,
        public IntegrationAuthConfig $auth,
        public array $endpoints = [],
        public array $permissions = [],
        public array $rateLimits = []
    ) {}

    /**
     * Create from array configuration
     */
    public static function fromArray(string $name, array $config): self
    {
        $authConfig = IntegrationAuthConfig::fromArray($config['auth'] ?? []);

        return new self(
            name: $name,
            auth: $authConfig,
            endpoints: $config['endpoints'] ?? [],
            permissions: $config['permissions'] ?? [],
            rateLimits: $config['rate_limits'] ?? []
        );
    }

    /**
     * Check if authentication is required
     */
    public function requiresAuthentication(): bool
    {
        return $this->auth->isRequired();
    }

    /**
     * Get authentication type
     */
    public function getAuthType(): string
    {
        return $this->auth->type;
    }

    /**
     * Get credential key name
     */
    public function getCredentialKey(): string
    {
        return $this->auth->credentialKey;
    }

    /**
     * Apply authentication to HTTP options
     */
    public function applyAuthToHttpOptions(array $httpOptions, array $credentials): array
    {
        return $this->auth->applyToHttpOptions($httpOptions, $credentials);
    }

    /**
     * Check if endpoint is allowed for this integration
     */
    public function isEndpointAllowed(string $url): bool
    {
        if (empty($this->endpoints)) {
            return true; // No restrictions
        }

        foreach ($this->endpoints as $allowedEndpoint) {
            if (str_starts_with($url, $allowedEndpoint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get rate limit for specific period
     */
    public function getRateLimit(string $period): ?int
    {
        return $this->rateLimits[$period] ?? null;
    }

    /**
     * Check if permission is granted
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
