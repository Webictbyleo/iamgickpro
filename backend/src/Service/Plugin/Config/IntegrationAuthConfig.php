<?php

declare(strict_types=1);

namespace App\Service\Plugin\Config;

/**
 * Integration Authentication Configuration
 * 
 * Defines authentication requirements and injection patterns for a specific integration
 */
readonly class IntegrationAuthConfig
{
    public function __construct(
        public bool $required = false,
        public string $type = 'none',
        public string $credentialKey = 'api_key',
        public string $injectAs = '',
        public string $injectPattern = '{credential}'
    ) {}

    /**
     * Create from array configuration
     */
    public static function fromArray(array $config): self
    {
        return new self(
            required: $config['required'] ?? false,
            type: $config['type'] ?? 'none',
            credentialKey: $config['credential_key'] ?? 'api_key',
            injectAs: $config['inject_as'] ?? '',
            injectPattern: $config['inject_pattern'] ?? '{credential}'
        );
    }

    /**
     * Check if authentication is required
     */
    public function isRequired(): bool
    {
        return $this->required && $this->type !== 'none';
    }

    /**
     * Apply authentication to HTTP options using Symfony-style dot notation
     */
    public function applyToHttpOptions(array $httpOptions, array $credentials): array
    {
        if (!$this->isRequired() || empty($this->injectAs)) {
            return $httpOptions;
        }

        // Get the credential value
        $credentialValue = $credentials[$this->credentialKey] ?? null;
        if ($credentialValue === null) {
            throw new \RuntimeException(sprintf(
                'Required credential "%s" not found for authentication type "%s"',
                $this->credentialKey,
                $this->type
            ));
        }

        // Format the credential using the pattern
        $formattedValue = str_replace('{credential}', (string)$credentialValue, $this->injectPattern);

        // Parse the injection path (e.g., "headers.Authorization", "query.api_key", "body.token")
        return $this->injectValue($httpOptions, $this->injectAs, $formattedValue);
    }

    /**
     * Inject value using Symfony-style dot notation
     */
    private function injectValue(array $httpOptions, string $path, string $value): array
    {
        $parts = explode('.', $path);
        $current = &$httpOptions;

        // Navigate to the target location
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $part = $parts[$i];
            if (!isset($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }

        // Set the final value
        $finalKey = end($parts);
        $current[$finalKey] = $value;

        return $httpOptions;
    }

    /**
     * Get the HTTP location type (headers, query, body, etc.)
     */
    public function getInjectionLocation(): string
    {
        $parts = explode('.', $this->injectAs);
        return $parts[0] ?? '';
    }

    /**
     * Get the injection key name
     */
    public function getInjectionKey(): string
    {
        $parts = explode('.', $this->injectAs);
        return $parts[1] ?? '';
    }
}
