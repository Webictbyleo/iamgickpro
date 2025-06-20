<?php

declare(strict_types=1);

namespace App\Service\Plugin;

use App\Entity\User;
use App\Service\IntegrationService;
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
     * @return ResponseInterface The HTTP response
     * @throws \RuntimeException If integration not found or credentials invalid
     */
    public function makeRequest(
        User $user,
        string $serviceName,
        string $method,
        string $url,
        array $options = []
    ): ResponseInterface {
        // Decrypt credentials only at the moment of use
        $credentials = $this->getDecryptedCredentials($user, $serviceName);
        
        // Merge authentication into request options
        $secureOptions = $this->mergeAuthenticationOptions($options, $credentials, $serviceName);
        
        // Make the HTTP request
        return $this->httpClient->request($method, $url, $secureOptions);
    }

    /**
     * Create a request builder for a specific service and user
     */
    public function forService(User $user, string $serviceName): ServiceRequestBuilder
    {
        return new ServiceRequestBuilder($this, $user, $serviceName);
    }

    /**
     * Get decrypted credentials for a user and service
     */
    private function getDecryptedCredentials(User $user, string $serviceName): array
    {
        // Some services don't require authentication
        if (in_array($serviceName, ['youtube'], true)) {
            return [];
        }
        
        $credentials = $this->integrationService->getCredentials($user, $serviceName);
        
        if ($credentials === null) {
            throw new \RuntimeException(sprintf('Integration not found or failed to decrypt credentials for service: %s', $serviceName));
        }
        
        return $credentials;
    }

    /**
     * Merge authentication options based on service type
     */
    private function mergeAuthenticationOptions(array $options, array $credentials, string $serviceName): array
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
}

/**
 * Service-specific request builder for fluent API
 */
class ServiceRequestBuilder
{
    public function __construct(
        private readonly SecureRequestBuilder $requestBuilder,
        private readonly User $user,
        private readonly string $serviceName
    ) {}

    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->requestBuilder->makeRequest($this->user, $this->serviceName, 'GET', $url, $options);
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->requestBuilder->makeRequest($this->user, $this->serviceName, 'POST', $url, $options);
    }

    public function put(string $url, array $options = []): ResponseInterface
    {
        return $this->requestBuilder->makeRequest($this->user, $this->serviceName, 'PUT', $url, $options);
    }

    public function delete(string $url, array $options = []): ResponseInterface
    {
        return $this->requestBuilder->makeRequest($this->user, $this->serviceName, 'DELETE', $url, $options);
    }
}
