<?php

declare(strict_types=1);

namespace App\DTO\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Project settings that control project behavior and defaults
 */
final readonly class ProjectSettings
{
    public function __construct(
        /**
         * Width of the canvas in pixels (1-10000)
         * @var int $canvasWidth
         */
        #[Assert\Type(type: 'integer', message: 'Canvas width must be an integer')]
        #[Assert\Range(
            min: 1,
            max: 10000,
            notInRangeMessage: 'Canvas width must be between 1 and 10000 pixels'
        )]
        public int $canvasWidth = 1920,

        /**
         * Height of the canvas in pixels (1-10000)
         * @var int $canvasHeight
         */
        #[Assert\Type(type: 'integer', message: 'Canvas height must be an integer')]
        #[Assert\Range(
            min: 1,
            max: 10000,
            notInRangeMessage: 'Canvas height must be between 1 and 10000 pixels'
        )]
        public int $canvasHeight = 1080,

        /**
         * Background color of the canvas as hex value (e.g., #ffffff, #000)
         * @var string $backgroundColor
         */
        #[Assert\Regex(
            pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            message: 'Background color must be a valid hex color code'
        )]
        public string $backgroundColor = '#ffffff',

        /**
         * Canvas orientation mode (portrait or landscape)
         * @var string $orientation
         */
        #[Assert\Choice(
            choices: ['portrait', 'landscape'],
            message: 'Orientation must be portrait or landscape'
        )]
        public string $orientation = 'landscape',

        /**
         * Measurement units for the canvas (px, cm, mm, in, pt)
         * @var string $units
         */
        #[Assert\Choice(
            choices: ['px', 'cm', 'mm', 'in', 'pt'],
            message: 'Invalid unit type'
        )]
        public string $units = 'px',

        /**
         * Dots per inch resolution for print output (72-600)
         * @var int $dpi
         */
        #[Assert\Type(type: 'integer', message: 'DPI must be an integer')]
        #[Assert\Range(
            min: 72,
            max: 600,
            notInRangeMessage: 'DPI must be between 72 and 600'
        )]
        public int $dpi = 300,

        /**
         * Whether the grid overlay is visible on the canvas
         * @var bool $gridVisible
         */
        #[Assert\Type(type: 'boolean', message: 'Grid visible setting must be boolean')]
        public bool $gridVisible = false,

        /**
         * Whether rulers are visible around the canvas edges
         * @var bool $rulersVisible
         */
        #[Assert\Type(type: 'boolean', message: 'Rulers visible setting must be boolean')]
        public bool $rulersVisible = true,

        /**
         * Whether guide lines are visible on the canvas
         * @var bool $guidesVisible
         */
        #[Assert\Type(type: 'boolean', message: 'Guides visible setting must be boolean')]
        public bool $guidesVisible = true,

        /**
         * Whether objects automatically snap to grid lines
         * @var bool $snapToGrid
         */
        #[Assert\Type(type: 'boolean', message: 'Snap to grid setting must be boolean')]
        public bool $snapToGrid = false,

        /**
         * Whether objects automatically snap to guide lines
         * @var bool $snapToGuides
         */
        #[Assert\Type(type: 'boolean', message: 'Snap to guides setting must be boolean')]
        public bool $snapToGuides = true,

        /**
         * Whether objects automatically snap to other objects
         * @var bool $snapToObjects
         */
        #[Assert\Type(type: 'boolean', message: 'Snap to objects setting must be boolean')]
        public bool $snapToObjects = true
    ) {}

    public function toArray(): array
    {
        return [
            'canvasWidth' => $this->canvasWidth,
            'canvasHeight' => $this->canvasHeight,
            'backgroundColor' => $this->backgroundColor,
            'orientation' => $this->orientation,
            'units' => $this->units,
            'dpi' => $this->dpi,
            'gridVisible' => $this->gridVisible,
            'rulersVisible' => $this->rulersVisible,
            'guidesVisible' => $this->guidesVisible,
            'snapToGrid' => $this->snapToGrid,
            'snapToGuides' => $this->snapToGuides,
            'snapToObjects' => $this->snapToObjects,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            canvasWidth: (int)($data['canvasWidth'] ?? 1920),
            canvasHeight: (int)($data['canvasHeight'] ?? 1080),
            backgroundColor: $data['backgroundColor'] ?? '#ffffff',
            orientation: $data['orientation'] ?? 'landscape',
            units: $data['units'] ?? 'px',
            dpi: (int)($data['dpi'] ?? 300),
            gridVisible: (bool)($data['gridVisible'] ?? false),
            rulersVisible: (bool)($data['rulersVisible'] ?? true),
            guidesVisible: (bool)($data['guidesVisible'] ?? true),
            snapToGrid: (bool)($data['snapToGrid'] ?? false),
            snapToGuides: (bool)($data['snapToGuides'] ?? true),
            snapToObjects: (bool)($data['snapToObjects'] ?? true),
        );
    }
}
