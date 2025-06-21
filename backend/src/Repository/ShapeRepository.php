<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Shape;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Shape Repository
 * 
 * Provides database operations for Shape entities including search and filtering capabilities.
 */
class ShapeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shape::class);
    }

    /**
     * Find shapes by search criteria with pagination
     * 
     * @param string $query Search term to match against filename, keywords, description, category, shape category
     * @param int $page Page number (1-based)
     * @param int $limit Number of results per page
     * @return Shape[]
     */
    public function findBySearch(
        string $query = '',
        int $page = 1,
        int $limit = 20
    ): array {
        $qb = $this->createSearchQueryBuilder($query);
        
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit)
           ->orderBy('s.originalFilename', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Count shapes matching search criteria
     * 
     * @param string $query Search term to match against filename, keywords, description, category, shape category
     * @return int
     */
    public function countBySearch(string $query = ''): int {
        $qb = $this->createSearchQueryBuilder($query);
        $qb->select('COUNT(s.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all available categories
     * 
     * @return string[]
     */
    public function getCategories(): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('DISTINCT s.category')
           ->orderBy('s.category', 'ASC');

        $results = $qb->getQuery()->getScalarResult();
        return array_column($results, 'category');
    }

    /**
     * Get all available shape categories
     * 
     * @return string[]
     */
    public function getShapeCategories(): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('DISTINCT s.shapeCategory')
           ->orderBy('s.shapeCategory', 'ASC');

        $results = $qb->getQuery()->getScalarResult();
        return array_column($results, 'shapeCategory');
    }

    /**
     * Get popular shapes (most commonly used categories)
     * 
     * @param int $limit Number of shapes to return
     * @return Shape[]
     */
    public function getPopularShapes(int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.category IN (:popularCategories)')
           ->setParameter('popularCategories', ['mostlyused', 'basic', 'essential'])
           ->orderBy('s.originalFilename', 'ASC')
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find shapes by exact category
     * 
     * @param string $category The category to filter by
     * @param int $page Page number (1-based)
     * @param int $limit Number of results per page
     * @return Shape[]
     */
    public function findByCategory(string $category, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.category = :category')
           ->setParameter('category', $category)
           ->orderBy('s.originalFilename', 'ASC');

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Count shapes by category
     * 
     * @param string $category The category to count
     * @return int
     */
    public function countByCategory(string $category): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)')
           ->where('s.category = :category')
           ->setParameter('category', $category);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find shapes by keyword
     * 
     * @param string $keyword The keyword to search for
     * @param int $page Page number (1-based)
     * @param int $limit Number of results per page
     * @return Shape[]
     */
    public function findByKeyword(string $keyword, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.keywords LIKE :keyword')
           ->setParameter('keyword', '%' . $keyword . '%')
           ->orderBy('s.originalFilename', 'ASC');

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get statistics about shapes in the database
     * 
     * @return array{total: int, categories: int, averageSize: float}
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as total, AVG(s.fileSize) as averageSize');
        
        $result = $qb->getQuery()->getSingleResult();
        
        $categoriesQb = $this->createQueryBuilder('s');
        $categoriesQb->select('COUNT(DISTINCT s.category) as categories');
        $categoriesResult = $categoriesQb->getQuery()->getSingleResult();

        return [
            'total' => (int) $result['total'],
            'categories' => (int) $categoriesResult['categories'],
            'averageSize' => (float) $result['averageSize']
        ];
    }

    /**
     * Create base query builder for search operations
     * 
     * This method creates a comprehensive search across all relevant shape fields:
     * - Original filename and normalized filename
     * - Category and shape category  
     * - Description text
     * - Keywords array
     */
    private function createSearchQueryBuilder(
        string $query = '',
        ?string $category = null,
        ?string $shapeCategory = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s');

        if (!empty($query)) {
            $qb->where(
                $qb->expr()->orX(
                    // Search in filenames
                    $qb->expr()->like('s.originalFilename', ':query'),
                    $qb->expr()->like('s.normalizedFilename', ':query'),
                    // Search in categories - this allows searching by category name
                    $qb->expr()->like('s.category', ':query'),
                    $qb->expr()->like('s.shapeCategory', ':query'),
                    // Search in description
                    $qb->expr()->like('s.description', ':query'),
                    // Search in keywords (JSON field)
                    $qb->expr()->like('s.keywords', ':queryKeyword')
                )
            )
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('queryKeyword', '%' . $query . '%');
        }

        if ($category !== null) {
            $qb->andWhere('s.category = :category')
               ->setParameter('category', $category);
        }

        if ($shapeCategory !== null) {
            $qb->andWhere('s.shapeCategory = :shapeCategory')
               ->setParameter('shapeCategory', $shapeCategory);
        }

        return $qb;
    }

    /**
     * Save a shape entity
     */
    public function save(Shape $shape): void
    {
        $this->getEntityManager()->persist($shape);
        $this->getEntityManager()->flush();
    }

    /**
     * Remove a shape entity
     */
    public function remove(Shape $shape): void
    {
        $this->getEntityManager()->remove($shape);
        $this->getEntityManager()->flush();
    }

    /**
     * Check if a shape with the given path already exists
     */
    public function existsByPath(string $path): bool
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id)')
           ->where('s.path = :path')
           ->setParameter('path', $path);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Batch insert shapes for better performance during import
     * 
     * @param Shape[] $shapes
     */
    public function batchSave(array $shapes): void
    {
        $em = $this->getEntityManager();
        
        foreach ($shapes as $i => $shape) {
            $em->persist($shape);
            
            // Flush every 50 entities to prevent memory issues
            if ($i % 50 === 0) {
                $em->flush();
                $em->clear();
            }
        }
        
        // Flush remaining entities
        $em->flush();
        $em->clear();
    }
}
