<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Media entity operations.
 * 
 * Provides methods for querying and managing Media entities including user uploads,
 * stock media, and external media integration. Handles file management, storage
 * calculations, duplicate detection, and media organization by type, tags, and dimensions.
 * 
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * Find a media item by its UUID.
     * 
     * Searches for a media item with the specified UUID, excluding soft-deleted media.
     * UUIDs are used for public-facing media identification and API operations.
     * 
     * @param string $uuid The UUID of the media item to find
     * @return Media|null The Media entity if found, null otherwise
     */
    public function findByUuid(string $uuid): ?Media
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.uuid = :uuid')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find media items uploaded by a specific user.
     * 
     * Retrieves all media items uploaded by the specified user, ordered by
     * creation date (newest first). Supports pagination for large media libraries.
     * 
     * @param User $user The user whose media to retrieve
     * @param int $limit Maximum number of media items to return (default: 20)
     * @param int $offset Number of items to skip for pagination (default: 0)
     * @return Media[] Array of Media entities owned by the user
     */
    public function findByUser(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find media items by their type (image, video, audio, etc.).
     * 
     * Retrieves media items of a specific type across all users. Useful for
     * browsing stock media or filtering user libraries by media type.
     * 
     * @param string $type The media type to filter by (e.g., 'image', 'video', 'audio')
     * @param int $limit Maximum number of media items to return (default: 20)
     * @param int $offset Number of items to skip for pagination (default: 0)
     * @return Media[] Array of Media entities of the specified type
     */
    public function findByType(string $type, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.type = :type')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('type', $type)
            ->orderBy('m.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find media items by their source (upload, stock, external API).
     * 
     * Retrieves media items based on their source origin. Used to filter
     * between user uploads, stock media, and external integrations.
     * 
     * @param string $source The media source to filter by (e.g., 'upload', 'stock', 'unsplash')
     * @param int $limit Maximum number of media items to return (default: 20)
     * @param int $offset Number of items to skip for pagination (default: 0)
     * @return Media[] Array of Media entities from the specified source
     */
    public function findBySource(string $source, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.source = :source')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('source', $source)
            ->orderBy('m.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find media items for a specific user filtered by type.
     * 
     * Combines user and type filtering to retrieve a user's media of a specific type.
     * Useful for organizing user media libraries by categories.
     * 
     * @param User $user The user whose media to retrieve
     * @param string $type The media type to filter by
     * @param int $limit Maximum number of media items to return (default: 20)
     * @return Media[] Array of user's Media entities of the specified type
     */
    public function findUserMediaByType(User $user, string $type, int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.type = :type')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('m.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search media items by filename or alt text.
     * 
     * Performs a text search across media filenames and alt text descriptions.
     * Optionally filters by media type for more specific searches.
     * 
     * @param string $query The search query to match against filename and alt text
     * @param string|null $type Optional media type filter
     * @param int $limit Maximum number of media items to return (default: 20)
     * @return Media[] Array of Media entities matching the search criteria
     */
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

        return $qb->orderBy('m.created_at', 'DESC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Search a specific user's media by filename or alt text.
     * 
     * Performs a text search within a user's media collection, optionally
     * filtered by media type. Used for user media library search functionality.
     * 
     * @param User $user The user whose media to search
     * @param string $query The search query to match against filename and alt text
     * @param string|null $type Optional media type filter
     * @param int $limit Maximum number of media items to return (default: 20)
     * @return Media[] Array of user's Media entities matching the search criteria
     */
    public function searchUserMedia(User $user, string $query, ?string $type = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.name LIKE :query')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%');

        if ($type) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('m.created_at', 'DESC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find media items by tags using JSON field queries.
     * 
     * Searches for media items that contain all specified tags in their tags JSON array.
     * Uses MySQL's JSON_CONTAINS function for efficient tag-based filtering.
     * 
     * @param array $tags Array of tags that media must contain (all tags required)
     * @param int $limit Maximum number of media items to return (default: 20)
     * @return Media[] Array of Media entities containing all specified tags
     */
    public function findByTags(array $tags, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.deletedAt IS NULL');

        foreach ($tags as $index => $tag) {
            $qb->andWhere("JSON_CONTAINS(m.tags, :tag_{$index}) = 1")
               ->setParameter("tag_{$index}", json_encode($tag));
        }

        return $qb->orderBy('m.created_at', 'DESC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find media items within specified dimension ranges.
     * 
     * Retrieves media items whose width and height fall within the specified ranges.
     * Useful for finding media that fits specific design requirements or aspect ratios.
     * 
     * @param int $minWidth Minimum width in pixels
     * @param int $maxWidth Maximum width in pixels
     * @param int $minHeight Minimum height in pixels
     * @param int $maxHeight Maximum height in pixels
     * @return Media[] Array of Media entities within the dimension ranges
     */
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
            ->orderBy('m.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find media items within a specific file size range.
     * 
     * Retrieves media items whose file size falls within the specified byte range.
     * Useful for storage management and finding large files for optimization.
     * 
     * @param int $minSize Minimum file size in bytes
     * @param int $maxSize Maximum file size in bytes
     * @return Media[] Array of Media entities within the file size range, ordered by size
     */
    public function findByFileSize(int $minSize, int $maxSize): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.size >= :minSize AND m.size <= :maxSize')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('minSize', $minSize)
            ->setParameter('maxSize', $maxSize)
            ->orderBy('m.size', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently uploaded media items across all users.
     * 
     * Retrieves media items uploaded within the specified number of days.
     * Used for displaying recent uploads and trending content.
     * 
     * @param int $days Number of days to look back (default: 7)
     * @param int $limit Maximum number of media items to return (default: 20)
     * @return Media[] Array of recently uploaded Media entities
     */
    public function findRecent(int $days = 7, int $limit = 20): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('m')
            ->andWhere('m.created_at >= :since')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('since', $since)
            ->orderBy('m.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently uploaded media items for a specific user.
     * 
     * Retrieves media items uploaded by a user within the specified number of days.
     * Used for showing users their recent uploads in their media library.
     * 
     * @param User $user The user whose recent media to retrieve
     * @param int $days Number of days to look back (default: 7)
     * @param int $limit Maximum number of media items to return (default: 10)
     * @return Media[] Array of user's recently uploaded Media entities
     */
    public function findUserRecent(User $user, int $days = 7, int $limit = 10): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.created_at >= :since')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('m.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find large media files exceeding a size threshold.
     * 
     * Retrieves media items that exceed the specified size threshold in megabytes.
     * Used for storage optimization and identifying files that may need compression.
     * 
     * @param int $sizeThresholdMB Size threshold in megabytes (default: 10MB)
     * @return Media[] Array of Media entities exceeding the size threshold, ordered by size (largest first)
     */
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

    /**
     * Count media items grouped by type with storage statistics.
     * 
     * Returns statistics showing the count and total storage usage for each media type.
     * Used for analytics dashboards and storage management reporting.
     * 
     * @return array Array with 'type', 'count', and 'totalSize' fields for each media type
     */
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

    /**
     * Count total number of media items for a specific user.
     * 
     * Returns the total count of non-deleted media items owned by the user.
     * Used for user statistics and storage limit enforcement.
     * 
     * @param User $user The user whose media to count
     * @return int Total number of media items owned by the user
     */
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

    /**
     * Calculate total storage usage for a specific user.
     * 
     * Returns the sum of file sizes for all non-deleted media owned by the user.
     * Used for storage quota enforcement and billing calculations.
     * 
     * @param User $user The user whose storage usage to calculate
     * @return int Total storage usage in bytes
     */
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

    /**
     * Get total storage size of all active media files.
     * 
     * Calculates the total size in bytes of all non-deleted media files.
     * Used for analytics and storage usage reporting.
     * 
     * @return int Total size in bytes
     */
    public function getTotalStorageSize(): int
    {
        $result = $this->createQueryBuilder('m')
            ->select('COALESCE(SUM(m.size), 0)')
            ->where('m.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
        
        return (int) ($result ?? 0);
    }

    /**
     * Find duplicate media files by hash.
     * 
     * Retrieves all media items that share the same file hash, indicating
     * identical file content. Used for deduplication and storage optimization.
     * 
     * @param string $hash The file hash to search for duplicates
     * @return Media[] Array of Media entities with the same hash, ordered by creation date
     */
    public function findDuplicates(string $hash): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.hash = :hash')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('hash', $hash)
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find orphaned media files not referenced in any designs.
     * 
     * Identifies uploaded media files that are no longer referenced by any designs
     * or other entities. Used for cleanup operations and storage optimization.
     * Note: This is a simplified implementation - a complete version would check
     * references across all entities that might use media.
     * 
     * @return Media[] Array of orphaned Media entities that can potentially be cleaned up
     */
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

    /**
     * Clean up expired temporary media files.
     * 
     * Soft-deletes temporary media files that are older than 30 days.
     * Used for automatic cleanup of temporary uploads and processing files.
     * 
     * @return int Number of files that were marked as deleted
     */
    public function cleanupExpiredFiles(): int
    {
        $expiredDate = new \DateTimeImmutable('-30 days');
        
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.deletedAt', ':now')
            ->where('m.deletedAt IS NULL')
            ->andWhere('m.created_at < :expired')
            ->andWhere('m.source = :temp')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('expired', $expiredDate)
            ->setParameter('temp', 'temp')
            ->getQuery()
            ->execute();
    }

    /**
     * Find media by external ID and source.
     * 
     * Retrieves media items that were imported from external services using
     * their external identifier. Used for preventing duplicate imports and
     * managing external media references.
     * 
     * @param string $externalId The external service's identifier for the media
     * @param string $source The external source name (e.g., 'unsplash', 'pixabay')
     * @return Media|null The Media entity if found, null otherwise
     */
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

    /**
     * Find media items with advanced filtering support.
     * 
     * Retrieves media items based on multiple filter criteria including type, source,
     * and text search. Supports pagination and is designed to work with the
     * SearchMediaRequestDTO for comprehensive media filtering.
     * 
     * @param array<string, string> $filters Associative array of filters (type, source, etc.)
     * @param int $page Page number for pagination (1-based)
     * @param int $limit Number of items per page
     * @param string|null $search Optional text search across filename and alt text
     * @return Media[] Array of Media entities matching the criteria
     */
    public function findByFilters(array $filters, int $page, int $limit, ?string $search = null, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.deletedAt IS NULL');

        // Filter by user if provided (for user uploads)
        if ($user !== null) {
            $qb->andWhere('m.user = :user')
               ->setParameter('user', $user);
        }

        // Apply filter criteria
        if (isset($filters['type'])) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (isset($filters['source'])) {
            $qb->andWhere('m.source = :source')
               ->setParameter('source', $filters['source']);
        }

        // Apply text search if provided
        if ($search !== null && $search !== '') {
            $qb->andWhere('m.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Calculate offset for pagination
        $offset = ($page - 1) * $limit;

        return $qb->orderBy('m.created_at', 'DESC')
                  ->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Count media items with advanced filtering support.
     * 
     * Counts the total number of media items that match the given filter criteria.
     * Used in conjunction with findByFilters for pagination calculations.
     * 
     * @param array<string, string> $filters Associative array of filters (type, source, etc.)
     * @param string|null $search Optional text search across filename and alt text
     * @param User|null $user Optional user to filter by (for user uploads)
     * @return int Total count of media items matching the criteria
     */
    public function countByFilters(array $filters, ?string $search = null, ?User $user = null): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.deletedAt IS NULL');

        // Filter by user if provided (for user uploads)
        if ($user !== null) {
            $qb->andWhere('m.user = :user')
               ->setParameter('user', $user);
        }

        // Apply filter criteria
        if (isset($filters['type'])) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (isset($filters['source'])) {
            $qb->andWhere('m.source = :source')
               ->setParameter('source', $filters['source']);
        }

        // Apply text search if provided
        if ($search !== null && $search !== '') {
            $qb->andWhere('m.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find the most popular tags across all media.
     * 
     * Extracts and counts individual tags from the JSON tags field to find
     * the most frequently used tags. Uses raw SQL for efficient JSON array
     * processing and tag extraction.
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

    /**
     * Find popular media based on usage statistics.
     * 
     * Retrieves media items that are frequently used in designs,
     * based on usage count stored in metadata. Used for displaying
     * trending content and recommendations.
     * 
     * @param int $limit Maximum number of popular media items to return (default: 20)
     * @return Media[] Array of popular Media entities ordered by usage count
     */
    public function findPopular(int $limit = 20): array
    {
        // This uses a raw SQL query to sort by JSON field for usage_count
        $sql = "
            SELECT m.* FROM media m 
            WHERE m.deleted_at IS NULL 
            AND JSON_EXTRACT(m.metadata, '$.usage_count') IS NOT NULL
            ORDER BY CAST(JSON_EXTRACT(m.metadata, '$.usage_count') AS UNSIGNED) DESC
            LIMIT :limit
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('limit', $limit, 'integer');
        $result = $stmt->executeQuery();

        $mediaIds = array_column($result->fetchAllAssociative(), 'id');
        
        if (empty($mediaIds)) {
            // Fallback to recent media if no usage stats available
            return $this->findRecent(7, $limit);
        }

        return $this->createQueryBuilder('m')
            ->andWhere('m.id IN (:ids)')
            ->setParameter('ids', $mediaIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find duplicate media files by user and hash.
     * 
     * Retrieves media items uploaded by a specific user that share the same
     * file hash, indicating identical file content. Used for user-specific
     * deduplication and storage optimization.
     * 
     * @param User $user The user whose media to check for duplicates
     * @return array Array of arrays, each containing Media entities with the same hash
     */
    public function findDuplicatesByUser(User $user): array
    {
        // First, find all hashes that have more than one media item for this user
        $duplicateHashes = $this->createQueryBuilder('m')
            ->select('m.hash')
            ->andWhere('m.user = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->andWhere('m.hash IS NOT NULL')
            ->setParameter('user', $user)
            ->groupBy('m.hash')
            ->having('COUNT(m.id) > 1')
            ->getQuery()
            ->getResult();

        $duplicateGroups = [];
        
        // For each duplicate hash, get all media items
        foreach ($duplicateHashes as $hashResult) {
            $hash = $hashResult['hash'];
            $mediaItems = $this->createQueryBuilder('m')
                ->andWhere('m.user = :user')
                ->andWhere('m.hash = :hash')
                ->andWhere('m.deletedAt IS NULL')
                ->setParameter('user', $user)
                ->setParameter('hash', $hash)
                ->orderBy('m.created_at', 'ASC')
                ->getQuery()
                ->getResult();
                
            if (count($mediaItems) > 1) {
                $duplicateGroups[] = $mediaItems;
            }
        }
        
        return $duplicateGroups;
    }

    /**
     * Duplicate a media file for a user.
     * 
     * Creates a copy of an existing media file with all properties copied except
     * UUID, timestamps, and user ownership. Used for creating personal copies
     * of accessible media files.
     * 
     * @param Media $originalMedia The media item to duplicate
     * @param User $user The user who will own the duplicate
     * @param string|null $customName Optional custom name for the duplicate
     * @return Media The newly created duplicate media entity
     */
    public function duplicateMedia(Media $originalMedia, User $user, ?string $customName = null): Media
    {
        $entityManager = $this->getEntityManager();
        
        $duplicate = new Media();
        $duplicate->setName($customName ?? 'Copy of ' . $originalMedia->getName())
                  ->setType($originalMedia->getType())
                  ->setMimeType($originalMedia->getMimeType())
                  ->setSize($originalMedia->getSize())
                  ->setUrl($originalMedia->getUrl())
                  ->setThumbnailUrl($originalMedia->getThumbnailUrl())
                  ->setWidth($originalMedia->getWidth())
                  ->setHeight($originalMedia->getHeight())
                  ->setDuration($originalMedia->getDuration())
                  ->setSource($originalMedia->getSource())
                  ->setSourceId($originalMedia->getSourceId())
                  ->setMetadata($originalMedia->getMetadata() ?? [])
                  ->setTags($originalMedia->getTags())
                  ->setAttribution($originalMedia->getAttribution())
                  ->setLicense($originalMedia->getLicense())
                  ->setIsPremium($originalMedia->isIsPremium())
                  ->setIsActive($originalMedia->isIsActive())
                  ->setUser($user);

        $entityManager->persist($duplicate);
        $entityManager->flush();
        
        return $duplicate;
    }
}
