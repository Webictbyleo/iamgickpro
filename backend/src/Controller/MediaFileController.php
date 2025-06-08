<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Media File Serving Controller
 * 
 * Handles secure serving of uploaded media files and thumbnails.
 * Provides access control, caching, and proper MIME type handling.
 */
class MediaFileController extends AbstractController
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly string $mediaUploadDirectory,
        private readonly string $thumbnailDirectory
    ) {}

    /**
     * Serve media files with access control
     */
    #[Route('/media/{filename}', name: 'serve_media', methods: ['GET'])]
    public function serveMedia(string $filename, Request $request): Response
    {
        $filePath = $this->mediaUploadDirectory . '/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Media file not found');
        }

        // Optional: Check if user has access to this media file
        // This is useful for private media or subscription-based content
        $media = $this->mediaRepository->findOneBy(['url' => '/media/' . $filename]);
        if ($media && !$this->canAccessMedia($media)) {
            throw $this->createAccessDeniedException('Access denied to this media file');
        }

        return $this->createFileResponse($filePath, $filename, $request);
    }

    /**
     * Serve thumbnail files
     */
    #[Route('/thumbnails/{filename}', name: 'serve_thumbnail', methods: ['GET'])]
    public function serveThumbnail(string $filename, Request $request): Response
    {
        $filePath = $this->thumbnailDirectory . '/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Thumbnail file not found');
        }

        return $this->createFileResponse($filePath, $filename, $request, true);
    }

    /**
     * Serve media by UUID (more secure)
     */
    #[Route('/secure-media/{uuid}', name: 'serve_media_by_uuid', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function serveMediaByUuid(string $uuid, Request $request): Response
    {
        $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
        
        if (!$media) {
            throw $this->createNotFoundException('Media not found');
        }

        if (!$this->canAccessMedia($media)) {
            throw $this->createAccessDeniedException('Access denied to this media file');
        }

        $metadata = $media->getMetadata();
        $filePath = $metadata['file_path'] ?? null;
        
        if (!$filePath || !file_exists($filePath)) {
            throw $this->createNotFoundException('Media file not found on disk');
        }

        $filename = basename($filePath);
        return $this->createFileResponse($filePath, $filename, $request);
    }

    /**
     * Create a proper file response with caching and security headers
     */
    private function createFileResponse(
        string $filePath, 
        string $filename, 
        Request $request, 
        bool $isThumbnail = false
    ): BinaryFileResponse {
        $response = new BinaryFileResponse($filePath);
        
        // Set proper content type
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $response->headers->set('Content-Type', $mimeType);
        
        // Set content disposition for downloads vs inline display
        $disposition = $this->shouldForceDownload($mimeType) ? 
            ResponseHeaderBag::DISPOSITION_ATTACHMENT : 
            ResponseHeaderBag::DISPOSITION_INLINE;
        
        $response->setContentDisposition($disposition, $filename);
        
        // Set caching headers
        if ($isThumbnail) {
            // Thumbnails can be cached longer
            $response->setMaxAge(2592000); // 30 days
            $response->setSharedMaxAge(2592000);
        } else {
            // Regular media files
            $response->setMaxAge(604800); // 7 days
            $response->setSharedMaxAge(604800);
        }
        
        // Security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // Handle conditional requests for caching
        $response->isNotModified($request);
        
        return $response;
    }

    /**
     * Check if user can access the media file
     */
    private function canAccessMedia(Media $media): bool
    {
        $user = $this->getUser();
        
        // Allow access if:
        // 1. Media is public and active
        // 2. User owns the media
        // 3. User has admin role
        
        if (!$media->isIsActive()) {
            return false;
        }

        // If user is not logged in, only allow public media
        if (!$user) {
            return !$media->isIsPremium();
        }

        // User owns the media
        if ($media->getUser() === $user) {
            return true;
        }

        // Admin can access everything
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Premium content requires premium subscription
        if ($media->isIsPremium()) {
            return $this->isGranted('ROLE_PREMIUM');
        }

        // Public content is accessible to all users
        return true;
    }

    /**
     * Determine if file should be forced to download
     */
    private function shouldForceDownload(string $mimeType): bool
    {
        // Force download for potentially dangerous file types
        $dangerousTypes = [
            'application/x-executable',
            'application/x-msdownload',
            'application/x-msdos-program',
            'text/html',
            'text/javascript',
            'application/javascript'
        ];
        
        return in_array($mimeType, $dangerousTypes, true);
    }
}
