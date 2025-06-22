<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for testing integration credentials.
 * 
 * Handles testing of API credentials for third-party services
 * by making simple API calls to verify connectivity and authentication.
 */
class TestIntegrationRequestDTO
{
    /**
     * Service name for the integration.
     * 
     * Supported services: openai, removebg, unsplash, pexels, replicate
     */
    #[Assert\NotBlank(message: 'Service name is required')]
    #[Assert\Choice(
        choices: ['openai', 'removebg', 'unsplash', 'pexels', 'replicate'],
        message: 'Invalid service name. Supported: openai, removebg, unsplash, pexels, replicate'
    )]
    public readonly string $serviceName;

    /**
     * Optional credentials to test.
     * 
     * If not provided, will use stored credentials for the user.
     * If provided, will test these credentials without storing them.
     */
    #[Assert\Type('array', message: 'Credentials must be an array')]
    public readonly ?array $credentials;

    public function __construct(
        string $serviceName,
        ?array $credentials = null
    ) {
        $this->serviceName = $serviceName;
        $this->credentials = $credentials;
    }
}
