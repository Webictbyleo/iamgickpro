<?php

declare(strict_types=1);

namespace App\Message;

final readonly class ProcessMediaUploadMessage
{
    public function __construct(
        public int $mediaId,
        public string $filePath,
        public array $metadata = []
    ) {}
}
