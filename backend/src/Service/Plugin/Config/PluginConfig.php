<?php

declare(strict_types=1);

namespace App\Service\Plugin\Config;

/**
 * Plugin Configuration Class
 * 
 * Defines plugin metadata, dependencies, and requirements in a structured way
 */
readonly class PluginConfig
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $version,
        public string $icon,
        public PluginType $type,
        public array $supportedCommands,
        public array $dependencies,
        public ?InternetConfig $internet = null,
        public array $requirements = [],
        public array $metadata = []
    ) {}

    /**
     * Create config from array (for loading from config files)
     */
    public static function fromArray(string $id, array $config): self
    {
        return new self(
            id: $id,
            name: $config['name'],
            description: $config['description'],
            version: $config['version'],
            icon: $config['icon'],
            type: PluginType::from($config['type']),
            supportedCommands: $config['supported_commands'] ?? [],
            dependencies: $config['dependencies'] ?? [],
            internet: isset($config['internet']) ? InternetConfig::fromArray($config['internet']) : null,
            requirements: $config['requirements'] ?? [],
            metadata: $config['metadata'] ?? []
        );
    }

    /**
     * Check if plugin requires layers
     */
    public function requiresLayer(): bool
    {
        return $this->type === PluginType::LAYER_BASED;
    }

    /**
     * Check if plugin needs internet access
     */
    public function needsInternet(): bool
    {
        return $this->internet !== null;
    }

    /**
     * Get required integrations
     */
    public function getRequiredIntegrations(): array
    {
        return $this->internet?->integrations ?? [];
    }
}
