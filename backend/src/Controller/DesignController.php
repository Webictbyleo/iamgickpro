<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\CreateDesignRequestDTO;
use App\DTO\DuplicateDesignRequestDTO;
use App\DTO\SearchRequestDTO;
use App\DTO\UpdateDesignRequestDTO;
use App\DTO\UpdateDesignThumbnailRequestDTO;
use App\DTO\Response\DesignResponseDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\PaginatedResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\Entity\Design;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\DesignRepository;
use App\Repository\ProjectRepository;
use App\Service\ResponseDTOFactory;
use App\Service\DesignService;
use App\Service\MediaProcessing\MediaProcessingService;
use App\Service\MediaProcessing\Config\ImageProcessingConfig;
use App\Controller\Trait\TypedResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Design Controller
 * 
 * Manages design operations including creation, retrieval, updating, and deletion.
 * Handles design duplication, thumbnail management, and search functionality.
 * All endpoints require authentication and enforce user ownership for security.
 */
#[Route('/api/designs', name: 'api_designs_')]
#[IsGranted('ROLE_USER')]
class DesignController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly DesignService $designService,
        private readonly ResponseDTOFactory $responseDTOFactory,
        private readonly MediaProcessingService $mediaProcessingService,
    ) {}

    /**
     * List designs for authenticated user
     * 
     * Returns a paginated list of designs belonging to the authenticated user.
     * Supports filtering by project, status, and search functionality.
     * 
     * @param Request $request HTTP request with optional query parameters:
     *                        - page: Page number (default: 1, min: 1)
     *                        - limit: Items per page (default: 20, max: 100)
     *                        - project_id: Filter by project ID
     *                        - search: Search term for design name/description
     *                        - sort: Sort field (name, created_at, updated_at)
     *                        - order: Sort direction (asc, desc)
     * @return JsonResponse<PaginatedResponseDTO|ErrorResponseDTO> Paginated list of designs or error response
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $projectId = $request->query->get('project') ? (int) $request->query->get('project') : null;
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $search = $request->query->get('search');
            $sortField = $request->query->get('sort', 'updatedAt');
            $sortField = lcfirst(str_replace('_', '', ucwords($sortField, '_')));

            $result = $this->designService->getUserDesigns(
                $user, 
                $page, 
                $limit, 
                $search, 
                $projectId, 
                $sortField
            );

                       $designResponses = array_map(
                fn(Design $design) => array(
                    'id' => $design->getId(),
                    'name' => $design->getName(),
                    'title' => $design->getName(),
                    'description' => $design->getTitle(), // Assuming title field stores description
                    'thumbnail' => $design->getThumbnail(),
                    'width' => $design->getCanvasWidth(),
                    'height' => $design->getCanvasHeight(),
                    'createdAt' => $design->getCreatedAt()->format('c'),
                    'updatedAt' => $design->getUpdatedAt()->format('c'),
                ),
                $result['designs']
            );


            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $designResponses,
                $page,
                $limit,
                $result['total'],
                'Designs retrieved successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\InvalidArgumentException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse('Failed to retrieve designs', [$e->getMessage()]);
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    /**
     * Create a new design
     * 
     * Creates a new design with the provided information and associates it with the authenticated user.
     * Validates design data and initializes default canvas settings.
     * 
     * @param CreateDesignRequestDTO $dto Design creation data including:
     *                                   - name: Design name (required)
     *                                   - description: Design description (optional)
     *                                   - projectId: Associated project ID (optional)
     *                                   - canvasData: Initial canvas configuration (optional)
     *                                   - thumbnail: Design thumbnail URL (optional)
     *                                   - width: Canvas width in pixels (default: 800)
     *                                   - height: Canvas height in pixels (default: 600)
     * @return JsonResponse<DesignResponseDTO|ErrorResponseDTO> Created design details or error response
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(CreateDesignRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $design = $this->designService->createDesignFromRequest(
                $user,
                $dto->name,
                $dto->width,
                $dto->height,
                $dto->description,
                $dto->hasProjectId() ? $dto->projectId : null,
                $dto->getDataArray()
            );

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $design, 
                'Design created successfully'
            );
            return $this->designResponse($designResponse, Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse('Failed to create design', [$e->getMessage()]);
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search designs
     * 
     * Performs a comprehensive search across designs accessible to the authenticated user.
     * Searches in design names, descriptions, and associated project information.
     * 
     * @param Request $request HTTP request with search parameters:
     *                        - q: Search query term (required)
     *                        - page: Page number (default: 1, min: 1)
     *                        - limit: Items per page (default: 20, max: 100)
     *                        - project_id: Filter by specific project (optional)
     *                        - sort: Sort field (relevance, name, created_at, updated_at)
     *                        - order: Sort direction (asc, desc)
     * @return JsonResponse<PaginatedResponseDTO|ErrorResponseDTO> Search results or error response
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $query = $request->query->get('q');
            if (!$query) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Search query is required');
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $projectId = $request->query->get('project_id') ? (int) $request->query->get('project_id') : null;
            $sortField = $request->query->get('sort', 'relevance');
            $sortOrder = $request->query->get('order', 'DESC');

            $result = $this->designService->searchDesigns(
                $user,
                $query,
                $page,
                $limit,
                $projectId,
                $sortField,
                $sortOrder
            );

            $designResponses = array_map(function (Design $design) {
                return $this->responseDTOFactory->createDesignResponse($design);
            }, $result['designs']);

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $designResponses,
                $page,
                $limit,
                $result['total'],
                'Search results retrieved successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\InvalidArgumentException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse('Failed to search designs', [$e->getMessage()]);
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get details of a specific design
     * 
     * Returns detailed information about a single design including canvas data and layers.
     * Only allows access to designs owned by the authenticated user or public designs.
     * 
     * @param int $id The design ID
     * @return JsonResponse<DesignResponseDTO|ErrorResponseDTO> Design details or error response
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $design = $this->designService->getDesignForUser($id, $user);
            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $design,
                'Design retrieved successfully',
                true // Include layers for the show method
            );
            return $this->designResponse($designResponse);

        } catch (\InvalidArgumentException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse('Failed to fetch design', [$e->getMessage()]);
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing design
     * 
     * Updates design information and canvas data with the provided information.
     * Only allows updates to designs owned by the authenticated user.
     * Supports partial updates and handles canvas data versioning.
     * 
     * @param int $id The design ID to update
     * @param UpdateDesignRequestDTO $dto Updated design data including:
     *                                   - name: Design name (optional)
     *                                   - description: Design description (optional)
     *                                   - canvasData: Updated canvas configuration (optional)
     *                                   - thumbnail: Design thumbnail URL (optional)
     *                                   - width: Canvas width in pixels (optional)
     *                                   - height: Canvas height in pixels (optional)
     * @return JsonResponse<DesignResponseDTO|ErrorResponseDTO> Updated design details or error response
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, UpdateDesignRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if there's any data to update
            if (!$dto->hasAnyData()) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('No data provided for update');
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            $design = $this->designService->getDesignForUser($id, $user);
            
            $updatedDesign = $this->designService->updateDesign(
                $design,
                $dto->name,
                $dto->description,
                $dto->getDataArray(),
                $dto->width,
                $dto->height
            );

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $updatedDesign,
                'Design updated successfully'
            );
            return $this->designResponse($designResponse);

        } catch (\InvalidArgumentException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse('Failed to update design', [$e->getMessage()]);
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a design
     * 
     * Permanently deletes a design and all its associated data (layers, media files, export jobs).
     * Only allows deletion of designs owned by the authenticated user.
     * This action cannot be undone.
     * 
     * @param int $id The design ID to delete
     * @return JsonResponse<SuccessResponseDTO|ErrorResponseDTO> Success confirmation or error response
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $design = $this->designService->getDesignForUser($id, $user);
            $this->designService->deleteDesign($design);

            $successResponse = $this->responseDTOFactory->createSuccessResponse('Design deleted successfully');
            return $this->successResponse($successResponse);

        } catch (\InvalidArgumentException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to delete design',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Duplicate an existing design
     * 
     * Creates a copy of an existing design with all its layers and settings.
     * Only allows duplication of designs owned by the authenticated user or public designs.
     * The duplicated design is always private and owned by the authenticated user.
     * 
     * @param int $id The design ID to duplicate
     * @param DuplicateDesignRequestDTO $dto Duplication options including:
     *                                      - name: Name for the duplicated design (optional, defaults to "Copy of {original name}")
     *                                      - projectId: Target project ID (optional)
     *                                      - includeLayers: Whether to include all layers (default: true)
     * @return JsonResponse<DesignResponseDTO|ErrorResponseDTO> Duplicated design details or error response
     */
    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'])]
    public function duplicate(int $id, DuplicateDesignRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $originalDesign = $this->designService->getDesignForDuplication($id, $user);
            
            $newName = $dto->name ?? $originalDesign->getName() . ' (Copy)';

            $duplicatedDesign = $this->designService->duplicateDesignFromRequest(
                $originalDesign,
                $newName,
                $dto->projectId,
                $user
            );

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $duplicatedDesign,
                'Design duplicated successfully'
            );
            return $this->designResponse($designResponse, Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to duplicate design',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update design thumbnail
     * 
     * Updates the thumbnail image for a design.
     * Only allows updates to designs owned by the authenticated user.
     * Validates thumbnail format and size requirements.
     * 
     * @param int $id The design ID to update thumbnail for
     * @param UpdateDesignThumbnailRequestDTO $dto Thumbnail data including:
     *                                             - thumbnail: Base64 encoded image or URL (required)
     *                                             - format: Image format (png, jpg, webp)
     * @return JsonResponse<DesignResponseDTO|ErrorResponseDTO> Updated design details or error response
     */
    #[Route('/{id}/thumbnail', name: 'update_thumbnail', methods: ['PUT'])]
    public function updateThumbnail(int $id, UpdateDesignThumbnailRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $design = $this->designService->getDesignForUser($id, $user);
            
            $updatedDesign = $this->designService->updateDesignThumbnail($design, $dto->thumbnail);

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $updatedDesign,
                'Thumbnail updated successfully'
            );
            return $this->designResponse($designResponse);

        } catch (\InvalidArgumentException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to update thumbnail',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
