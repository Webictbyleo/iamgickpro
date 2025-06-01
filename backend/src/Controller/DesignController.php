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
        private readonly EntityManagerInterface $entityManager,
        private readonly DesignRepository $designRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly ResponseDTOFactory $responseDTOFactory,
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

            $projectId = $request->query->get('project');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $offset = ($page - 1) * $limit;

            if ($projectId) {
                $project = $this->projectRepository->find($projectId);
                if (!$project || ($project->getUser() !== $user && !$project->getIsPublic())) {
                    $errorResponse = $this->responseDTOFactory->createErrorResponse('Project not found or access denied');
                    return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
                }
                $designs = $this->designRepository->findByProject($project, $limit, $offset);
                $total = count($this->designRepository->findByProject($project));
            } else {
                $designs = $this->designRepository->findByUser($user, $limit, $offset);
                $total = count($this->designRepository->findByUser($user));
            }

            $designResponses = array_map(
                fn(Design $design) => $this->responseDTOFactory->createDesignResponse($design),
                $designs
            );

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $designResponses,
                $page,
                $limit,
                $total,
                'Designs retrieved successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch designs',
                [$e->getMessage()]
            );
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

            // Validate project access if project ID is provided
            if ($dto->hasProjectId()) {
                $project = $this->projectRepository->find($dto->projectId);
                if (!$project || $project->getUser() !== $user) {
                    $errorResponse = $this->responseDTOFactory->createErrorResponse('Project not found or access denied');
                    return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
                }
            }

            $design = new Design();
            $design->setName($dto->name);
            $design->setWidth($dto->width);
            $design->setHeight($dto->height);
            $design->setCanvasWidth($dto->width);
            $design->setCanvasHeight($dto->height);
            $design->setData($dto->data);
            $design->setBackground(['type' => 'color', 'color' => '#ffffff']); // Default background
            
            if ($dto->description) {
                $design->setTitle($dto->description); // Assuming title field stores description
            }

            if ($dto->hasProjectId()) {
                $design->setProject($project);
            }

            // Validate design
            $errors = $this->validator->validate($design);
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

            $this->entityManager->persist($design);
            $this->entityManager->flush();

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $design, 
                'Design created successfully'
            );
            return $this->designResponse($designResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create design',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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

            $design = $this->designRepository->find($id);
            if (!$design) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Design not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this design
            $project = $design->getProject();
            if ($project->getUser() !== $user && !$project->getIsPublic()) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $design,
                'Design retrieved successfully'
            );
            return $this->designResponse($designResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch design',
                [$e->getMessage()]
            );
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

            $design = $this->designRepository->find($id);
            if (!$design) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Design not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this design
            if ($design->getProject()->getUser() !== $user) {
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
                $design->setName($dto->name);
            }

            if ($dto->width !== null) {
                $design->setCanvasWidth($dto->width);
            }

            if ($dto->height !== null) {
                $design->setCanvasHeight($dto->height);
            }

            if ($dto->data !== null) {
                $design->setData($dto->data);
            }

            if ($dto->description !== null) {
                $design->setTitle($dto->description); // Assuming title field stores description
            }

            // Update hasAnimations based on layer animations
            $hasAnimations = $design->getLayers()->exists(function($key, $layer) {
                return !empty($layer->getAnimations());
            });
            $design->setHasAnimations($hasAnimations);

            // Validate design
            $errors = $this->validator->validate($design);
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

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $design,
                'Design updated successfully'
            );
            return $this->designResponse($designResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to update design',
                [$e->getMessage()]
            );
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

            $design = $this->designRepository->find($id);
            if (!$design) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Design not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this design
            if ($design->getProject()->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($design);
            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse('Design deleted successfully');
            return $this->successResponse($successResponse);

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

            $originalDesign = $this->designRepository->find($id);
            if (!$originalDesign) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Design not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this design
            $project = $originalDesign->getProject();
            if ($project->getUser() !== $user && !$project->getIsPublic()) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $newName = $dto->name ?? $originalDesign->getName() . ' (Copy)';
            $targetProjectId = $dto->projectId ?? $project->getId();

            // Verify target project access
            $targetProject = $this->projectRepository->find($targetProjectId);
            if (!$targetProject || $targetProject->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Target project not found or access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $duplicatedDesign = $this->designRepository->duplicateDesign($originalDesign, $targetProject, $newName);

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $duplicatedDesign,
                'Design duplicated successfully'
            );
            return $this->designResponse($designResponse, Response::HTTP_CREATED);

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

            $design = $this->designRepository->find($id);
            if (!$design) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Design not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this design
            if ($design->getProject()->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $design->setThumbnail($dto->thumbnail);
            $this->entityManager->flush();

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $design,
                'Thumbnail updated successfully'
            );
            return $this->designResponse($designResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to update thumbnail',
                [$e->getMessage()]
            );
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
            $offset = ($page - 1) * $limit;

            $designs = $this->designRepository->searchByName($query, $limit, $offset);

            // Filter designs the user has access to
            $accessibleDesigns = array_filter($designs, function(Design $design) use ($user) {
                $project = $design->getProject();
                return $project->getUser() === $user || $project->getIsPublic();
            });

            $designResponses = array_map(
                fn(Design $design) => $this->responseDTOFactory->createDesignResponse($design),
                $accessibleDesigns
            );

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $designResponses,
                $page,
                $limit,
                count($accessibleDesigns),
                'Search results retrieved successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to search designs',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
