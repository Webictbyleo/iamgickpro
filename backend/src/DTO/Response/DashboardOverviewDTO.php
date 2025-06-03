<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * Dashboard Overview DTO
 * 
 * Contains key performance metrics for the dashboard overview.
 */
class DashboardOverviewDTO
{
    public function __construct(
        public readonly int $totalDesigns,
        public readonly int $totalProjects,
        public readonly int $totalExports,
        public readonly int $completedExports,
        public readonly int $storageUsed,
        public readonly float $successRate
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            totalDesigns: $data['total_designs'] ?? 0,
            totalProjects: $data['total_projects'] ?? 0,
            totalExports: $data['total_exports'] ?? 0,
            completedExports: $data['completed_exports'] ?? 0,
            storageUsed: $data['storage_used'] ?? 0,
            successRate: $data['success_rate'] ?? 0.0
        );
    }
}
