<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Dashboard Growth DTO
 * 
 * Contains monthly growth metrics for trending analysis.
 */
class DashboardGrowthDTO
{
    public function __construct(
        public readonly int $designs,
        public readonly int $exports
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            designs: $data['designs'] ?? 0,
            exports: $data['exports'] ?? 0
        );
    }
}
