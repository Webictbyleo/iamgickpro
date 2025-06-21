<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message for processing YouTube thumbnail generation in the background
 */
class ProcessYoutubeThumbnailMessage
{
    public function __construct(
        public readonly string $jobId,
        public readonly int $userId,
        public readonly string $videoUrl,
        public readonly int $thumbnailCount,
        public readonly string $style,
        public readonly ?string $customPrompt = null
    ) {}
}
