<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Project entity operations.
 * 
 * Provides methods for querying and retrieving Project entities with various filters
 * and criteria. Handles project visibility, user ownership, tagging system, and
 * relationships with Design entities. Supports both private and public project operations.
 * 
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Find all projects belonging to a specific user.
     * 
     * Retrieves all projects owned by the specified user, excluding soft-deleted
     * projects. Results are ordered by most recently updated.
     * 
     * @param User $user The user whose projects to retrieve
     * @param int $limit Maximum number of results to return (default: 0 for no limit)
     * @param int $offset Number of results to skip for pagination (default: 0)
     * @param string $sortBy Field to sort by (default: 'updated')
     * @param string $sortOrder Sort direction (default: 'desc')
     * @return Project[] Array of Project entities belonging to the user
     */
    public function findByUser(User $user, int $limit = 0, int $offset = 0, string $sortBy = 'updated', string $sortOrder = 'desc'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user);

        // Sort by field
        switch ($sortBy) {
            case 'name':
                $qb->orderBy('p.title', $sortOrder);
                break;
            case 'created_at':
            case 'created':
                $qb->orderBy('p.createdAt', $sortOrder);
                break;
            case 'updated_at':
            case 'updated':
            default:
                $qb->orderBy('p.updatedAt', $sortOrder);
                break;
        }

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        if ($offset > 0) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a project by its UUID.
     * 
     * Searches for a project with the specified UUID, excluding soft-deleted projects.
     * UUIDs are used for public-facing project identification.
     * 
     * @param string $uuid The UUID of the project to find
     * @return Project|null The Project entity if found, null otherwise
     */
    public function findByUuid(string $uuid): ?Project
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a project by user and UUID.
     * 
     * Searches for a project owned by a specific user using its UUID.
     * This provides an additional security layer by ensuring the project
     * belongs to the specified user.
     * 
     * @param User $user The user who should own the project
     * @param string $uuid The UUID of the project to find
     * @return Project|null The Project entity if found and owned by user, null otherwise
     */
    public function findByUserAndUuid(User $user, string $uuid): ?Project
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.uuid = :uuid')
            ->setParameter('user', $user)
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search projects by name - compatible method for controller
     * 
     * @param string $search The search query
     * @param int $limit Maximum number of results to return (default: 20)
     * @param int $offset Number of results to skip for pagination (default: 0)
     * @return Project[] Array of Project entities matching the search criteria
     */
    public function searchByName(string $search, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.title LIKE :search OR p.description LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find projects by specific tags - compatible with controller usage
     * 
     * Retrieves projects that contain any of the specified tags.
     * Uses LIKE operations for database compatibility.
     * 
     * @param array $tags Array of tags that projects should contain
     * @param int $limit Maximum number of results to return (default: 20)
     * @param int $offset Number of results to skip for pagination (default: 0)
     * @return Project[] Array of Project entities containing specified tags
     */
    public function findByTags(array $tags, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('p');

        // Build tag conditions using LIKE for compatibility
        $tagConditions = [];
        foreach ($tags as $index => $tag) {
            $tagConditions[] = "p.tags LIKE :tag_{$index}";
            $qb->setParameter("tag_{$index}", '%"' . $tag . '"%');
        }

        if (!empty($tagConditions)) {
            $qb->andWhere(implode(' OR ', $tagConditions));
        }

        return $qb->orderBy('p.updatedAt', 'DESC')
                  ->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find public projects with optional filtering.
     * 
     * Retrieves publicly visible projects with optional search and tag filtering.
     * Supports pagination for large result sets. Used for the public gallery
     * and template browsing features.
     * 
     * @param string|null $search Optional search query for name/description
     * @param array|null $tags Optional array of tags to filter by
     * @param int $limit Maximum number of results to return (default: 20)
     * @param int $offset Number of results to skip for pagination (default: 0)
     * @return Project[] Array of public Project entities matching criteria
     */
    public function findPublicProjects(?string $search = null, ?array $tags = null, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isPublic = :public')
            ->setParameter('public', true);

        if ($search) {
            $qb->andWhere('p.title LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($tags) {
            // Use compatible tag search for all databases
            $tagConditions = [];
            foreach ($tags as $index => $tag) {
                $tagConditions[] = "p.tags LIKE :tag_{$index}";
                $qb->setParameter("tag_{$index}", '%"' . $tag . '"%');
            }
            if (!empty($tagConditions)) {
                $qb->andWhere(implode(' OR ', $tagConditions));
            }
        }

        return $qb->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent projects for a specific user.
     * 
     * Retrieves the most recently updated projects belonging to a user,
     * limited to the specified number of results. Used for dashboard
     * and quick access features.
     * 
     * @param User $user The user whose recent projects to retrieve
     * @param int $limit Maximum number of projects to return (default: 10)
     * @return Project[] Array of the user's most recent Project entities
     */
    public function findRecentProjects(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search user projects by name or description.
     * 
     * Performs a case-insensitive search on project names and descriptions
     * for projects belonging to the specified user. Uses LIKE pattern matching.
     * 
     * @param User $user The user whose projects to search
     * @param string $query The search query to match against project fields
     * @param int $limit Maximum number of results to return (default: 20)
     * @return Project[] Array of Project entities matching the search criteria
     */
    public function searchUserProjects(User $user, string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.title LIKE :query OR p.description LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find projects with their design count for a specific user.
     * 
     * Retrieves all projects owned by a user along with the count of designs
     * in each project. Useful for project management and analytics dashboards.
     * 
     * @param User $user The user whose projects to retrieve with design counts
     * @return array Array containing Project entities with designsCount field
     */
    public function findProjectsWithDesignCount(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'COUNT(d.id) as designsCount')
            ->leftJoin('p.designs', 'd')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->groupBy('p.id')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total projects for a specific user.
     * 
     * Returns the total number of projects owned by a user,
     * excluding soft-deleted projects. Used for user statistics.
     * 
     * @param User $user The user to count projects for
     * @return int Total number of projects owned by the user
     */
    public function countUserProjects(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count total public projects in the system.
     * 
     * Returns the total number of publicly visible projects,
     * excluding soft-deleted projects. Used for platform statistics.
     * 
     * @return int Total number of public projects
     */
    public function countPublicProjects(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isPublic = :public')
            ->setParameter('public', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find projects updated within a date range.
     * 
     * Retrieves projects that were updated between the specified start and end dates.
     * Useful for analytics and tracking project activity over time.
     * 
     * @param \DateTimeInterface $start The start date for the range
     * @param \DateTimeInterface $end The end date for the range
     * @return Project[] Array of Project entities updated within the date range
     */
    public function findProjectsUpdatedBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.updatedAt >= :start')
            ->andWhere('p.updatedAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Duplicate a project for a user
     * 
     * Creates a complete copy of a project including all its properties
     * but assigns it to the specified user as the new owner.
     * 
     * @param Project $originalProject The project to duplicate
     * @param User $newOwner The user who will own the duplicated project
     * @param string $newName The name for the duplicated project
     * @return Project The newly created duplicated project
     */
    public function duplicateProject(Project $originalProject, User $newOwner, string $newName): Project
    {
        $duplicatedProject = new Project();
        $duplicatedProject->setTitle($newName);  // Use setTitle which syncs with name
        $duplicatedProject->setDescription($originalProject->getDescription());
        $duplicatedProject->setUser($newOwner);
        $duplicatedProject->setIsPublic(false); // Always private initially
        $duplicatedProject->setTags($originalProject->getTags());
        $duplicatedProject->setThumbnail($originalProject->getThumbnail());

        $this->getEntityManager()->persist($duplicatedProject);
        $this->getEntityManager()->flush();

        return $duplicatedProject;
    }

    /**
     * Find the most popular tags across all public projects.
     * 
     * Analyzes all public projects to determine the most frequently used tags.
     * Uses simple string operations for database compatibility.
     * 
     * @param int $limit Maximum number of popular tags to return (default: 20)
     * @return array Array of tags with 'tag' and 'count' fields, ordered by popularity
     */
    public function findMostPopularTags(int $limit = 20): array
    {
        // Get all public projects with tags
        $projects = $this->createQueryBuilder('p')
            ->select('p.tags')
            ->andWhere('p.isPublic = :public')
            ->andWhere('p.tags IS NOT NULL')
            ->setParameter('public', true)
            ->getQuery()
            ->getResult();

        $tagCounts = [];
        foreach ($projects as $project) {
            $tags = $project['tags'];
            if (is_string($tags)) {
                $decodedTags = json_decode($tags, true);
                if (is_array($decodedTags)) {
                    foreach ($decodedTags as $tag) {
                        if (is_string($tag) && trim($tag) !== '') {
                            $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                        }
                    }
                }
            }
        }

        // Sort by count and limit results
        arsort($tagCounts);
        $result = [];
        $count = 0;
        foreach ($tagCounts as $tag => $tagCount) {
            if ($count >= $limit) {
                break;
            }
            $result[] = ['tag' => $tag, 'count' => $tagCount];
            $count++;
        }

        return $result;
    }
}
