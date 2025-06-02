<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Plugin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Plugin entity operations.
 * 
 * Provides methods for managing plugins in the design platform's plugin ecosystem.
 * Handles plugin discovery, approval workflows, security management, and marketplace
 * functionality. Supports plugin categorization, developer management, and usage analytics.
 * 
 * @extends ServiceEntityRepository<Plugin>
 */
class PluginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plugin::class);
    }

    /**
     * Find a plugin by its UUID.
     * 
     * Searches for a plugin with the specified UUID, excluding soft-deleted plugins.
     * UUIDs are used for public-facing plugin identification and API operations.
     * 
     * @param string $uuid The UUID of the plugin to find
     * @return Plugin|null The Plugin entity if found, null otherwise
     */
    public function findByUuid(string $uuid): ?Plugin
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.uuid = :uuid')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find approved and active plugins for the marketplace.
     * 
     * Retrieves plugins that have been approved for public use and are currently active.
     * Results are ordered by download count (most popular first) and creation date.
     * Used for displaying plugins in the marketplace.
     * 
     * @param int $limit Maximum number of plugins to return (default: 20)
     * @param int $offset Number of plugins to skip for pagination (default: 0)
     * @return Plugin[] Array of approved and active Plugin entities
     */
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

    /**
     * Find plugins by category.
     * 
     * Retrieves approved and active plugins from a specific category, ordered by
     * popularity (download count). Used for category-based plugin browsing.
     * 
     * @param string $category The plugin category to filter by
     * @param int $limit Maximum number of plugins to return (default: 20)
     * @param int $offset Number of plugins to skip for pagination (default: 0)
     * @return Plugin[] Array of Plugin entities in the specified category
     */
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

    /**
     * Search plugins by name or description.
     * 
     * Performs a text search across plugin names and descriptions for approved
     * and active plugins. Results are ordered by popularity (download count).
     * 
     * @param string $query The search query to match against name and description
     * @param int $limit Maximum number of plugins to return (default: 20)
     * @return Plugin[] Array of Plugin entities matching the search criteria
     */
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

    /**
     * Find plugins by tags using JSON field queries.
     * 
     * Searches for approved and active plugins that contain all specified tags
     * in their tags JSON array. Uses MySQL's JSON_CONTAINS function for efficient
     * tag-based filtering.
     * 
     * @param array $tags Array of tags that plugins must contain (all tags required)
     * @param int $limit Maximum number of plugins to return (default: 20)
     * @return Plugin[] Array of Plugin entities containing all specified tags
     */
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

    /**
     * Find the most popular plugins by download count.
     * 
     * Retrieves the top plugins ordered by download count for featuring on
     * the homepage or popular plugins section.
     * 
     * @param int $limit Maximum number of plugins to return (default: 10)
     * @return Plugin[] Array of most popular Plugin entities
     */
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

    /**
     * Find recently added plugins within a time period.
     * 
     * Retrieves approved and active plugins that were created within the specified
     * number of days. Used for showcasing new plugins in the marketplace.
     * 
     * @param int $days Number of days to look back (default: 7)
     * @param int $limit Maximum number of plugins to return (default: 10)
     * @return Plugin[] Array of recently added Plugin entities
     */
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

    /**
     * Find recently updated plugins within a time period.
     * 
     * Retrieves approved and active plugins that were updated within the specified
     * number of days. Used for showcasing plugins with recent updates.
     * 
     * @param int $days Number of days to look back (default: 7)
     * @param int $limit Maximum number of plugins to return (default: 10)
     * @return Plugin[] Array of recently updated Plugin entities
     */
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

    /**
     * Find plugins pending review for approval.
     * 
     * Retrieves all plugins with 'pending' status for admin review, ordered by
     * submission date (oldest first). Used in the admin panel for plugin approval workflow.
     * 
     * @return Plugin[] Array of Plugin entities awaiting approval
     */
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

    /**
     * Find plugins by their approval status.
     * 
     * Retrieves plugins filtered by their status (pending, approved, rejected, etc.).
     * Used for admin management and status-based plugin filtering.
     * 
     * @param string $status The plugin status to filter by
     * @return Plugin[] Array of Plugin entities with the specified status
     */
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

    /**
     * Find plugins by developer name.
     * 
     * Retrieves approved and active plugins created by a specific developer,
     * ordered by popularity. Used for developer profile pages and portfolios.
     * 
     * @param string $developer The developer name to filter by
     * @param int $limit Maximum number of plugins to return (default: 20)
     * @return Plugin[] Array of Plugin entities created by the specified developer
     */
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

    /**
     * Find plugins requiring specific permissions.
     * 
     * Searches for approved and active plugins that require all specified permissions.
     * Used for security auditing and permission-based plugin discovery.
     * 
     * @param array $permissions Array of permissions that plugins must require (all permissions required)
     * @return Plugin[] Array of Plugin entities requiring all specified permissions
     */
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

    /**
     * Find plugins with security reports.
     * 
     * Retrieves plugins that have security reports recorded in their securityReports
     * JSON field. Used for security monitoring and vulnerability management.
     * 
     * @return Plugin[] Array of Plugin entities with security reports
     */
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

    /**
     * Count plugins grouped by category.
     * 
     * Returns statistics showing the number of approved and active plugins in each category.
     * Used for marketplace analytics and category popularity tracking.
     * 
     * @return array Array with 'category' and 'count' fields for each plugin category
     */
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

    /**
     * Count plugins grouped by approval status.
     * 
     * Returns statistics showing the number of plugins in each status state
     * (pending, approved, rejected, etc.). Used for admin dashboard analytics.
     * 
     * @return array Array with 'status' and 'count' fields for each plugin status
     */
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

    /**
     * Increment the download count for a plugin.
     * 
     * Atomically increases the download counter by 1 when a plugin is downloaded
     * or installed. Used for tracking plugin popularity and usage statistics.
     * 
     * @param Plugin $plugin The plugin whose download count to increment
     * @return void
     */
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

    /**
     * Find plugins that need security scanning.
     * 
     * Retrieves approved and active plugins that haven't been security scanned
     * in the last 30 days or have never been scanned. Used for automated
     * security monitoring and vulnerability detection.
     * 
     * @return Plugin[] Array of Plugin entities requiring security scanning
     */
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

    /**
     * Find obsolete plugins that haven't been updated recently.
     * 
     * Retrieves plugins that haven't been updated within the specified number
     * of months. Used for identifying potentially abandoned or outdated plugins
     * that may need maintenance or removal.
     * 
     * @param int $months Number of months to consider a plugin obsolete (default: 12)
     * @return Plugin[] Array of obsolete Plugin entities, ordered by last update date
     */
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

    /**
     * Find top plugin developers by total downloads and plugin count.
     * 
     * Returns statistics for the most successful plugin developers, including
     * their total plugin count and combined download numbers. Used for
     * developer leaderboards and recognition programs.
     * 
     * @param int $limit Maximum number of developers to return (default: 10)
     * @return array Array with 'developer', 'pluginCount', and 'totalDownloads' fields
     */
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

    /**
     * Find the most popular tags across all approved plugins.
     * 
     * Extracts and counts individual tags from the JSON tags field to find
     * the most frequently used tags among approved plugins. Uses raw SQL for
     * efficient JSON array processing and tag extraction.
     * 
     * @param int $limit Maximum number of popular tags to return (default: 20)
     * @return array Array of associative arrays with 'tag' and 'count' keys, ordered by popularity
     */
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

    /**
     * Find plugins with advanced filtering and sorting options.
     * 
     * Provides comprehensive plugin filtering by category, search terms, and status
     * with flexible sorting options. Supports pagination for large result sets.
     * Used for advanced plugin search and admin management interfaces.
     * 
     * @param array $filters Array of filter criteria including category, search, status, sortBy, sortOrder, page, limit
     * @return Plugin[] Array of Plugin entities matching the filter criteria
     */
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

    /**
     * Count plugins matching specific filter criteria.
     * 
     * Returns the total count of plugins that match the same filter criteria
     * used by findByFilters(). Used for pagination calculations and result
     * count displays in search interfaces.
     * 
     * @param array $filters Array of filter criteria to count against
     * @return int Total number of plugins matching the filter criteria
     */
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

    /**
     * Find plugins created by a specific user.
     * 
     * Retrieves all plugins (regardless of status) created by the specified user
     * with pagination support. Used for user plugin management dashboards and
     * developer portfolio pages.
     * 
     * @param mixed $user The user entity or user ID whose plugins to retrieve
     * @param int $page Page number for pagination (default: 1)
     * @param int $limit Number of plugins per page (default: 20)
     * @return Plugin[] Array of Plugin entities created by the user
     */
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

    /**
     * Count total plugins created by a specific user.
     * 
     * Returns the total number of plugins (regardless of status) created by
     * the specified user. Used for user statistics and pagination calculations.
     * 
     * @param mixed $user The user entity or user ID whose plugins to count
     * @return int Total number of plugins created by the user
     */
    public function countByUser($user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all unique plugin categories.
     * 
     * Extracts and returns all unique categories from the categories JSON field
     * across all plugins. Uses raw SQL for efficient JSON array processing to
     * build category lists for filters and navigation.
     * 
     * @return array Array of unique category names
     */
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
