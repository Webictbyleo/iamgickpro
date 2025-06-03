<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Design Analytics Response DTO
 * 
 * Data Transfer Object for individual design analytics.
 * Contains performance metrics for a specific design.
 */
class DesignAnalyticsResponseDTO
{
    public function __construct(
        public readonly string $designId,
        public readonly array $metrics,
        public readonly array $timeline,
        public readonly array $exports,
        public readonly array $engagement
    ) {}

    public static function fromData(string $designId, array $data): self
    {
        return new self(
            designId: $designId,
            metrics: $data['metrics'] ?? [],
            timeline: $data['timeline'] ?? [],
            exports: $data['exports'] ?? [],
            engagement: $data['engagement'] ?? []
        );
    }
}
