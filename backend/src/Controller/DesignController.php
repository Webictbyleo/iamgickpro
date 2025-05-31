<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\CreateDesignRequestDTO;
use App\DTO\DuplicateDesignRequestDTO;
use App\DTO\SearchRequestDTO;
use App\DTO\UpdateDesignRequestDTO;
use App\DTO\UpdateDesignThumbnailRequestDTO;
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
     * @param CreateDesignRequestDTO $dto Design creation data
     * @return JsonResponse<DesignResponseDTO|ErrorResponseDTO>
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
