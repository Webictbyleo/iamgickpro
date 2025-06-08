<?php

declare(strict_types=1);

namespace App\Service\MediaProcessing;

use App\Service\MediaProcessing\Config\ProcessingConfigInterface;

/**
 * Message for processing media files in background
 */
class ProcessMediaMessage
{
    public function __construct(
        public readonly string $jobId,
        public readonly string $inputPath,
        public readonly string $outputPath,
        public readonly ProcessingConfigInterface $config,
    ) {}
}
