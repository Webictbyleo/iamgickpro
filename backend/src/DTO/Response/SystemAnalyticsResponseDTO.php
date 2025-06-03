<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * System Analytics Response DTO
 * 
 * Data Transfer Object for system-wide analytics (Admin only).
 * Contains platform-wide statistics and performance metrics.
 */
class SystemAnalyticsResponseDTO
{
    public function __construct(
        public readonly array $platformStats,
        public readonly array $userMetrics,
        public readonly array $performanceData,
        public readonly array $systemHealth
    ) {}

    public static function fromData(array $data): self
    {
        return new self(
            platformStats: $data['platform_stats'] ?? [],
            userMetrics: $data['user_metrics'] ?? [],
            performanceData: $data['performance_data'] ?? [],
            systemHealth: $data['system_health'] ?? []
        );
    }
}
