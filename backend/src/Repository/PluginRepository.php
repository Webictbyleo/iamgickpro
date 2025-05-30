<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Plugin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plugin>
 */
class PluginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plugin::class);
    }

    public function findByUuid(string $uuid): ?Plugin
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.uuid = :uuid')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findApprovedPlugins(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->orderBy('p.downloads', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('category', $category)
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->orderBy('p.downloads', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function searchPlugins(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :query OR p.description LIKE :query')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->orderBy('p.downloads', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByTags(array $tags, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', 'approved')
            ->setParameter('active', true);

        foreach ($tags as $index => $tag) {
            $qb->andWhere("JSON_CONTAINS(p.tags, :tag_{$index}) = 1")
               ->setParameter("tag_{$index}", json_encode($tag));
        }

        return $qb->orderBy('p.downloads', 'DESC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function findMostPopular(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->orderBy('p.downloads', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecentlyAdded(int $days = 7, int $limit = 10): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.createdAt >= :since')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('since', $since)
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecentlyUpdated(int $days = 7, int $limit = 10): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.updatedAt >= :since')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('since', $since)
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPendingReview(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', 'pending')
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', $status)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDeveloper(string $developer, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.developer = :developer')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('developer', $developer)
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->orderBy('p.downloads', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByPermissions(array $permissions): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', 'approved')
            ->setParameter('active', true);

        foreach ($permissions as $index => $permission) {
            $qb->andWhere("JSON_CONTAINS(p.permissions, :perm_{$index}) = 1")
               ->setParameter("perm_{$index}", json_encode($permission));
        }

        return $qb->orderBy('p.downloads', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function findSecurityReports(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.securityReports IS NOT NULL')
            ->andWhere('JSON_LENGTH(p.securityReports) > 0')
            ->andWhere('p.deletedAt IS NULL')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByCategory(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.category', 'COUNT(p.id) as count')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->groupBy('p.category')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.status', 'COUNT(p.id) as count')
            ->andWhere('p.deletedAt IS NULL')
            ->groupBy('p.status')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function incrementDownloadCount(Plugin $plugin): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.downloads', 'p.downloads + 1')
            ->where('p.id = :id')
            ->setParameter('id', $plugin->getId())
            ->getQuery()
            ->execute();
    }

    public function findPluginsNeedingSecurityScan(): array
    {
        $scanThreshold = new \DateTimeImmutable('-30 days');
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.lastSecurityScan IS NULL OR p.lastSecurityScan < :threshold')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->setParameter('threshold', $scanThreshold)
            ->orderBy('p.lastSecurityScan', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findObsoletePlugins(int $months = 12): array
    {
        $threshold = new \DateTimeImmutable("-{$months} months");
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.updatedAt < :threshold')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('threshold', $threshold)
            ->orderBy('p.updatedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTopDevelopers(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.developer', 'COUNT(p.id) as pluginCount', 'SUM(p.downloads) as totalDownloads')
            ->andWhere('p.status = :status')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('status', 'approved')
            ->setParameter('active', true)
            ->groupBy('p.developer')
            ->orderBy('totalDownloads', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPopularTags(int $limit = 20): array
    {
        $sql = "
            SELECT tag, COUNT(*) as count
            FROM (
                SELECT JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']'))) as tag
                FROM plugins p
                CROSS JOIN (
                    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                ) numbers
                WHERE p.deleted_at IS NULL 
                AND p.status = 'approved'
                AND p.is_active = 1
                AND JSON_EXTRACT(p.tags, CONCAT('$[', numbers.n, ']')) IS NOT NULL
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

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u');

        if (isset($filters['category']) && $filters['category']) {
            $qb->andWhere('JSON_CONTAINS(p.categories, :category) = 1')
               ->setParameter('category', json_encode($filters['category']));
        }

        if (isset($filters['search']) && $filters['search']) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status']) && $filters['status']) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $filters['status']);
        }

        // Sorting
        $sortBy = $filters['sortBy'] ?? 'install_count';
        $sortOrder = $filters['sortOrder'] ?? 'desc';
        
        switch ($sortBy) {
            case 'name':
                $qb->orderBy('p.name', $sortOrder);
                break;
            case 'created_at':
                $qb->orderBy('p.created_at', $sortOrder);
                break;
            case 'rating':
                $qb->orderBy('p.rating', $sortOrder);
                break;
            case 'install_count':
            default:
                $qb->orderBy('p.install_count', $sortOrder);
                break;
        }

        // Pagination
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 20;
        $offset = ($page - 1) * $limit;

        return $qb->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    public function countByFilters(array $filters): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        if (isset($filters['category']) && $filters['category']) {
            $qb->andWhere('JSON_CONTAINS(p.categories, :category) = 1')
               ->setParameter('category', json_encode($filters['category']));
        }

        if (isset($filters['search']) && $filters['search']) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status']) && $filters['status']) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $filters['status']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByUser($user, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByUser($user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCategories(): array
    {
        $sql = "
            SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(categories, CONCAT('$[', idx.idx, ']'))) as category
            FROM plugins p
            JOIN (
                SELECT 0 as idx UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
            ) idx ON JSON_LENGTH(p.categories) > idx.idx
            WHERE JSON_UNQUOTE(JSON_EXTRACT(categories, CONCAT('$[', idx.idx, ']'))) IS NOT NULL
            ORDER BY category
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();
        
        return array_column($result, 'category');
    }
}
