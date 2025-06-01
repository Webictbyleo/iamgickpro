<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Value object representing design-level configuration and settings.
 * 
 * This object contains global design settings that affect the entire
 * canvas and design behavior, separate from individual layer properties.
 */
readonly class DesignData
{
    public function __construct(
        /**
         * Background color of the design canvas.
         * 
         * Can be a hex color (#ffffff), RGB/RGBA, or named color.
         * Defaults to transparent if not specified.
         */
        #[Assert\Regex(
            pattern: '/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|[a-zA-Z]+|transparent)$/',
            message: 'Background color must be a valid color format'
        )]
        public string $backgroundColor = 'transparent',

        /**
         * Animation settings for the design.
         * 
         * Contains configuration for design-level animations including:
         * - duration: Total animation duration in seconds
         * - loop: Whether animations should loop
         * - autoplay: Whether to start animations automatically
         * - easing: Global easing function for animations
         * 
         * @var array<string, mixed> $animationSettings Global animation configuration
         */
        #[Assert\Type('array', message: 'Animation settings must be an array')]
        public array $animationSettings = [],

        /**
         * Grid and snap settings for the design canvas.
         * 
         * Contains configuration for design assistance tools:
         * - gridSize: Size of grid squares in pixels
         * - snapToGrid: Whether elements snap to grid
         * - showGrid: Whether grid is visible
         * - snapToObjects: Whether elements snap to other objects
         * - snapTolerance: Distance in pixels for snap activation
         * 
         * @var array<string, mixed> $gridSettings Grid and snap configuration
         */
        #[Assert\Type('array', message: 'Grid settings must be an array')]
        public array $gridSettings = [
            'gridSize' => 20,
            'snapToGrid' => false,
            'showGrid' => false,
            'snapToObjects' => true,
            'snapTolerance' => 5,
        ],

        /**
         * View and zoom settings for the design canvas.
         * 
         * Contains configuration for canvas view state:
         * - zoom: Current zoom level (1.0 = 100%)
         * - panX: Horizontal pan offset in pixels
         * - panY: Vertical pan offset in pixels
         * - viewMode: Current view mode (fit, fill, actual, etc.)
         * 
         * @var array<string, mixed> $viewSettings View and zoom configuration
         */
        #[Assert\Type('array', message: 'View settings must be an array')]
        public array $viewSettings = [
            'zoom' => 1.0,
            'panX' => 0,
            'panY' => 0,
            'viewMode' => 'fit',
        ],

        /**
         * Global styles and themes applied to the design.
         * 
         * Contains design-level styling that can be applied to layers:
         * - colorPalette: Array of color swatches for the design
         * - fontPairs: Recommended font combinations
         * - theme: Overall design theme or style guide
         * 
         * @var array<string, mixed> $globalStyles Global styling configuration
         */
        #[Assert\Type('array', message: 'Global styles must be an array')]
        public array $globalStyles = [],

        /**
         * Custom metadata and extended properties.
         * 
         * Allows for custom properties and future extensibility
         * without breaking the schema. Should contain key-value
         * pairs of additional design-level configuration.
         * 
         * @var array<string, mixed> $customProperties Extended configuration
         */
        #[Assert\Type('array', message: 'Custom properties must be an array')]
        public array $customProperties = [],
    ) {}

    /**
     * Converts the DesignData object to an array format.
     * 
     * Provides compatibility with existing code that expects
     * array-based design data.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'backgroundColor' => $this->backgroundColor,
            'animationSettings' => $this->animationSettings,
            'gridSettings' => $this->gridSettings,
            'viewSettings' => $this->viewSettings,
            'globalStyles' => $this->globalStyles,
            'customProperties' => $this->customProperties,
        ];
    }

    /**
     * Creates a DesignData object from array data.
     * 
     * Factory method for creating typed DesignData objects from
     * legacy array-based input data.
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            backgroundColor: $data['backgroundColor'] ?? 'transparent',
            animationSettings: $data['animationSettings'] ?? [],
            gridSettings: array_merge([
                'gridSize' => 20,
                'snapToGrid' => false,
                'showGrid' => false,
                'snapToObjects' => true,
                'snapTolerance' => 5,
            ], $data['gridSettings'] ?? []),
            viewSettings: array_merge([
                'zoom' => 1.0,
                'panX' => 0,
                'panY' => 0,
                'viewMode' => 'fit',
            ], $data['viewSettings'] ?? []),
            globalStyles: $data['globalStyles'] ?? [],
            customProperties: $data['customProperties'] ?? [],
        );
    }

    /**
     * Merges this DesignData with another set of data.
     * 
     * Creates a new DesignData object with updated values,
     * useful for partial updates where only some properties change.
     * 
     * @param array<string, mixed> $updates
     * @return self
     */
    public function withUpdates(array $updates): self
    {
        return self::fromArray(array_merge($this->toArray(), $updates));
    }
}
