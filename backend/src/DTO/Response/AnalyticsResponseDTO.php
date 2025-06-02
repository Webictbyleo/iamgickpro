<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Dashboard Analytics Response DTO
 * 
 * Data Transfer Object for dashboard analytics data.
 * Contains comprehensive statistics for the user dashboard.
 */
class DashboardAnalyticsResponseDTO
{
    public function __construct(
        public readonly array $overview,
        public readonly array $charts,
        public readonly array $trends,
        public readonly array $topPerformers
    ) {}

    public static function fromData(array $data): self
    {
        return new self(
            overview: $data['overview'] ?? [],
            charts: $data['charts'] ?? [],
            trends: $data['trends'] ?? [],
            topPerformers: $data['top_performers'] ?? []
        );
    }
}

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
