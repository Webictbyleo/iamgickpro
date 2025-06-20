<?php

declare(strict_types=1);

namespace App\Service\Plugin\Plugins;

use App\Entity\Layer;
use App\Entity\User;

/**
 * Abstract Layer-Based Plugin
 * 
 * Base class for plugins that operate on design layers (e.g., RemoveBgPlugin)
 * These plugins require a layer as input and modify or analyze layer data
 */
abstract class AbstractLayerPlugin extends AbstractPlugin
{
    /**
     * Execute command on a specific layer
     * Layer is required for layer-based plugins
     */
    final public function executeCommand(User $user, ?Layer $layer, string $command, array $parameters = [], array $options = []): array
    {
        if (!$layer) {
            throw new \RuntimeException('Layer is required for layer-based plugins');
        }

        if (!$this->supportsCommand($command)) {
            throw new \RuntimeException(sprintf('Command "%s" is not supported by plugin "%s"', $command, $this->getName()));
        }

        return $this->executeLayerCommand($user, $layer, $command, $parameters, $options);
    }

    /**
     * Execute layer-specific command
     * This method must be implemented by concrete layer-based plugins
     */
    abstract protected function executeLayerCommand(User $user, Layer $layer, string $command, array $parameters = [], array $options = []): array;

    /**
     * Validate layer compatibility
     * Override this method to add layer-specific validation
     */
    protected function validateLayer(Layer $layer): bool
    {
        // Default validation - can be overridden by specific plugins
        return true;
    }

    /**
     * Get supported layer types
     * Override this method to specify which layer types the plugin supports
     */
    protected function getSupportedLayerTypes(): array
    {
        return ['image', 'text', 'shape', 'group']; // Default to all types
    }
}
