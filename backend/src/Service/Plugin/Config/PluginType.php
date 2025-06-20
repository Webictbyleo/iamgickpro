<?php

declare(strict_types=1);

namespace App\Service\Plugin\Config;

/**
 * Plugin Type Enum
 * 
 * Defines the different types of plugins supported by the system
 */
enum PluginType: string
{
    case LAYER_BASED = 'layer_based';     // Plugins that work on design layers (e.g., RemoveBgPlugin)
    case STANDALONE = 'standalone';       // Plugins that work independently (e.g., YoutubeThumbnailPlugin)
    case UTILITY = 'utility';            // Utility plugins for system operations
    case GENERATOR = 'generator';         // Content generation plugins
    case PROCESSOR = 'processor';         // Data processing plugins

    /**
     * Get human-readable description
     */
    public function getDescription(): string
    {
        return match($this) {
            self::LAYER_BASED => 'Works on design layers (requires layer input)',
            self::STANDALONE => 'Works independently (no layer required)',
            self::UTILITY => 'System utility functions',
            self::GENERATOR => 'Content generation tools',
            self::PROCESSOR => 'Data processing tools'
        };
    }

    /**
     * Check if this plugin type requires a layer
     */
    public function requiresLayer(): bool
    {
        return $this === self::LAYER_BASED;
    }
}
