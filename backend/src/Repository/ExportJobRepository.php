<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ExportJob;
use App\Entity\User;
use App\Entity\Design;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Types;

/**
 * Export Job Repository
 * 
 * This repository manages the persistence and querying of export jobs within the design platform.
 * Export jobs represent background tasks that convert designs into various output formats (PNG, JPEG, 
 * SVG, PDF, MP4, GIF, etc.). The repository handles job queue management, status tracking, retry logic,
 * performance monitoring, and cleanup operations.
 * 
 * Key functionalities:
 * - Job queue management with priority-based processing
 * - Status tracking (queued, processing, completed, failed)
 * - Retry mechanisms for failed jobs
 * - Performance analytics and processing time statistics
 * - File size monitoring and storage optimization
 * - Cleanup of expired and old export jobs
 * - User quota tracking and export history
 * - System health monitoring and queue depth analysis
 * 
 * @extends ServiceEntityRepository<ExportJob>
 */
class ExportJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExportJob::class);
    }

    /**
     * Persist an export job entity
     * 
     * @param ExportJob $entity The export job to save
     * @param bool $flush Whether to immediately flush changes to database
     */
    public function save(ExportJob $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove an export job entity
     * 
     * @param ExportJob $entity The export job to remove
     * @param bool $flush Whether to immediately flush changes to database
     */
    public function remove(ExportJob $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }



    /**
     * Find export jobs associated with a specific design
     * 
     * Retrieves all export jobs that were created for a particular design,
     * ordered by creation date (most recent first). Useful for showing
     * export history for a design.
     *
     * @param Design $design The design to find export jobs for
     * @param int $limit Maximum number of jobs to return
     * @return ExportJob[] Array of export jobs for the design
     */
    public function findByDesign(Design $design, int $limit = 20): array
    {
        return $this->createQueryBuilder('ej')
            ->andWhere('ej.design = :design')
            ->setParameter('design', $design)
            ->orderBy('ej.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find export jobs by status
     * 
     * Retrieves export jobs filtered by their current status (queued, processing,
     * completed, failed). Results are ordered by creation time for queue processing.
     *
     * @param string $status The status to filter by
     * @param int $limit Maximum number of jobs to return
     * @return ExportJob[] Array of export jobs with the specified status
     */
    public function findByStatus(string $status, int $limit = 100): array
    {
        return $this->createQueryBuilder('ej')
            ->andWhere('ej.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ej.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending export jobs (queued or processing)
     * 
     * Retrieves jobs that are either waiting in queue or currently being processed.
     * Results are ordered by priority (highest first) then by creation time (oldest first)
     * to ensure fair queue processing.
     *
     * @param int $limit Maximum number of pending jobs to return
     * @return ExportJob[] Array of pending export jobs ordered by priority and age
     */
    public function findPendingJobs(int $limit = 100): array
    {
        return $this->createQueryBuilder('ej')
            ->andWhere('ej.status IN (:statuses)')
            ->setParameter('statuses', ['queued', 'processing'])
            ->orderBy('ej.priority', 'DESC')
            ->addOrderBy('ej.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find next job to process based on priority and creation time
     * 
     * Retrieves the next job that should be processed by the export worker.
     * Uses priority-based scheduling with FIFO for same-priority jobs.
     *
     * @return ExportJob|null The next job to process, or null if queue is empty
     */
    public function findNextJobToProcess(): ?ExportJob
    {
        return $this->createQueryBuilder('ej')
            ->andWhere('ej.status = :status')
            ->setParameter('status', 'queued')
            ->orderBy('ej.priority', 'DESC')
            ->addOrderBy('ej.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find stuck jobs (processing for too long)
     * 
     * Identifies export jobs that have been in processing status for longer than
     * the specified threshold, indicating they may have crashed or become stuck.
     * Used by cleanup scripts to reset or retry stuck jobs.
     *
     * @param \DateTimeInterface $threshold Jobs started before this time are considered stuck
     * @return ExportJob[] Array of jobs that appear to be stuck in processing
     */
    public function findStuckJobs(\DateTimeInterface $threshold): array
    {
        return $this->createQueryBuilder('ej')
            ->andWhere('ej.status = :status')
            ->andWhere('ej.startedAt < :threshold')
            ->setParameter('status', 'processing')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired export jobs
     * 
     * Retrieves export jobs that have passed their expiration date and should be
     * cleaned up to free storage space. Only includes completed or failed jobs
     * as active jobs should not be expired.
     *
     * @return ExportJob[] Array of expired export jobs ready for cleanup
     */
    public function findExpiredJobs(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('ej')
            ->andWhere('ej.expiresAt IS NOT NULL')
            ->andWhere('ej.expiresAt < :now')
            ->andWhere('ej.status IN (:statuses)')
            ->setParameter('now', $now)
            ->setParameter('statuses', ['completed', 'failed'])
            ->getQuery()
            ->getResult();
    }

    /**
     * Find jobs by export format
     * 
     * Retrieves export jobs filtered by their output format (PNG, JPEG, SVG, etc.).
     * Useful for analyzing format-specific usage patterns and performance metrics.
     *
     * @param string $format The export format to filter by (e.g., 'png', 'jpeg', 'svg')
     * @param int $limit Maximum number of jobs to return
     * @return ExportJob[] Array of export jobs for the specified format
     */
    public function findByFormat(string $format, int $limit = 50): array
    {
        return $this->createQueryBuilder('ej')
            ->andWhere('ej.format = :format')
            ->setParameter('format', $format)
            ->orderBy('ej.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get export statistics for a user
     * 
     * Generates comprehensive statistics about a user's export activity including
     * total counts by status and format breakdown. Used for user dashboards and
     * quota management.
     *
     * @param User $user The user to generate statistics for
     * @return array Statistics including total, completed, failed counts and format breakdown
     */
    public function getUserExportStats(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $userId = $user->getId();

        // Get overall statistics
        $overallStats = $conn->executeQuery(
            "SELECT 
                COUNT(*) as total_exports,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_exports,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_exports,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_exports,
                COUNT(CASE WHEN status = 'queued' THEN 1 END) as queued_exports
            FROM export_jobs 
            WHERE user_id = ?",
            [$userId]
        )->fetchAssociative();

        // Get format breakdown
        $formatStats = $conn->executeQuery(
            "SELECT 
                format,
                COUNT(*) as format_count
            FROM export_jobs 
            WHERE user_id = ?
            GROUP BY format
            ORDER BY format_count DESC",
            [$userId]
        )->fetchAllAssociative();

        return [
            'total_exports' => (int) $overallStats['total_exports'],
            'completed_exports' => (int) $overallStats['completed_exports'],
            'failed_exports' => (int) $overallStats['failed_exports'],
            'processing_exports' => (int) $overallStats['processing_exports'],
            'queued_exports' => (int) $overallStats['queued_exports'],
            'format_breakdown' => $formatStats
        ];
    }

    /**
     * Get system-wide export statistics
     * 
     * Provides comprehensive system-level metrics including export counts by status,
     * average processing times, and format distribution. Used for system monitoring
     * and performance analysis.
     *
     * @return array System statistics including totals, processing times, and format breakdown
     */
    public function getSystemExportStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Get overall system statistics
        $overallStats = $conn->executeQuery(
            "SELECT 
                COUNT(*) as total_exports,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_exports,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_exports,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_exports,
                COUNT(CASE WHEN status = 'queued' THEN 1 END) as queued_exports,
                AVG(CASE WHEN completed_at IS NOT NULL AND started_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, started_at, completed_at) END) as avg_processing_time
            FROM export_jobs"
        )->fetchAssociative();

        // Get format breakdown separately
        $formatStats = $conn->executeQuery(
            "SELECT 
                format,
                COUNT(*) as format_count
            FROM export_jobs 
            GROUP BY format
            ORDER BY format_count DESC"
        )->fetchAllAssociative();

        return [
            'overall' => [
                'total_exports' => (int) $overallStats['total_exports'],
                'completed_exports' => (int) $overallStats['completed_exports'],
                'failed_exports' => (int) $overallStats['failed_exports'],
                'processing_exports' => (int) $overallStats['processing_exports'],
                'queued_exports' => (int) $overallStats['queued_exports'],
                'avg_processing_time' => $overallStats['avg_processing_time'] 
                    ? round((float) $overallStats['avg_processing_time'], 2) 
                    : null
            ],
            'format_breakdown' => $formatStats
        ];
    }

    /**
     * Get export queue depth by priority
     * 
     * Analyzes the current queue depth grouped by priority level to help
     * with capacity planning and queue management. Shows how many jobs
     * are waiting at each priority level.
     *
     * @return array Queue depth statistics grouped by priority level
     */
    public function getQueueDepth(): array
    {
        return $this->createQueryBuilder('ej')
            ->select([
                'ej.priority',
                'COUNT(ej.id) as job_count'
            ])
            ->andWhere('ej.status = :status')
            ->setParameter('status', 'queued')
            ->groupBy('ej.priority')
            ->orderBy('ej.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent exports for a user
     * 
     * Retrieves a user's export jobs from the specified time period,
     * ordered by creation date. Used for user export history and
     * recent activity displays.
     *
     * @param User $user The user to find exports for
     * @param int $days Number of days to look back (default: 7)
     * @param int $limit Maximum number of exports to return
     * @return ExportJob[] Array of recent export jobs for the user
     */
    public function findRecentExports(User $user, int $days = 7, int $limit = 20): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('ej')
            ->andWhere('ej.user = :user')
            ->andWhere('ej.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('ej.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find exports by file size range
     * 
     * Retrieves completed export jobs whose output files fall within the specified
     * size range. Useful for storage analysis and identifying large files that
     * may need optimization.
     *
     * @param int $minSize Minimum file size in bytes
     * @param int $maxSize Maximum file size in bytes
     * @param int $limit Maximum number of exports to return
     * @return ExportJob[] Array of exports within the specified file size range
     */
    public function findByFileSizeRange(int $minSize, int $maxSize, int $limit = 50): array
    {
        return $this->createQueryBuilder('ej')
            ->andWhere('ej.fileSize >= :minSize')
            ->andWhere('ej.fileSize <= :maxSize')
            ->andWhere('ej.status = :status')
            ->setParameter('minSize', $minSize)
            ->setParameter('maxSize', $maxSize)
            ->setParameter('status', 'completed')
            ->orderBy('ej.fileSize', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count exports by user in time period
     * 
     * Counts the number of export jobs created by a user since the specified date.
     * Used for quota enforcement and usage tracking.
     *
     * @param User $user The user to count exports for
     * @param \DateTimeInterface $since Count exports created after this date
     * @return int Number of exports created by the user in the time period
     */
    public function countUserExportsInPeriod(User $user, \DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('ej')
            ->select('COUNT(ej.id)')
            ->andWhere('ej.user = :user')
            ->andWhere('ej.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get processing time statistics
     * 
     * Analyzes processing times for completed export jobs. Can be filtered by user
     * or return global statistics. Provides performance metrics for analytics.
     *
     * @param User|null $user Optional user to filter by
     * @return array Processing time statistics
     */
    public function getProcessingTimeStats(?User $user = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        if ($user) {
            // User-specific statistics
            $stats = $conn->executeQuery(
                "SELECT 
                    AVG(CASE WHEN processing_time_ms IS NOT NULL THEN processing_time_ms ELSE NULL END) as avg_processing_time,
                    MIN(CASE WHEN processing_time_ms IS NOT NULL THEN processing_time_ms ELSE NULL END) as fastest,
                    MAX(CASE WHEN processing_time_ms IS NOT NULL THEN processing_time_ms ELSE NULL END) as slowest,
                    COUNT(CASE WHEN processing_time_ms IS NOT NULL THEN 1 ELSE NULL END) as sample_count
                FROM export_jobs 
                WHERE user_id = ? AND status = 'completed'",
                [$user->getId()]
            )->fetchAssociative();

            return [
                'avg_processing_time' => $stats['avg_processing_time'] ? round((float) $stats['avg_processing_time']) : 0,
                'fastest' => (int) ($stats['fastest'] ?? 0),
                'slowest' => (int) ($stats['slowest'] ?? 0),
                'sample_count' => (int) ($stats['sample_count'] ?? 0)
            ];
        } else {
            // Global statistics by format
            $sql = '
                SELECT 
                    format,
                    AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_time,
                    MIN(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as min_time,
                    MAX(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as max_time,
                    COUNT(*) as sample_count
                FROM export_jobs 
                WHERE status = "completed" 
                AND started_at IS NOT NULL 
                AND completed_at IS NOT NULL
                GROUP BY format
            ';

            return $conn->executeQuery($sql)->fetchAllAssociative();
        }
    }

    /**
     * Cleanup old completed exports
     * 
     * Removes completed and failed export jobs that are older than the specified
     * date to free up database space. Part of regular maintenance operations.
     *
     * @param \DateTimeInterface $before Remove jobs created before this date
     * @return int Number of export jobs deleted
     */
    public function cleanupOldExports(\DateTimeInterface $before): int
    {
        $qb = $this->createQueryBuilder('ej')
            ->delete()
            ->andWhere('ej.createdAt < :before')
            ->andWhere('ej.status IN (:statuses)')
            ->setParameter('before', $before)
            ->setParameter('statuses', ['completed', 'failed']);

        return $qb->getQuery()->execute();
    }

    /**
     * Reset stuck jobs to queued status
     * 
     * Resets jobs that have been processing for too long back to queued status
     * so they can be retried. Clears processing metadata to allow fresh attempts.
     *
     * @param \DateTimeInterface $threshold Jobs started before this time will be reset
     * @return int Number of jobs reset to queued status
     */
    public function resetStuckJobs(\DateTimeInterface $threshold): int
    {
        $qb = $this->createQueryBuilder('ej')
            ->update()
            ->set('ej.status', ':newStatus')
            ->set('ej.startedAt', 'NULL')
            ->set('ej.progress', '0')
            ->set('ej.errorMessage', 'NULL')
            ->andWhere('ej.status = :oldStatus')
            ->andWhere('ej.startedAt < :threshold')
            ->setParameter('newStatus', 'queued')
            ->setParameter('oldStatus', 'processing')
            ->setParameter('threshold', $threshold);

        return $qb->getQuery()->execute();
    }

    /**
     * Get failed exports with error analysis
     * 
     * Retrieves detailed information about failed export jobs for error analysis
     * and debugging. Includes error messages, user context, and design information
     * to help identify patterns in failures.
     *
     * @param \DateTimeInterface $since Only include failures after this date
     * @return ExportJob[] Array of failed exports with analysis data
     */
    public function getFailedExportsAnalysis(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('ej')
            ->select([
                'ej.id',
                'ej.format',
                'ej.errorMessage',
                'ej.failedAt',
                'ej.fileSize',
                'u.id as user_id',
                'd.id as design_id'
            ])
            ->leftJoin('ej.user', 'u')
            ->leftJoin('ej.design', 'd')
            ->andWhere('ej.status = :status')
            ->andWhere('ej.failedAt >= :since')
            ->setParameter('status', 'failed')
            ->setParameter('since', $since)
            ->orderBy('ej.failedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get export volume statistics by time period
     * 
     * Provides daily export volume statistics over the specified time period.
     * Includes total exports, success/failure rates, and total file sizes.
     * Used for trend analysis and capacity planning.
     *
     * @param int $days Number of days to analyze (default: 30)
     * @return array Daily export statistics including counts and file sizes
     */
    public function getExportVolumeStats(int $days = 30): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT 
                DATE(created_at) as export_date,
                COUNT(*) as total_exports,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_exports,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_exports,
                SUM(CASE WHEN status = "completed" THEN file_size ELSE 0 END) as total_file_size
            FROM export_job 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY export_date DESC
        ';

        return $conn->executeQuery($sql, ['days' => $days])->fetchAllAssociative();
    }

    /**
     * Find exports that can be retried
     * 
     * Identifies failed export jobs that are eligible for retry based on
     * retry count limits and recent failure time. Used by automated retry
     * systems to recover from transient failures.
     *
     * @param int $maxRetries Maximum number of retries allowed per job
     * @return ExportJob[] Array of failed exports eligible for retry
     */
    public function findRetryableExports(int $maxRetries = 3): array
    {
        return $this->createQueryBuilder('ej')
            ->andWhere('ej.status = :status')
            ->andWhere('ej.retryCount < :maxRetries')
            ->andWhere('ej.failedAt > :recentThreshold')
            ->setParameter('status', 'failed')
            ->setParameter('maxRetries', $maxRetries)
            ->setParameter('recentThreshold', new \DateTimeImmutable('-24 hours'))
            ->orderBy('ej.failedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find export jobs by user with optional filtering
     * 
     * Retrieves export jobs for a specific user with optional filtering by status
     * and format. Supports pagination for large result sets. Used for user
     * export history and management interfaces.
     *
     * @param User $user The user to find export jobs for
     * @param string|null $status Optional status filter
     * @param string|null $format Optional format filter
     * @param int $page Page number for pagination (1-based)
     * @param int $limit Number of results per page
     * @return ExportJob[] Array of export jobs matching the criteria
     */
    public function findByUser($user, ?string $status = null, ?string $format = null, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('ej')
            ->andWhere('ej.user = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('ej.status = :status')
               ->setParameter('status', $status);
        }

        if ($format) {
            $qb->andWhere('ej.format = :format')
               ->setParameter('format', $format);
        }

        $offset = ($page - 1) * $limit;

        return $qb->orderBy('ej.createdAt', 'DESC')
                  ->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Count export jobs by user with optional filtering
     * 
     * Counts the total number of export jobs for a user matching the specified
     * filters. Used in conjunction with findByUser() to support pagination.
     *
     * @param User $user The user to count export jobs for
     * @param string|null $status Optional status filter
     * @param string|null $format Optional format filter
     * @return int Total count of matching export jobs
     */
    public function countByUser($user, ?string $status = null, ?string $format = null): int
    {
        $qb = $this->createQueryBuilder('ej')
            ->select('COUNT(ej.id)')
            ->andWhere('ej.user = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('ej.status = :status')
               ->setParameter('status', $status);
        }

        if ($format) {
            $qb->andWhere('ej.format = :format')
               ->setParameter('format', $format);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get comprehensive statistics for a user's export jobs
     * 
     * Provides detailed statistics about a user's export activity including
     * totals by status, format breakdown, and success rates. Used for user
     * dashboards and account management.
     *
     * @param User $user The user to generate statistics for
     * @return array Comprehensive export statistics including totals and format breakdown
     */
    public function getUserStats($user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $userId = $user->getId();

        // Get overall statistics
        $overallStats = $conn->executeQuery(
            "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing
            FROM export_jobs 
            WHERE user_id = ?",
            [$userId]
        )->fetchAssociative();

        // Get format breakdown
        $formatStats = $conn->executeQuery(
            "SELECT 
                format,
                COUNT(*) as count
            FROM export_jobs 
            WHERE user_id = ?
            GROUP BY format",
            [$userId]
        )->fetchAllAssociative();

        // Convert format stats to associative array
        $byFormat = [];
        foreach ($formatStats as $stat) {
            $byFormat[$stat['format']] = (int) $stat['count'];
        }

        return [
            'total' => (int) $overallStats['total'],
            'completed' => (int) $overallStats['completed'],
            'failed' => (int) $overallStats['failed'],
            'pending' => (int) $overallStats['pending'],
            'processing' => (int) $overallStats['processing'],
            'by_format' => $byFormat,
        ];
    }

    /**
     * Get queue statistics for system administrators
     * 
     * Provides real-time queue health metrics for system monitoring including
     * current queue depths, processing times, and overall system health status.
     * Used by admin dashboards and monitoring systems.
     *
     * @return array Queue health statistics including depths, times, and health status
     */
    public function getQueueStats(): array
    {
        $sql = "
            SELECT 
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                AVG(CASE WHEN processing_time_ms IS NOT NULL THEN processing_time_ms ELSE NULL END) as avg_processing_time,
                CASE 
                    WHEN SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) > 100 THEN 'poor'
                    WHEN SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) > 50 THEN 'fair'
                    ELSE 'good'
                END as queue_health
            FROM export_jobs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";

        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery($sql)->fetchAssociative();

        return [
            'pending' => (int) $result['pending'],
            'processing' => (int) $result['processing'],
            'failed' => (int) $result['failed'],
            'avg_processing_time' => $result['avg_processing_time'] ? round((float) $result['avg_processing_time']) : null,
            'queue_health' => $result['queue_health'],
        ];
    }

    /**
     * Get export statistics for a user with date range
     * 
     * Provides export statistics for analytics including timeline data,
     * success rates, and format breakdown within a specific date range.
     *
     * @param User $user The user to generate statistics for
     * @param \DateTimeInterface|null $startDate Start date for filtering (optional)
     * @param \DateTimeInterface|null $endDate End date for filtering (optional)
     * @return array Export statistics with timeline and breakdown data
     */
    public function getExportStatsForUser(User $user, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $userId = $user->getId();
        
        $params = [$userId];
        $whereClause = "WHERE user_id = ?";
        
        if ($startDate) {
            $whereClause .= " AND created_at >= ?";
            $params[] = $startDate->format('Y-m-d H:i:s');
        }
        
        if ($endDate) {
            $whereClause .= " AND created_at <= ?";
            $params[] = $endDate->format('Y-m-d H:i:s');
        }

        // Get overall statistics
        $overallStats = $conn->executeQuery(
            "SELECT 
                COUNT(*) as total_exports,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_exports,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_exports
            FROM export_jobs 
            $whereClause",
            $params
        )->fetchAssociative();

        // Get timeline data (last 30 days if no date range specified)
        $timelineParams = [$userId];
        $timelineWhere = "WHERE user_id = ?";
        
        if (!$startDate && !$endDate) {
            $timelineWhere .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        } elseif ($startDate) {
            $timelineWhere .= " AND created_at >= ?";
            $timelineParams[] = $startDate->format('Y-m-d H:i:s');
            if ($endDate) {
                $timelineWhere .= " AND created_at <= ?";
                $timelineParams[] = $endDate->format('Y-m-d H:i:s');
            }
        }

        $timeline = $conn->executeQuery(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed
            FROM export_jobs 
            $timelineWhere
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $timelineParams
        )->fetchAllAssociative();

        return [
            'total_exports' => (int) $overallStats['total_exports'],
            'successful_exports' => (int) $overallStats['successful_exports'],
            'failed_exports' => (int) $overallStats['failed_exports'],
            'timeline' => array_map(function($row) {
                return [
                    'date' => $row['date'],
                    'count' => (int) $row['count'],
                    'completed' => (int) $row['completed'],
                    'failed' => (int) $row['failed']
                ];
            }, $timeline)
        ];
    }

    /**
     * Get format breakdown statistics for a user
     * 
     * Returns the distribution of export formats used by a specific user.
     * Used for analytics and usage pattern analysis.
     *
     * @param User $user The user to analyze
     * @return array Format breakdown with counts for each format
     */
    public function getFormatBreakdownForUser(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $formatStats = $conn->executeQuery(
            "SELECT 
                format,
                COUNT(*) as count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
            FROM export_jobs 
            WHERE user_id = ?
            GROUP BY format
            ORDER BY count DESC",
            [$user->getId()]
        )->fetchAllAssociative();

        $breakdown = [];
        foreach ($formatStats as $stat) {
            $breakdown[$stat['format']] = [
                'total' => (int) $stat['count'],
                'completed' => (int) $stat['completed']
            ];
        }

        return $breakdown;
    }
}
