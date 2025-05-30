<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
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

    public function uploadFile(UploadedFile $file, User $user, ?string $alt = null): Media
    {
        $this->validateFile($file);

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

        // Get image dimensions if it's an image
        $width = null;
        $height = null;
        if (str_starts_with($mimeType, 'image/')) {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        }

        $media = new Media();
        $media->setName($originalFilename)
              ->setUrl('/media/' . $fileName)
              ->setMimeType($mimeType)
              ->setSize($fileSize)
              ->setWidth($width)
              ->setHeight($height)
              ->setSource('upload')
              ->setUser($user);

        // Store metadata including original filename and file path
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
        if (!str_starts_with($media->getMimeType(), 'image/')) {
            return null;
        }

        if ($media->getSource() !== 'upload') {
            return null;
        }

        $metadata = $media->getMetadata();
        $filePath = $metadata['file_path'] ?? null;
        
        if (!$filePath || !file_exists($filePath)) {
            return null;
        }

        $thumbnailName = 'thumb_' . pathinfo($metadata['original_filename'] ?? 'unknown', PATHINFO_FILENAME) . '.jpg';
        $thumbnailPath = $this->thumbnailDirectory . '/' . $thumbnailName;

        try {
            // Generate thumbnail using ImageMagick
            $command = sprintf(
                'convert "%s" -thumbnail 300x300^ -gravity center -extent 300x300 "%s"',
                escapeshellarg($filePath),
                escapeshellarg($thumbnailPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($thumbnailPath)) {
                $media->setThumbnailUrl('/thumbnails/' . $thumbnailName);
                $this->entityManager->flush();

                $this->logger->info('Thumbnail generated', [
                    'media_id' => $media->getId(),
                    'thumbnail_path' => $thumbnailPath
                ]);

                return $thumbnailPath;
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate thumbnail', [
                'media_id' => $media->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    public function processUploadedFile(Media $media, string $filePath, array $metadata = []): void
    {
        try {
            // Generate thumbnail if it's an image
            if ($this->isImageFile($media)) {
                $thumbnailPath = $this->generateThumbnail($media);
                
                // Update media metadata with thumbnail path if generated
                if ($thumbnailPath) {
                    $currentMetadata = $media->getMetadata();
                    $currentMetadata['thumbnail'] = $thumbnailPath;
                    $media->setMetadata($currentMetadata);
                }
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
        // Extract video metadata (duration, dimensions, etc.)
        try {
            $videoInfo = shell_exec("ffprobe -v quiet -print_format json -show_format -show_streams " . escapeshellarg($filePath));
            if ($videoInfo) {
                $info = json_decode($videoInfo, true);
                $metadata = $media->getMetadata();
                $metadata['video_info'] = $info;
                $media->setMetadata($metadata);
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
        return $this->mediaRepository->findByFilters([
            'query' => $query,
            'type' => $type,
            'source' => $source,
            'user' => $user,
            'limit' => $limit,
            'offset' => $offset
        ]);
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
            $command = sprintf(
                'convert "%s" -quality %d',
                escapeshellarg($filePath),
                $quality
            );

            if ($maxWidth || $maxHeight) {
                $resize = ($maxWidth && $maxHeight) 
                    ? "{$maxWidth}x{$maxHeight}>" 
                    : ($maxWidth ? "{$maxWidth}>" : "x{$maxHeight}>");
                $command .= " -resize {$resize}";
            }

            $command .= sprintf(' "%s"', escapeshellarg($optimizedPath));

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($optimizedPath)) {
                // Replace original with optimized version
                rename($optimizedPath, $filePath);
                
                // Update file size
                $newSize = filesize($filePath);
                $media->setSize($newSize);
                
                // Update dimensions if resized
                if ($maxWidth || $maxHeight) {
                    $imageInfo = getimagesize($filePath);
                    if ($imageInfo) {
                        $media->setWidth($imageInfo[0]);
                        $media->setHeight($imageInfo[1]);
                    }
                }
                
                $this->entityManager->flush();

                $this->logger->info('Image optimized', [
                    'media_id' => $media->getId(),
                    'new_size' => $newSize
                ]);

                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to optimize image', [
                'media_id' => $media->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

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
        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new \InvalidArgumentException(sprintf(
                'File type "%s" is not allowed',
                $mimeType
            ));
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
}
