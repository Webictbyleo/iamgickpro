<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Design;
use App\Entity\Layer;
use App\Repository\LayerRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for managing layers and their operations
 */
class LayerService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LayerRepository $layerRepository,
    ) {
    }

    /**
     * Create a new layer
     */
    public function createLayer(
        Design $design,
        string $type,
        string $name,
        array $properties = [],
        ?Layer $parent = null
    ): Layer {
        $layer = new Layer();
        $layer->setType($type)
              ->setName($name)
              ->setDesign($design)
              ->setProperties($properties)
              ->setParent($parent)
              ->setZIndex($this->getNextZIndex($design));

        $this->entityManager->persist($layer);
        $this->entityManager->flush();

        return $layer;
    }

    /**
     * Duplicate a layer
     */
    public function duplicateLayer(Layer $originalLayer, ?Design $targetDesign = null): Layer
    {
        $design = $targetDesign ?? $originalLayer->getDesign();
        
        $newLayer = new Layer();
        $newLayer->setType($originalLayer->getType())
                 ->setName($originalLayer->getName() . ' (Copy)')
                 ->setDesign($design)
                 ->setProperties($originalLayer->getProperties())
                 ->setX($originalLayer->getX() + 10) // Offset slightly
                 ->setY($originalLayer->getY() + 10)
                 ->setWidth($originalLayer->getWidth())
                 ->setHeight($originalLayer->getHeight())
                 ->setRotation($originalLayer->getRotation())
                 ->setScaleX($originalLayer->getScaleX())
                 ->setScaleY($originalLayer->getScaleY())
                 ->setOpacity($originalLayer->getOpacity())
                 ->setZIndex($this->getNextZIndex($design))
                 ->setVisible($originalLayer->isVisible())
                 ->setLocked(false) // Unlock duplicated layers
                 ->setAnimations($originalLayer->getAnimations())
                 ->setMask($originalLayer->getMask())
                 ->setParent($originalLayer->getParent());

        $this->entityManager->persist($newLayer);
        $this->entityManager->flush();

        return $newLayer;
    }

    /**
     * Update layer transform properties
     */
    public function updateTransform(Layer $layer, array $transform): Layer
    {
        if (isset($transform['x'])) {
            $layer->setX((float) $transform['x']);
        }
        if (isset($transform['y'])) {
            $layer->setY((float) $transform['y']);
        }
        if (isset($transform['width'])) {
            $layer->setWidth((float) $transform['width']);
        }
        if (isset($transform['height'])) {
            $layer->setHeight((float) $transform['height']);
        }
        if (isset($transform['rotation'])) {
            $layer->setRotation((float) $transform['rotation']);
        }
        if (isset($transform['scaleX'])) {
            $layer->setScaleX((float) $transform['scaleX']);
        }
        if (isset($transform['scaleY'])) {
            $layer->setScaleY((float) $transform['scaleY']);
        }
        if (isset($transform['opacity'])) {
            $layer->setOpacity((float) $transform['opacity']);
        }

        $this->entityManager->flush();

        return $layer;
    }

    /**
     * Update layer properties
     */
    public function updateProperties(Layer $layer, array $properties): Layer
    {
        $currentProperties = $layer->getProperties();
        $mergedProperties = array_merge($currentProperties, $properties);
        
        $layer->setProperties($mergedProperties);
        $this->entityManager->flush();

        return $layer;
    }

    /**
     * Move layer to new z-index position
     */
    public function moveToZIndex(Layer $layer, int $newZIndex): Layer
    {
        $design = $layer->getDesign();
        $currentZIndex = $layer->getZIndex();

        if ($currentZIndex === $newZIndex) {
            return $layer;
        }

        // Get all layers in design
        $layers = $this->layerRepository->findBy(['design' => $design], ['zIndex' => 'ASC']);

        // Reorder z-indices
        if ($newZIndex > $currentZIndex) {
            // Moving up
            foreach ($layers as $l) {
                $z = $l->getZIndex();
                if ($z > $currentZIndex && $z <= $newZIndex) {
                    $l->setZIndex($z - 1);
                }
            }
        } else {
            // Moving down
            foreach ($layers as $l) {
                $z = $l->getZIndex();
                if ($z >= $newZIndex && $z < $currentZIndex) {
                    $l->setZIndex($z + 1);
                }
            }
        }

        $layer->setZIndex($newZIndex);
        $this->entityManager->flush();

        return $layer;
    }

    /**
     * Move layer up one level
     */
    public function moveUp(Layer $layer): Layer
    {
        $design = $layer->getDesign();
        $currentZIndex = $layer->getZIndex();
        
        $higherLayer = $this->layerRepository->createQueryBuilder('l')
            ->where('l.design = :design')
            ->andWhere('l.zIndex > :zIndex')
            ->setParameter('design', $design)
            ->setParameter('zIndex', $currentZIndex)
            ->orderBy('l.zIndex', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($higherLayer) {
            $higherZIndex = $higherLayer->getZIndex();
            $higherLayer->setZIndex($currentZIndex);
            $layer->setZIndex($higherZIndex);
            $this->entityManager->flush();
        }

        return $layer;
    }

    /**
     * Move layer down one level
     */
    public function moveDown(Layer $layer): Layer
    {
        $design = $layer->getDesign();
        $currentZIndex = $layer->getZIndex();
        
        $lowerLayer = $this->layerRepository->createQueryBuilder('l')
            ->where('l.design = :design')
            ->andWhere('l.zIndex < :zIndex')
            ->setParameter('design', $design)
            ->setParameter('zIndex', $currentZIndex)
            ->orderBy('l.zIndex', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lowerLayer) {
            $lowerZIndex = $lowerLayer->getZIndex();
            $lowerLayer->setZIndex($currentZIndex);
            $layer->setZIndex($lowerZIndex);
            $this->entityManager->flush();
        }

        return $layer;
    }

    /**
     * Move layer to top
     */
    public function moveToTop(Layer $layer): Layer
    {
        $maxZIndex = $this->getMaxZIndex($layer->getDesign());
        return $this->moveToZIndex($layer, $maxZIndex + 1);
    }

    /**
     * Move layer to bottom
     */
    public function moveToBottom(Layer $layer): Layer
    {
        return $this->moveToZIndex($layer, 0);
    }

    /**
     * Set layer visibility
     */
    public function setVisibility(Layer $layer, bool $visible): Layer
    {
        $layer->setVisible($visible);
        $this->entityManager->flush();
        return $layer;
    }

    /**
     * Lock/unlock layer
     */
    public function setLocked(Layer $layer, bool $locked): Layer
    {
        $layer->setLocked($locked);
        $this->entityManager->flush();
        return $layer;
    }

    /**
     * Update layer animations
     */
    public function updateAnimations(Layer $layer, array $animations): Layer
    {
        $layer->setAnimations($animations);
        $this->entityManager->flush();
        return $layer;
    }

    /**
     * Add animation to layer
     */
    public function addAnimation(Layer $layer, array $animation): Layer
    {
        $animations = $layer->getAnimations() ?? [];
        $animations[] = $animation;
        
        $layer->setAnimations($animations);
        $this->entityManager->flush();
        
        return $layer;
    }

    /**
     * Remove animation from layer
     */
    public function removeAnimation(Layer $layer, int $animationIndex): Layer
    {
        $animations = $layer->getAnimations() ?? [];
        
        if (isset($animations[$animationIndex])) {
            unset($animations[$animationIndex]);
            $animations = array_values($animations); // Reindex array
            
            $layer->setAnimations($animations);
            $this->entityManager->flush();
        }
        
        return $layer;
    }

    /**
     * Set layer mask
     */
    public function setMask(Layer $layer, ?array $mask): Layer
    {
        $layer->setMask($mask);
        $this->entityManager->flush();
        return $layer;
    }

    /**
     * Set layer parent (for grouping)
     */
    public function setParent(Layer $layer, ?Layer $parent): Layer
    {
        // Prevent circular references
        if ($parent && $this->wouldCreateCircularReference($layer, $parent)) {
            throw new \InvalidArgumentException('Setting this parent would create a circular reference');
        }

        $layer->setParent($parent);
        $this->entityManager->flush();
        return $layer;
    }

    /**
     * Get layer hierarchy (children)
     */
    public function getHierarchy(Layer $layer): array
    {
        $children = $this->layerRepository->findBy(['parent' => $layer], ['zIndex' => 'ASC']);
        
        $result = [];
        foreach ($children as $child) {
            $result[] = [
                'layer' => $child,
                'children' => $this->getHierarchy($child),
            ];
        }
        
        return $result;
    }

    /**
     * Get layer bounds (including all descendants)
     */
    public function getBounds(Layer $layer): array
    {
        $minX = $layer->getX();
        $minY = $layer->getY();
        $maxX = $layer->getX() + $layer->getWidth();
        $maxY = $layer->getY() + $layer->getHeight();

        $children = $this->layerRepository->findBy(['parent' => $layer]);
        
        foreach ($children as $child) {
            $childBounds = $this->getBounds($child);
            $minX = min($minX, $childBounds['x']);
            $minY = min($minY, $childBounds['y']);
            $maxX = max($maxX, $childBounds['x'] + $childBounds['width']);
            $maxY = max($maxY, $childBounds['y'] + $childBounds['height']);
        }

        return [
            'x' => $minX,
            'y' => $minY,
            'width' => $maxX - $minX,
            'height' => $maxY - $minY,
        ];
    }

    /**
     * Delete layer and handle children
     */
    public function deleteLayer(Layer $layer, bool $deleteChildren = true): void
    {
        if ($deleteChildren) {
            // Delete all children recursively
            $children = $this->layerRepository->findBy(['parent' => $layer]);
            foreach ($children as $child) {
                $this->deleteLayer($child, true);
            }
        } else {
            // Move children to parent's parent
            $children = $this->layerRepository->findBy(['parent' => $layer]);
            $newParent = $layer->getParent();
            
            foreach ($children as $child) {
                $child->setParent($newParent);
            }
        }

        $this->entityManager->remove($layer);
        $this->entityManager->flush();
    }

    /**
     * Get the next available z-index for a design
     */
    private function getNextZIndex(Design $design): int
    {
        return $this->getMaxZIndex($design) + 1;
    }

    /**
     * Get the maximum z-index in a design
     */
    private function getMaxZIndex(Design $design): int
    {
        $result = $this->layerRepository->createQueryBuilder('l')
            ->select('MAX(l.zIndex)')
            ->where('l.design = :design')
            ->setParameter('design', $design)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Check if setting a parent would create a circular reference
     */
    private function wouldCreateCircularReference(Layer $layer, Layer $potentialParent): bool
    {
        $current = $potentialParent;
        
        while ($current !== null) {
            if ($current->getId() === $layer->getId()) {
                return true;
            }
            $current = $current->getParent();
        }
        
        return false;
    }
}
