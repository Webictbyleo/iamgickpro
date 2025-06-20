<?php

declare(strict_types=1);

namespace App\Service\Plugin\Config;

/**
 * Domain Configuration
 * 
 * Defines allowed and blocked domains for internet access
 */
readonly class DomainConfig
{
    public function __construct(
        public array $allowed = [],
        public array $blocked = []
    ) {}

    /**
     * Create from array configuration
     */
    public static function fromArray(array $config): self
    {
        return new self(
            allowed: $config['allowed'] ?? [],
            blocked: $config['blocked'] ?? []
        );
    }

    /**
     * Check if a URL is allowed
     */
    public function isUrlAllowed(string $url): bool
    {
        $parsedUrl = parse_url($url);
        if ($parsedUrl === false || !isset($parsedUrl['host'])) {
            return false;
        }

        $domain = strtolower($parsedUrl['host']);

        // Check blocked domains first
        if ($this->isDomainBlocked($domain)) {
            return false;
        }

        // If no allowed domains specified, allow all (except blocked)
        if (empty($this->allowed)) {
            return true;
        }

        // Check allowed domains
        return $this->isDomainAllowed($domain);
    }

    /**
     * Check if domain is explicitly allowed
     */
    private function isDomainAllowed(string $domain): bool
    {
        foreach ($this->allowed as $allowedDomain) {
            if ($this->matchesDomainPattern($domain, strtolower($allowedDomain))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if domain is explicitly blocked
     */
    private function isDomainBlocked(string $domain): bool
    {
        foreach ($this->blocked as $blockedDomain) {
            if ($this->matchesDomainPattern($domain, strtolower($blockedDomain))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if domain matches pattern (supports wildcards)
     */
    private function matchesDomainPattern(string $domain, string $pattern): bool
    {
        // Exact match
        if ($domain === $pattern) {
            return true;
        }

        // Wildcard match (e.g., *.example.com)
        if (str_starts_with($pattern, '*.')) {
            $baseDomain = substr($pattern, 2);
            return str_ends_with($domain, '.' . $baseDomain) || $domain === $baseDomain;
        }

        return false;
    }

    /**
     * Get all allowed domains
     */
    public function getAllowedDomains(): array
    {
        return $this->allowed;
    }

    /**
     * Get all blocked domains
     */
    public function getBlockedDomains(): array
    {
        return $this->blocked;
    }
}
