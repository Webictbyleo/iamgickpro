<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for video information extraction requests.
 * 
 * Used to extract basic metadata and information from YouTube
 * videos without performing full analysis or design generation.
 */
final readonly class ExtractVideoInfoRequestDTO
{
    public function __construct(
        /**
         * YouTube video URL to extract information from.
         * 
         * Must be a valid YouTube URL. The system will extract
         * basic metadata including title, description, duration,
         * thumbnail, channel info, and tags.
         */
        #[Assert\NotBlank(message: 'Video URL is required')]
        #[Assert\Url(message: 'Must be a valid URL')]
        #[Assert\Regex(
            pattern: '/^https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)[\w-]+/',
            message: 'Must be a valid YouTube URL'
        )]
        public string $videoUrl,
    ) {
    }
}
