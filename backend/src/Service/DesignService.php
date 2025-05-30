<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Design;
use App\Entity\Layer;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\DesignRepository;
use App\Repository\LayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Psr\Log\LoggerInterface;

/**
 * Service for managing designs and their operations
 */
class DesignService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DesignRepository $designRepository,
        private readonly LayerRepository $layerRepository,
        private readonly LoggerInterface $logger,
        private readonly string $thumbnailDirectory,
    ) {
        // Ensure thumbnail directory exists
        $this->ensureDirectoryExists($this->thumbnailDirectory);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }
    }

    /**
     * Create a new design
     */
    public function createDesign(
        Project $project,
        string $name,
        int $width = 1920,
        int $height = 1080,
        array $data = []
    ): Design {
        $design = new Design();
        $design->setName($name)
               ->setProject($project)
               ->setCanvasWidth($width)
               ->setCanvasHeight($height)
               ->setData($data);

        $this->entityManager->persist($design);
        $this->entityManager->flush();

        return $design;
    }

    /**
     * Duplicate a design
     */
    public function duplicateDesign(Design $originalDesign, ?Project $targetProject = null): Design {
        $newDesign = new Design();
        $newDesign->setName($originalDesign->getName() . ' (Copy)')
                  ->setProject($targetProject ?? $originalDesign->getProject())
                  ->setCanvasWidth($originalDesign->getCanvasWidth())
                  ->setCanvasHeight($originalDesign->getCanvasHeight())
                  ->setData($originalDesign->getData())
                  ->setAnimationSettings($originalDesign->getAnimationSettings());

        $this->entityManager->persist($newDesign);
        $this->entityManager->flush();

        // Duplicate all layers
        $originalLayers = $this->layerRepository->findBy(['design' => $originalDesign], ['zIndex' => 'ASC']);
        $layerMapping = [];

        foreach ($originalLayers as $originalLayer) {
            $newLayer = $this->duplicateLayer($originalLayer, $newDesign);
            $layerMapping[$originalLayer->getId()] = $newLayer;
        }

        // Update parent relationships for duplicated layers
        foreach ($layerMapping as $originalId => $newLayer) {
            $originalLayer = $this->layerRepository->find($originalId);
            if ($originalLayer->getParent()) {
                $newParent = $layerMapping[$originalLayer->getParent()->getId()] ?? null;
                if ($newParent) {
                    $newLayer->setParent($newParent);
                }
            }
        }

        $this->entityManager->flush();

        return $newDesign;
    }

    /**
     * Duplicate a layer
     */
    private function duplicateLayer(Layer $originalLayer, Design $targetDesign): Layer
    {
        $newLayer = new Layer();
        $newLayer->setType($originalLayer->getType())
                 ->setName($originalLayer->getName())
                 ->setDesign($targetDesign)
                 ->setProperties($originalLayer->getProperties())
                 ->setX($originalLayer->getX())
                 ->setY($originalLayer->getY())
                 ->setWidth($originalLayer->getWidth())
                 ->setHeight($originalLayer->getHeight())
                 ->setRotation($originalLayer->getRotation())
                 ->setScaleX($originalLayer->getScaleX())
                 ->setScaleY($originalLayer->getScaleY())
                 ->setOpacity($originalLayer->getOpacity())
                 ->setZIndex($originalLayer->getZIndex())
                 ->setVisible($originalLayer->isVisible())
                 ->setLocked($originalLayer->isLocked())
                 ->setAnimations($originalLayer->getAnimations())
                 ->setMask($originalLayer->getMask());

        $this->entityManager->persist($newLayer);

        return $newLayer;
    }

    /**
     * Update design canvas dimensions
     */
    public function updateCanvasDimensions(Design $design, int $width, int $height): Design
    {
        $design->setCanvasWidth($width)
               ->setCanvasHeight($height);

        $this->entityManager->flush();

        return $design;
    }

    /**
     * Update design data/settings
     */
    public function updateData(Design $design, array $data): Design
    {
        $currentData = $design->getData();
        $mergedData = array_merge($currentData, $data);
        
        $design->setData($mergedData);

        $this->entityManager->flush();

        return $design;
    }

    /**
     * Update animation settings
     */
    public function updateAnimationSettings(Design $design, array $animationSettings): Design
    {
        $design->setAnimationSettings($animationSettings);

        $this->entityManager->flush();

        return $design;
    }

    /**
     * Generate thumbnail for design
     */
    public function generateThumbnail(Design $design): string
    {
        try {
            $thumbnailFilename = sprintf('design_%s_thumb.jpg', $design->getId());
            $thumbnailPath = $this->thumbnailDirectory . '/' . $thumbnailFilename;
            
            // Generate SVG content from design data
            $svgContent = $this->generateSvgFromDesign($design);
            
            // Save temporary SVG file
            $tempSvgPath = sys_get_temp_dir() . '/' . uniqid('design_', true) . '.svg';
            file_put_contents($tempSvgPath, $svgContent);
            
            // Convert SVG to JPEG thumbnail using ImageMagick
            $command = sprintf(
                'convert "%s" -thumbnail 300x300^ -gravity center -extent 300x300 -quality 85 "%s"',
                escapeshellarg($tempSvgPath),
                escapeshellarg($thumbnailPath)
            );
            
            exec($command, $output, $returnCode);
            
            // Clean up temp file
            if (file_exists($tempSvgPath)) {
                unlink($tempSvgPath);
            }
            
            if ($returnCode === 0 && file_exists($thumbnailPath)) {
                $thumbnailUrl = '/uploads/thumbnails/' . $thumbnailFilename;
                $design->setThumbnail($thumbnailUrl);
                $this->entityManager->flush();
                
                $this->logger->info('Design thumbnail generated successfully', [
                    'design_id' => $design->getId(),
                    'thumbnail_path' => $thumbnailPath
                ]);
                
                return $thumbnailUrl;
            } else {
                throw new \RuntimeException('Failed to generate thumbnail with ImageMagick');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate design thumbnail', [
                'design_id' => $design->getId(),
                'error' => $e->getMessage()
            ]);
            
            // Fallback to placeholder
            $placeholderUrl = sprintf('/thumbnails/placeholder_design_%s.jpg', $design->getId());
            $design->setThumbnail($placeholderUrl);
            $this->entityManager->flush();
            
            return $placeholderUrl;
        }
    }

    /**
     * Generate SVG content from design data
     */
    private function generateSvgFromDesign(Design $design): string
    {
        $canvasSettings = $design->getCanvasSettings();
        $width = $canvasSettings['width'] ?? 800;
        $height = $canvasSettings['height'] ?? 600;
        $backgroundColor = $canvasSettings['backgroundColor'] ?? '#ffffff';
        
        $layers = $this->layerRepository->findByDesignOrderedByZIndex($design);
        
        $svg = sprintf(
            '<svg width="%d" height="%d" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg">',
            $width, $height, $width, $height
        );
        
        // Add background
        $svg .= sprintf(
            '<rect width="100%%" height="100%%" fill="%s"/>',
            htmlspecialchars($backgroundColor)
        );
        
        // Add layers
        foreach ($layers as $layer) {
            if (!$layer->getVisible()) {
                continue;
            }
            
            $svg .= $this->renderLayerToSvg($layer);
        }
        
        $svg .= '</svg>';
        
        return $svg;
    }

    /**
     * Render a single layer to SVG
     */
    private function renderLayerToSvg(Layer $layer): string
    {
        $type = $layer->getType();
        $properties = $layer->getProperties();
        $transform = sprintf(
            'translate(%d,%d) rotate(%f) scale(%f,%f)',
            $layer->getX() ?? 0,
            $layer->getY() ?? 0,
            $layer->getRotation() ?? 0,
            $layer->getScaleX() ?? 1,
            $layer->getScaleY() ?? 1
        );
        
        switch ($type) {
            case 'text':
                return $this->renderTextLayerToSvg($layer, $properties, $transform);
            case 'image':
                return $this->renderImageLayerToSvg($layer, $properties, $transform);
            case 'shape':
                return $this->renderShapeLayerToSvg($layer, $properties, $transform);
            default:
                return '';
        }
    }

    private function renderTextLayerToSvg(Layer $layer, array $properties, string $transform): string
    {
        $text = $properties['text'] ?? '';
        $fontSize = $properties['fontSize'] ?? 16;
        $fontFamily = $properties['fontFamily'] ?? 'Arial';
        $color = $properties['color'] ?? '#000000';
        
        return sprintf(
            '<text x="0" y="0" font-family="%s" font-size="%d" fill="%s" transform="%s">%s</text>',
            htmlspecialchars($fontFamily),
            $fontSize,
            htmlspecialchars($color),
            $transform,
            htmlspecialchars($text)
        );
    }

    private function renderImageLayerToSvg(Layer $layer, array $properties, string $transform): string
    {
        $src = $properties['src'] ?? '';
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        if (empty($src)) {
            return '';
        }
        
        return sprintf(
            '<image x="0" y="0" width="%d" height="%d" href="%s" transform="%s"/>',
            $width,
            $height,
            htmlspecialchars($src),
            $transform
        );
    }

    private function renderShapeLayerToSvg(Layer $layer, array $properties, string $transform): string
    {
        $shapeType = $properties['shapeType'] ?? 'rectangle';
        $fill = $properties['fill'] ?? '#cccccc';
        $stroke = $properties['stroke'] ?? '#000000';
        $strokeWidth = $properties['strokeWidth'] ?? 1;
        $width = $layer->getWidth() ?? 100;
        $height = $layer->getHeight() ?? 100;
        
        switch ($shapeType) {
            case 'rectangle':
                return sprintf(
                    '<rect x="0" y="0" width="%d" height="%d" fill="%s" stroke="%s" stroke-width="%d" transform="%s"/>',
                    $width, $height, htmlspecialchars($fill), htmlspecialchars($stroke), $strokeWidth, $transform
                );
            case 'circle':
                $radius = min($width, $height) / 2;
                return sprintf(
                    '<circle cx="%d" cy="%d" r="%d" fill="%s" stroke="%s" stroke-width="%d" transform="%s"/>',
                    $width/2, $height/2, $radius, htmlspecialchars($fill), htmlspecialchars($stroke), $strokeWidth, $transform
                );
            default:
                return '';
        }
    }

    /**
     * Get design statistics
     */
    public function getDesignStats(Design $design): array
    {
        $layers = $this->layerRepository->findBy(['design' => $design]);
        $layersByType = [];
        $totalAnimations = 0;

        foreach ($layers as $layer) {
            $type = $layer->getType();
            $layersByType[$type] = ($layersByType[$type] ?? 0) + 1;
            
            if ($layer->getAnimations()) {
                $totalAnimations += count($layer->getAnimations());
            }
        }

        return [
            'total_layers' => count($layers),
            'layers_by_type' => $layersByType,
            'total_animations' => $totalAnimations,
            'canvas_size' => [
                'width' => $design->getCanvasWidth(),
                'height' => $design->getCanvasHeight(),
            ],
            'has_animations' => $totalAnimations > 0,
            'created_at' => $design->getCreatedAt(),
            'updated_at' => $design->getUpdatedAt(),
        ];
    }

    /**
     * Clean up orphaned designs (designs without projects)
     */
    public function cleanupOrphanedDesigns(): int
    {
        $orphanedDesigns = $this->designRepository->createQueryBuilder('d')
            ->where('d.project IS NULL')
            ->andWhere('d.createdAt < :threshold')
            ->setParameter('threshold', new \DateTimeImmutable('-30 days'))
            ->getQuery()
            ->getResult();

        $count = count($orphanedDesigns);

        foreach ($orphanedDesigns as $design) {
            $this->entityManager->remove($design);
        }

        $this->entityManager->flush();

        return $count;
    }

    /**
     * Export design data for backup or migration
     */
    public function exportDesignData(Design $design): array
    {
        $layers = $this->layerRepository->findBy(['design' => $design], ['zIndex' => 'ASC']);
        
        $layersData = [];
        foreach ($layers as $layer) {
            $layersData[] = [
                'id' => $layer->getId(),
                'type' => $layer->getType(),
                'name' => $layer->getName(),
                'properties' => $layer->getProperties(),
                'transform' => [
                    'x' => $layer->getX(),
                    'y' => $layer->getY(),
                    'width' => $layer->getWidth(),
                    'height' => $layer->getHeight(),
                    'rotation' => $layer->getRotation(),
                    'scaleX' => $layer->getScaleX(),
                    'scaleY' => $layer->getScaleY(),
                    'opacity' => $layer->getOpacity(),
                ],
                'zIndex' => $layer->getZIndex(),
                'visible' => $layer->isVisible(),
                'locked' => $layer->isLocked(),
                'animations' => $layer->getAnimations(),
                'mask' => $layer->getMask(),
                'parent_id' => $layer->getParent()?->getId(),
            ];
        }

        return [
            'design' => [
                'id' => $design->getId(),
                'name' => $design->getName(),
                'canvas_width' => $design->getCanvasWidth(),
                'canvas_height' => $design->getCanvasHeight(),
                'data' => $design->getData(),
                'animation_settings' => $design->getAnimationSettings(),
                'created_at' => $design->getCreatedAt()->format('c'),
                'updated_at' => $design->getUpdatedAt()?->format('c'),
            ],
            'layers' => $layersData,
        ];
    }
}
