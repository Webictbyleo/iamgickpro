<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\VideoAnalysis;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Types;

/**
 * Video Analysis Repository
 * 
 * This repository manages the persistence and querying of video analysis jobs within the design platform.
 * Video analysis jobs represent background tasks that process YouTube videos to generate AI-powered
 * thumbnail designs. The repository handles job queue management, status tracking, result storage,
 * performance monitoring, and cleanup operations.
 * 
 * Key functionalities:
 * - Video analysis job queue management
 * - Status tracking (pending, processing, completed, failed)
 * - Video metadata and analysis result storage
 * - Performance analytics and processing time statistics
 * - User job history and quota tracking
 * - Cleanup of expired and old analysis jobs
 * - Design suggestion and thumbnail data management
 * 
 * @extends ServiceEntityRepository<VideoAnalysis>
 */
class VideoAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VideoAnalysis::class);
    }

    /**
     * Persist a video analysis entity
     * 
     * @param VideoAnalysis $entity The video analysis to save
     * @param bool $flush Whether to immediately flush changes to database
     */
    public function save(VideoAnalysis $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a video analysis entity
     * 
     * @param VideoAnalysis $entity The video analysis to remove
     * @param bool $flush Whether to immediately flush changes to database
     */
    public function remove(VideoAnalysis $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find a video analysis job by UUID
     * 
     * @param string $uuid The UUID to search for
     * @return VideoAnalysis|null The video analysis or null if not found
     */
    public function findByUuid(string $uuid): ?VideoAnalysis
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Find video analysis jobs for a specific user
     * 
     * @param User $user The user to search for
     * @param array $criteria Additional search criteria
     * @param array $orderBy Order by fields
     * @param int|null $limit Maximum results to return
     * @param int|null $offset Results offset for pagination
     * @return VideoAnalysis[] Array of video analysis jobs
     */
    public function findByUser(
        User $user,
        array $criteria = [],
        array $orderBy = ['createdAt' => 'DESC'],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $criteria['user'] = $user;
        
        return $this->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset
        );
    }

    /**
     * Count video analysis jobs for a specific user
     * 
     * @param User $user The user to count jobs for
     * @param array $criteria Additional search criteria
     * @return int Total count of matching jobs
     */
    public function countByUser(User $user, array $criteria = []): int
    {
        $criteria['user'] = $user;
        
        return $this->count($criteria);
    }

    /**
     * Find video analysis jobs by status
     * 
     * @param string $status The status to filter by
     * @param int|null $limit Maximum results to return
     * @return VideoAnalysis[] Array of video analysis jobs
     */
    public function findByStatus(string $status, ?int $limit = null): array
    {
        return $this->findBy(
            ['status' => $status],
            ['createdAt' => 'ASC'],
            $limit
        );
    }

    /**
     * Find pending video analysis jobs for processing
     * 
     * @param int $limit Maximum number of jobs to return
     * @return VideoAnalysis[] Array of pending jobs ordered by creation date
     */
    public function findPendingJobs(int $limit = 10): array
    {
        return $this->findBy(
            ['status' => VideoAnalysis::STATUS_PENDING],
            ['createdAt' => 'ASC'],
            $limit
        );
    }

    /**
     * Find processing jobs that may have stalled
     * 
     * @param \DateTimeImmutable $stalledBefore Jobs processing before this time
     * @return VideoAnalysis[] Array of potentially stalled jobs
     */
    public function findStalledJobs(\DateTimeImmutable $stalledBefore): array
    {
        return $this->createQueryBuilder('va')
            ->where('va.status = :status')
            ->andWhere('va.startedAt < :stalledBefore')
            ->setParameter('status', VideoAnalysis::STATUS_PROCESSING)
            ->setParameter('stalledBefore', $stalledBefore, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired video analysis jobs for cleanup
     * 
     * @return VideoAnalysis[] Array of expired jobs
     */
    public function findExpiredJobs(): array
    {
        return $this->createQueryBuilder('va')
            ->where('va.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get user's recent video analysis jobs
     * 
     * @param User $user The user to get jobs for
     * @param int $limit Maximum number of recent jobs
     * @return VideoAnalysis[] Array of recent jobs
     */
    public function getUserRecentJobs(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('va')
            ->where('va.user = :user')
            ->orderBy('va.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get video analysis statistics for a user
     * 
     * @param User $user The user to get statistics for
     * @return array Statistics including job counts and processing times
     */
    public function getUserStats(User $user): array
    {
        $qb = $this->createQueryBuilder('va')
            ->select([
                'COUNT(va.id) as totalJobs',
                'SUM(CASE WHEN va.status = :completed THEN 1 ELSE 0 END) as completedJobs',
                'SUM(CASE WHEN va.status = :failed THEN 1 ELSE 0 END) as failedJobs',
                'SUM(CASE WHEN va.status = :processing THEN 1 ELSE 0 END) as processingJobs',
                'AVG(va.processingTimeMs) as avgProcessingTime'
            ])
            ->where('va.user = :user')
            ->setParameter('user', $user)
            ->setParameter('completed', VideoAnalysis::STATUS_COMPLETED)
            ->setParameter('failed', VideoAnalysis::STATUS_FAILED)
            ->setParameter('processing', VideoAnalysis::STATUS_PROCESSING);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Get system-wide video analysis statistics
     * 
     * @return array System statistics including queue depth and performance metrics
     */
    public function getSystemStats(): array
    {
        $qb = $this->createQueryBuilder('va')
            ->select([
                'COUNT(va.id) as totalJobs',
                'SUM(CASE WHEN va.status = :pending THEN 1 ELSE 0 END) as pendingJobs',
                'SUM(CASE WHEN va.status = :processing THEN 1 ELSE 0 END) as processingJobs',
                'SUM(CASE WHEN va.status = :completed THEN 1 ELSE 0 END) as completedJobs',
                'SUM(CASE WHEN va.status = :failed THEN 1 ELSE 0 END) as failedJobs',
                'AVG(va.processingTimeMs) as avgProcessingTime'
            ])
            ->setParameter('pending', VideoAnalysis::STATUS_PENDING)
            ->setParameter('processing', VideoAnalysis::STATUS_PROCESSING)
            ->setParameter('completed', VideoAnalysis::STATUS_COMPLETED)
            ->setParameter('failed', VideoAnalysis::STATUS_FAILED);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Find duplicate video analysis requests
     * 
     * @param User $user The user to check for duplicates
     * @param string $videoUrl The video URL to check
     * @param \DateTimeImmutable $since Only check for duplicates since this time
     * @return VideoAnalysis|null Existing analysis if found
     */
    public function findDuplicateAnalysis(
        User $user,
        string $videoUrl,
        \DateTimeImmutable $since
    ): ?VideoAnalysis {
        return $this->createQueryBuilder('va')
            ->where('va.user = :user')
            ->andWhere('va.videoUrl = :videoUrl')
            ->andWhere('va.createdAt > :since')
            ->andWhere('va.status IN (:validStatuses)')
            ->orderBy('va.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('user', $user)
            ->setParameter('videoUrl', $videoUrl)
            ->setParameter('since', $since, Types::DATETIME_IMMUTABLE)
            ->setParameter('validStatuses', [
                VideoAnalysis::STATUS_PENDING,
                VideoAnalysis::STATUS_PROCESSING,
                VideoAnalysis::STATUS_COMPLETED
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get paginated video analysis jobs for a user
     * 
     * @param User $user The user to get jobs for
     * @param int $page Page number (1-based)
     * @param int $limit Items per page
     * @param string|null $status Optional status filter
     * @param string $sort Sort order ('newest', 'oldest', 'status')
     * @return array Array with 'jobs' and 'total' keys
     */
    public function findPaginatedByUser(
        User $user,
        int $page = 1,
        int $limit = 10,
        ?string $status = null,
        string $sort = 'newest'
    ): array {
        $qb = $this->createQueryBuilder('va')
            ->where('va.user = :user')
            ->setParameter('user', $user);

        if ($status !== null) {
            $qb->andWhere('va.status = :status')
               ->setParameter('status', $status);
        }

        // Apply sorting
        switch ($sort) {
            case 'oldest':
                $qb->orderBy('va.createdAt', 'ASC');
                break;
            case 'status':
                $qb->orderBy('va.status', 'ASC')
                   ->addOrderBy('va.createdAt', 'DESC');
                break;
            case 'newest':
            default:
                $qb->orderBy('va.createdAt', 'DESC');
                break;
        }

        // Count total results
        $totalQb = clone $qb;
        $total = (int) $totalQb->select('COUNT(va.id)')
                              ->getQuery()
                              ->getSingleScalarResult();

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $jobs = $qb->setFirstResult($offset)
                   ->setMaxResults($limit)
                   ->getQuery()
                   ->getResult();

        return [
            'jobs' => $jobs,
            'total' => $total,
        ];
    }

    /**
     * Delete expired video analysis jobs
     * 
     * @return int Number of deleted jobs
     */
    public function deleteExpiredJobs(): int
    {
        return $this->createQueryBuilder('va')
            ->delete()
            ->where('va.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();
    }
}
