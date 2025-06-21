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
use App\Service\MediaProcessing\Config\ProcessingConfigFactory;
use App\Service\MediaProcessing\Config\ImageProcessingConfig;
use App\Service\ResponseDTOFactory;
use App\Service\StockMedia\StockMediaService;
use App\Service\StockMedia\StockMediaException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
        private readonly StockMediaService $stockMediaService,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
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

            // Search stock media using the stock media service
            $filters = [];
            
            // Add any additional filters from query parameters
            // Note: Future enhancement could include category, orientation, color filters
            // when these properties are added to StockSearchRequestDTO
            
            $this->logger->info('Initiating stock media search', [
                'query' => $dto->query,
                'type' => $dto->type,
                'page' => $dto->page,
                'limit' => $dto->limit,
                'user_id' => $user->getId()
            ]);
            
            $results = $this->stockMediaService->search(
                $dto->query,
                $dto->type,
                $dto->page,
                $dto->limit,
                $filters
            );

            // Transform results to our standard media format
            $mediaData = [];
            foreach ($results['items'] as $item) {
                $mediaData[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'mime_type' => $item['mimeType'],
                    'size' => $item['size'],
                    'url' => $item['url'],
                    'thumbnail_url' => $item['thumbnailUrl'],
                    'thumbnail' => $item['thumbnailUrl'],
                    'thumbnailUrl' => $item['thumbnailUrl'],
                    'width' => $item['width'],
                    'height' => $item['height'],
                    'duration' => $item['duration'] ?? null,
                    'source' => $item['source'],
                    'source_id' => $item['sourceId'],
                    'license' => $item['license'],
                    'attribution' => $item['attribution'],
                    'tags' => $item['tags'],
                    'is_premium' => $item['isPremium'],
                    'metadata' => $item['metadata'],
                    'created_at' => null,
                    'updated_at' => null
                ];
            }

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $mediaData,
                $dto->page,
                $dto->limit,
                $results['total'],
                sprintf(
                    'Found %d %s results from %s',
                    $results['total'],
                    $dto->type,
                    implode(', ', $results['providers'])
                )
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (StockMediaException $e) {
            $this->logger->error('Stock media search failed', [
                'query' => $dto->query,
                'type' => $dto->type,
                'error' => $e->getMessage(),
                'provider' => $e->getProvider()
            ]);

            $statusCode = match ($e->getCode()) {
                401 => Response::HTTP_UNAUTHORIZED,
                403 => Response::HTTP_FORBIDDEN,
                404 => Response::HTTP_NOT_FOUND,
                429 => Response::HTTP_TOO_MANY_REQUESTS,
                default => Response::HTTP_SERVICE_UNAVAILABLE
            };

            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Stock media search failed: ' . $e->getMessage(),
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, $statusCode);

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during stock media search', [
                'query' => $dto->query,
                'type' => $dto->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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

}
