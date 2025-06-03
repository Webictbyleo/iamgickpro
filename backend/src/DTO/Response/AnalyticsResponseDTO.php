<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Analytics Response DTO
 * 
 * Base Data Transfer Object for analytics responses.
 * Contains standardized analytics data structure.
 */
class AnalyticsResponseDTO
{
    public function __construct(
        public readonly array $data,
        public readonly string $type,
        public readonly string $period,
        public readonly ?\DateTimeImmutable $generatedAt = null
    ) {}

    public static function fromData(array $data, string $type = 'general', string $period = 'all'): self
    {
        return new self(
            data: $data,
            type: $type,
            period: $period,
            generatedAt: new \DateTimeImmutable()
        );
    }
}
