<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Design;
use App\Entity\Layer;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\DesignRepository;
use App\Repository\LayerRepository;
use App\Repository\ProjectRepository;
use App\Service\Svg\SvgRendererService;
use App\Service\MediaProcessing\MediaProcessingService;
use App\Service\MediaProcessing\Config\ImageProcessingConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service for managing designs and their operations
 */
class DesignService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DesignRepository $designRepository,
        private readonly LayerRepository $layerRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly SvgRendererService $svgRendererService,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        private readonly MediaProcessingService $mediaProcessingService,
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
     * Duplicate a single layer to a target design
     */
    private function duplicateLayerToDesign(Layer $originalLayer, Design $targetDesign): Layer
    {
        $newLayer = new Layer();
        $newLayer->setName($originalLayer->getName())
                 ->setType($originalLayer->getType())
                 ->setDesign($targetDesign)
                 ->setProperties($originalLayer->getProperties())
                 ->setTransform($originalLayer->getTransform())
                 ->setVisible($originalLayer->isVisible())
                 ->setLocked($originalLayer->isLocked())
                 ->setZIndex($originalLayer->getZIndex());

        $this->entityManager->persist($newLayer);
        $this->entityManager->flush();

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
     * Generate SVG content from design data using the comprehensive SVG renderer
     */
    private function generateSvgFromDesign(Design $design): string
    {
        try {
            return $this->svgRendererService->renderDesignToSvg($design);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate SVG from design', [
                'design_id' => $design->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a simple error placeholder SVG
            $width = $design->getWidth() ?? 800;
            $height = $design->getHeight() ?? 600;
            
            return sprintf(
                '<svg width="%d" height="%d" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg">' .
                '<rect width="100%%" height="100%%" fill="#f3f4f6"/>' .
                '<text x="50%%" y="50%%" text-anchor="middle" dominant-baseline="middle" ' .
                'font-family="Arial, sans-serif" font-size="16" fill="#ef4444">' .
                'Error rendering design</text>' .
                '</svg>',
                $width, $height, $width, $height
            );
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

    /**
     * Create a new design with enhanced logic
     */
    public function createDesignFromRequest(
        User $user,
        string $name,
        int $width = 800,
        int $height = 600,
        ?string $description = null,
        ?int $projectId = null,
        array $data = []
    ): Design {
        // Validate and get/create project
        $project = $this->resolveProject($user, $projectId);
        
        $design = new Design();
        $design->setName($name);
        $design->setWidth($width);
        $design->setHeight($height);
        $design->setCanvasWidth($width);
        $design->setCanvasHeight($height);
        $design->setData($data);
        $design->setBackground(['type' => 'color', 'color' => '#ffffff']); // Default background
        $design->setProject($project);
        
        if ($description) {
            $design->setTitle($description);
        }
        
        // Validate design
        $errors = $this->validator->validate($design);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errorMessages));
        }
        
        $this->entityManager->persist($design);
        $this->entityManager->flush();
        
        return $design;
    }

    /**
     * Get paginated designs for user
     */
    public function getUserDesigns(
        User $user,
        int $page = 1,
        int $limit = 20,
        ?string $search = null,
        ?int $projectId = null,
        string $sortField = 'updatedAt',
        string $sortOrder = 'DESC'
    ): array {
        $offset = ($page - 1) * $limit;
        
        if ($projectId) {
            $project = $this->projectRepository->find($projectId);
            if (!$project || ($project->getUser() !== $user && !$project->getIsPublic())) {
                throw new \InvalidArgumentException('Project not found or access denied');
            }
            return $this->designRepository->findByProjectPaginated($project, $page, $limit);
        }
        
        if ($search) {
            return $this->designRepository->searchByUserPaginated($user, $search, $page, $limit);
        }
        
        return $this->designRepository->findByUserPaginated($user, $page, $limit, $sortField);
    }

    /**
     * Update design with validation
     */
    public function updateDesign(
        Design $design,
        ?string $name = null,
        ?string $description = null,
        ?array $data = null,
        ?int $width = null,
        ?int $height = null,
        ?array $background = null
    ): Design {
        if ($name !== null) {
            $design->setName($name);
        }
        
        if ($description !== null) {
            $design->setTitle($description);
        }
        
        if ($data !== null) {
            $design->setData($data);
        }
        
        if ($width !== null && $height !== null) {
            $design->setWidth($width);
            $design->setHeight($height);
            $design->setCanvasWidth($width);
            $design->setCanvasHeight($height);
        }
        
        if ($background !== null) {
            $design->setBackground($background);
        }
        
        // Validate design
        $errors = $this->validator->validate($design);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errorMessages));
        }
        
        $this->entityManager->persist($design);
        $this->entityManager->flush();
        
        return $design;
    }

    /**
     * Delete design and cleanup
     */
    public function deleteDesign(Design $design): void
    {
        // Delete all layers first
        $layers = $this->layerRepository->findBy(['design' => $design]);
        foreach ($layers as $layer) {
            $this->entityManager->remove($layer);
        }
        
        // Delete thumbnail file if exists
        $thumbnailPath = $this->getThumbnailPath($design);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        
        $this->entityManager->remove($design);
        $this->entityManager->flush();
    }

    /**
     * Duplicate design with enhanced options
     */
    public function duplicateDesignFromRequest(
        Design $originalDesign,
        string $newName,
        ?int $targetProjectId = null,
        User $user
    ): Design {
        $targetProject = $targetProjectId ? 
            $this->resolveProject($user, $targetProjectId) : 
            $originalDesign->getProject();
        
        $newDesign = new Design();
        $newDesign->setName($newName);
        $newDesign->setProject($targetProject);
        $newDesign->setWidth($originalDesign->getWidth());
        $newDesign->setHeight($originalDesign->getHeight());
        $newDesign->setCanvasWidth($originalDesign->getCanvasWidth());
        $newDesign->setCanvasHeight($originalDesign->getCanvasHeight());
        $newDesign->setData($originalDesign->getData());
        $newDesign->setBackground($originalDesign->getBackground());
        $newDesign->setAnimationSettings($originalDesign->getAnimationSettings());
        
        $this->entityManager->persist($newDesign);
        $this->entityManager->flush();
        
        // Duplicate all layers
        $this->duplicateDesignLayers($originalDesign, $newDesign);
        
        return $newDesign;
    }

    /**
     * Search designs with advanced filtering
     */
    public function searchDesigns(
        User $user,
        string $query,
        int $page = 1,
        int $limit = 20,
        ?int $projectId = null,
        string $sortField = 'relevance',
        string $sortOrder = 'DESC'
    ): array {
        if ($projectId) {
            $project = $this->projectRepository->find($projectId);
            if (!$project || ($project->getUser() !== $user && !$project->getIsPublic())) {
                throw new \InvalidArgumentException('Project not found or access denied');
            }
            return $this->designRepository->searchInProjectPaginated($project, $query, $page, $limit);
        }
        
        return $this->designRepository->searchByUserPaginated($user, $query, $page, $limit, $sortField, $sortOrder);
    }

    /**
     * Validate design ownership
     */
    public function validateDesignAccess(Design $design, User $user): void
    {
        if ($design->getProject()->getUser() !== $user && !$design->getProject()->getIsPublic()) {
            throw new \InvalidArgumentException('Access denied to design');
        }
    }

    /**
     * Update design thumbnail
     */
    public function updateDesignThumbnail(Design $design, string $thumbnailData): Design
    {
        // Process thumbnail data (handle data URLs and regular URLs)
        $thumbnailPath = $this->processThumbnailData($thumbnailData, $design->getId());
        
        $design->setThumbnail($thumbnailPath);
        $this->entityManager->flush();
        
        $this->logger->info('Design thumbnail updated successfully', [
            'design_id' => $design->getId(),
            'thumbnail_path' => $thumbnailPath
        ]);
        
        return $design;
    }

    private function processThumbnailData(string $thumbnailData, int $designId): string
    {
        // If it's a data URL, extract and save the image using MediaProcessingService
        if (str_starts_with($thumbnailData, 'data:image/')) {
            return $this->saveDataUrlAsFile($thumbnailData, $designId);
        }
        
        // If it's a regular URL, validate and return
        if (filter_var($thumbnailData, FILTER_VALIDATE_URL)) {
            return $thumbnailData;
        }
        
        throw new \InvalidArgumentException('Invalid thumbnail data: must be a data URL or valid URL');
    }

    /**
     * Save data URL as a file using MediaProcessingService for security and optimization
     * 
     * @param string $dataUrl The data URL containing base64 encoded image
     * @param int $designId The design ID for generating unique filename
     * @return string The saved file path
     * @throws \InvalidArgumentException If data URL is invalid or processing fails
     */
    private function saveDataUrlAsFile(string $dataUrl, int $designId): string
    {
        // Parse the data URL
        if (!preg_match('/^data:image\/([a-zA-Z0-9+.-]+);base64,(.+)$/', $dataUrl, $matches)) {
            throw new \InvalidArgumentException('Invalid data URL format');
        }
        
        $mimeType = $matches[1];
        $base64Data = $matches[2];
        
        // Validate and determine file extension
        $allowedTypes = [
            'png' => 'png',
            'jpeg' => 'jpg', 
            'jpg' => 'jpg',
            'webp' => 'webp',
            'gif' => 'gif'
        ];
        
        if (!isset($allowedTypes[$mimeType])) {
            throw new \InvalidArgumentException("Unsupported image type: {$mimeType}");
        }
        
        $extension = $allowedTypes[$mimeType];
        
        // Decode base64 data
        $imageData = base64_decode($base64Data, true);
        if ($imageData === false) {
            throw new \InvalidArgumentException('Invalid base64 data');
        }
        
        // Validate image data
        if (strlen($imageData) === 0) {
            throw new \InvalidArgumentException('Empty image data');
        }
        
        // Create temporary file for initial processing
        $tempFile = tmpfile();
        if ($tempFile === false) {
            throw new \RuntimeException('Failed to create temporary file');
        }
        
        fwrite($tempFile, $imageData);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        
        // Validate image using getimagesize first
        $imageInfo = @getimagesize($tempPath);
        if ($imageInfo === false) {
            fclose($tempFile);
            throw new \InvalidArgumentException('Invalid image data - cannot process as image');
        }
        
        // Extract image dimensions
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        
        // Define thumbnail constraints
        $maxWidth = 800;   // Maximum width for thumbnails
        $maxHeight = 600;  // Maximum height for thumbnails
        $quality = 85;     // Quality for JPEG/WebP compression
        
        // Calculate new dimensions if resizing is needed
        $needsResize = ($originalWidth > $maxWidth || $originalHeight > $maxHeight);
        
        if ($needsResize) {
            // Calculate aspect ratio preserving dimensions
            $aspectRatio = $originalWidth / $originalHeight;
            
            if ($originalWidth > $originalHeight) {
                $newWidth = min($maxWidth, $originalWidth);
                $newHeight = (int) round($newWidth / $aspectRatio);
            } else {
                $newHeight = min($maxHeight, $originalHeight);
                $newWidth = (int) round($newHeight * $aspectRatio);
            }
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }
        
        // Generate unique filename
        $filename = sprintf(
            'design_%d_thumbnail_%s.%s',
            $designId,
            uniqid(),
            $extension
        );
        
        $outputPath = $this->thumbnailDirectory . '/' . $filename;
        
        try {
            // Use MediaProcessingService for image processing and optimization
            $config = new ImageProcessingConfig(
                width: $newWidth,
                height: $newHeight,
                quality: $quality,
                format: $extension,
                maintainAspectRatio: true,
                preserveTransparency: true,
                stripMetadata: true, // Remove metadata for smaller file size
                progressive: true    // Progressive JPEG for better loading
            );
            
            $result = $this->mediaProcessingService->processImage(
                $tempPath,
                $outputPath,
                $config
            );
            
            if (!$result->isSuccess()) {
                throw new \RuntimeException('MediaProcessing failed: ' . $result->getErrorMessage());
            }
            
            // Verify the processed file exists and has content
            if (!file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new \RuntimeException('Processed thumbnail file is empty or missing');
            }
            
        } catch (\Exception $e) {
            // Clean up temporary file
            fclose($tempFile);
            
            // Clean up output file if it was created
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }
            
            throw new \RuntimeException('Failed to process thumbnail: ' . $e->getMessage());
        }
        
        // Clean up temporary file
        fclose($tempFile);
        
        // Return relative path for storage in database
        return '/uploads/thumbnails/' . $filename;
    }

    /**
     * Duplicate all layers from source to target design
     */
    private function duplicateDesignLayers(Design $sourceDesign, Design $targetDesign): void
    {
        $originalLayers = $this->layerRepository->findBy(['design' => $sourceDesign], ['zIndex' => 'ASC']);
        $layerMapping = [];

        foreach ($originalLayers as $originalLayer) {
            $newLayer = $this->duplicateLayerToDesign($originalLayer, $targetDesign);
            $layerMapping[$originalLayer->getId()] = $newLayer;
        }

        // Update parent relationships for duplicated layers
        foreach ($layerMapping as $originalId => $newLayer) {
            $originalLayer = $this->layerRepository->find($originalId);
            if ($originalLayer && $originalLayer->getParent()) {
                $newParent = $layerMapping[$originalLayer->getParent()->getId()] ?? null;
                if ($newParent) {
                    $newLayer->setParent($newParent);
                }
            }
        }
        
        $this->entityManager->flush();
    }

    /**
     * Get thumbnail path for design
     */
    private function getThumbnailPath(Design $design): string
    {
        return $this->thumbnailDirectory . '/' . ($design->getThumbnail() ?? 'default.png');
    }

    /**
     * Get design by ID with access validation
     */
    public function getDesignForUser(int $designId, User $user): Design
    {
        $design = $this->designRepository->find($designId);
        if (!$design) {
            throw new \InvalidArgumentException('Design not found');
        }
        
        $this->validateDesignAccess($design, $user);
        return $design;
    }

    /**
     * Get design for duplication (public or owned)
     */
    public function getDesignForDuplication(int $designId, User $user): Design
    {
        $design = $this->designRepository->find($designId);
        if (!$design) {
            throw new \InvalidArgumentException('Design not found');
        }
        
        // For duplication, allow access to public designs or owned designs
        $project = $design->getProject();
        if ($project->getUser() !== $user && !$project->getIsPublic()) {
            throw new \InvalidArgumentException('Access denied');
        }
        
        return $design;
    }

    private function resolveProject(User $user, ?int $projectId = null): Project
    {
        // If a project ID is provided, validate it exists and belongs to user
        if ($projectId !== null) {
            $project = $this->projectRepository->find($projectId);
            if (!$project) {
                throw new \InvalidArgumentException('Project not found');
            }
            
            if ($project->getUser() !== $user) {
                throw new \InvalidArgumentException('Access denied to project');
            }
            
            return $project;
        }
        
        // No project ID provided - get user's first project or create a default one
        $existingProjects = $this->projectRepository->findBy(['user' => $user], ['id' => 'ASC'], 1);
        
        if (!empty($existingProjects)) {
            return $existingProjects[0];
        }
        
        // Create a default project for the user
        $defaultProject = new Project();
        $defaultProject->setTitle('My First Project');
        $defaultProject->setDescription('Default project created automatically');
        $defaultProject->setUser($user);
        $defaultProject->setIsPublic(false);
        
        $this->entityManager->persist($defaultProject);
        $this->entityManager->flush();
        
        $this->logger->info('Created default project for user', [
            'user_id' => $user->getId(),
            'project_id' => $defaultProject->getId()
        ]);
        
        return $defaultProject;
    }
}
