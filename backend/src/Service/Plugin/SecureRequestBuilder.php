<?php

declare(strict_types=1);

namespace App\Service\Plugin;

use App\Entity\User;
use App\Service\IntegrationService;
use App\Service\Plugin\Config\InternetConfig;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Secure Request Builder
 * 
 * Provides a secure way for plugins to make HTTP requests to third-party APIs
 * without exposing decrypted credentials. All API credentials remain encrypted
 * and are only decrypted at the moment of making the actual HTTP request.
 */
class SecureRequestBuilder
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly IntegrationService $integrationService
    ) {}

    /**
     * Build and execute a secure HTTP request
     * 
     * @param User $user The user making the request
     * @param string $serviceName The third-party service name (e.g., 'removebg')
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $url The API endpoint URL
     * @param array $options HTTP client options (headers, body, etc.)
     * @param InternetConfig|null $internetConfig Optional internet configuration to enforce
     * @return ResponseInterface The HTTP response
     * @throws \RuntimeException If integration not found or credentials invalid
     */
    public function makeRequest(
        User $user,
        string $serviceName,
        string $method,
        string $url,
        array $options = [],
        ?InternetConfig $internetConfig = null
    ): ResponseInterface {
        // Validate internet configuration if provided
        if ($internetConfig !== null) {
            $this->validateInternetConfig($url, $internetConfig);
            $this->validateInternetRequirements($user, $serviceName, $internetConfig);
        }
        
        // Decrypt credentials only at the moment of use
        $credentials = $this->getDecryptedCredentials($user, $serviceName, $internetConfig);
        
        // Merge authentication into request options
        $secureOptions = $this->mergeAuthenticationOptions($options, $credentials, $serviceName, $internetConfig);
        
        // Apply internet config constraints to request options
        if ($internetConfig !== null) {
            $secureOptions = $this->applyInternetConfigConstraints($secureOptions, $internetConfig);
        }
        
        // Make the HTTP request
        return $this->httpClient->request($method, $url, $secureOptions);
    }

    /**
     * Create a request builder for a specific service and user
     */
    public function forService(User $user, string $serviceName, ?InternetConfig $internetConfig = null): ServiceRequestBuilder
    {
        return new ServiceRequestBuilder($this, $user, $serviceName, $internetConfig);
    }

    /**
     * Validate internet requirements from InternetConfig
     */
    private function validateInternetRequirements(User $user, string $serviceName, InternetConfig $config): void
    {
        // Check if internet access is required
        if (!$config->required) {
            // Internet is not required, so we can skip further validation
            return;
        }
        
        // Check if authentication is required
        if ($config->requiresAuth) {
            // Verify that the service has credentials configured
            $hasCredentials = false;
            
            // Check each required integration
            foreach ($config->integrations as $integration) {
                try {
                    $credentials = $this->integrationService->getCredentials($user, $integration);
                    if ($credentials !== null && !empty($credentials)) {
                        $hasCredentials = true;
                        break;
                    }
                } catch (\Exception $e) {
                    // Continue checking other integrations
                    continue;
                }
            }
            
            if (!$hasCredentials) {
                throw new \RuntimeException(sprintf(
                    'Authentication required for %s but no valid credentials found. Required integrations: %s',
                    $serviceName,
                    implode(', ', $config->integrations)
                ));
            }
            
            // Validate auth type requirements
            $authType = $config->authType;
            if ($authType === 'api_key') {
                // For API key auth, ensure we have an API key
                $validApiKey = false;
                foreach ($config->integrations as $integration) {
                    try {
                        $credentials = $this->integrationService->getCredentials($user, $integration);
                        if (isset($credentials['api_key']) && !empty($credentials['api_key'])) {
                            $validApiKey = true;
                            break;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                
                if (!$validApiKey) {
                    throw new \RuntimeException(sprintf(
                        'API key authentication required for %s but no valid API key found',
                        $serviceName
                    ));
                }
            } elseif ($authType === 'oauth') {
                // For OAuth, we might need different validation
                // This can be extended based on specific OAuth requirements
                throw new \RuntimeException(sprintf(
                    'OAuth authentication required for %s but OAuth validation not yet implemented',
                    $serviceName
                ));
            }
        }
        
        // Validate permissions if specified
        if (!empty($config->permissions)) {
            // For now, we'll log the required permissions
            // This could be extended to integrate with a permission system
            foreach ($config->permissions as $permission) {
                // Log or validate specific permissions
                // This is a placeholder for future permission validation
            }
        }
    }

    /**
     * Get decrypted credentials for a user and service
     */
    private function getDecryptedCredentials(User $user, string $serviceName, ?InternetConfig $internetConfig = null): array
    {
        // If InternetConfig is provided and authentication is not required, return empty credentials
        if ($internetConfig !== null && !$internetConfig->requiresAuth) {
            return [];
        }
        
        // Some services don't require authentication (legacy check)
        if (in_array($serviceName, ['youtube'], true)) {
            return [];
        }
        
        // Determine which integration to use based on InternetConfig
        $targetIntegration = $serviceName; // Default to service name
        if ($internetConfig !== null && !empty($internetConfig->integrations)) {
            // Use the first integration from the config
            $targetIntegration = $internetConfig->integrations[0];
        }
        
        $credentials = $this->integrationService->getCredentials($user, $targetIntegration);
        
        // If InternetConfig requires auth, we must have credentials
        if ($internetConfig !== null && $internetConfig->requiresAuth && $credentials === null) {
            throw new \RuntimeException(sprintf(
                'Authentication required by plugin configuration but no credentials found for integration: %s',
                $targetIntegration
            ));
        }
        
        // Legacy behavior: throw if no credentials (when no InternetConfig)
        if ($internetConfig === null && $credentials === null) {
            throw new \RuntimeException(sprintf('Integration not found or failed to decrypt credentials for service: %s', $serviceName));
        }
        
        return $credentials ?? [];
    }

    /**
     * Merge authentication options based on service type and InternetConfig
     */
    private function mergeAuthenticationOptions(array $options, array $credentials, string $serviceName, ?InternetConfig $internetConfig = null): array
    {
        // If InternetConfig specifies no auth required, skip authentication
        if ($internetConfig !== null && !$internetConfig->requiresAuth) {
            return $options;
        }
        
        // If we have InternetConfig, use its auth type preference
        if ($internetConfig !== null && !empty($credentials)) {
            return $this->applyAuthenticationByType($options, $credentials, $internetConfig->authType, $serviceName);
        }
        
        // Legacy service-specific authentication (fallback)
        return $this->applyLegacyAuthentication($options, $credentials, $serviceName);
    }

    /**
     * Apply authentication based on the specified auth type
     */
    private function applyAuthenticationByType(array $options, array $credentials, string $authType, string $serviceName): array
    {
        switch ($authType) {
            case 'api_key':
                // Generic API key authentication
                if (isset($credentials['api_key'])) {
                    // Try service-specific header first
                    if ($serviceName === 'removebg') {
                        $options['headers']['X-Api-Key'] = $credentials['api_key'];
                    } elseif ($serviceName === 'openai') {
                        $options['headers']['Authorization'] = 'Bearer ' . $credentials['api_key'];
                    } elseif ($serviceName === 'unsplash') {
                        $options['headers']['Authorization'] = 'Client-ID ' . $credentials['api_key'];
                    } elseif ($serviceName === 'pexels') {
                        $options['headers']['Authorization'] = $credentials['api_key'];
                    } else {
                        // Default to Bearer token
                        $options['headers']['Authorization'] = 'Bearer ' . $credentials['api_key'];
                    }
                }
                break;
                
            case 'oauth':
                // OAuth token authentication
                if (isset($credentials['access_token'])) {
                    $options['headers']['Authorization'] = 'Bearer ' . $credentials['access_token'];
                }
                break;
                
            case 'basic':
                // Basic authentication
                if (isset($credentials['username']) && isset($credentials['password'])) {
                    $options['auth_basic'] = [$credentials['username'], $credentials['password']];
                }
                break;
                
            default:
                // Fallback to legacy authentication
                return $this->applyLegacyAuthentication($options, $credentials, $serviceName);
        }
        
        return $options;
    }

    /**
     * Apply legacy service-specific authentication
     */
    private function applyLegacyAuthentication(array $options, array $credentials, string $serviceName): array
    {
        switch ($serviceName) {
            case 'removebg':
                $options['headers'] = array_merge($options['headers'] ?? [], [
                    'X-Api-Key' => $credentials['api_key'] ?? '',
                ]);
                break;
                
            case 'openai':
                $options['headers'] = array_merge($options['headers'] ?? [], [
                    'Authorization' => 'Bearer ' . ($credentials['api_key'] ?? ''),
                ]);
                break;
                
            case 'youtube':
                // YouTube oEmbed doesn't require authentication
                // Just ensure we have proper headers
                $options['headers'] = array_merge($options['headers'] ?? [], [
                    'Accept' => 'application/json',
                ]);
                break;
                
            case 'unsplash':
                $options['headers'] = array_merge($options['headers'] ?? [], [
                    'Authorization' => 'Client-ID ' . ($credentials['access_key'] ?? ''),
                ]);
                break;
                
            case 'pexels':
                $options['headers'] = array_merge($options['headers'] ?? [], [
                    'Authorization' => $credentials['api_key'] ?? '',
                ]);
                break;
                
            default:
                // Generic API key handling
                if (isset($credentials['api_key'])) {
                    $options['headers'] = array_merge($options['headers'] ?? [], [
                        'Authorization' => 'Bearer ' . $credentials['api_key'],
                    ]);
                }
                break;
        }
        
        return $options;
    }

    /**
     * Validate that the URL complies with the InternetConfig constraints
     */
    private function validateInternetConfig(string $url, InternetConfig $config): void
    {
        // Parse the URL to get the domain
        $parsedUrl = parse_url($url);
        if ($parsedUrl === false || !isset($parsedUrl['host'])) {
            throw new \RuntimeException('Invalid URL provided for HTTP request');
        }
        
        $domain = strtolower($parsedUrl['host']);
        
        // Check allowed domains if specified
        $allowedDomains = $config->getAllowedDomains();
        if (!empty($allowedDomains)) {
            $domainAllowed = false;
            foreach ($allowedDomains as $allowedDomain) {
                $allowedDomain = strtolower($allowedDomain);
                // Support wildcards (e.g., *.example.com)
                if (str_starts_with($allowedDomain, '*.')) {
                    $allowedDomain = substr($allowedDomain, 2);
                    if (str_ends_with($domain, $allowedDomain)) {
                        $domainAllowed = true;
                        break;
                    }
                } elseif ($domain === $allowedDomain) {
                    $domainAllowed = true;
                    break;
                }
            }
            
            if (!$domainAllowed) {
                throw new \RuntimeException(sprintf(
                    'Domain "%s" is not allowed by plugin configuration. Allowed domains: %s',
                    $domain,
                    implode(', ', $allowedDomains)
                ));
            }
        }
        
        // Check blocked domains if specified
        $blockedDomains = $config->getBlockedDomains();
        if (!empty($blockedDomains)) {
            foreach ($blockedDomains as $blockedDomain) {
                $blockedDomain = strtolower($blockedDomain);
                // Support wildcards (e.g., *.example.com)
                if (str_starts_with($blockedDomain, '*.')) {
                    $blockedDomain = substr($blockedDomain, 2);
                    if (str_ends_with($domain, $blockedDomain)) {
                        throw new \RuntimeException(sprintf(
                            'Domain "%s" is blocked by plugin configuration',
                            $domain
                        ));
                    }
                } elseif ($domain === $blockedDomain) {
                    throw new \RuntimeException(sprintf(
                        'Domain "%s" is blocked by plugin configuration',
                        $domain
                    ));
                }
            }
        }
    }

    /**
     * Apply InternetConfig constraints to request options
     */
    private function applyInternetConfigConstraints(array $options, InternetConfig $config): array
    {
        // Apply timeout if specified
        $timeout = $config->getTimeout();
        if ($timeout !== null && $timeout > 0) {
            $options['timeout'] = $timeout;
        }
        
        // Apply max redirects if specified
        $maxRedirects = $config->getMaxRedirects();
        if ($maxRedirects !== null) {
            $options['max_redirects'] = max(0, $maxRedirects);
        }
        
        // Add User-Agent if not already specified
        if (!isset($options['headers']['User-Agent']) && !isset($options['headers']['user-agent'])) {
            $options['headers']['User-Agent'] = 'IAmGickPro-Plugin/1.0';
        }
        
        return $options;
    }
}

/**
 * Service-specific request builder for fluent API
 */
class ServiceRequestBuilder
{
    public function __construct(
        private readonly SecureRequestBuilder $requestBuilder,
        private readonly User $user,
        private readonly string $serviceName,
        private readonly ?InternetConfig $internetConfig = null
    ) {}

    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->requestBuilder->makeRequest($this->user, $this->serviceName, 'GET', $url, $options, $this->internetConfig);
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->requestBuilder->makeRequest($this->user, $this->serviceName, 'POST', $url, $options, $this->internetConfig);
    }

    public function put(string $url, array $options = []): ResponseInterface
    {
        return $this->requestBuilder->makeRequest($this->user, $this->serviceName, 'PUT', $url, $options, $this->internetConfig);
    }

    public function delete(string $url, array $options = []): ResponseInterface
    {
        return $this->requestBuilder->makeRequest($this->user, $this->serviceName, 'DELETE', $url, $options, $this->internetConfig);
    }
}
