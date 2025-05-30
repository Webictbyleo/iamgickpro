<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function findByUuid(string $uuid): ?Media
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.uuid = :uuid')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.type = :type')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('type', $type)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findBySource(string $source, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.source = :source')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('source', $source)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findUserMediaByType(User $user, string $type, int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.type = :type')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchMedia(string $query, ?string $type = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.fileName LIKE :query OR m.altText LIKE :query')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('query', '%' . $query . '%');

        if ($type) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('m.createdAt', 'DESC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function searchUserMedia(User $user, string $query, ?string $type = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.fileName LIKE :query OR m.altText LIKE :query')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%');

        if ($type) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('m.createdAt', 'DESC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function findByTags(array $tags, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.deletedAt IS NULL');

        foreach ($tags as $index => $tag) {
            $qb->andWhere("JSON_CONTAINS(m.tags, :tag_{$index}) = 1")
               ->setParameter("tag_{$index}", json_encode($tag));
        }

        return $qb->orderBy('m.createdAt', 'DESC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function findByDimensions(int $minWidth, int $maxWidth, int $minHeight, int $maxHeight): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.width >= :minWidth AND m.width <= :maxWidth')
            ->andWhere('m.height >= :minHeight AND m.height <= :maxHeight')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('minWidth', $minWidth)
            ->setParameter('maxWidth', $maxWidth)
            ->setParameter('minHeight', $minHeight)
            ->setParameter('maxHeight', $maxHeight)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFileSize(int $minSize, int $maxSize): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.fileSize >= :minSize AND m.fileSize <= :maxSize')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('minSize', $minSize)
            ->setParameter('maxSize', $maxSize)
            ->orderBy('m.fileSize', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRecent(int $days = 7, int $limit = 20): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('m')
            ->andWhere('m.createdAt >= :since')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('since', $since)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findUserRecent(User $user, int $days = 7, int $limit = 10): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.createdAt >= :since')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findLargeFiles(int $sizeThresholdMB = 10): array
    {
        $sizeThreshold = $sizeThresholdMB * 1024 * 1024; // Convert to bytes
        
        return $this->createQueryBuilder('m')
            ->andWhere('m.fileSize >= :threshold')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('threshold', $sizeThreshold)
            ->orderBy('m.fileSize', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByType(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.type', 'COUNT(m.id) as count', 'SUM(m.fileSize) as totalSize')
            ->andWhere('m.deletedAt IS NULL')
            ->groupBy('m.type')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUserMedia(User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.user = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getUserStorageUsage(User $user): int
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.fileSize)')
            ->andWhere('m.user = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    public function findDuplicates(string $hash): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.hash = :hash')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('hash', $hash)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOrphanedFiles(): array
    {
        // Find media files that are not referenced in any designs
        return $this->createQueryBuilder('m')
            ->andWhere('m.deletedAt IS NULL')
            ->andWhere('m.source = :upload')
            ->setParameter('upload', 'upload')
            ->getQuery()
            ->getResult();
    }

    public function cleanupExpiredFiles(): int
    {
        $expiredDate = new \DateTimeImmutable('-30 days');
        
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.deletedAt', ':now')
            ->where('m.deletedAt IS NULL')
            ->andWhere('m.createdAt < :expired')
            ->andWhere('m.source = :temp')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('expired', $expiredDate)
            ->setParameter('temp', 'temp')
            ->getQuery()
            ->execute();
    }

    public function findByExternalId(string $externalId, string $source): ?Media
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.externalId = :externalId')
            ->andWhere('m.source = :source')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('externalId', $externalId)
            ->setParameter('source', $source)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPopularTags(int $limit = 20): array
    {
        $sql = "
            SELECT tag, COUNT(*) as count
            FROM (
                SELECT JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']'))) as tag
                FROM media m
                CROSS JOIN (
                    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                ) numbers
                WHERE m.deleted_at IS NULL 
                AND JSON_EXTRACT(m.tags, CONCAT('$[', numbers.n, ']')) IS NOT NULL
            ) tags_extracted
            WHERE tag IS NOT NULL AND tag != 'null'
            GROUP BY tag
            ORDER BY count DESC
            LIMIT :limit
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('limit', $limit, 'integer');
        
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
