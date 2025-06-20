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
        public ?int $maxRedirects = null,
        // Enhanced per-integration configurations
        public array $enhancedIntegrations = [] // Array of integration name => config arrays
    ) {}

    /**
     * Create from array configuration
     */
    public static function fromArray(array $config): self
    {
        // Handle enhanced integrations (new format) vs legacy integrations (old format)
        $enhancedIntegrations = [];
        $legacyIntegrations = [];
        
        if (isset($config['integrations'])) {
            if (is_array($config['integrations']) && !empty($config['integrations'])) {
                // Check if it's the new enhanced format (associative array) or legacy format (indexed array)
                $firstKey = array_key_first($config['integrations']);
                if (is_string($firstKey)) {
                    // New enhanced format: integrations are associative array with configs
                    $enhancedIntegrations = $config['integrations'];
                    $legacyIntegrations = array_keys($enhancedIntegrations);
                } else {
                    // Legacy format: integrations are simple array of strings
                    $legacyIntegrations = $config['integrations'];
                }
            }
        }
        
        // Handle domains (new nested format vs legacy flat format)
        $allowedDomains = [];
        $blockedDomains = [];
        
        if (isset($config['domains'])) {
            $allowedDomains = $config['domains']['allow'] ?? $config['domains']['allowed'] ?? [];
            $blockedDomains = $config['domains']['block'] ?? $config['domains']['blocked'] ?? [];
        } else {
            // Legacy format
            $allowedDomains = $config['allowed_domains'] ?? [];
            $blockedDomains = $config['blocked_domains'] ?? [];
        }
        
        // Handle constraints (new nested format vs legacy flat format)
        $timeout = null;
        $maxRedirects = null;
        
        if (isset($config['constraints'])) {
            $timeout = $config['constraints']['timeout'] ?? null;
            $maxRedirects = $config['constraints']['max_redirects'] ?? null;
        } else {
            // Legacy format
            $timeout = $config['timeout'] ?? null;
            $maxRedirects = $config['max_redirects'] ?? null;
        }
        
        return new self(
            required: $config['required'] ?? true,
            integrations: $legacyIntegrations,
            endpoints: $config['endpoints'] ?? [],
            requiresAuth: $config['requires_auth'] ?? true,
            authType: $config['auth_type'] ?? 'api_key',
            permissions: $config['permissions'] ?? [],
            rateLimit: $config['rate_limit'] ?? [],
            metadata: $config['metadata'] ?? [],
            allowedDomains: $allowedDomains,
            blockedDomains: $blockedDomains,
            timeout: $timeout,
            maxRedirects: $maxRedirects,
            enhancedIntegrations: $enhancedIntegrations
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

    // ========================================
    // Enhanced Integration Configuration Methods
    // ========================================

    /**
     * Check if enhanced integration configuration is available
     */
    public function hasEnhancedIntegrations(): bool
    {
        return !empty($this->enhancedIntegrations);
    }

    /**
     * Get enhanced integration configuration for a specific integration
     */
    public function getIntegrationConfig(string $integration): ?array
    {
        return $this->enhancedIntegrations[$integration] ?? null;
    }

    /**
     * Get authentication configuration for a specific integration
     */
    public function getIntegrationAuth(string $integration): array
    {
        $config = $this->getIntegrationConfig($integration);
        
        if ($config === null) {
            // Fallback to legacy configuration
            $isRequired = $this->requiresAuth && in_array($integration, $this->integrations);
            return [
                'required' => $isRequired,
                'type' => $this->authType,
                'credential_key' => 'api_key',
                'injection_method' => 'header',
                'injection_key' => $this->getDefaultInjectionKey($integration),
                'injection_pattern' => $this->getDefaultInjectionPattern($integration)
            ];
        }
        
        $auth = $config['auth'] ?? [];
        
        // Parse injection using Symfony-style dot notation
        $injectAs = $auth['inject_as'] ?? 'headers.Authorization';
        $injectionParts = explode('.', $injectAs, 2);
        $injectionMethod = $injectionParts[0] ?? 'headers';
        $injectionKey = $injectionParts[1] ?? 'Authorization';
        
        // Map method names
        $methodMap = [
            'headers' => 'header',
            'query' => 'query',
            'body' => 'body',
            'auth_basic' => 'auth_basic'
        ];
        
        return [
            'required' => $auth['required'] ?? false,
            'type' => $auth['type'] ?? 'api_key',
            'credential_key' => $auth['credential_key'] ?? 'api_key',
            'injection_method' => $methodMap[$injectionMethod] ?? 'header',
            'injection_key' => $injectionKey,
            'injection_pattern' => $auth['inject_pattern'] ?? '{credential}'
        ];
    }

    /**
     * Get endpoints for a specific integration
     */
    public function getIntegrationEndpoints(string $integration): array
    {
        $config = $this->getIntegrationConfig($integration);
        
        if ($config === null) {
            // Fallback to legacy endpoints (all endpoints for all integrations)
            return $this->endpoints;
        }
        
        return $config['endpoints'] ?? [];
    }

    /**
     * Get rate limits for a specific integration
     */
    public function getIntegrationRateLimit(string $integration): array
    {
        $config = $this->getIntegrationConfig($integration);
        
        if ($config === null) {
            // Fallback to global rate limit
            return $this->rateLimit;
        }
        
        return $config['rate_limit'] ?? [];
    }

    /**
     * Get permissions for a specific integration
     */
    public function getIntegrationPermissions(string $integration): array
    {
        $config = $this->getIntegrationConfig($integration);
        
        if ($config === null) {
            // Fallback to global permissions
            return $this->permissions;
        }
        
        return $config['permissions'] ?? [];
    }

    /**
     * Check if integration requires authentication (enhanced or legacy)
     */
    public function integrationRequiresAuth(string $integration): bool
    {
        $auth = $this->getIntegrationAuth($integration);
        return $auth['required'] ?? false;
    }

    /**
     * Apply authentication to HTTP options for a specific integration
     */
    public function applyIntegrationAuth(string $integration, array $httpOptions, array $credentials): array
    {
        $auth = $this->getIntegrationAuth($integration);
        
        if (!$auth['required']) {
            return $httpOptions;
        }
        
        $credentialKey = $auth['credential_key'];
        $credentialValue = $credentials[$credentialKey] ?? null;
        
        if ($credentialValue === null) {
            return $httpOptions;
        }
        
        $injectionMethod = $auth['injection_method'];
        $injectionKey = $auth['injection_key'];
        $injectionPattern = $auth['injection_pattern'];
        
        // Replace placeholders in injection pattern
        $injectedValue = str_replace('{credential}', $credentialValue, $injectionPattern);
        $injectedValue = str_replace('{' . $credentialKey . '}', $credentialValue, $injectedValue);
        
        switch ($injectionMethod) {
            case 'header':
                $httpOptions['headers'] = array_merge($httpOptions['headers'] ?? [], [
                    $injectionKey => $injectedValue
                ]);
                break;
                
            case 'query':
                $httpOptions['query'] = array_merge($httpOptions['query'] ?? [], [
                    $injectionKey => $injectedValue
                ]);
                break;
                
            case 'body':
                if (is_array($httpOptions['body'] ?? null)) {
                    $httpOptions['body'][$injectionKey] = $injectedValue;
                } else {
                    $httpOptions['body'] = array_merge($httpOptions['body'] ?? [], [
                        $injectionKey => $injectedValue
                    ]);
                }
                break;
                
            case 'auth_basic':
                if ($auth['type'] === 'basic' && isset($credentials['username']) && isset($credentials['password'])) {
                    $httpOptions['auth_basic'] = [$credentials['username'], $credentials['password']];
                }
                break;
        }
        
        return $httpOptions;
    }

    /**
     * Get default injection key for legacy integrations
     */
    private function getDefaultInjectionKey(string $integration): string
    {
        return match ($integration) {
            'removebg' => 'X-Api-Key',
            'openai' => 'Authorization',
            'unsplash' => 'Authorization',
            'pexels' => 'Authorization',
            default => 'Authorization'
        };
    }

    /**
     * Get default injection pattern for legacy integrations
     */
    private function getDefaultInjectionPattern(string $integration): string
    {
        return match ($integration) {
            'removebg' => '{credential}',
            'openai' => 'Bearer {credential}',
            'unsplash' => 'Client-ID {credential}',
            'pexels' => '{credential}',
            default => 'Bearer {credential}'
        };
    }
}
