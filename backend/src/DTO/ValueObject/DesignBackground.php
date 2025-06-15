<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Value object representing design background configuration.
 * 
 * Supports both solid colors and gradient backgrounds (linear/radial).
 */
readonly class DesignBackground
{
    public function __construct(
        /**
         * Type of background: 'solid', 'linear', or 'radial'
         */
        #[Assert\Choice(
            choices: ['solid', 'linear', 'radial'],
            message: 'Background type must be solid, linear, or radial'
        )]
        public string $type = 'solid',

        /**
         * Solid color for solid backgrounds.
         * Can be a hex color (#ffffff), RGB/RGBA, or named color.
         */
        #[Assert\Regex(
            pattern: '/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|[a-zA-Z]+|transparent)$/',
            message: 'Color must be a valid color format'
        )]
        public ?string $color = null,

        /**
         * Gradient configuration for linear and radial backgrounds.
         * 
         * @var array<string, mixed>|null $gradient Gradient configuration
         */
        #[Assert\Type('array', message: 'Gradient must be an array')]
        public ?array $gradient = null,
    ) {}

    /**
     * Creates a solid background.
     */
    public static function solid(string $color): self
    {
        return new self(
            type: 'solid',
            color: $color
        );
    }

    /**
     * Creates a linear gradient background.
     * 
     * @param array<array{color: string, stop: float}> $colors
     */
    public static function linear(array $colors, int $angle = 90): self
    {
        return new self(
            type: 'linear',
            gradient: [
                'colors' => $colors,
                'angle' => $angle,
            ]
        );
    }

    /**
     * Creates a radial gradient background.
     * 
     * @param array<array{color: string, stop: float}> $colors
     */
    public static function radial(
        array $colors,
        float $centerX = 0.5,
        float $centerY = 0.5,
        float $radius = 0.7
    ): self {
        return new self(
            type: 'radial',
            gradient: [
                'colors' => $colors,
                'centerX' => $centerX,
                'centerY' => $centerY,
                'radius' => $radius,
            ]
        );
    }

    /**
     * Converts the DesignBackground object to an array format.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'type' => $this->type,
        ];

        if ($this->color !== null) {
            $result['color'] = $this->color;
        }

        if ($this->gradient !== null) {
            $result['gradient'] = $this->gradient;
        }

        return $result;
    }

    /**
     * Creates a DesignBackground object from array data.
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? 'solid',
            color: $data['color'] ?? null,
            gradient: $data['gradient'] ?? null
        );
    }

    /**
     * Gets the primary color of the background.
     * For gradients, returns the first color.
     */
    public function getPrimaryColor(): string
    {
        if ($this->type === 'solid' && $this->color !== null) {
            return $this->color;
        }

        if ($this->gradient !== null && isset($this->gradient['colors'][0]['color'])) {
            return $this->gradient['colors'][0]['color'];
        }

        return '#ffffff';
    }

    /**
     * Validates the gradient configuration based on type.
     */
    public function isValidGradient(): bool
    {
        if ($this->type === 'solid') {
            return $this->color !== null;
        }

        if ($this->gradient === null) {
            return false;
        }

        // Check if colors array exists and has at least 2 colors
        if (!isset($this->gradient['colors']) || !is_array($this->gradient['colors']) || count($this->gradient['colors']) < 2) {
            return false;
        }

        // Validate each color stop
        foreach ($this->gradient['colors'] as $colorStop) {
            if (!is_array($colorStop) || !isset($colorStop['color']) || !isset($colorStop['stop'])) {
                return false;
            }
        }

        // Type-specific validation
        if ($this->type === 'linear') {
            return isset($this->gradient['angle']) && is_numeric($this->gradient['angle']);
        }

        if ($this->type === 'radial') {
            return isset($this->gradient['centerX'], $this->gradient['centerY'], $this->gradient['radius']) &&
                   is_numeric($this->gradient['centerX']) &&
                   is_numeric($this->gradient['centerY']) &&
                   is_numeric($this->gradient['radius']);
        }

        return false;
    }
}
