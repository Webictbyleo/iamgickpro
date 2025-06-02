<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Analytics Request DTO
 * 
 * Data Transfer Object for analytics filtering and date range requests.
 * Provides validation and transformation for analytics API endpoints.
 */
class AnalyticsRequestDTO
{
    public function __construct(
        #[Assert\DateTime]
        public readonly ?\DateTimeInterface $startDate = null,

        #[Assert\DateTime]
        public readonly ?\DateTimeInterface $endDate = null,

        #[Assert\Choice(['day', 'week', 'month', 'quarter', 'year'])]
        public readonly ?string $period = null,

        #[Assert\Choice(['designs', 'exports', 'templates', 'projects', 'users', 'storage'])]
        public readonly ?string $type = null,

        #[Assert\Choice(['png', 'jpeg', 'svg', 'pdf', 'mp4', 'gif'])]
        public readonly ?string $format = null,

        #[Assert\Range(min: 1, max: 1000)]
        public readonly int $limit = 100
    ) {}

    public static function fromRequest(Request $request): self
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $period = $request->query->get('period');
        $type = $request->query->get('type');
        $format = $request->query->get('format');
        $limit = max(1, min(1000, (int) $request->query->get('limit', 100)));

        return new self(
            startDate: $startDate ? new \DateTimeImmutable($startDate) : null,
            endDate: $endDate ? new \DateTimeImmutable($endDate) : null,
            period: $period,
            type: $type,
            format: $format,
            limit: $limit
        );
    }

    public function getStartDate(): \DateTimeInterface
    {
        if ($this->startDate) {
            return $this->startDate;
        }

        // Default to 30 days ago
        return new \DateTimeImmutable('-30 days');
    }

    public function getEndDate(): \DateTimeInterface
    {
        if ($this->endDate) {
            return $this->endDate;
        }

        // Default to now
        return new \DateTimeImmutable();
    }
}
