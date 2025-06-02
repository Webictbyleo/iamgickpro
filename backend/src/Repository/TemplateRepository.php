<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Template entity operations.
 * 
 * Provides methods for querying and retrieving Template entities with various filters
 * and criteria. Handles template categorization, ratings, usage tracking, and
 * recommendation systems. Supports both free and premium template operations.
 * 
 * @extends ServiceEntityRepository<Template>
 */
class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    /**
     * Find a template by its UUID.
     * 
     * Searches for a template with the specified UUID, excluding soft-deleted templates.
     * UUIDs are used for public-facing template identification.
     * 
     * @param string $uuid The UUID of the template to find
     * @return Template|null The Template entity if found, null otherwise
     */
    public function findByUuid(string $uuid): ?Template
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.uuid = :uuid')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find public templates with pagination.
     * 
     * Retrieves publicly available templates ordered by usage count and rating.
     * Used for the main template gallery and browsing features.
     * 
     * @param int $limit Maximum number of templates to return (default: 20)
     * @param int $offset Number of templates to skip for pagination (default: 0)
     * @return Template[] Array of public Template entities
     */
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

    /**
     * Find templates by category with pagination.
     * 
     * Retrieves public templates belonging to a specific category,
     * ordered by usage count. Used for category-based browsing.
     * 
     * @param string $category The category to filter templates by
     * @param int $limit Maximum number of templates to return (default: 20)
     * @param int $offset Number of templates to skip for pagination (default: 0)
     * @return Template[] Array of Template entities in the specified category
     */
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

    /**
     * Find templates by specific tags.
     * 
     * Retrieves public templates that contain all of the specified tags.
     * Uses JSON operations to search within the tags array field.
     * 
     * @param array $tags Array of tags that templates must contain
     * @param int $limit Maximum number of templates to return (default: 20)
     * @return Template[] Array of Template entities containing all specified tags
     */
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

    /**
     * Find premium templates with pagination.
     * 
     * Retrieves paid/premium templates ordered by rating and usage count.
     * Used for premium template sections and subscription features.
     * 
     * @param int $limit Maximum number of templates to return (default: 20)
     * @param int $offset Number of templates to skip for pagination (default: 0)
     * @return Template[] Array of premium Template entities
     */
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

    /**
     * Find free templates with pagination.
     * 
     * Retrieves free templates ordered by usage count and rating.
     * Used for showcasing free content and attracting new users.
     * 
     * @param int $limit Maximum number of templates to return (default: 20)
     * @param int $offset Number of templates to skip for pagination (default: 0)
     * @return Template[] Array of free Template entities
     */
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

    /**
     * Search templates by name or description.
     * 
     * Performs a case-insensitive search on template names and descriptions.
     * Uses LIKE pattern matching for flexible searching across public templates.
     * 
     * @param string $query The search query to match against template fields
     * @param int $limit Maximum number of results to return (default: 20)
     * @return Template[] Array of Template entities matching the search criteria
     */
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

    /**
     * Find most popular templates.
     * 
     * Retrieves the most frequently used templates ordered by usage count.
     * Used for homepage featured sections and trending template displays.
     * 
     * @param int $limit Maximum number of popular templates to return (default: 10)
     * @return Template[] Array of the most popular Template entities
     */
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

    /**
     * Find top-rated templates.
     * 
     * Retrieves templates with the highest ratings above a minimum threshold.
     * Combines rating and usage count for balanced quality recommendations.
     * 
     * @param int $limit Maximum number of top-rated templates to return (default: 10)
     * @param float $minRating Minimum rating threshold (default: 4.0)
     * @return Template[] Array of top-rated Template entities
     */
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

    /**
     * Find templates by dimensions with tolerance.
     * 
     * Searches for templates that have dimensions within a specified tolerance
     * of the given width and height. Useful for finding templates that match
     * specific canvas sizes or aspect ratios.
     * 
     * @param int $width The target width in pixels
     * @param int $height The target height in pixels
     * @param int $tolerance The allowed deviation in pixels (default: 50)
     * @return Template[] Array of Template entities matching the dimension criteria
     */
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

    /**
     * Find recently added templates.
     * 
     * Retrieves templates that were created within the specified number of days.
     * Used for "New Templates" sections and keeping content fresh.
     * 
     * @param int $days Number of days to look back (default: 7)
     * @param int $limit Maximum number of templates to return (default: 20)
     * @return Template[] Array of recently added Template entities
     */
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

    /**
     * Find recommended templates.
     * 
     * Retrieves templates that have been manually marked as recommended
     * by administrators. Used for curated template collections and
     * editorial picks.
     * 
     * @param int $limit Maximum number of recommended templates to return (default: 10)
     * @return Template[] Array of recommended Template entities
     */
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

    /**
     * Count templates by category.
     * 
     * Returns statistics showing how many templates exist in each category.
     * Used for category navigation and analytics dashboards.
     * 
     * @return array Array with category and count fields, ordered by count (highest first)
     */
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
     * Count all public templates.
     * 
     * Returns the total number of publicly available templates,
     * excluding soft-deleted templates. Used for platform statistics.
     * 
     * @return int Total number of public templates
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
     * Count templates by specific category.
     * 
     * Returns the number of public templates in a specific category,
     * excluding soft-deleted templates. Used for category-specific statistics.
     * 
     * @param string $category The category to count templates for
     * @return int Number of templates in the specified category
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

    /**
     * Find popular categories with usage statistics.
     * 
     * Analyzes template usage to determine the most popular categories.
     * Returns categories with template count and total usage metrics.
     * 
     * @param int $limit Maximum number of popular categories to return (default: 10)
     * @return array Array with category, templateCount, and totalUsage fields
     */
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

    /**
     * Increment the usage count for a template.
     * 
     * Atomically increments the usage counter when a template is used.
     * This helps track template popularity and usage statistics.
     * 
     * @param Template $template The template to increment usage count for
     */
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

    /**
     * Update the rating for a template.
     * 
     * Updates the template's rating with a new calculated value.
     * Typically called after processing user ratings and calculating averages.
     * 
     * @param Template $template The template to update
     * @param float $newRating The new rating value to set
     */
    public function updateRating(Template $template, float $newRating): void
    {
        $template->setRating(number_format($newRating, 2));
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
    }

    /**
     * Find templates similar to a given template.
     * 
     * Uses category matching and tag similarity to find related templates.
     * Excludes the current template from results. Used for recommendation
     * systems and "You might also like" features.
     * 
     * @param Template $template The reference template to find similar ones for
     * @param int $limit Maximum number of similar templates to return (default: 5)
     * @return Template[] Array of similar Template entities
     */
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

    /**
     * Find the most popular tags across all public templates.
     * 
     * Analyzes all public templates to determine the most frequently used tags.
     * Uses native SQL with JSON functions to extract and count individual tags
     * from the JSON tags field. Returns tag names with their usage counts.
     * 
     * @param int $limit Maximum number of popular tags to return (default: 20)
     * @return array Array of tags with 'tag' and 'count' fields, ordered by popularity
     */
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
     * Get all distinct categories from active public templates.
     * 
     * Returns a simple array of unique category names from all public templates.
     * Used for populating category filters and navigation menus.
     * 
     * @return array Array of category names
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
