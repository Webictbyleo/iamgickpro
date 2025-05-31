<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

readonly class FileUploadService
{
    public function __construct(
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        private string $avatarUploadDirectory,
        private string $mediaUploadDirectory,
        private array $allowedAvatarMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        private int $maxAvatarSize = 2 * 1024 * 1024 // 2MB
    ) {
    }

    public function uploadAvatar(UploadedFile $file): string
    {
        $this->validateAvatarFile($file);

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->avatarUploadDirectory, $fileName);
            
            $this->logger->info('Avatar uploaded successfully', [
                'filename' => $fileName,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]);
            
            return $fileName;
        } catch (FileException $e) {
            $this->logger->error('Avatar upload failed', [
                'error' => $e->getMessage(),
                'original_name' => $file->getClientOriginalName()
            ]);
            
            throw new FileException('Failed to upload avatar: ' . $e->getMessage());
        }
    }

    public function uploadMedia(UploadedFile $file, array $allowedMimeTypes = null): string
    {
        if ($allowedMimeTypes !== null) {
            $this->validateMediaFile($file, $allowedMimeTypes);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->mediaUploadDirectory, $fileName);
            
            $this->logger->info('Media file uploaded successfully', [
                'filename' => $fileName,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            
            return $fileName;
        } catch (FileException $e) {
            $this->logger->error('Media upload failed', [
                'error' => $e->getMessage(),
                'original_name' => $file->getClientOriginalName()
            ]);
            
            throw new FileException('Failed to upload media: ' . $e->getMessage());
        }
    }

    public function deleteFile(string $filename, string $directory): bool
    {
        $filePath = $directory . '/' . $filename;
        
        if (file_exists($filePath)) {
            $deleted = unlink($filePath);
            
            if ($deleted) {
                $this->logger->info('File deleted successfully', [
                    'filename' => $filename,
                    'directory' => $directory
                ]);
            } else {
                $this->logger->error('Failed to delete file', [
                    'filename' => $filename,
                    'directory' => $directory
                ]);
            }
            
            return $deleted;
        }
        
        return false;
    }

    public function deleteAvatar(string $filename): bool
    {
        return $this->deleteFile($filename, $this->avatarUploadDirectory);
    }

    public function deleteMedia(string $filename): bool
    {
        return $this->deleteFile($filename, $this->mediaUploadDirectory);
    }

    public function getAvatarUrl(string $filename): string
    {
        return '/uploads/avatars/' . $filename;
    }

    public function getMediaUrl(string $filename): string
    {
        return '/uploads/media/' . $filename;
    }

    private function validateAvatarFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxAvatarSize) {
            throw new FileException(sprintf(
                'Avatar file is too large (%d bytes). Maximum allowed size is %d bytes.',
                $file->getSize(),
                $this->maxAvatarSize
            ));
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->allowedAvatarMimeTypes, true)) {
            throw new FileException(sprintf(
                'Invalid file type "%s". Allowed types are: %s',
                $mimeType,
                implode(', ', $this->allowedAvatarMimeTypes)
            ));
        }

        // Additional security check: verify the file is actually an image
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new FileException('Uploaded file is not a valid image.');
        }
    }

    private function validateMediaFile(UploadedFile $file, array $allowedMimeTypes): void
    {
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new FileException(sprintf(
                'Invalid file type "%s". Allowed types are: %s',
                $mimeType,
                implode(', ', $allowedMimeTypes)
            ));
        }
    }

    public function createUploadDirectories(): void
    {
        if (!is_dir($this->avatarUploadDirectory)) {
            mkdir($this->avatarUploadDirectory, 0755, true);
            $this->logger->info('Avatar upload directory created', [
                'directory' => $this->avatarUploadDirectory
            ]);
        }

        if (!is_dir($this->mediaUploadDirectory)) {
            mkdir($this->mediaUploadDirectory, 0755, true);
            $this->logger->info('Media upload directory created', [
                'directory' => $this->mediaUploadDirectory
            ]);
        }
    }
}
