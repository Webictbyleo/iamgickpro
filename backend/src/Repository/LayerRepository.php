<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Layer;
use App\Entity\Design;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Layer entity operations.
 * 
 * Provides methods for querying and manipulating Layer entities within designs.
 * Handles layer hierarchy, z-index management, spatial queries, and layer transformations.
 * Supports complex layer operations including animation, masking, and blend modes.
 * 
 * @extends ServiceEntityRepository<Layer>
 */
class LayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Layer::class);
    }

    /**
     * Find all layers belonging to a specific design.
     * 
     * Retrieves all layers within a design, ordered by z-index (bottom to top)
     * and creation date. This maintains the proper layer stacking order.
     * 
     * @param Design $design The design to find layers for
     * @return Layer[] Array of Layer entities ordered by z-index and creation date
     */
    public function findByDesign(Design $design): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->setParameter('design', $design)
            ->orderBy('l.zIndex', 'ASC')
            ->addOrderBy('l.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a layer by its UUID.
     * 
     * Searches for a layer with the specified UUID. UUIDs are used for
     * client-side layer identification and API operations.
     * 
     * @param string $uuid The UUID of the layer to find
     * @return Layer|null The Layer entity if found, null otherwise
     */
    public function findByUuid(string $uuid): ?Layer
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a layer by design and UUID.
     * 
     * Searches for a layer within a specific design using its UUID.
     * This provides an additional security layer by ensuring the layer
     * belongs to the specified design.
     * 
     * @param Design $design The design the layer should belong to
     * @param string $uuid The UUID of the layer to find
     * @return Layer|null The Layer entity if found within the design, null otherwise
     */
    public function findByDesignAndUuid(Design $design, string $uuid): ?Layer
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->andWhere('l.uuid = :uuid')
            ->setParameter('design', $design)
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find root layers (layers without a parent) in a design.
     * 
     * Retrieves only the top-level layers that don't have a parent layer.
     * Useful for building layer hierarchies and tree structures in the UI.
     * 
     * @param Design $design The design to find root layers for
     * @return Layer[] Array of root Layer entities ordered by z-index
     */
    public function findRootLayers(Design $design): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->andWhere('l.parent IS NULL')
            ->setParameter('design', $design)
            ->orderBy('l.zIndex', 'ASC')
            ->addOrderBy('l.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find child layers of a specific parent layer.
     * 
     * Retrieves all layers that are children of the specified parent layer.
     * Used for building hierarchical layer structures and group operations.
     * 
     * @param Layer $parent The parent layer to find children for
     * @return Layer[] Array of child Layer entities ordered by z-index
     */
    public function findChildren(Layer $parent): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('l.zIndex', 'ASC')
            ->addOrderBy('l.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find layers by type within a design.
     * 
     * Retrieves all layers of a specific type (e.g., 'text', 'image', 'shape')
     * within a design. Useful for type-specific operations and filtering.
     * 
     * @param Design $design The design to search within
     * @param string $type The layer type to filter by
     * @return Layer[] Array of Layer entities of the specified type
     */
    public function findByType(Design $design, string $type): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->andWhere('l.type = :type')
            ->setParameter('design', $design)
            ->setParameter('type', $type)
            ->orderBy('l.zIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find visible layers within a design.
     * 
     * Retrieves only layers that are currently visible (not hidden).
     * Used for rendering operations and export functionality.
     * 
     * @param Design $design The design to find visible layers for
     * @return Layer[] Array of visible Layer entities ordered by z-index
     */
    public function findVisibleLayers(Design $design): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->andWhere('l.visible = :visible')
            ->setParameter('design', $design)
            ->setParameter('visible', true)
            ->orderBy('l.zIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find layers with animations within a design.
     * 
     * Retrieves layers that have animation data defined. Uses JSON functions
     * to check for non-empty animations array. Used for animation processing.
     * 
     * @param Design $design The design to find animated layers for
     * @return Layer[] Array of Layer entities that have animations
     */
    public function findLayersWithAnimation(Design $design): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->andWhere('l.animations IS NOT NULL')
            ->andWhere('JSON_LENGTH(l.animations) > 0')
            ->setParameter('design', $design)
            ->orderBy('l.zIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find locked layers within a design.
     * 
     * Retrieves layers that are locked and cannot be edited.
     * Used for layer management and preventing accidental modifications.
     * 
     * @param Design $design The design to find locked layers for
     * @return Layer[] Array of locked Layer entities ordered by z-index
     */
    public function findLockedLayers(Design $design): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->andWhere('l.locked = :locked')
            ->setParameter('design', $design)
            ->setParameter('locked', true)
            ->orderBy('l.zIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find layers within a specific area of the canvas.
     * 
     * Performs spatial query to find layers whose position intersects with
     * the specified rectangular area. Uses JSON extraction to query transform
     * coordinates. Useful for selection operations and collision detection.
     * 
     * @param Design $design The design to search within
     * @param float $x The x-coordinate of the area's top-left corner
     * @param float $y The y-coordinate of the area's top-left corner
     * @param float $width The width of the search area
     * @param float $height The height of the search area
     * @return Layer[] Array of Layer entities within the specified area
     */
    public function findLayersInArea(Design $design, float $x, float $y, float $width, float $height): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->andWhere("JSON_EXTRACT(l.transform, '$.x') >= :minX")
            ->andWhere("JSON_EXTRACT(l.transform, '$.x') <= :maxX")
            ->andWhere("JSON_EXTRACT(l.transform, '$.y') >= :minY")
            ->andWhere("JSON_EXTRACT(l.transform, '$.y') <= :maxY")
            ->setParameter('design', $design)
            ->setParameter('minX', $x)
            ->setParameter('maxX', $x + $width)
            ->setParameter('minY', $y)
            ->setParameter('maxY', $y + $height)
            ->orderBy('l.zIndex', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find layers within a specific z-index range.
     * 
     * Retrieves layers that have z-index values within the specified range.
     * Useful for bulk operations on layer groups or specific depth ranges.
     * 
     * @param Design $design The design to search within
     * @param int $minZ The minimum z-index value (inclusive)
     * @param int $maxZ The maximum z-index value (inclusive)
     * @return Layer[] Array of Layer entities within the z-index range
     */
    public function findLayersByZIndexRange(Design $design, int $minZ, int $maxZ): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->andWhere('l.zIndex >= :minZ AND l.zIndex <= :maxZ')
            ->setParameter('design', $design)
            ->setParameter('minZ', $minZ)
            ->setParameter('maxZ', $maxZ)
            ->orderBy('l.zIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the maximum z-index value for layers in a design.
     * 
     * Returns the highest z-index value among all layers in the design.
     * Used for adding new layers on top of existing ones.
     * 
     * @param Design $design The design to find the maximum z-index for
     * @return int The maximum z-index value, or 0 if no layers exist
     */
    public function getMaxZIndex(Design $design): int
    {
        $result = $this->createQueryBuilder('l')
            ->select('MAX(l.zIndex)')
            ->andWhere('l.design = :design')
            ->setParameter('design', $design)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Get the minimum z-index value for layers in a design.
     * 
     * Returns the lowest z-index value among all layers in the design.
     * Used for adding new layers at the bottom of the stack.
     * 
     * @param Design $design The design to find the minimum z-index for
     * @return int The minimum z-index value, or 0 if no layers exist
     */
    public function getMinZIndex(Design $design): int
    {
        $result = $this->createQueryBuilder('l')
            ->select('MIN(l.zIndex)')
            ->andWhere('l.design = :design')
            ->setParameter('design', $design)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Count layers by type within a design.
     * 
     * Returns statistics showing how many layers of each type exist in the design.
     * Useful for analytics and understanding design composition.
     * 
     * @param Design $design The design to analyze
     * @return array Array with type and count fields, ordered by count (highest first)
     */
    public function countLayersByType(Design $design): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.type', 'COUNT(l.id) as count')
            ->andWhere('l.design = :design')
            ->setParameter('design', $design)
            ->groupBy('l.type')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total layers in a design.
     * 
     * Returns the total number of layers within the specified design.
     * Used for performance considerations and design complexity analysis.
     * 
     * @param Design $design The design to count layers for
     * @return int Total number of layers in the design
     */
    public function countDesignLayers(Design $design): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.design = :design')
            ->setParameter('design', $design)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find layers that have mask applied.
     * 
     * Retrieves layers that have masking effects applied to them.
     * Used for advanced layer effects and rendering operations.
     * 
     * @param Design $design The design to find masked layers for
     * @return Layer[] Array of Layer entities that have masks applied
     */
    public function findLayersWithMask(Design $design): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->andWhere('l.mask IS NOT NULL')
            ->setParameter('design', $design)
            ->orderBy('l.zIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find layers with specific blend modes.
     * 
     * Retrieves layers that use specific blend modes or any non-normal blend mode.
     * Used for advanced rendering and layer effect processing.
     * 
     * @param Design $design The design to search within
     * @param string|null $blendMode Specific blend mode to filter by, or null for any non-normal
     * @return Layer[] Array of Layer entities with the specified blend mode
     */
    public function findLayersWithBlendMode(Design $design, ?string $blendMode = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.design = :design')
            ->setParameter('design', $design);

        if ($blendMode) {
            $qb->andWhere('l.blendMode = :blendMode')
               ->setParameter('blendMode', $blendMode);
        } else {
            $qb->andWhere('l.blendMode IS NOT NULL')
               ->andWhere('l.blendMode != :normal')
               ->setParameter('normal', 'normal');
        }

        return $qb->orderBy('l.zIndex', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Move a layer to a specific z-index position.
     * 
     * Repositions a layer to a new z-index while automatically adjusting
     * the z-indexes of other layers to maintain proper ordering. Handles
     * both moving layers up and down in the stack.
     * 
     * @param Layer $layer The layer to move
     * @param int $newZIndex The target z-index position
     */
    public function moveLayerToIndex(Layer $layer, int $newZIndex): void
    {
        $design = $layer->getDesign();
        $currentZIndex = $layer->getZIndex();

        if ($newZIndex === $currentZIndex) {
            return;
        }

        $em = $this->getEntityManager();

        if ($newZIndex > $currentZIndex) {
            // Moving up: shift layers down
            $em->createQueryBuilder()
                ->update(Layer::class, 'l')
                ->set('l.zIndex', 'l.zIndex - 1')
                ->where('l.design = :design')
                ->andWhere('l.zIndex > :current AND l.zIndex <= :new')
                ->setParameter('design', $design)
                ->setParameter('current', $currentZIndex)
                ->setParameter('new', $newZIndex)
                ->getQuery()
                ->execute();
        } else {
            // Moving down: shift layers up
            $em->createQueryBuilder()
                ->update(Layer::class, 'l')
                ->set('l.zIndex', 'l.zIndex + 1')
                ->where('l.design = :design')
                ->andWhere('l.zIndex >= :new AND l.zIndex < :current')
                ->setParameter('design', $design)
                ->setParameter('current', $currentZIndex)
                ->setParameter('new', $newZIndex)
                ->getQuery()
                ->execute();
        }

        $layer->setZIndex($newZIndex);
        $em->persist($layer);
        $em->flush();
    }

    /**
     * Create a duplicate of an existing layer.
     * 
     * Creates a complete copy of a layer with all its properties, placing it
     * at the top of the layer stack with a slight positional offset. The
     * duplicate includes all layer properties, animations, and masks.
     * 
     * @param Layer $layer The layer to duplicate
     * @return Layer The newly created duplicate layer
     */
    public function duplicateLayer(Layer $layer): Layer
    {
        $design = $layer->getDesign();
        $maxZIndex = $this->getMaxZIndex($design);

        $duplicate = new Layer(
            $design,
            $layer->getType(),
            $layer->getName() . ' Copy'
        );

        // Copy transform with slight offset
        $transform = $layer->getTransform();
        $transform['x'] = ($transform['x'] ?? 0) + 10;
        $transform['y'] = ($transform['y'] ?? 0) + 10;
        $duplicate->setTransform($transform);

        // Copy other properties
        $duplicate->setZIndex($maxZIndex + 1);
        $duplicate->setProperties($layer->getProperties());
        $duplicate->setOpacity($layer->getOpacity());
        $duplicate->setVisible($layer->getVisible());
        $duplicate->setLocked($layer->getLocked());

        if ($layer->getAnimations()) {
            $duplicate->setAnimations($layer->getAnimations());
        }

        if ($layer->getMask()) {
            $duplicate->setMask($layer->getMask());
        }

        $this->getEntityManager()->persist($duplicate);
        $this->getEntityManager()->flush();

        return $duplicate;
    }
}
