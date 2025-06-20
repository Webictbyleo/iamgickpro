<?php

declare(strict_types=1);

namespace App\Service\Plugin\Plugins;

use App\Entity\Layer;
use App\Entity\User;

/**
 * Plugin Interface
 * 
 * Defines the contract that all plugins must implement to integrate with the platform.
 * Provides methods for command execution, capability declaration, and user access control.
 * 
 * Note: Layer parameter is nullable to support both layer-based and standalone plugins
 */
interface PluginInterface
{
    /**
     * Get plugin name
     */
    public function getName(): string;

    /**
     * Get plugin description
     */
    public function getDescription(): string;

    /**
     * Get plugin version
     */
    public function getVersion(): string;

    /**
     * Get plugin icon URL or path
     */
    public function getIcon(): string;

    /**
     * Get list of supported commands
     */
    public function getSupportedCommands(): array;

    /**
     * Check if plugin supports a specific command
     */
    public function supportsCommand(string $command): bool;

    /**
     * Execute a plugin command
     * 
     * @param User $user The user executing the command
     * @param Layer|null $layer The target layer (null for standalone plugins)
     * @param string $command The command to execute
     * @param array $parameters Command parameters
     * @param array $options Command options
     * @return array Command execution result
     */
    public function executeCommand(User $user, ?Layer $layer, string $command, array $parameters = [], array $options = []): array;

    /**
     * Check if plugin is available for a specific user
     */
    public function isAvailableForUser(User $user): bool;

    /**
     * Get plugin requirements (integrations, permissions, etc.)
     */
    public function getRequirements(): array;

    /**
     * Validate if user meets plugin requirements
     */
    public function validateRequirements(User $user): bool;

    /**
     * Check if plugin requires layer input
     */
    public function requiresLayer(): bool;
}
