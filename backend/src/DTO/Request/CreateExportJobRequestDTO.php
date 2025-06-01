<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for export job creation requests.
 * 
 * Handles the submission of design export jobs to the background
 * processing system. Supports multiple output formats and quality
 * settings for rendering designs to various file types including
 * static images, PDFs, and animated formats.
 */
final readonly class CreateExportJobRequestDTO
{
    public function __construct(
        /**
         * ID of the design to export.
         * 
         * References the specific design that should be rendered
         * and exported. The user must have access permissions
         * to the design for the export to succeed.
         */
        #[Assert\NotBlank(message: 'Design ID is required')]
        #[Assert\Type(type: 'integer', message: 'Design ID must be an integer')]
        #[Assert\Positive(message: 'Design ID must be positive')]
        public int $designId,

        /**
         * Output format for the exported file.
         * 
         * Determines the file type and rendering pipeline used
         * for the export. Each format has different capabilities
         * and use cases:
         * 
         * - 'png': High-quality raster with transparency support
         * - 'jpeg': Compressed raster for smaller file sizes
         * - 'svg': Vector format for scalable graphics
         * - 'pdf': Print-ready document format
         * - 'mp4': Video format for animated designs
         * - 'gif': Animated raster format
         */
        #[Assert\NotBlank(message: 'Format is required')]
        #[Assert\Choice(choices: ['png', 'jpeg', 'svg', 'pdf', 'mp4', 'gif'], message: 'Invalid format')]
        public string $format = 'png',

        /**
         * Quality level for the export rendering.
         * 
         * Controls the balance between file size and visual quality:
         * - 'low': Faster rendering, smaller files, reduced quality
         * - 'medium': Balanced rendering and quality (default)
         * - 'high': Better quality, larger files, slower rendering
         * - 'ultra': Maximum quality for professional use
         */
        #[Assert\Choice(choices: ['low', 'medium', 'high', 'ultra'], message: 'Invalid quality')]
        public string $quality = 'medium',

        /**
         * Custom width for the exported file in pixels.
         * 
         * If provided, overrides the design's canvas width.
         * Must be used with height or scale. Null uses the
         * design's original dimensions.
         */
        #[Assert\Type(type: 'integer', message: 'Width must be an integer')]
        #[Assert\Positive(message: 'Width must be positive')]
        public ?int $width = null,

        /**
         * Custom height for the exported file in pixels.
         * 
         * If provided, overrides the design's canvas height.
         * Must be used with width or scale. Null uses the
         * design's original dimensions.
         */
        #[Assert\Type(type: 'integer', message: 'Height must be an integer')]
        #[Assert\Positive(message: 'Height must be positive')]
        public ?int $height = null,

        /**
         * Scale factor for resizing the export.
         * 
         * Multiplier applied to the design's original dimensions.
         * For example, 2.0 doubles the size, 0.5 halves it.
         * Alternative to specifying exact width/height.
         */
        #[Assert\Type(type: 'float', message: 'Scale must be a number')]
        #[Assert\PositiveOrZero(message: 'Scale must be positive or zero')]
        public ?float $scale = null,

        /**
         * Enable transparent background for supported formats.
         * 
         * When true, removes the canvas background and exports
         * with transparency. Only supported by PNG and SVG formats.
         * For other formats, the background will be white.
         */
        #[Assert\Type(type: 'bool', message: 'Transparent must be a boolean')]
        public bool $transparent = false,

        /**
         * Custom background color for the export.
         * 
         * Hex color code (e.g., "#ffffff") to use as the canvas
         * background. Overrides the design's background color.
         * Ignored when transparent is true.
         */
        #[Assert\Type(type: 'string', message: 'Background color must be a string')]
        public ?string $backgroundColor = null,

        /**
         * Animation-specific settings for video/GIF exports.
         * 
         * Configuration for animated exports including:
         * - duration: Animation length in seconds
         * - fps: Frames per second for video formats
         * - loop: Whether GIFs should loop infinitely
         * - timeline: Specific animation timeline to export
         * 
         * @var array<string, mixed>|null Animation configuration or null
         */
        #[Assert\Type(type: 'array', message: 'Animation settings must be an array')]
        public ?array $animationSettings = null,
    ) {
    }
}
