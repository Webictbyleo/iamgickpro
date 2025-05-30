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
 * @extends ServiceEntityRepository<ExportJob>
 */
class ExportJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExportJob::class);
    }

    public function save(ExportJob $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ExportJob $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }



    /**
     * Find export jobs by design
     *
     * @return ExportJob[]
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
     * @return ExportJob[]
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
     * @return ExportJob[]
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
     * @return ExportJob[]
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
     * @return ExportJob[]
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
     * Find jobs by format
     *
     * @return ExportJob[]
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
     */
    public function getUserExportStats(User $user): array
    {
        $qb = $this->createQueryBuilder('ej')
            ->select([
                'COUNT(ej.id) as total_exports',
                'COUNT(CASE WHEN ej.status = \'completed\' THEN 1 END) as completed_exports',
                'COUNT(CASE WHEN ej.status = \'failed\' THEN 1 END) as failed_exports',
                'COUNT(CASE WHEN ej.status = \'processing\' THEN 1 END) as processing_exports',
                'COUNT(CASE WHEN ej.status = \'queued\' THEN 1 END) as queued_exports',
                'ej.format',
                'COUNT(ej.format) as format_count'
            ])
            ->andWhere('ej.user = :user')
            ->setParameter('user', $user)
            ->groupBy('ej.format');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get system-wide export statistics
     */
    public function getSystemExportStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT 
                COUNT(*) as total_exports,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_exports,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_exports,
                COUNT(CASE WHEN status = "processing" THEN 1 END) as processing_exports,
                COUNT(CASE WHEN status = "queued" THEN 1 END) as queued_exports,
                AVG(CASE WHEN completed_at IS NOT NULL AND started_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, started_at, completed_at) END) as avg_processing_time,
                format,
                COUNT(format) as format_count
            FROM export_job 
            GROUP BY format
        ';

        return $conn->executeQuery($sql)->fetchAllAssociative();
    }

    /**
     * Get export queue depth by priority
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
     * @return ExportJob[]
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
     * @return ExportJob[]
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
     */
    public function getProcessingTimeStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT 
                format,
                AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_time,
                MIN(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as min_time,
                MAX(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as max_time,
                COUNT(*) as sample_count
            FROM export_job 
            WHERE status = "completed" 
            AND started_at IS NOT NULL 
            AND completed_at IS NOT NULL
            GROUP BY format
        ';

        return $conn->executeQuery($sql)->fetchAllAssociative();
    }

    /**
     * Cleanup old completed exports
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
     * @return ExportJob[]
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
     * Get export volume by time period
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
     * @return ExportJob[]
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
     * Get statistics for a user's export jobs
     */
    public function getUserStats($user): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                JSON_OBJECTAGG(format, format_count) as by_format
            FROM (
                SELECT 
                    status,
                    format,
                    COUNT(*) as format_count
                FROM export_jobs 
                WHERE user_id = :user_id
                GROUP BY format
            ) as format_stats
        ";

        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery($sql, ['user_id' => $user->getId()])->fetchAssociative();

        return [
            'total' => (int) $result['total'],
            'completed' => (int) $result['completed'],
            'failed' => (int) $result['failed'],
            'pending' => (int) $result['pending'],
            'processing' => (int) $result['processing'],
            'by_format' => json_decode($result['by_format'] ?? '{}', true),
        ];
    }

    /**
     * Get queue statistics for administrators
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
}
