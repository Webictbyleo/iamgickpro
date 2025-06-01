<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Template>
 */
class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    public function findByUuid(string $uuid): ?Template
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.uuid = :uuid')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPublicTemplates(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('public', true)
            ->orderBy('t.usageCount', 'DESC')
            ->addOrderBy('CAST(t.rating AS DECIMAL(3,2))', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.category = :category')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('category', $category)
            ->setParameter('public', true)
            ->orderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByTags(array $tags, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('public', true);

        foreach ($tags as $index => $tag) {
            $qb->andWhere("JSON_CONTAINS(t.tags, :tag_{$index}) = 1")
               ->setParameter("tag_{$index}", json_encode($tag));
        }

        return $qb->orderBy('t.usageCount', 'DESC')
                  ->addOrderBy('t.rating', 'DESC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function findPremiumTemplates(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isPremium = :premium')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('premium', true)
            ->setParameter('public', true)
            ->orderBy('t.rating', 'DESC')
            ->addOrderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findFreeTemplates(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isPremium = :premium')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('premium', false)
            ->setParameter('public', true)
            ->orderBy('t.usageCount', 'DESC')
            ->addOrderBy('t.rating', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function search(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.name LIKE :query OR t.description LIKE :query')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('public', true)
            ->orderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findMostPopular(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('public', true)
            ->orderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopRated(int $limit = 10, float $minRating = 4.0): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isPublic = :public')
            ->andWhere('CAST(t.rating AS DECIMAL(3,2)) >= :minRating')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('public', true)
            ->setParameter('minRating', $minRating)
            ->orderBy('CAST(t.rating AS DECIMAL(3,2))', 'DESC')
            ->addOrderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByDimensions(int $width, int $height, int $tolerance = 50): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.width BETWEEN :minWidth AND :maxWidth')
            ->andWhere('t.height BETWEEN :minHeight AND :maxHeight')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('minWidth', $width - $tolerance)
            ->setParameter('maxWidth', $width + $tolerance)
            ->setParameter('minHeight', $height - $tolerance)
            ->setParameter('maxHeight', $height + $tolerance)
            ->setParameter('public', true)
            ->orderBy('t.usageCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentlyAdded(int $days = 7, int $limit = 20): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('t')
            ->andWhere('t.createdAt >= :since')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('since', $since)
            ->setParameter('public', true)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecommended(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isRecommended = :recommended')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('recommended', true)
            ->setParameter('public', true)
            ->orderBy('t.rating', 'DESC')
            ->addOrderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByCategory(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.category', 'COUNT(t.id) as count')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('public', true)
            ->groupBy('t.category')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count all public templates
     */
    public function countPublicTemplates(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('public', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count templates by specific category
     */
    public function countTemplatesByCategory(string $category): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.category = :category')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('category', $category)
            ->setParameter('public', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPopularCategories(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.category', 'COUNT(t.id) as templateCount', 'SUM(t.usageCount) as totalUsage')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('public', true)
            ->groupBy('t.category')
            ->orderBy('totalUsage', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function incrementUsageCount(Template $template): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.usageCount', 't.usageCount + 1')
            ->where('t.id = :id')
            ->setParameter('id', $template->getId())
            ->getQuery()
            ->execute();
    }

    public function updateRating(Template $template, float $newRating): void
    {
        $template->setRating(number_format($newRating, 2));
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
    }

    public function findSimilarTemplates(Template $template, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.id != :currentId')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('currentId', $template->getId())
            ->setParameter('public', true);

        // Find templates with same category
        $qb->andWhere('t.category = :category')
           ->setParameter('category', $template->getCategory());

        // If template has tags, find templates with similar tags
        if ($template->getTags()) {
            foreach ($template->getTags() as $index => $tag) {
                $qb->orWhere("JSON_CONTAINS(t.tags, :tag_{$index}) = 1")
                   ->setParameter("tag_{$index}", json_encode($tag));
            }
        }

        return $qb->orderBy('t.rating', 'DESC')
                  ->addOrderBy('t.usageCount', 'DESC')
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
                FROM templates t
                CROSS JOIN (
                    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                ) numbers
                WHERE t.deleted_at IS NULL 
                AND t.is_public = 1
                AND JSON_EXTRACT(t.tags, CONCAT('$[', numbers.n, ']')) IS NOT NULL
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

    /**
     * Get all distinct categories from active public templates
     */
    public function findAllCategories(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('DISTINCT t.category')
            ->andWhere('t.isPublic = :public')
            ->andWhere('t.deletedAt IS NULL')
            ->andWhere('t.category IS NOT NULL')
            ->setParameter('public', true)
            ->orderBy('t.category', 'ASC')
            ->getQuery()
            ->getResult();

        // Extract just the category names from the result array
        return array_map(fn($item) => $item['category'], $result);
    }
}
