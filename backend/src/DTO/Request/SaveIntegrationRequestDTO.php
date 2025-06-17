<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for saving integration credentials.
 * 
 * Handles storage of encrypted third-party API credentials
 * and settings for various integrations like OpenAI, Remove.bg, etc.
 */
class SaveIntegrationRequestDTO
{
    /**
     * Service name for the integration.
     * 
     * Supported services: openai, removebg, unsplash, pexels
     */
    #[Assert\NotBlank(message: 'Service name is required')]
    #[Assert\Choice(
        choices: ['openai', 'removebg', 'unsplash', 'pexels'],
        message: 'Invalid service name. Supported: openai, removebg, unsplash, pexels'
    )]
    public readonly string $serviceName;

    /**
     * API credentials for the service.
     * 
     * Contains service-specific credentials like API keys, access tokens, etc.
     * Will be encrypted before storage.
     */
    #[Assert\NotBlank(message: 'Credentials are required')]
    #[Assert\Type('array', message: 'Credentials must be an array')]
    public readonly array $credentials;

    /**
     * Optional settings for the integration.
     * 
     * Contains non-sensitive configuration options for the service.
     */
    #[Assert\Type('array', message: 'Settings must be an array')]
    public readonly ?array $settings;

    public function __construct(
        string $serviceName,
        array $credentials,
        ?array $settings = null
    ) {
        $this->serviceName = $serviceName;
        $this->credentials = $credentials;
        $this->settings = $settings;
    }
}
