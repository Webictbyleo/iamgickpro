<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Media;
use App\Entity\User;
use App\Exception\SubscriptionLimitExceededException;
use App\Repository\MediaRepository;
use App\Service\MediaProcessing\MediaProcessingService;
use App\Service\MediaProcessing\Config\ProcessingConfigFactory;
use App\Service\SubscriptionConstraintService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

readonly class MediaService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MediaRepository $mediaRepository,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        private MediaProcessingService $mediaProcessingService,
        private SubscriptionConstraintService $subscriptionConstraintService,
        private string $mediaUploadDirectory,
        private string $thumbnailDirectory,
        private int $maxFileSize,
        private array $allowedMimeTypes
    ) {
        // Ensure directories exist
        $this->ensureDirectoryExists($this->mediaUploadDirectory);
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
     * Upload a file and create a Media entity
     * 
     * @param UploadedFile $file The uploaded file
     * @param User $user The user uploading the file
     * @param string|null $alt Optional alt text for the media
     * @return Media The created Media entity
     * @throws \InvalidArgumentException If file validation fails or SVG security checks fail
     * @throws \RuntimeException If file upload fails
     */
    public function uploadFile(UploadedFile $file, User $user, ?string $alt = null): Media
    {
        $this->validateFile($file);
        
        // Check storage limit before uploading
        $fileSize = $file->getSize();
        $this->subscriptionConstraintService->enforceFileUploadLimit($user, $fileSize);

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->mediaUploadDirectory, $fileName);
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload file', [
                'filename' => $fileName,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to upload file');
        }

        $filePath = $this->mediaUploadDirectory . '/' . $fileName;
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);

        // Handle SVG files with security sanitization
        if ($this->isSvgFile($filePath, $mimeType, $fileName)) {
            $this->sanitizeSvgFile($filePath);
            // Override MIME type for SVG files that might be misdetected
            $mimeType = 'image/svg+xml';
        }

        // Determine media type from MIME type
        $type = $this->getMediaTypeFromMimeType($mimeType);

        // Extract basic properties for Media entity using existing MediaProcessingService
        $width = null;
        $height = null;
        $duration = null;

        try {
            if ($this->mediaProcessingService) {
                $metadata = $this->mediaProcessingService->extractMetadata($filePath);
                
                if (!empty($metadata)) {
                    // Extract dimensions for images and videos
                    if (isset($metadata['video'])) {
                        $width = $metadata['video']['width'] ?? null;
                        $height = $metadata['video']['height'] ?? null;
                    } elseif (isset($metadata['width'], $metadata['height'])) {
                        $width = $metadata['width'];
                        $height = $metadata['height'];
                    }
                    
                    // Extract duration for videos and audio
                    if (isset($metadata['duration'])) {
                        $duration = (int) ceil($metadata['duration']);
                    }
                }
            }
            
            // Fallback to PHP's getimagesize for images if MediaProcessingService didn't work
            if (!$width && !$height && str_starts_with($mimeType, 'image/')) {
                $imageInfo = getimagesize($filePath);
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to extract media properties during upload', [
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'error' => $e->getMessage()
            ]);
        }

        $media = new Media();
        $media->setName($originalFilename)
              ->setType($type)
              ->setUrl('/media/' . $fileName)
              ->setMimeType($mimeType)
              ->setSize($fileSize)
              ->setWidth($width)
              ->setHeight($height)
              ->setDuration($duration)
              ->setSource('upload')
              ->setUser($user);

        // Store minimal essential metadata
        $media->setMetadata([
            'original_filename' => $originalFilename,
            'file_path' => $filePath,
            'alt' => $alt ?? $originalFilename
        ]);

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        // Generate thumbnail asynchronously
        $this->generateThumbnail($media);
        
        $this->logger->info('File uploaded successfully', [
            'media_id' => $media->getId(),
            'filename' => $fileName,
            'user_id' => $user->getId()
        ]);

        return $media;
    }

    public function createFromStock(
        string $stockId,
        string $source,
        string $name,
        string $url,
        ?string $thumbnailUrl = null,
        ?array $metadata = null,
        ?string $license = null,
        ?User $user = null
    ): Media {
        $media = new Media();
        $media->setName($name)
              ->setUrl($url)
              ->setThumbnailUrl($thumbnailUrl)
              ->setSource($source)
              ->setSourceId($stockId)
              ->setLicense($license ?? 'stock')
              ->setUser($user);

        if ($metadata) {
            $media->setMetadata($metadata);
            
            // Extract dimensions from metadata if available
            if (isset($metadata['width'])) {
                $media->setWidth((int) $metadata['width']);
            }
            if (isset($metadata['height'])) {
                $media->setHeight((int) $metadata['height']);
            }
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        $this->logger->info('Stock media created', [
            'media_id' => $media->getId(),
            'stock_id' => $stockId,
            'source' => $source
        ]);

        return $media;
    }

    public function generateThumbnail(Media $media): ?string
    {
        if ($media->getSource() !== 'upload') {
            return null;
        }

        $metadata = $media->getMetadata();
        $filePath = $metadata['file_path'] ?? null;
        
        if (!$filePath || !file_exists($filePath)) {
            return null;
        }

        $mimeType = $media->getMimeType();
        $isImage = str_starts_with($mimeType, 'image/');
        $isVideo = str_starts_with($mimeType, 'video/');

        if (!$isImage && !$isVideo) {
            $this->logger->info('Thumbnail generation skipped for unsupported media type', [
                'media_id' => $media->getId(),
                'mime_type' => $mimeType
            ]);
            return null;
        }
        
        try {
            if ($isImage) {
                return $this->generateImageThumbnail($media, $filePath);
            } elseif ($isVideo) {
                return $this->generateVideoThumbnail($media, $filePath);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate thumbnail', [
                'media_id' => $media->getId(),
                'error' => $e->getMessage(),
                'mime_type' => $mimeType
            ]);
        }

        return null;
    }

    private function generateImageThumbnail(Media $media, string $filePath): ?string
    {
        // Use media UUID for safe thumbnail naming (no database ID exposure)
        $thumbnailName = 'thumb_' . $media->getUuid()->toRfc4122() . '.jpg';
        $thumbnailPath = $this->thumbnailDirectory . '/' . $thumbnailName;
        
        // Generate thumbnail using production-ready MediaProcessingService
        $thumbnailConfig = ProcessingConfigFactory::createImage(
            300, 300, 
            85, // quality
            'jpg', // format
            true, // maintain aspect ratio
            false // no transparency for thumbnails
        );

        $result = $this->mediaProcessingService->processImage(
            $filePath,
            $thumbnailPath,
            $thumbnailConfig
        );
        
        if ($result->isSuccess() && file_exists($thumbnailPath)) {
            $media->setThumbnailUrl('/thumbnails/' . $thumbnailName);
            $this->entityManager->flush();
            
            $this->logger->info('Image thumbnail generated using MediaProcessingService', [
                'media_id' => $media->getId(),
                'thumbnail_path' => $thumbnailPath,
                'processing_time' => $result->getProcessingTime()
            ]);

            return $thumbnailPath;
        } else {
            $this->logger->error('Image thumbnail generation failed', [
                'media_id' => $media->getId(),
                'error' => $result->getErrorMessage() ?? 'Unknown error'
            ]);
        }

        return null;
    }

    private function generateVideoThumbnail(Media $media, string $filePath): ?string
    {
        // Use media UUID for safe thumbnail naming (no database ID exposure)
        $thumbnailName = 'thumb_' . $media->getUuid()->toRfc4122() . '.gif';
        $thumbnailPath = $this->thumbnailDirectory . '/' . $thumbnailName;
        
        // Generate GIF thumbnail from video using FFmpeg
        $result = $this->generateVideoGifThumbnail($filePath, $thumbnailPath);
        
        if ($result && file_exists($thumbnailPath)) {
            $media->setThumbnailUrl('/thumbnails/' . $thumbnailName);
            $this->entityManager->flush();
            
            $this->logger->info('Video GIF thumbnail generated successfully', [
                'media_id' => $media->getId(),
                'thumbnail_path' => $thumbnailPath,
                'file_size' => filesize($thumbnailPath)
            ]);

            return $thumbnailPath;
        } else {
            $this->logger->error('Video GIF thumbnail generation failed', [
                'media_id' => $media->getId(),
                'input_path' => $filePath
            ]);
        }

        return null;
    }

    private function generateVideoGifThumbnail(string $videoPath, string $outputPath): bool
    {
        try {
            // For GIF generation, we need to use FFmpeg directly with palette optimization
            // This creates a high-quality GIF with optimized colors
            $result = $this->mediaProcessingService->getFfmpegProcessor()->generateVideoGif(
                $videoPath,
                $outputPath,
                startTime: 1.0, // Start from 1 second into video
                duration: 3.0,  // 3 second GIF
                width: 300,
                height: 300,
                fps: 10 // 10 FPS for good quality/size balance
            );

            if ($result->isSuccess()) {
                $this->logger->info('Video GIF thumbnail created successfully', [
                    'input' => $videoPath,
                    'output' => $outputPath,
                    'processing_time' => $result->getProcessingTime(),
                    'file_size' => file_exists($outputPath) ? filesize($outputPath) : 0
                ]);
                return true;
            } else {
                $this->logger->error('Video GIF thumbnail processing failed', [
                    'input' => $videoPath,
                    'output' => $outputPath,
                    'error' => $result->getErrorMessage()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception during video GIF thumbnail generation', [
                'input' => $videoPath,
                'output' => $outputPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function processUploadedFile(Media $media, string $filePath, array $metadata = []): void
    {
        try {
            // Generate thumbnail for both images and videos
            $thumbnailPath = $this->generateThumbnail($media);
            
            // Update media metadata with thumbnail path if generated
            if ($thumbnailPath) {
                $currentMetadata = $media->getMetadata();
                $currentMetadata['thumbnail'] = $thumbnailPath;
                $media->setMetadata($currentMetadata);
            }

            // Additional processing based on file type
            if ($this->isVideoFile($media)) {
                $this->processVideoFile($media, $filePath);
            }

            // Optimize file if needed
            $this->optimizeFile($media, $filePath);

            $this->entityManager->flush();

            $this->logger->info('Media file processed successfully', [
                'media_id' => $media->getId(),
                'file_path' => $filePath
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process uploaded file', [
                'media_id' => $media->getId(),
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function isVideoFile(Media $media): bool
    {
        return in_array($media->getType(), ['video']);
    }

    private function processVideoFile(Media $media, string $filePath): void
    {
        // Extract video metadata using production-ready MediaProcessingService
        try {
            $metadata = $this->mediaProcessingService->extractMetadata($filePath);
            if (!empty($metadata)) {
                $currentMetadata = $media->getMetadata();
                $currentMetadata['video_info'] = $metadata;
                $media->setMetadata($currentMetadata);

                // Update media dimensions if available
                if (isset($metadata['width']) && isset($metadata['height'])) {
                    $media->setWidth($metadata['width']);
                    $media->setHeight($metadata['height']);
                }

                $this->logger->info('Video metadata extracted using MediaProcessingService', [
                    'media_id' => $media->getId(),
                    'duration' => $metadata['duration'] ?? null,
                    'dimensions' => isset($metadata['width'], $metadata['height']) 
                        ? $metadata['width'] . 'x' . $metadata['height'] 
                        : null
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to extract video metadata', [
                'media_id' => $media->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function searchMedia(
        ?string $query = null,
        ?string $type = null,
        ?string $source = null,
        ?User $user = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        // Build filters array from non-null parameters
        $filters = [];
        if ($type !== null) {
            $filters['type'] = $type;
        }
        if ($source !== null) {
            $filters['source'] = $source;
        }
        
        // Calculate page from offset and limit
        $page = (int) floor($offset / $limit) + 1;
        
        return $this->mediaRepository->findByFilters($filters, $page, $limit, $query);
    }

    public function getUserMedia(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->mediaRepository->findByUser($user, $limit, $offset);
    }

    public function getRecentMedia(int $limit = 20): array
    {
        return $this->mediaRepository->findRecent($limit);
    }

    public function getPopularMedia(int $limit = 20): array
    {
        return $this->mediaRepository->findPopular($limit);
    }

    public function incrementUsageCount(Media $media): void
    {
        $metadata = $media->getMetadata() ?? [];
        $usageCount = $metadata['usage_count'] ?? 0;
        $metadata['usage_count'] = $usageCount + 1;
        $media->setMetadata($metadata);
        $this->entityManager->flush();
    }

    public function deleteMedia(Media $media): void
    {
        // Delete physical files for uploaded media
        if ($media->getSource() === 'upload') {
            $metadata = $media->getMetadata();
            $filePath = $metadata['file_path'] ?? null;
            
            if ($filePath && file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete thumbnail
            if ($media->getThumbnailUrl()) {
                $thumbnailPath = $this->thumbnailDirectory . '/' . basename($media->getThumbnailUrl());
                if (file_exists($thumbnailPath)) {
                    unlink($thumbnailPath);
                }
            }
        }

        $this->entityManager->remove($media);
        $this->entityManager->flush();

        $this->logger->info('Media deleted', [
            'media_id' => $media->getId(),
            'name' => $media->getName()
        ]);
    }

    public function bulkDelete(array $mediaIds, User $user): int
    {
        $deletedCount = 0;
        
        foreach ($mediaIds as $mediaId) {
            $media = $this->mediaRepository->find($mediaId);
            
            if ($media && $media->getUser() === $user) {
                $this->deleteMedia($media);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    public function getStorageStats(User $user): array
    {
        $userMedia = $this->mediaRepository->findByUser($user);
        $totalSize = 0;
        $totalFiles = count($userMedia);
        
        $typeStats = [];
        
        foreach ($userMedia as $media) {
            $totalSize += $media->getSize() ?? 0;
            
            $type = explode('/', $media->getMimeType())[0] ?? 'unknown';
            if (!isset($typeStats[$type])) {
                $typeStats[$type] = ['count' => 0, 'size' => 0];
            }
            $typeStats[$type]['count']++;
            $typeStats[$type]['size'] += $media->getSize() ?? 0;
        }

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatFileSize($totalSize),
            'by_type' => $typeStats
        ];
    }

    public function findDuplicates(User $user): array
    {
        return $this->mediaRepository->findDuplicatesByUser($user);
    }

    public function optimizeImage(Media $media, array $options = []): bool
    {
        if (!str_starts_with($media->getMimeType(), 'image/') || 
            $media->getSource() !== 'upload') {
            return false;
        }

        $metadata = $media->getMetadata();
        $filePath = $metadata['file_path'] ?? null;
        
        if (!$filePath || !file_exists($filePath)) {
            return false;
        }

        $quality = $options['quality'] ?? 85;
        $maxWidth = $options['max_width'] ?? null;
        $maxHeight = $options['max_height'] ?? null;

        $optimizedPath = $filePath . '.optimized';

        try {
            // Create optimization config using production-ready MediaProcessingService
            $optimizationConfig = ProcessingConfigFactory::createImage(
                $maxWidth ?? $media->getWidth(),
                $maxHeight ?? $media->getHeight(),
                $quality,
                null, // keep original format
                true, // maintain aspect ratio
                true, // preserve transparency
                true, // strip metadata for optimization
                false, // not progressive
                null // no background color
            );

            $result = $this->mediaProcessingService->processImage(
                $filePath,
                $optimizedPath,
                $optimizationConfig
            );

            if ($result->isSuccess() && file_exists($optimizedPath)) {
                // Replace original with optimized version
                rename($optimizedPath, $filePath);
                
                // Update file size
                $newSize = filesize($filePath);
                $media->setSize($newSize);
                
                // Update dimensions from processing result
                $resultMetadata = $result->getMetadata();
                if (isset($resultMetadata['width']) && isset($resultMetadata['height'])) {
                    $media->setWidth($resultMetadata['width']);
                    $media->setHeight($resultMetadata['height']);
                }
                
                $this->entityManager->flush();

                $this->logger->info('Image optimized using MediaProcessingService', [
                    'media_id' => $media->getId(),
                    'new_size' => $newSize,
                    'processing_time' => $result->getProcessingTime()
                ]);

                return true;
            } else {
                $this->logger->error('Image optimization failed', [
                    'media_id' => $media->getId(),
                    'error' => $result->getErrorMessage() ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to optimize image', [
                'media_id' => $media->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Validate uploaded file for size, type, and content security
     * 
     * @param UploadedFile $file The file to validate
     * @throws \InvalidArgumentException If file is invalid, too large, wrong type, or fails security checks
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException(sprintf(
                'File size (%s) exceeds maximum allowed size (%s)',
                $this->formatFileSize($file->getSize()),
                $this->formatFileSize($this->maxFileSize)
            ));
        }

        $mimeType = $file->getMimeType();
        
        // Special handling for files that might be SVG but have generic MIME types
        if ($this->isSvgFile($file->getPathname(), $mimeType, $file->getClientOriginalName())) {
            // If content is SVG but MIME type is not image/svg+xml, validate it's really SVG
            if ($mimeType !== 'image/svg+xml') {
                $this->logger->warning('File detected as SVG with non-standard MIME type', [
                    'filename' => $file->getClientOriginalName(),
                    'detected_mime' => $mimeType,
                    'file_size' => $file->getSize()
                ]);
            }
            $this->validateSvgFile($file);
        } elseif (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new \InvalidArgumentException(sprintf(
                'File type "%s" is not allowed',
                $mimeType
            ));
        }
    }

    /**
     * Validate SVG file content for basic security checks before upload
     */
    private function validateSvgFile(UploadedFile $file): void
    {
        try {
            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                throw new \InvalidArgumentException('Cannot read SVG file');
            }

            // Check file size limit for SVG (stricter than general file limit)
            $maxSvgSize = min($this->maxFileSize, 2 * 1024 * 1024); // Max 2MB for SVG
            if (strlen($content) > $maxSvgSize) {
                throw new \InvalidArgumentException('SVG file is too large');
            }

            // Basic content validation - check for obviously malicious patterns
            $dangerousPatterns = [
                '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
                '/javascript:/i',
                '/data:\s*text\/html/i',
                '/vbscript:/i',
                '/<iframe\b/i',
                '/<object\b/i',
                '/<embed\b/i',
                '/<form\b/i',
                '/on\w+\s*=/i' // Event handlers like onclick, onload, etc.
            ];

            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    throw new \InvalidArgumentException('SVG file contains potentially malicious content');
                }
            }

            // Verify it's valid XML
            $dom = new \DOMDocument();
            $previousSetting = libxml_use_internal_errors(true);
            $loaded = $dom->loadXML($content, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR);
            libxml_use_internal_errors($previousSetting);

            if (!$loaded) {
                throw new \InvalidArgumentException('SVG file is not valid XML');
            }

            // Check if root element is SVG
            if ($dom->documentElement->nodeName !== 'svg') {
                throw new \InvalidArgumentException('File is not a valid SVG');
            }

        } catch (\Exception $e) {
            $this->logger->warning('SVG validation failed', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            throw new \InvalidArgumentException('SVG file validation failed: ' . $e->getMessage());
        }
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    private function isImageFile(Media $media): bool
    {
        return str_starts_with($media->getMimeType(), 'image/');
    }

    /**
     * Robust SVG file detection using MIME type and content verification
     * 
     * @param string $filePath Path to the file
     * @param string $mimeType MIME type from mime_content_type()
     * @param string $fileName Original filename (for logging only)
     * @return bool True if the file is an SVG
     * @throws \InvalidArgumentException If MIME type suggests SVG but content verification fails
     */
    private function isSvgFile(string $filePath, string $mimeType, string $fileName): bool
    {
        // Method 1: Check MIME type (multiple possible values for SVG)
        $svgMimeTypes = [
            'image/svg+xml',
            'image/svg',
            'text/xml',
            'application/xml',
            'text/plain' // Some systems report SVG as plain text
        ];
        
        if (in_array($mimeType, $svgMimeTypes)) {
            // If MIME type suggests it could be SVG, verify by content
            $isValidSvg = $this->verifySvgContent($filePath);
            
            if (!$isValidSvg) {
                // Security concern: MIME type suggests SVG but content verification failed
                $this->logger->warning('File rejected: MIME type suggests SVG but content verification failed', [
                    'filename' => $fileName,
                    'mime_type' => $mimeType,
                    'file_path' => $filePath
                ]);
                
                throw new \InvalidArgumentException(
                    'File appears to be SVG based on MIME type but fails content validation. This may indicate a malicious file.'
                );
            }
            
            return true;
        }

        // Method 2: Content-based detection as fallback for misdetected MIME types
        return $this->verifySvgContent($filePath);
    }

    /**
     * Verify if file content is actually SVG by examining the content
     * Requires strong evidence that the file is SVG
     * 
     * @param string $filePath Path to the file
     * @return bool True if content appears to be SVG
     */
    private function verifySvgContent(string $filePath): bool
    {
        try {
            // Read first 2KB of file to check for SVG markers
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return false;
            }
            
            $content = fread($handle, 2048);
            fclose($handle);
            
            if ($content === false) {
                return false;
            }

            // Look for SVG indicators in the content
            $content = trim($content);
            
            // Must have XML declaration OR start with <svg
            $hasXmlDeclaration = stripos($content, '<?xml') === 0;
            $startWithSvg = stripos(ltrim($content), '<svg') === 0;
            
            if (!$hasXmlDeclaration && !$startWithSvg) {
                return false;
            }

            // Must contain SVG namespace or svg element
            $svgRequiredIndicators = [
                'xmlns="http://www.w3.org/2000/svg"',
                'xmlns:svg="http://www.w3.org/2000/svg"',
                '<svg',
                '</svg>'
            ];

            $foundRequired = false;
            foreach ($svgRequiredIndicators as $indicator) {
                if (stripos($content, $indicator) !== false) {
                    $foundRequired = true;
                    break;
                }
            }

            if (!$foundRequired) {
                return false;
            }

            // Additional verification: try to parse as XML and check root element
            try {
                $dom = new \DOMDocument();
                $previousSetting = libxml_use_internal_errors(true);
                $loaded = $dom->loadXML($content, LIBXML_NOENT);
                libxml_use_internal_errors($previousSetting);
                
                if ($loaded && $dom->documentElement && $dom->documentElement->nodeName === 'svg') {
                    return true;
                }
            } catch (\Exception $e) {
                // If XML parsing fails, it's not a valid SVG
                return false;
            }

            return false;
            
        } catch (\Exception $e) {
            $this->logger->warning('Failed to verify SVG content', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function optimizeFile(Media $media, string $filePath): void
    {
        try {
            if ($this->isImageFile($media)) {
                $this->optimizeImage($media, ['quality' => 85]);
            }
            
            $this->logger->info('File optimization completed', [
                'media_id' => $media->getId(),
                'file_path' => $filePath
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to optimize file', [
                'media_id' => $media->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getMediaTypeFromMimeType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        
        // PDF and other document types
        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv'
        ])) {
            return 'document';
        }
        
        return 'other';
    }

    /**
     * Sanitize SVG files to remove potentially malicious content
     * 
     * @param string $filePath Path to the SVG file
     * @throws \RuntimeException If the SVG file is invalid or cannot be sanitized
     */
    private function sanitizeSvgFile(string $filePath): void
    {
        try {
            $svgContent = file_get_contents($filePath);
            if ($svgContent === false) {
                throw new \RuntimeException('Cannot read SVG file');
            }

            // Parse the SVG as XML
            $dom = new \DOMDocument();
            $dom->loadXML($svgContent, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR);

            // Remove dangerous elements and attributes
            $this->removeDangerousElements($dom);
            $this->removeDangerousAttributes($dom);

            // Save sanitized SVG back to file
            $sanitizedContent = $dom->saveXML();
            if ($sanitizedContent === false || file_put_contents($filePath, $sanitizedContent) === false) {
                throw new \RuntimeException('Failed to save sanitized SVG');
            }

            $this->logger->info('SVG file sanitized successfully', [
                'file_path' => $filePath,
                'original_size' => strlen($svgContent),
                'sanitized_size' => strlen($sanitizedContent)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to sanitize SVG file', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            
            // Delete the potentially malicious file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            throw new \RuntimeException('SVG file contains invalid or potentially malicious content');
        }
    }

    /**
     * Remove dangerous elements from SVG DOM
     */
    private function removeDangerousElements(\DOMDocument $dom): void
    {
        $dangerousElements = [
            'script',
            'object',
            'embed',
            'iframe',
            'form',
            'input',
            'textarea',
            'button',
            'link',
            'meta',
            'base',
            'applet',
            'audio',
            'video',
            'source',
            'track',
            'canvas',
            'foreignObject'
        ];

        foreach ($dangerousElements as $tagName) {
            $elements = $dom->getElementsByTagName($tagName);
            $elementsToRemove = [];
            
            // Collect elements to remove (can't modify during iteration)
            foreach ($elements as $element) {
                $elementsToRemove[] = $element;
            }
            
            // Remove collected elements
            foreach ($elementsToRemove as $element) {
                if ($element->parentNode) {
                    $element->parentNode->removeChild($element);
                }
            }
        }
    }

    /**
     * Remove dangerous attributes from all SVG elements
     */
    private function removeDangerousAttributes(\DOMDocument $dom): void
    {
        $dangerousAttributes = [
            'onload',
            'onerror',
            'onclick',
            'onmouseover',
            'onmouseout',
            'onmousemove',
            'onmousedown',
            'onmouseup',
            'onfocus',
            'onblur',
            'onkeydown',
            'onkeyup',
            'onkeypress',
            'onsubmit',
            'onreset',
            'onselect',
            'onchange',
            'href',
            'xlink:href'
        ];

        $xpath = new \DOMXPath($dom);
        $allElements = $xpath->query('//*');

        if ($allElements) {
            foreach ($allElements as $element) {
                if ($element instanceof \DOMElement) {
                    foreach ($dangerousAttributes as $attr) {
                        if ($element->hasAttribute($attr)) {
                            $element->removeAttribute($attr);
                        }
                    }
                    
                    // Remove any attribute that starts with "on" (event handlers)
                    $attributesToRemove = [];
                    if ($element->attributes) {
                        foreach ($element->attributes as $attribute) {
                            if ($attribute instanceof \DOMAttr && str_starts_with(strtolower($attribute->name), 'on')) {
                                $attributesToRemove[] = $attribute->name;
                            }
                        }
                    }
                    
                    foreach ($attributesToRemove as $attrName) {
                        $element->removeAttribute($attrName);
                    }
                }
            }
        }
    }
}
