<?php

declare(strict_types=1);

namespace App\Service\Plugin\Plugins;

use App\Entity\Layer;
use App\Entity\User;
use App\Service\Plugin\Config\PluginConfig;
use App\Service\Plugin\PluginService;
use Psr\Log\LoggerInterface;

/**
 * Abstract Base Plugin Class
 * 
 * Provides common functionality for all plugins while allowing different execution patterns
 * for layer-based vs standalone plugins
 */
abstract class AbstractPlugin implements PluginInterface
{
    protected PluginConfig $config;

    public function __construct(
        protected readonly PluginService $pluginService,
        protected readonly LoggerInterface $logger,
        protected readonly string $environment,
        protected readonly string $projectDir
    ) {}

    /**
     * Set plugin configuration
     */
    public function setConfig(PluginConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * Get plugin configuration
     */
    public function getConfig(): PluginConfig
    {
        return $this->config;
    }

    // Base implementation methods from PluginInterface
    public function getName(): string
    {
        return $this->config->name;
    }

    public function getDescription(): string
    {
        return $this->config->description;
    }

    public function getVersion(): string
    {
        return $this->config->version;
    }

    public function getIcon(): string
    {
        return $this->config->icon;
    }

    public function getSupportedCommands(): array
    {
        return $this->config->supportedCommands;
    }

    public function supportsCommand(string $command): bool
    {
        return in_array($command, $this->getSupportedCommands(), true);
    }

    public function getRequirements(): array
    {
        return $this->config->requirements;
    }

    public function isAvailableForUser(User $user): bool
    {
        return $this->validateRequirements($user);
    }

    /**
     * Get plugin-specific directory
     */
    public function getPluginDirectory(string $subPath = ''): string
    {
        $basePath = $this->pluginService->getPluginDirectory($this->config->id);
        return $subPath ? $basePath . '/' . $subPath : $basePath;
    }

    /**
     * Check if plugin requires layer input
     */
    public function requiresLayer(): bool
    {
        return $this->config->requiresLayer();
    }

    /**
     * Check if plugin needs internet access
     */
    public function needsInternet(): bool
    {
        return $this->config->needsInternet();
    }

    /**
     * Get required integrations
     */
    public function getRequiredIntegrations(): array
    {
        return $this->config->getRequiredIntegrations();
    }

    // Abstract methods that must be implemented by concrete plugins
    abstract public function executeCommand(User $user, ?Layer $layer, string $command, array $parameters = [], array $options = []): array;
    abstract public function validateRequirements(User $user): bool;
}
