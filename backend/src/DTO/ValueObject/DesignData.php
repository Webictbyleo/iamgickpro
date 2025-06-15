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
         * Background color of the design canvas (legacy).
         * 
         * Can be a hex color (#ffffff), RGB/RGBA, or named color.
         * Defaults to transparent if not specified.
         * 
         * @deprecated Use $background property instead for gradient support
         */
        #[Assert\Regex(
            pattern: '/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|[a-zA-Z]+|transparent)$/',
            message: 'Background color must be a valid color format'
        )]
        public string $backgroundColor = 'transparent',

        /**
         * Background configuration supporting gradients.
         * 
         * New background system that supports solid colors, linear gradients,
         * and radial gradients. Takes precedence over backgroundColor.
         */
        public ?DesignBackground $background = null,

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
        $result = [
            'backgroundColor' => $this->backgroundColor, // Keep for backward compatibility
            'animationSettings' => $this->animationSettings,
            'gridSettings' => $this->gridSettings,
            'viewSettings' => $this->viewSettings,
            'globalStyles' => $this->globalStyles,
            'customProperties' => $this->customProperties,
        ];

        // Add new background format if available
        if ($this->background !== null) {
            $result['background'] = $this->background->toArray();
        }

        return $result;
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
        // Handle background configuration
        $background = null;
        if (isset($data['background']) && is_array($data['background'])) {
            $background = DesignBackground::fromArray($data['background']);
        } elseif (isset($data['backgroundColor']) && $data['backgroundColor'] !== 'transparent') {
            // Convert legacy backgroundColor to new format
            $background = DesignBackground::solid($data['backgroundColor']);
        }

        return new self(
            backgroundColor: $data['backgroundColor'] ?? 'transparent',
            background: $background,
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

    /**
     * Gets the effective background color.
     * 
     * Returns the primary color from the new background format if available,
     * otherwise falls back to the legacy backgroundColor.
     */
    public function getEffectiveBackgroundColor(): string
    {
        if ($this->background !== null) {
            return $this->background->getPrimaryColor();
        }

        return $this->backgroundColor;
    }

    /**
     * Sets a solid background color.
     * 
     * Updates both the new background format and legacy backgroundColor
     * for maximum compatibility.
     */
    public function withSolidBackground(string $color): self
    {
        return new self(
            backgroundColor: $color,
            background: DesignBackground::solid($color),
            animationSettings: $this->animationSettings,
            gridSettings: $this->gridSettings,
            viewSettings: $this->viewSettings,
            globalStyles: $this->globalStyles,
            customProperties: $this->customProperties,
        );
    }

    /**
     * Sets a gradient background.
     * 
     * @param array<array{color: string, stop: float}> $colors
     */
    public function withGradientBackground(
        string $type,
        array $colors,
        array $options = []
    ): self {
        $background = match ($type) {
            'linear' => DesignBackground::linear($colors, $options['angle'] ?? 90),
            'radial' => DesignBackground::radial(
                $colors,
                $options['centerX'] ?? 0.5,
                $options['centerY'] ?? 0.5,
                $options['radius'] ?? 0.7
            ),
            default => throw new \InvalidArgumentException("Invalid gradient type: {$type}")
        };

        return new self(
            backgroundColor: $colors[0]['color'] ?? $this->backgroundColor,
            background: $background,
            animationSettings: $this->animationSettings,
            gridSettings: $this->gridSettings,
            viewSettings: $this->viewSettings,
            globalStyles: $this->globalStyles,
            customProperties: $this->customProperties,
        );
    }
}
