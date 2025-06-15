<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Design;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Design entity operations.
 * 
 * Provides methods for querying and retrieving Design entities with various filters
 * and criteria. Handles soft deletes and maintains relationships with Project and User entities.
 * 
 * @extends ServiceEntityRepository<Design>
 */
class DesignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Design::class);
    }

    /**
     * Find all designs belonging to a specific project.
     * 
     * Retrieves designs that are associated with the given project,
     * excluding soft-deleted designs. Results are ordered by most recently updated.
     * 
     * @param Project $project The project to find designs for
     * @return Design[] Array of Design entities belonging to the project
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.project = :project')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a design by its UUID.
     * 
     * Searches for a design with the specified UUID, excluding soft-deleted designs.
     * 
     * @param string $uuid The UUID of the design to find
     * @return Design|null The Design entity if found, null otherwise
     */
    public function findByUuid(string $uuid): ?Design
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.uuid = :uuid')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a design by project and UUID.
     * 
     * Searches for a design within a specific project using its UUID.
     * This provides an additional security layer by ensuring the design
     * belongs to the specified project.
     * 
     * @param Project $project The project the design should belong to
     * @param string $uuid The UUID of the design to find
     * @return Design|null The Design entity if found within the project, null otherwise
     */
    public function findByProjectAndUuid(Project $project, string $uuid): ?Design
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.project = :project')
            ->andWhere('d.uuid = :uuid')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds all designs associated with a specific user.
     *
     * @param User $user The user whose designs are to be retrieved.
     * @return Design[] Array of Design entities associated with the user.
     */

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent designs for a specific user.
     * 
     * Retrieves the most recently updated designs belonging to a user,
     * limited to the specified number of results. Only includes designs
     * from non-deleted projects.
     * 
     * @param User $user The user whose designs to retrieve
     * @param int $limit Maximum number of designs to return (default: 10)
     * @return Design[] Array of the user's most recent Design entities
     */
    public function findRecentDesigns(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search designs by name for a specific user.
     * 
     * Performs a case-insensitive search on design names for designs
     * belonging to the specified user. Uses LIKE pattern matching.
     * 
     * @param User $user The user whose designs to search
     * @param string $query The search query to match against design names
     * @param int $limit Maximum number of results to return (default: 20)
     * @return Design[] Array of Design entities matching the search criteria
     */
    public function searchUserDesigns(User $user, string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.name LIKE :query')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find designs with their layer count for a specific project.
     * 
     * Retrieves all designs within a project along with the count of layers
     * for each design. Useful for understanding design complexity.
     * 
     * @param Project $project The project to get designs from
     * @return array Array containing Design entities with layersCount field
     */
    public function findDesignsWithLayerCount(Project $project): array
    {
        return $this->createQueryBuilder('d')
            ->select('d', 'COUNT(l.id) as layersCount')
            ->leftJoin('d.layers', 'l')
            ->andWhere('d.project = :project')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->groupBy('d.id')
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find animated designs for a specific user.
     * 
     * Retrieves all designs that have animations enabled, belonging to the
     * specified user. Useful for filtering animated content.
     * 
     * @param User $user The user whose animated designs to retrieve
     * @return Design[] Array of animated Design entities
     */
    public function findAnimatedDesigns(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.hasAnimation = :animated')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('animated', true)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find designs by dimensions with tolerance.
     * 
     * Searches for designs that have dimensions within a specified tolerance
     * of the given width and height. Useful for finding designs of similar sizes.
     * 
     * @param int $width The target width in pixels
     * @param int $height The target height in pixels
     * @param int $tolerance The allowed deviation in pixels (default: 50)
     * @return Design[] Array of Design entities matching the dimension criteria
     */
    public function findByDimensions(int $width, int $height, int $tolerance = 50): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.width BETWEEN :minWidth AND :maxWidth')
            ->andWhere('d.height BETWEEN :minHeight AND :maxHeight')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('minWidth', $width - $tolerance)
            ->setParameter('maxWidth', $width + $tolerance)
            ->setParameter('minHeight', $height - $tolerance)
            ->setParameter('maxHeight', $height + $tolerance)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total designs for a specific user.
     * 
     * Returns the total number of designs belonging to a user across all
     * their projects, excluding soft-deleted designs and projects.
     * 
     * @param User $user The user to count designs for
     * @return int Total number of designs owned by the user
     */
    public function countUserDesigns(User $user): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count designs within a specific project.
     * 
     * Returns the total number of designs belonging to a specific project,
     * excluding soft-deleted designs.
     * 
     * @param Project $project The project to count designs for
     * @return int Total number of designs in the project
     */
    public function countProjectDesigns(Project $project): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.project = :project')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find designs updated within a date range.
     * 
     * Retrieves designs that were updated between the specified start and end dates.
     * Useful for analytics and tracking design activity over time.
     * 
     * @param \DateTimeInterface $start The start date for the range
     * @param \DateTimeInterface $end The end date for the range
     * @return Design[] Array of Design entities updated within the date range
     */
    public function findDesignsUpdatedBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.updatedAt >= :start')
            ->andWhere('d.updatedAt <= :end')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find complex designs with many layers.
     * 
     * Retrieves designs that have a minimum number of layers, indicating
     * complexity. Results include the layer count for each design.
     * 
     * @param int $minLayers Minimum number of layers required (default: 10)
     * @return array Array containing Design entities with layersCount field
     */
    public function findComplexDesigns(int $minLayers = 10): array
    {
        return $this->createQueryBuilder('d')
            ->select('d', 'COUNT(l.id) as layersCount')
            ->leftJoin('d.layers', 'l')
            ->andWhere('d.deletedAt IS NULL')
            ->groupBy('d.id')
            ->having('layersCount >= :minLayers')
            ->setParameter('minLayers', $minLayers)
            ->orderBy('layersCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find designs with similar dimensions to a given design.
     * 
     * Searches for designs that have the same width and height as the provided
     * design, excluding the design itself. Useful for finding templates or
     * designs with matching canvas sizes.
     * 
     * @param Design $design The reference design to compare dimensions against
     * @return Design[] Array of Design entities with matching dimensions
     */
    public function findDuplicateDesigns(Design $design): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.id != :currentId')
            ->andWhere('d.width = :width')
            ->andWhere('d.height = :height')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('currentId', $design->getId())
            ->setParameter('width', $design->getWidth())
            ->setParameter('height', $design->getHeight())
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the most popular canvas sizes.
     * 
     * Analyzes all designs to determine the most commonly used canvas dimensions.
     * Returns dimension pairs with their usage count, ordered by popularity.
     * 
     * @param int $limit Maximum number of canvas sizes to return (default: 10)
     * @return array Array of dimensions with width, height, and count fields
     */
    public function findPopularCanvasSizes(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.width as width', 'd.height as height', 'COUNT(d.id) as count')
            ->andWhere('d.deletedAt IS NULL')
            ->groupBy('d.width', 'd.height')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search designs by name across all public designs and user's designs.
     * 
     * Performs a case-insensitive search on design names. Searches across
     * all public designs and the user's own designs, with proper access control.
     * 
     * @param string $query The search query to match against design names
     * @param int $limit Maximum number of results to return (default: 20)
     * @param int $offset Number of results to skip for pagination (default: 0)
     * @return Design[] Array of Design entities matching the search criteria
     */
    public function searchByName(string $query, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('d.name LIKE :query')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Duplicate a design by creating a copy with a new name and project.
     * 
     * Creates an exact copy of the given design including all its data,
     * layers, and properties, but assigns it to a different project and name.
     * 
     * @param Design $originalDesign The design to duplicate
     * @param Project $targetProject The project to assign the duplicated design to
     * @param string $newName The name for the duplicated design
     * @return Design The newly created duplicated design
     */
    public function duplicateDesign(Design $originalDesign, Project $targetProject, string $newName): Design
    {
        $duplicatedDesign = new Design();
        $duplicatedDesign->setName($newName);
        $duplicatedDesign->setDescription($originalDesign->getDescription());
        $duplicatedDesign->setProject($targetProject);
        $duplicatedDesign->setWidth($originalDesign->getWidth());
        $duplicatedDesign->setHeight($originalDesign->getHeight());
        $duplicatedDesign->setData($originalDesign->getData());
        $duplicatedDesign->setBackground($originalDesign->getBackground());
        $duplicatedDesign->setHasAnimation($originalDesign->getHasAnimation());
        $duplicatedDesign->setFps($originalDesign->getFps());
        $duplicatedDesign->setDuration($originalDesign->getDuration());
        
        // UUID is automatically generated in constructor - no need to set it manually
        
        // Persist the new design
        $this->getEntityManager()->persist($duplicatedDesign);
        $this->getEntityManager()->flush();
        
        return $duplicatedDesign;
    }

    /**
     * Find designs belonging to a specific project with pagination.
     * 
     * Retrieves designs that are associated with the given project,
     * excluding soft-deleted designs. Results are ordered by most recently updated.
     * 
     * @param Project $project The project to find designs for
     * @param int $page Page number (1-based)
     * @param int $limit Number of results per page
     * @return array Array containing designs and pagination info
     */
    public function findByProjectPaginated(Project $project, int $page = 1, int $limit = 10): array
    {
        $query = $this->createQueryBuilder('d')
            ->andWhere('d.project = :project')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->orderBy('d.updatedAt', 'DESC');

        // Get total count
        $totalQuery = clone $query;
        $total = $totalQuery->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();

        // Apply pagination
        $designs = $query
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'designs' => $designs,
            'total' => (int) $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int) ceil($total / $limit)
        ];
    }

    /**
     * Find designs belonging to a specific user with pagination.
     * 
     * Retrieves designs that are associated with the given user,
     * excluding soft-deleted designs. Results are ordered by most recently updated.
     * 
     * @param User $user The user whose designs are to be retrieved
     * @param int $page Page number (1-based)
     * @param int $limit Number of results per page
     * @return array Array containing designs and pagination info
     */
    public function findByUserPaginated(User $user, int $page = 1, int $limit = 10): array
    {
        $query = $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('d.updatedAt', 'DESC');

        // Get total count
        $totalQuery = clone $query;
        $total = $totalQuery->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();

        // Apply pagination
        $designs = $query
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'designs' => $designs,
            'total' => (int) $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int) ceil($total / $limit)
        ];
    }
}
