<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Plugin Command Request DTO
 * 
 * Handles commands sent from plugin panels to execute specific plugin operations.
 * This DTO provides a secure interface for plugins to interact with the platform
 * without direct access to sensitive data or system internals.
 */
class PluginCommandRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Plugin identifier is required')]
        #[Assert\Regex(pattern: '/^[a-z0-9\-]+$/', message: 'Plugin identifier must contain only lowercase letters, numbers, and hyphens')]
        public readonly string $pluginId,

        #[Assert\NotBlank(message: 'Command is required')]
        #[Assert\Length(max: 100, maxMessage: 'Command must not exceed 100 characters')]
        public readonly string $command,

        #[Assert\NotBlank(message: 'Layer ID is required')]
        public readonly int $layerId,

        #[Assert\Type(type: 'array', message: 'Parameters must be an array')]
        public readonly array $parameters = [],

        #[Assert\Type(type: 'array', message: 'Options must be an array')]
        public readonly array $options = []
    ) {}

    /**
     * Get command parameters safely
     */
    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Get command options safely
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Check if parameter exists
     */
    public function hasParameter(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Check if option exists
     */
    public function hasOption(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }
}
