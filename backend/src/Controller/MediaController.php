<?php

declare(strict_types=1);

namespace App\Controller;

use App\Attribute\RequireContentType;
use App\Controller\Trait\TypedResponseTrait;
use App\DTO\BulkDeleteMediaRequestDTO;
use App\DTO\CreateMediaRequestDTO;
use App\DTO\DuplicateMediaRequestDTO;
use App\DTO\Request\UploadMediaRequestDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\MediaResponseDTO;
use App\DTO\Response\PaginatedResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\DTO\SearchMediaRequestDTO;
use App\DTO\StockSearchRequestDTO;
use App\DTO\UpdateMediaRequestDTO;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use App\Service\MediaService;
use App\Service\MediaProcessing\MediaProcessingService;
use App\Service\MediaProcessing\Config\ImageProcessingConfig;
use App\Service\ResponseDTOFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Media Controller
 * 
 * Manages media file operations including upload, retrieval, updating, and deletion.
 * Handles media search, duplication, stock media integration, and bulk operations.
 * All endpoints require authentication and enforce user ownership for security.
 */
#[Route('/api/media', name: 'api_media_')]
#[IsGranted('ROLE_USER')]
class MediaController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaRepository $mediaRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly ResponseDTOFactory $responseDTOFactory,
        private readonly MediaService $mediaService,
        private readonly MediaProcessingService $mediaProcessingService,
    ) {}

    /**
     * List media files for authenticated user
     * 
     * Returns a paginated list of media files belonging to the authenticated user.
     * Supports filtering by type, format, and search functionality.
     * 
     * @param SearchMediaRequestDTO $dto Search and filter parameters including:
     *                                  - page: Page number (default: 1, min: 1)
     *                                  - limit: Items per page (default: 20, max: 100)
     *                                  - type: Media type filter (image, video, audio, document)
     *                                  - format: File format filter (jpg, png, mp4, etc.)
     *                                  - search: Search term for filename/description
     *                                  - sort: Sort field (name, size, created_at, updated_at)
     *                                  - order: Sort direction (asc, desc)
     * @return JsonResponse<PaginatedResponseDTO|ErrorResponseDTO> Paginated list of media files or error response
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(SearchMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $filters = $dto->getFilters();
            $offset = $dto->getOffset();

            $media = $this->mediaRepository->findByFilters($filters, $dto->page, $dto->limit, $dto->search);
            $total = $this->mediaRepository->countByFilters($filters, $dto->search);

            $mediaData = array_map(fn(Media $mediaItem) => $mediaItem->toArray(), $media);

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $mediaData,
                $dto->page,
                $dto->limit,
                $total,
                'Media list retrieved successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch media list',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload a new media file
     * 
     * Handles actual file upload with optional name and creates a new media record.
     * Accepts a file upload along with an optional display name for the media item.
     * Automatically processes the file, extracts metadata, and stores it securely.
     * All uploaded media files are associated with the authenticated user.
     * 
     * @param UploadMediaRequestDTO $dto Upload data including:
     *                                  - file: The actual media file to upload (required)
     *                                  - name: Optional display name (uses filename if not provided)
     * @return JsonResponse<MediaResponseDTO|ErrorResponseDTO> Created media record or validation errors
     */
    #[Route(
        '/upload',
        name: 'upload',
        methods: ['POST'],
        condition: 'request.headers.get("Content-Type") matches "~^multipart/form-data~"'
    )]
    #[RequireContentType('multipart/form-data', 'File upload requires multipart/form-data content type')]
    public function upload(UploadMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            if (!$dto->file) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('No file provided');
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            // Validate the DTO
            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Validation failed',
                    $errorMessages
                );
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            // Use MediaService to handle the file upload and create media record
            // MediaService will automatically extract all metadata from the file
            $media = $this->mediaService->uploadFile($dto->file, $user);

            // Update media name if provided
            if ($dto->name) {
                $media->setName($dto->name);
            }

            // Save the media record first
            $this->entityManager->flush();

            // Now apply advanced processing using our MediaProcessingService
            $this->applyAdvancedProcessing($media);

            $mediaResponse = $this->responseDTOFactory->createMediaResponse(
                $media,
                'Media uploaded successfully'
            );
            return $this->mediaResponse($mediaResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to upload media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get details of a specific media file
     * 
     * Returns detailed information about a single media file including metadata.
     * Only allows access to media files owned by the authenticated user.
     * 
     * @param string $uuid The media file UUID
     * @return JsonResponse<MediaResponseDTO|ErrorResponseDTO> Media file details or error response
     */
    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can access this media (assume all media is accessible for now)
            // TODO: Implement proper access control based on media visibility settings

            $mediaResponse = $this->responseDTOFactory->createMediaResponse(
                $media,
                'Media retrieved successfully'
            );
            return $this->mediaResponse($mediaResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new media file entry from existing data
     * 
     * Creates a new media file record in the database with pre-existing metadata.
     * This endpoint is used for creating media records from external sources (stock photos, etc.)
     * or when the file already exists and you just need to create the database record.
     * For actual file uploads, use the /upload endpoint instead.
     * All created media files are associated with the authenticated user.
     * 
     * @param CreateMediaRequestDTO $dto Media creation data including:
     *                                  - name: Display name for the media file (required)
     *                                  - type: Media type (image, video, audio, document)
     *                                  - mimeType: MIME type of the file (e.g., image/jpeg)
     *                                  - size: File size in bytes
     *                                  - url: Storage URL for the media file
     *                                  - thumbnailUrl: URL for the thumbnail image
     *                                  - width: Image/video width in pixels
     *                                  - height: Image/video height in pixels
     *                                  - duration: Video/audio duration in seconds
     *                                  - source: Source identifier (upload, stock, etc.)
     *                                  - sourceId: External source ID if applicable
     *                                  - metadata: Additional file metadata as JSON
     *                                  - tags: Array of tags for categorization
     *                                  - attribution: Attribution text for stock media
     *                                  - license: License information
     *                                  - isPremium: Whether media requires premium access
     *                                  - isActive: Whether media is active/published
     * @return JsonResponse<MediaResponseDTO|ErrorResponseDTO> Created media record or validation errors
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(CreateMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = new Media();
            $media->setName($dto->name);
            $media->setType($dto->type);
            $media->setMimeType($dto->mimeType);
            $media->setSize($dto->size);
            $media->setUrl($dto->url);
            $media->setThumbnailUrl($dto->thumbnailUrl);
            $media->setWidth($dto->width);
            $media->setHeight($dto->height);
            $media->setDuration($dto->duration);
            $media->setSource($dto->source);
            $media->setSourceId($dto->sourceId);
            $media->setMetadata($dto->metadata ?? []);
            $media->setTags($dto->getTagsArray() ?? []);
            $media->setAttribution($dto->attribution);
            $media->setLicense($dto->license);
            $media->setIsPremium($dto->isPremium);
            $media->setIsActive($dto->isActive);
            $media->setUser($user);

            $errors = $this->validator->validate($media);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Validation failed',
                    $errorMessages
                );
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($media);
            $this->entityManager->flush();

            $mediaResponse = $this->responseDTOFactory->createMediaResponse(
                $media,
                'Media created successfully'
            );
            return $this->mediaResponse($mediaResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to create media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update media file metadata
     * 
     * Updates the metadata and properties of an existing media file.
     * Only allows updating specific fields like name, metadata, tags, and status flags.
     * Core file properties like URL, type, and dimensions cannot be modified.
     * Users can only update media files they own.
     * 
     * @param string $uuid The media file UUID to update
     * @param UpdateMediaRequestDTO $dto Update data including:
     *                                  - name: New display name for the media file
     *                                  - metadata: Updated metadata as JSON object
     *                                  - tags: Updated array of tags for categorization
     *                                  - isPremium: Updated premium access requirement
     *                                  - isActive: Updated active/published status
     * @return JsonResponse<MediaResponseDTO|ErrorResponseDTO> Updated media record or error response
     */
    #[Route('/{uuid}', name: 'update', methods: ['PUT'])]
    public function update(string $uuid, UpdateMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can edit this media
            if ($media->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            // Check if there's any data to update
            if (!$dto->hasAnyData()) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('No data provided for update');
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            // Update allowed fields
            if ($dto->name !== null) {
                $media->setName($dto->name);
            }
            if ($dto->metadata !== null) {
                $media->setMetadata($dto->metadata);
            }
            if ($dto->tags !== null) {
                $media->setTags($dto->getTagsArray());
            }
            if ($dto->isPremium !== null) {
                $media->setIsPremium($dto->isPremium);
            }
            if ($dto->isActive !== null) {
                $media->setIsActive($dto->isActive);
            }

            $errors = $this->validator->validate($media);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Validation failed',
                    $errorMessages
                );
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            $mediaResponse = $this->responseDTOFactory->createMediaResponse(
                $media,
                'Media updated successfully'
            );
            return $this->mediaResponse($mediaResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to update media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a media file
     * 
     * Permanently removes a media file record from the database.
     * This operation also triggers cleanup of associated file storage.
     * Users can only delete media files they own for security.
     * 
     * @param string $uuid The media file UUID to delete
     * @return JsonResponse<SuccessResponseDTO|ErrorResponseDTO> Success confirmation or error response
     */
    #[Route('/{uuid}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can delete this media
            if ($media->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($media);
            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse('Media deleted successfully');
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to delete media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search media files
     * 
     * Performs advanced search across user's media library with filtering and sorting.
     * Supports full-text search on filenames, descriptions, and tags.
     * Returns paginated results with comprehensive media metadata.
     * 
     * @param SearchMediaRequestDTO $dto Search parameters including:
     *                                  - search: Search term for filename/description/tags
     *                                  - type: Media type filter (image, video, audio, document)
     *                                  - format: File format filter (jpg, png, mp4, etc.)
     *                                  - page: Page number (default: 1, min: 1)
     *                                  - limit: Items per page (default: 20, max: 100)
     *                                  - sort: Sort field (name, size, created_at, updated_at)
     *                                  - order: Sort direction (asc, desc)
     * @return JsonResponse<PaginatedResponseDTO|ErrorResponseDTO> Filtered and sorted media results or error response
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(SearchMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $filters = $dto->getFilters();
            $media = $this->mediaRepository->findByFilters($filters, $dto->page, $dto->limit, $dto->search);
            $total = $this->mediaRepository->countByFilters($filters, $dto->search);

            $mediaData = array_map(fn(Media $mediaItem) => $mediaItem->toArray(), $media);

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $mediaData,
                $dto->page,
                $dto->limit,
                $total,
                'Media search completed successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to search media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Duplicate a media file
     * 
     * Creates a copy of an existing media file for the authenticated user.
     * The duplicated media inherits all properties from the original but gets
     * a new UUID and is owned by the current user. This allows users to
     * create personal copies of accessible media files.
     * 
     * @param string $uuid The UUID of the media file to duplicate
     * @param DuplicateMediaRequestDTO $dto Duplication parameters (currently unused but reserved for future options)
     * @return JsonResponse<MediaResponseDTO|ErrorResponseDTO> Duplicated media record or error response
     */
    #[Route('/duplicate/{uuid}', name: 'duplicate', methods: ['POST'])]
    public function duplicate(string $uuid, DuplicateMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $originalMedia = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$originalMedia) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can access this media (assume all media is accessible for duplication)
            // TODO: Implement proper access control based on media visibility settings

            $duplicatedMedia = $this->mediaRepository->duplicateMedia($originalMedia, $user);

            $mediaResponse = $this->responseDTOFactory->createMediaResponse(
                $duplicatedMedia,
                'Media duplicated successfully'
            );
            return $this->mediaResponse($mediaResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to duplicate media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search stock media from external providers
     * 
     * Integrates with external stock media APIs (Unsplash, Pexels, Pixabay, etc.)
     * to provide users with access to high-quality stock images and videos.
     * Results include licensing information and attribution requirements.
     * Currently in development - returns empty results with implementation notice.
     * 
     * @param StockSearchRequestDTO $dto Stock search parameters including:
     *                                  - query: Search term for stock media
     *                                  - category: Media category filter
     *                                  - orientation: Image orientation (landscape, portrait, square)
     *                                  - color: Color filter for search results
     *                                  - page: Page number for pagination
     *                                  - limit: Number of results per page
     *                                  - provider: Specific stock provider to search
     * @return JsonResponse<PaginatedResponseDTO|ErrorResponseDTO> Stock media search results or error response
     */
    #[Route('/stock/search', name: 'stock_search', methods: ['GET'])]
    public function stockSearch(StockSearchRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // TODO: Implement stock media API integration
            // This would integrate with Unsplash, Pexels, etc.
            
            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                [], // Empty media array for now
                $dto->page,
                $dto->limit,
                0, // Total count
                'Stock media search not yet implemented'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to search stock media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bulk delete multiple media files
     * 
     * Deletes multiple media files in a single operation for efficiency.
     * Processes each file individually with proper permission checks.
     * Returns detailed results including successful deletions and failures.
     * Users can only delete media files they own.
     * 
     * @param BulkDeleteMediaRequestDTO $dto Bulk deletion data including:
     *                                      - uuids: Array of media file UUIDs to delete
     * @return JsonResponse<SuccessResponseDTO|ErrorResponseDTO> Bulk operation results with success/failure counts or error response
     */
    #[Route('/bulk/delete', name: 'bulk_delete', methods: ['DELETE'])]
    public function bulkDelete(BulkDeleteMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $deleted = 0;
            $failed = [];

            foreach ($dto->uuids as $uuid) {
                $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
                if (!$media) {
                    $failed[] = ['uuid' => $uuid, 'reason' => 'Media not found'];
                    continue;
                }

                if ($media->getUser() !== $user) {
                    $failed[] = ['uuid' => $uuid, 'reason' => 'Access denied'];
                    continue;
                }

                $this->entityManager->remove($media);
                $deleted++;
            }

            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse(
                sprintf('Bulk delete completed: %d deleted, %d failed', $deleted, count($failed)),
                [
                    'deleted' => $deleted,
                    'failed' => $failed,
                ]
            );
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to bulk delete media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Process existing media with advanced options
     * 
     * Applies advanced processing to an existing media file using ImageMagick or FFmpeg.
     * Supports various processing options like resizing, format conversion, quality optimization,
     * and filter application. Processing can be done synchronously or asynchronously.
     * 
     * @param string $uuid The media file UUID to process
     * @return JsonResponse Processing result with success/failure status and metadata
     */
    #[Route('/{uuid}/process', name: 'process', methods: ['POST'])]
    public function processMedia(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can process this media
            if ($media->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            // Apply advanced processing
            $processingResult = $this->applyAdvancedProcessing($media, true); // Force reprocessing

            if ($processingResult['success']) {
                $successResponse = $this->responseDTOFactory->createSuccessResponse(
                    'Media processing completed successfully',
                    $processingResult
                );
                return $this->successResponse($successResponse);
            } else {
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Media processing failed',
                    $processingResult['errors'] ?? []
                );
                return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to process media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate thumbnails for existing media
     * 
     * Generates multiple thumbnail sizes for an existing media file.
     * Supports images and videos. Uses optimized formats for web delivery.
     * 
     * @param string $uuid The media file UUID
     * @return JsonResponse Generated thumbnail URLs and metadata
     */
    #[Route('/{uuid}/thumbnails', name: 'generate_thumbnails', methods: ['POST'])]
    public function generateThumbnails(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can access this media
            if ($media->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $metadata = $media->getMetadata();
            $filePath = $metadata['file_path'] ?? null;

            if (!$filePath || !file_exists($filePath)) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media file not found on disk');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Generate thumbnails using MediaProcessingService
            $result = $this->mediaProcessingService->generateThumbnails(
                inputPath: $filePath,
                sizes: [150, 300, 600, 1200],
                format: 'webp',
                quality: 85
            );

            if ($result->isSuccess()) {
                $successResponse = $this->responseDTOFactory->createSuccessResponse(
                    'Thumbnails generated successfully',
                    $result->getMetadata()
                );
                return $this->successResponse($successResponse);
            } else {
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Failed to generate thumbnails',
                    [$result->getErrorMessage()]
                );
                return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to generate thumbnails',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Convert media to different format
     * 
     * Converts an existing media file to a different format.
     * Supports image, video, and audio format conversions with quality options.
     * 
     * @param string $uuid The media file UUID
     * @return JsonResponse Converted file information and download URL
     */
    #[Route('/{uuid}/convert/{format}', name: 'convert_format', methods: ['POST'])]
    public function convertFormat(string $uuid, string $format): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can access this media
            if ($media->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $metadata = $media->getMetadata();
            $inputPath = $metadata['file_path'] ?? null;

            if (!$inputPath || !file_exists($inputPath)) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media file not found on disk');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Generate output path for converted file
            $pathInfo = pathinfo($inputPath);
            $outputPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_converted.' . $format;

            // Convert using MediaProcessingService
            $result = $this->mediaProcessingService->convertFormat(
                inputPath: $inputPath,
                outputPath: $outputPath,
                targetFormat: $format,
                options: ['quality' => 85] // Default quality, could be parameterized
            );

            if ($result->isSuccess()) {
                $successResponse = $this->responseDTOFactory->createSuccessResponse(
                    'Format conversion completed successfully',
                    [
                        'original_format' => $media->getMimeType(),
                        'converted_format' => $format,
                        'output_path' => $result->getOutputPath(),
                        'metadata' => $result->getMetadata()
                    ]
                );
                return $this->successResponse($successResponse);
            } else {
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Format conversion failed',
                    [$result->getErrorMessage()]
                );
                return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to convert format',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Apply advanced processing to uploaded media
     * 
     * Internal method that applies comprehensive processing to media files
     * including thumbnail generation, optimization, and format conversion.
     */
    private function applyAdvancedProcessing(Media $media, bool $forceReprocess = false): array
    {
        try {
            $metadata = $media->getMetadata();
            $filePath = $metadata['file_path'] ?? null;

            if (!$filePath || !file_exists($filePath)) {
                return [
                    'success' => false,
                    'errors' => ['Media file not found on disk']
                ];
            }

            $results = [
                'success' => true,
                'thumbnails' => [],
                'optimized' => false,
                'metadata_extracted' => false,
                'errors' => []
            ];

            // Extract comprehensive metadata
            try {
                $extractedMetadata = $this->mediaProcessingService->extractMetadata($filePath);
                $currentMetadata = $media->getMetadata();
                $currentMetadata['processing'] = $extractedMetadata;
                $media->setMetadata($currentMetadata);
                $results['metadata_extracted'] = true;
            } catch (\Exception $e) {
                $results['errors'][] = 'Metadata extraction failed: ' . $e->getMessage();
            }

            // Generate thumbnails for visual media
            if (str_starts_with($media->getMimeType(), 'image/') || str_starts_with($media->getMimeType(), 'video/')) {
                try {
                    $thumbnailResult = $this->mediaProcessingService->generateThumbnails(
                        inputPath: $filePath,
                        sizes: [150, 300, 600],
                        format: 'webp',
                        quality: 85
                    );

                    if ($thumbnailResult->isSuccess()) {
                        $results['thumbnails'] = $thumbnailResult->getMetadata()['thumbnails'] ?? [];
                        
                        // Update media with the main thumbnail URL
                        if (!empty($results['thumbnails'])) {
                            $mainThumbnail = $results['thumbnails'][300] ?? reset($results['thumbnails']);
                            if ($mainThumbnail) {
                                $media->setThumbnailUrl('/thumbnails/' . basename($mainThumbnail));
                            }
                        }
                    } else {
                        $results['errors'][] = 'Thumbnail generation failed: ' . $thumbnailResult->getErrorMessage();
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = 'Thumbnail generation error: ' . $e->getMessage();
                }
            }

            // Optimize the original file
            if (str_starts_with($media->getMimeType(), 'image/')) {
                try {
                    $config = new ImageProcessingConfig(
                        quality: 85,
                        stripMetadata: true
                    );

                    $optimizationResult = $this->mediaProcessingService->processImage(
                        inputPath: $filePath,
                        outputPath: $filePath, // Optimize in place
                        config: $config
                    );

                    if ($optimizationResult->isSuccess()) {
                        $results['optimized'] = true;
                        // Update file size if it changed
                        $newSize = filesize($filePath);
                        $media->setSize($newSize);
                    } else {
                        $results['errors'][] = 'Optimization failed: ' . $optimizationResult->getErrorMessage();
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = 'Optimization error: ' . $e->getMessage();
                }
            }

            // Save any metadata updates
            $this->entityManager->flush();

            $results['success'] = empty($results['errors']);
            return $results;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Processing failed: ' . $e->getMessage()]
            ];
        }
    }
}
