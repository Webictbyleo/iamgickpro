<?php

declare(strict_types=1);

namespace App\Service\Plugin\Config;

/**
 * Injection Configuration
 * 
 * Defines how authentication credentials should be injected into HTTP requests
 */
readonly class InjectionConfig
{
    public function __construct(
        public string $method = 'none',
        public string $key = '',
        public string $pattern = '{value}',
        public array $options = []
    ) {}

    /**
     * Create from array configuration
     */
    public static function fromArray(array $config): self
    {
        return new self(
            method: $config['method'] ?? 'none',
            key: $config['key'] ?? '',
            pattern: $config['pattern'] ?? '{value}',
            options: $config['options'] ?? []
        );
    }

    /**
     * Apply injection to HTTP options
     */
    public function apply(array $httpOptions, array $credentials): array
    {
        if ($this->method === 'none' || empty($credentials)) {
            return $httpOptions;
        }

        switch ($this->method) {
            case 'header':
                return $this->applyHeaderInjection($httpOptions, $credentials);
            case 'query':
                return $this->applyQueryInjection($httpOptions, $credentials);
            case 'body':
                return $this->applyBodyInjection($httpOptions, $credentials);
            case 'auth_basic':
                return $this->applyBasicAuthInjection($httpOptions, $credentials);
            default:
                return $httpOptions;
        }
    }

    /**
     * Apply header injection
     */
    private function applyHeaderInjection(array $httpOptions, array $credentials): array
    {
        if (empty($this->key)) {
            return $httpOptions;
        }

        $value = $this->interpolatePattern($credentials);
        $httpOptions['headers'] = array_merge($httpOptions['headers'] ?? [], [
            $this->key => $value
        ]);

        return $httpOptions;
    }

    /**
     * Apply query parameter injection
     */
    private function applyQueryInjection(array $httpOptions, array $credentials): array
    {
        if (empty($this->key)) {
            return $httpOptions;
        }

        $value = $this->interpolatePattern($credentials);
        $httpOptions['query'] = array_merge($httpOptions['query'] ?? [], [
            $this->key => $value
        ]);

        return $httpOptions;
    }

    /**
     * Apply body injection
     */
    private function applyBodyInjection(array $httpOptions, array $credentials): array
    {
        if (empty($this->key)) {
            return $httpOptions;
        }

        $value = $this->interpolatePattern($credentials);
        
        // Handle different body types
        if (is_array($httpOptions['body'] ?? null)) {
            $httpOptions['body'][$this->key] = $value;
        } else {
            // If body is not an array, create it as an array
            $httpOptions['body'] = array_merge($httpOptions['body'] ?? [], [
                $this->key => $value
            ]);
        }

        return $httpOptions;
    }

    /**
     * Apply basic authentication injection
     */
    private function applyBasicAuthInjection(array $httpOptions, array $credentials): array
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        
        if (!empty($username) && !empty($password)) {
            $httpOptions['auth_basic'] = [$username, $password];
        }

        return $httpOptions;
    }

    /**
     * Interpolate pattern with credential values
     */
    private function interpolatePattern(array $credentials): string
    {
        $pattern = $this->pattern;
        
        // Replace placeholders like {api_key}, {username}, etc.
        foreach ($credentials as $key => $value) {
            $pattern = str_replace('{' . $key . '}', (string)$value, $pattern);
        }
        
        return $pattern;
    }

    /**
     * Check if injection is configured
     */
    public function isConfigured(): bool
    {
        return $this->method !== 'none' && !empty($this->key);
    }
}
