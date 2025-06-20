<?php

declare(strict_types=1);

namespace App\Service\Plugin\Plugins;

use App\Entity\Layer;
use App\Entity\User;

/**
 * Abstract Standalone Plugin
 * 
 * Base class for plugins that work independently without requiring a design layer
 * (e.g., YoutubeThumbnailPlugin, file generators, utility tools)
 */
abstract class AbstractStandalonePlugin extends AbstractPlugin
{
    /**
     * Execute command without layer requirement
     * Layer parameter is optional for standalone plugins
     */
    final public function executeCommand(User $user, ?Layer $layer, string $command, array $parameters = [], array $options = []): array
    {
        if (!$this->supportsCommand($command)) {
            throw new \RuntimeException(sprintf('Command "%s" is not supported by plugin "%s"', $command, $this->getName()));
        }

        return $this->executeStandaloneCommand($user, $command, $parameters, $options);
    }

    /**
     * Execute standalone command
     * This method must be implemented by concrete standalone plugins
     */
    abstract protected function executeStandaloneCommand(User $user, string $command, array $parameters = [], array $options = []): array;

    /**
     * Validate standalone plugin requirements
     * Override this method to add plugin-specific validation
     */
    protected function validateStandaloneRequirements(User $user, array $parameters = []): bool
    {
        // Default validation - can be overridden by specific plugins
        return true;
    }

    /**
     * Get plugin capabilities
     * Override this method to specify what the plugin can do
     */
    protected function getCapabilities(): array
    {
        return []; // Default to no specific capabilities
    }
}
