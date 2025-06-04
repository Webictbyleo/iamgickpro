<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message for background video analysis processing
 */
final readonly class AnalyzeVideoMessage
{
    public function __construct(
        public int $analysisId,
        public string $youtubeUrl,
        public array $options = []
    ) {}
}
