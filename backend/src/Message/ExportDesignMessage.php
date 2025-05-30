<?php

declare(strict_types=1);

namespace App\Message;

final readonly class ExportDesignMessage
{
    public function __construct(
        public int $exportJobId,
        public int $designId,
        public string $format,
        public array $options = []
    ) {}
}
