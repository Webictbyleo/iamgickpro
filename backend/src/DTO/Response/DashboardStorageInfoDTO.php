<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Dashboard Storage Info DTO
 * 
 * Contains storage usage metrics and limits.
 */
class DashboardStorageInfoDTO
{
    public function __construct(
        public readonly int $usedBytes,
        public readonly int $limitBytes,
        public readonly float $percentageUsed,
        public readonly int $filesCount
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            usedBytes: $data['used_bytes'] ?? 0,
            limitBytes: $data['limit_bytes'] ?? 0,
            percentageUsed: $data['percentage_used'] ?? 0.0,
            filesCount: $data['files_count'] ?? 0
        );
    }
}
