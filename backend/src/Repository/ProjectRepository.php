<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUuid(string $uuid): ?Project
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.uuid = :uuid')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserAndUuid(User $user, string $uuid): ?Project
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.uuid = :uuid')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPublicProjects(?string $search = null, ?array $tags = null, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isPublic = :public')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('public', true);

        if ($search) {
            $qb->andWhere('p.title LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($tags) {
            $qb->andWhere('JSON_OVERLAPS(p.tags, :tags) = 1')
               ->setParameter('tags', json_encode($tags));
        }

        return $qb->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findRecentProjects(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchUserProjects(User $user, string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.name LIKE :query OR p.description LIKE :query')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findProjectsWithDesignCount(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'COUNT(d.id) as designsCount')
            ->leftJoin('p.designs', 'd')
            ->andWhere('p.user = :user')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->groupBy('p.id')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUserProjects(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPublicProjects(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isPublic = :public')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('public', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findProjectsUpdatedBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.updatedAt >= :start')
            ->andWhere('p.updatedAt <= :end')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTags(array $tags, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('p.isPublic = :public')
            ->setParameter('public', true);

        foreach ($tags as $index => $tag) {
            $qb->andWhere("JSON_CONTAINS(p.tags, :tag_{$index}) = 1")
               ->setParameter("tag_{$index}", json_encode($tag));
        }

        return $qb->orderBy('p.updatedAt', 'DESC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function findMostPopularTags(int $limit = 20): array
    {
        $sql = "
            SELECT tag, COUNT(*) as count
            FROM (
                SELECT JSON_UNQUOTE(JSON_EXTRACT(tags, CONCAT('$[', numbers.n, ']'))) as tag
                FROM projects p
                CROSS JOIN (
                    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                ) numbers
                WHERE p.deleted_at IS NULL 
                AND p.is_public = 1
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
}
