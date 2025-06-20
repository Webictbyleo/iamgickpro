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
        public string $authType = 'none',
        public bool $authRequired = false,
        public array $credentials = [],
        public InjectionConfig $injection = new InjectionConfig(),
        public array $endpoints = [],
        public array $permissions = [],
        public array $rateLimit = []
    ) {}

    /**
     * Create from array configuration
     */
    public static function fromArray(string $name, array $config): self
    {
        $injectionConfig = InjectionConfig::fromArray($config['injection'] ?? []);
        
        return new self(
            name: $name,
            authType: $config['auth_type'] ?? 'none',
            authRequired: $config['auth_required'] ?? false,
            credentials: $config['credentials'] ?? [],
            injection: $injectionConfig,
            endpoints: $config['endpoints'] ?? [],
            permissions: $config['permissions'] ?? [],
            rateLimit: $config['rate_limit'] ?? []
        );
    }

    /**
     * Check if authentication is required
     */
    public function requiresAuthentication(): bool
    {
        return $this->authRequired && $this->authType !== 'none';
    }

    /**
     * Get credential key name for a specific type
     */
    public function getCredentialKey(string $type = 'api_key'): ?string
    {
        return $this->credentials[$type] ?? null;
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
        return $this->rateLimit[$period] ?? null;
    }
}
