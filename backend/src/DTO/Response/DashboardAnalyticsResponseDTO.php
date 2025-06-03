<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Dashboard Analytics Response DTO
 * 
 * Data Transfer Object for dashboard analytics data.
 * Contains comprehensive statistics for the user dashboard including
 * overview metrics and recent activity.
 */
class DashboardAnalyticsResponseDTO
{
    public function __construct(
        public readonly DashboardOverviewDTO $overview,
        public readonly array $recentActivity
    ) {}

    public static function fromData(array $data): self
    {
        return new self(
            overview: DashboardOverviewDTO::fromArray($data['overview'] ?? []),
            recentActivity: $data['recent_activity'] ?? []
        );
    }
}
