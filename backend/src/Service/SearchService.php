<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\MediaRepository;
use App\Repository\TemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * SearchService provides comprehensive search functionality across projects, templates, and media.
 * 
 * This service handles:
 * - Full-text search across multiple entity types
 * - Type-specific searches with proper filtering
 * - Search suggestions for autocomplete functionality
 * - Pagination and result formatting
 * - User-scoped searches for security
 * 
 * @author GitHub Copilot
 * @version 1.0
 */
readonly class SearchService
{
    /**
     * @param EntityManagerInterface $entityManager Doctrine entity manager for database operations
     * @param ProjectRepository $projectRepository Repository for project-related queries
     * @param MediaRepository $mediaRepository Repository for media-related queries
     * @param TemplateRepository $templateRepository Repository for template-related queries
     * @param LoggerInterface $logger Logger for search activity tracking
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository,
        private MediaRepository $mediaRepository,
        private TemplateRepository $templateRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Performs a comprehensive search across all entity types or a specific type.
     * 
     * @param User $user The user performing the search (for access control)
     * @param string $query The search query string
     * @param string $type The type of search: 'all', 'projects', 'templates', or 'media'
     * @param int $page The page number for pagination (1-based)
     * @param int $limit The number of results per page
     * 
     * @return array{items: array, total: int} Search results with items and total count
     */
    public function search(User $user, string $query, string $type = 'all', int $page = 1, int $limit = 20): array
    {
        // Initialize default result structure
        $results = [
            'items' => [],
            'total' => 0
        ];

        // Route to appropriate search method based on type
        switch ($type) {
            case 'projects':
                $results = $this->searchProjects($user, $query, $page, $limit);
                break;
            case 'templates':
                // Search templates without category/tag filters for general search
                $results = $this->searchTemplates($user, $query, '', '', $page, $limit);
                break;
            case 'media':
                // Search media without type filter for general search
                $results = $this->searchMedia($user, $query, '', $page, $limit);
                break;
            case 'all':
            default:
                // Perform combined search across all entity types
                $results = $this->searchAll($user, $query, $page, $limit);
                break;
        }

        // Log search activity for analytics and debugging
        $this->logger->info('Search performed', [
            'user_id' => $user->getId(),
            'query' => $query,
            'type' => $type,
            'results_count' => count($results['items']),
            'total' => $results['total']
        ]);

        return $results;
    }

    /**
     * Searches user's projects by name and description.
     * 
     * @param User $user The user whose projects to search
     * @param string $query The search query string
     * @param int $page The page number for pagination (1-based)
     * @param int $limit The number of results per page
     * 
     * @return array{items: array, total: int} Formatted project results with pagination info
     */
    public function searchProjects(User $user, string $query, int $page = 1, int $limit = 20): array
    {
        // Calculate offset for pagination
        $offset = ($page - 1) * $limit;

        // Build query to search user's projects by name and description
        $qb = $this->projectRepository->createQueryBuilder('p')
            ->where('p.user = :user') // Security: only search user's own projects
            ->andWhere('p.name LIKE :query OR p.description LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%') // Use wildcards for partial matching
            ->orderBy('p.updatedAt', 'DESC'); // Most recently updated first

        // Clone query for counting total results (without pagination)
        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        // Apply pagination and execute query
        $projects = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => array_map([$this, 'formatProject'], $projects),
            'total' => (int) $total
        ];
    }

    /**
     * Searches public templates by name, description, tags, and category.
     * 
     * @param User $user The user performing the search (for future personalization)
     * @param string $query The search query string
     * @param string $category Optional category filter
     * @param string $tags Comma-separated tags filter
     * @param int $page The page number for pagination (1-based)
     * @param int $limit The number of results per page
     * 
     * @return array{items: array, total: int} Formatted template results with pagination info
     */
    public function searchTemplates(User $user, string $query, string $category = '', string $tags = '', int $page = 1, int $limit = 20): array
    {
        // Calculate offset for pagination
        $offset = ($page - 1) * $limit;

        // Build base query - only search active (published) templates
        $qb = $this->templateRepository->createQueryBuilder('t')
            ->where('t.is_active = :active')
            ->setParameter('active', true);

        // Add text search if query provided
        if (!empty($query)) {
            $qb->andWhere('t.name LIKE :query OR t.description LIKE :query OR t.tags LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        // Add category filter if specified
        if (!empty($category)) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }

        // Add tag-based filtering if specified
        if (!empty($tags)) {
            $tagArray = explode(',', $tags);
            foreach ($tagArray as $index => $tag) {
                $paramName = 'tag' . $index;
                // Each tag must be present in the template's tags
                $qb->andWhere('t.tags LIKE :' . $paramName)
                   ->setParameter($paramName, '%' . trim($tag) . '%');
            }
        }

        // Order by popularity (usage_count) first, then by recency
        $qb->orderBy('t.usage_count', 'DESC')
           ->addOrderBy('t.created_at', 'DESC');

        // Clone query for counting total results
        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

        // Apply pagination and execute query
        $templates = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => array_map([$this, 'formatTemplate'], $templates),
            'total' => (int) $total
        ];
    }

    /**
     * Searches user's media files by name and tags.
     * 
     * @param User $user The user whose media to search
     * @param string $query The search query string
     * @param string $type Optional media type filter (image, video, etc.)
     * @param int $page The page number for pagination (1-based)
     * @param int $limit The number of results per page
     * 
     * @return array{items: array, total: int} Formatted media results with pagination info
     */
    public function searchMedia(User $user, string $query, string $type = '', int $page = 1, int $limit = 20): array
    {
        // Calculate offset for pagination
        $offset = ($page - 1) * $limit;

        // Build base query - only search user's own media for security
        $qb = $this->mediaRepository->createQueryBuilder('m')
            ->where('m.user = :user')
            ->setParameter('user', $user);

        // Add text search if query provided (search in name and tags)
        if (!empty($query)) {
            $qb->andWhere('m.name LIKE :query OR m.tags LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        // Add media type filter if specified (image, video, audio, etc.)
        if (!empty($type)) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $type);
        }

        // Order by creation date, newest first
        $qb->orderBy('m.created_at', 'DESC');

        // Clone query for counting total results
        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

        // Apply pagination and execute query
        $media = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => array_map([$this, 'formatMedia'], $media),
            'total' => (int) $total
        ];
    }

    /**
     * Generates search suggestions for autocomplete functionality.
     * 
     * Returns suggestions from project names and template names that match
     * the partial query. Requires at least 2 characters to prevent too
     * many suggestions.
     * 
     * @param User $user The user to get personalized suggestions for
     * @param string $query The partial search query (minimum 2 characters)
     * @param int $limit Maximum number of suggestions to return
     * 
     * @return array<int, array{text: string, type: string}> Array of suggestion objects
     */
    public function getSearchSuggestions(User $user, string $query, int $limit = 10): array
    {
        // Require minimum 2 characters to prevent excessive suggestions
        if (strlen($query) < 2) {
            return [];
        }

        $suggestions = [];

        // Get project name suggestions from user's projects
        $projects = $this->projectRepository->createQueryBuilder('p')
            ->select('p.name')
            ->where('p.user = :user')
            ->andWhere('p.name LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Add project suggestions to results
        foreach ($projects as $project) {
            $suggestions[] = [
                'text' => $project['name'],
                'type' => 'project'
            ];
        }

        // Get template name suggestions from active templates
        $templates = $this->templateRepository->createQueryBuilder('t')
            ->select('t.name')
            ->where('t.is_active = :active')
            ->andWhere('t.name LIKE :query')
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Add template suggestions to results
        foreach ($templates as $template) {
            $suggestions[] = [
                'text' => $template['name'],
                'type' => 'template'
            ];
        }

        // Limit total suggestions to requested amount
        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Performs a combined search across all entity types with distributed results.
     * 
     * Allocates roughly 1/3 of results to each entity type (projects, templates, media)
     * to provide balanced search results. The actual pagination is applied to the
     * combined results after merging.
     * 
     * @param User $user The user performing the search
     * @param string $query The search query string
     * @param int $page The page number for pagination (1-based)
     * @param int $limit The number of results per page
     * 
     * @return array{items: array, total: int} Combined and paginated results
     */
    private function searchAll(User $user, string $query, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $results = [];

        // Distribute search results across entity types for balanced results
        // Each entity type gets roughly 1/3 of the requested results

        // Search projects (limit to 1/3 of results)
        $projectLimit = (int) ceil($limit / 3);
        $projectResults = $this->searchProjects($user, $query, 1, $projectLimit);
        
        // Search templates (limit to 1/3 of results)
        $templateLimit = (int) ceil($limit / 3);
        $templateResults = $this->searchTemplates($user, $query, '', '', 1, $templateLimit);
        
        // Search media (use remaining allocation)
        $mediaLimit = $limit - count($projectResults['items']) - count($templateResults['items']);
        $mediaResults = $this->searchMedia($user, $query, '', 1, max(1, $mediaLimit));

        // Combine all results into a single array with type indicators
        $allResults = [];
        
        // Add projects with result type marker
        foreach ($projectResults['items'] as $item) {
            $item['result_type'] = 'project';
            $allResults[] = $item;
        }
        
        // Add templates with result type marker
        foreach ($templateResults['items'] as $item) {
            $item['result_type'] = 'template';
            $allResults[] = $item;
        }
        
        // Add media with result type marker
        foreach ($mediaResults['items'] as $item) {
            $item['result_type'] = 'media';
            $allResults[] = $item;
        }

        // Calculate total across all entity types
        $total = $projectResults['total'] + $templateResults['total'] + $mediaResults['total'];

        // Apply final pagination to combined results
        return [
            'items' => array_slice($allResults, $offset, $limit),
            'total' => $total
        ];
    }

    /**
     * Formats a Project entity for API response.
     * 
     * @param object $project The Project entity to format
     * 
     * @return array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     thumbnail: ?string,
     *     updatedAt: string,
     *     type: string
     * } Formatted project data
     */
    private function formatProject(object $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'thumbnail' => $project->getThumbnail(), // Project thumbnail URL
            'updatedAt' => $project->getUpdatedAt()->format('c'), // ISO 8601 format
            'type' => 'project'
        ];
    }

    /**
     * Formats a Template entity for API response.
     * 
     * @param object $template The Template entity to format
     * 
     * @return array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     thumbnail_url: ?string,
     *     category: string,
     *     tags: array,
     *     is_premium: bool,
     *     type: string
     * } Formatted template data
     */
    private function formatTemplate(object $template): array
    {
        return [
            'id' => $template->getId(),
            'name' => $template->getName(),
            'description' => $template->getDescription(),
            'thumbnail_url' => $template->getThumbnailUrl(), // Template preview image
            'category' => $template->getCategory(), // Template category (social-media, print, etc.)
            'tags' => $template->getTags(), // Array of tags for filtering
            'is_premium' => $template->isPremium(), // Whether template requires premium access
            'type' => 'template'
        ];
    }

    /**
     * Formats a Media entity for API response.
     * 
     * @param object $media The Media entity to format
     * 
     * @return array{
     *     id: int,
     *     name: string,
     *     type: string,
     *     mime_type: string,
     *     size: int,
     *     url: string,
     *     thumbnail_url: ?string,
     *     tags: ?array,
     *     created_at: ?string,
     *     type: string
     * } Formatted media data
     */
    private function formatMedia(object $media): array
    {
        return [
            'id' => $media->getId(),
            'name' => $media->getName(), // Original filename or user-assigned name
            'type' => $media->getType(), // Media type: image, video, audio, etc.
            'mime_type' => $media->getMimeType(), // Specific MIME type (image/jpeg, video/mp4, etc.)
            'size' => $media->getSize(), // File size in bytes
            'url' => $media->getUrl(), // Direct URL to media file
            'thumbnail_url' => $media->getThumbnailUrl(), // URL to thumbnail/preview
            'tags' => $media->getTags(), // User-assigned tags for organization
            'created_at' => $media->getCreatedAt()?->format('Y-m-d H:i:s'), // Upload timestamp
            'type' => 'media'
        ];
    }
}
