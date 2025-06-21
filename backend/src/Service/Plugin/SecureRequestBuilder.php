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
        $secureOptions = $this->mergeAuthenticationOptions($options, $credentials, $serviceName, $user, $internetConfig);
       
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
        
        // Validate each integration individually
        $hasValidCredentials = false;
        
        foreach ($config->integrations as $integration) {
            $authConfig = $config->getIntegrationAuth($integration);
            
            if (!$authConfig['required']) {
                // This integration doesn't require auth, continue to next
                continue;
            }
            
            try {
                $credentials = $this->integrationService->getCredentials($user, $integration);
                if ($credentials === null || empty($credentials)) {
                    continue;
                }
                
                // Check if required credential key exists
                $credentialKey = $authConfig['credential_key'];
                if (isset($credentials[$credentialKey]) && !empty($credentials[$credentialKey])) {
                    $hasValidCredentials = true;
                    break;
                }
            } catch (\Exception $e) {
                // Continue checking other integrations
                continue;
            }
        }
        
        if (!$hasValidCredentials) {
            throw new \RuntimeException(sprintf(
                'Authentication required for %s but no valid credentials found. Required integrations: %s',
                $serviceName,
                implode(', ', $config->integrations)
            ));
        }
    }

    /**
     * Get decrypted credentials for a user and service
     */
    private function getDecryptedCredentials(User $user, string $serviceName, ?InternetConfig $internetConfig = null): array
    {
        // If InternetConfig is provided and authentication is not required, return empty credentials
        if ($internetConfig !== null && !$internetConfig->required) {
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
        
        // Enhanced integration validation
        if ($internetConfig !== null) {
            $authConfig = $internetConfig->getIntegrationAuth($targetIntegration);
            
            if ($authConfig['required'] && $credentials === null) {
                throw new \RuntimeException(sprintf(
                    'Authentication required by plugin configuration but no credentials found for integration: %s',
                    $targetIntegration
                ));
            }
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
    private function mergeAuthenticationOptions(array $options, array $credentials, string $serviceName, User $user, ?InternetConfig $internetConfig = null): array
    {
        // If no InternetConfig or credentials, skip authentication
        if ($internetConfig === null || empty($credentials)) {
            return $options;
        }
        
        // Use enhanced per-integration authentication
        // Find the first integration that requires auth and has credentials
        foreach ($internetConfig->integrations as $integration) {
            $authConfig = $internetConfig->getIntegrationAuth($integration);
            
            if (!$authConfig['required']) {
                continue;
            }
            
            // Try to get credentials for this specific integration
            try {
                $integrationCredentials = $this->integrationService->getCredentials($user, $integration);
                
                if ($integrationCredentials !== null && !empty($integrationCredentials)) {
                    // Apply enhanced authentication using the InternetConfig method
                    return $internetConfig->applyIntegrationAuth($integration, $options, $integrationCredentials);
                }
            } catch (\Exception $e) {
                // Continue to next integration
                continue;
            }
        }
        
        // If no enhanced integration worked, return original options
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
