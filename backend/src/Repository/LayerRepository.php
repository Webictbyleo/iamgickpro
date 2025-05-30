<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Layer;
use App\Entity\Design;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Layer>
 */
class LayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Layer::class);
    }

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

    public function findByUuid(string $uuid): ?Layer
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

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

    public function countDesignLayers(Design $design): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.design = :design')
            ->setParameter('design', $design)
            ->getQuery()
            ->getSingleScalarResult();
    }

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
