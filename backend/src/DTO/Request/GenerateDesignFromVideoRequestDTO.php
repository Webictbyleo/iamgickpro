<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for video analysis generation requests.
 * 
 * Handles the submission of YouTube video analysis jobs for
 * generating AI-powered thumbnail designs. Supports various
 * thumbnail styles, sizes, and customization options.
 */
final readonly class GenerateDesignFromVideoRequestDTO
{
    public function __construct(
        /**
         * YouTube video URL to analyze.
         * 
         * Must be a valid YouTube URL (youtube.com or youtu.be).
         * The system will extract video metadata, key frames,
         * and generate thumbnail designs based on the content.
         */
        #[Assert\NotBlank(message: 'Video URL is required')]
        #[Assert\Url(message: 'Must be a valid URL')]
        #[Assert\Regex(
            pattern: '/^https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)[\w-]+/',
            message: 'Must be a valid YouTube URL'
        )]
        public string $videoUrl,

        /**
         * Design types to generate from the video.
         * 
         * Array of design types that should be created:
         * - 'thumbnail': YouTube thumbnail designs (default)
         * - 'social_media': Social media post designs
         * - 'banner': Channel banner designs
         * - 'poster': Promotional poster designs
         * 
         * @var string[]|null Array of design types or null for defaults
         */
        #[Assert\Type(type: 'array', message: 'Design types must be an array')]
        #[Assert\All([
            new Assert\Choice(choices: [
                'thumbnail', 'social_media', 'banner', 'poster', 'infographic'
            ], message: 'Invalid design type')
        ])]
        public ?array $designTypes = null,

        /**
         * Custom prompt for AI design generation.
         * 
         * Additional instructions to guide the AI in creating
         * thumbnails. For example: "Make it colorful and gaming-focused"
         * or "Professional tech tutorial style".
         */
        #[Assert\Type(type: 'string', message: 'Custom prompt must be a string')]
        #[Assert\Length(max: 500, maxMessage: 'Custom prompt cannot exceed 500 characters')]
        public ?string $customPrompt = null,

        /**
         * Thumbnail generation options.
         * 
         * Configuration for the thumbnail generation process:
         * - style: Visual style preference
         * - maxThumbnails: Number of thumbnails to generate
         * - size: Output dimensions
         * - customPrompt: Additional style instructions
         * 
         * @var array<string, mixed>|null Generation options or null for defaults
         */
        #[Assert\Type(type: 'array', message: 'Options must be an array')]
        public ?array $options = null,
    ) {
    }

    /**
     * Get the thumbnail style from options.
     */
    public function getStyle(): string
    {
        return $this->options['style'] ?? 'professional';
    }

    /**
     * Get the maximum number of thumbnails to generate.
     */
    public function getMaxThumbnails(): int
    {
        return (int) ($this->options['maxThumbnails'] ?? 5);
    }

    /**
     * Get the thumbnail size from options.
     */
    public function getSize(): string
    {
        return $this->options['size'] ?? '1280x720';
    }

    /**
     * Get the design types, defaulting to thumbnail.
     */
    public function getDesignTypes(): array
    {
        return $this->designTypes ?? ['thumbnail'];
    }

    /**
     * Get the effective custom prompt (from options or main property).
     */
    public function getEffectiveCustomPrompt(): ?string
    {
        return $this->customPrompt ?? $this->options['customPrompt'] ?? null;
    }
}
