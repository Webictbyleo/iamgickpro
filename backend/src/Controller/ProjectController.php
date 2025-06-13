<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\CreateProjectRequestDTO;
use App\DTO\DuplicateProjectRequestDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\PaginatedResponseDTO;
use App\DTO\Response\ProjectResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\DTO\SearchProjectsRequestDTO;
use App\DTO\UpdateProjectRequestDTO;
use App\Entity\Project;
use App\Entity\User;
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
 * Project Controller
 * 
 * Manages project operations including creation, retrieval, updating, and deletion.
 * Handles project sharing, duplication, and public project discovery.
 * All endpoints require authentication and enforce user ownership for security.
 */
#[Route('/api/projects', name: 'api_projects_')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectRepository $projectRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    /**
     * List projects for authenticated user
     * 
     * Returns a paginated list of projects belonging to the authenticated user.
     * Supports filtering by status, sorting by various fields, and search functionality.
     * 
     * @param Request $request HTTP request with optional query parameters:
     *                        - page: Page number (default: 1, min: 1)
     *                        - limit: Items per page (default: 20, max: 100)
     *                        - sort: Sort field (name, created_at, updated_at)
     *                        - order: Sort direction (asc, desc)
     *                        - search: Search term for project name/description
     *                        - status: Filter by project status
     * @return JsonResponse<PaginatedResponseDTO|ErrorResponseDTO> Paginated list of projects or error response
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

            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $offset = ($page - 1) * $limit;

            $search = $request->query->get('search');
            $tags = $request->query->get('tags');
            $sortBy = $request->query->get('sort', 'updated');
            $sortOrder = $request->query->get('order', 'desc');

            if ($search) {
                $projects = $this->projectRepository->searchByName($search, $limit, $offset);
                $total = count($this->projectRepository->searchByName($search));
            } elseif ($tags) {
                $tagArray = explode(',', $tags);
                $projects = $this->projectRepository->findByTags($tagArray, $limit, $offset);
                $total = count($this->projectRepository->findByTags($tagArray));
            } else {
                $projects = $this->projectRepository->findByUser($user, $limit, $offset, $sortBy, $sortOrder);
                $total = $this->projectRepository->countUserProjects($user);
            }

            $projectResponses = array_map(
                fn(Project $project) => $this->responseDTOFactory->createProjectResponse($project),
                $projects
            );

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $projectResponses,
                $page,
                $limit,
                $total,
                'Projects retrieved successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch projects',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new project
     * 
     * Creates a new project with the provided information and associates it with the authenticated user.
     * Validates project data and sets default values for optional fields.
     * 
     * @param CreateProjectRequestDTO $dto Project creation data including:
     *                                    - name: Project name (required)
     *                                    - description: Project description (optional)
     *                                    - thumbnail: Project thumbnail URL (optional)
     *                                    - isPublic: Whether project is publicly visible (default: false)
     *                                    - tags: Array of project tags (optional)
     * @return JsonResponse<ProjectResponseDTO|ErrorResponseDTO> Created project details or error response
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(CreateProjectRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $project = new Project();
            $project->setName($dto->name);
            $project->setDescription($dto->description ?? '');
            $project->setTags($dto->getTagsArray());
            $project->setIsPublic($dto->isPublic);
            $project->setUser($user);

            if ($dto->thumbnail) {
                $project->setThumbnail($dto->thumbnail);
            }

            // Validate project
            $errors = $this->validator->validate($project);
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

            $this->entityManager->persist($project);
            $this->entityManager->flush();

            $projectResponse = $this->responseDTOFactory->createProjectResponse(
                $project,
                'Project created successfully'
            );
            return $this->projectResponse($projectResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to create project',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * List public projects
     * 
     * Returns a paginated list of publicly shared projects from all users.
     * Supports search, filtering, and sorting functionality for project discovery.
     * 
     * @param SearchProjectsRequestDTO $dto Search and filter parameters including:
     *                                     - page: Page number (default: 1, min: 1)
     *                                     - limit: Items per page (default: 20, max: 100)
     *                                     - search: Search term for project name/description
     *                                     - tags: Array of tags to filter by
     *                                     - sort: Sort field (name, created_at, updated_at, views)
     *                                     - order: Sort direction (asc, desc)
     *                                     - category: Project category filter
     * @return JsonResponse<PaginatedResponseDTO|ErrorResponseDTO> Paginated list of public projects or error response
     */
    #[Route('/public', name: 'public', methods: ['GET'])]
    public function publicProjects(SearchProjectsRequestDTO $dto): JsonResponse
    {
        try {
            $offset = $dto->getOffset();
            $tagArray = $dto->getTagsArray();

            if ($dto->search) {
                $projects = $this->projectRepository->findPublicProjects($dto->search, null, $dto->limit, $offset);
                $total = count($this->projectRepository->findPublicProjects($dto->search));
            } elseif ($tagArray) {
                $projects = $this->projectRepository->findPublicProjects(null, $tagArray, $dto->limit, $offset);
                $total = count($this->projectRepository->findPublicProjects(null, $tagArray));
            } else {
                $projects = $this->projectRepository->findPublicProjects(null, null, $dto->limit, $offset);
                $total = count($this->projectRepository->findPublicProjects());
            }

            $projectDTOs = array_map(function (Project $project) {
                return [
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                    'description' => $project->getDescription(),
                    'tags' => $project->getTags(),
                    'isPublic' => $project->getIsPublic(),
                    'createdAt' => $project->getCreatedAt()->format('c'),
                    'updatedAt' => $project->getUpdatedAt()?->format('c'),
                    'designCount' => count($project->getDesigns()),
                    'thumbnail' => $project->getThumbnail(),
                    'user' => [
                        'id' => $project->getUser()->getId(),
                        'name' => $project->getUser()->getName(),
                        'username' => $project->getUser()->getUsername(),
                        'avatar' => $project->getUser()->getAvatar(),
                    ],
                ];
            }, $projects);

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $projectDTOs,
                $dto->page,
                $dto->limit,
                $total,
                'Public projects retrieved successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch public projects',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get details of a specific project
     * 
     * Returns detailed information about a single project.
     * Only allows access to projects owned by the authenticated user or public projects.
     * 
     * @param int $id The project ID
     * @return JsonResponse<ProjectResponseDTO|ErrorResponseDTO> Project details or error response
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

            $project = $this->projectRepository->find($id);
            if (!$project) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Project not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this project
            if ($project->getUser() !== $user && !$project->getIsPublic()) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $projectResponse = $this->responseDTOFactory->createProjectResponse(
                $project,
                'Project retrieved successfully'
            );
            return $this->projectResponse($projectResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch project',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing project
     * 
     * Updates project information with the provided data.
     * Only allows updates to projects owned by the authenticated user.
     * Validates updated data and handles partial updates.
     * 
     * @param int $id The project ID to update
     * @param UpdateProjectRequestDTO $dto Updated project data including:
     *                                     - name: Project name (optional)
     *                                     - description: Project description (optional)
     *                                     - thumbnail: Project thumbnail URL (optional)
     *                                     - isPublic: Whether project is publicly visible (optional)
     *                                     - tags: Array of project tags (optional)
     * @return JsonResponse<ProjectResponseDTO|ErrorResponseDTO> Updated project details or error response
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, UpdateProjectRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $project = $this->projectRepository->find($id);
            if (!$project) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Project not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this project
            if ($project->getUser() !== $user) {
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
                $project->setName($dto->name);
            }

            if ($dto->description !== null) {
                $project->setDescription($dto->description);
            }

            if ($dto->tags !== null) {
                $project->setTags($dto->getTagsArray());
            }

            if ($dto->isPublic !== null) {
                $project->setIsPublic($dto->isPublic);
            }

            if ($dto->thumbnail !== null) {
                $project->setThumbnail($dto->thumbnail);
            }

            // Validate project
            $errors = $this->validator->validate($project);
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

            $projectResponse = $this->responseDTOFactory->createProjectResponse(
                $project,
                'Project updated successfully'
            );
            return $this->projectResponse($projectResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to update project',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a project
     * 
     * Permanently deletes a project and all its associated data (designs, media files, etc.).
     * Only allows deletion of projects owned by the authenticated user.
     * This action cannot be undone.
     * 
     * @param int $id The project ID to delete
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

            $project = $this->projectRepository->find($id);
            if (!$project) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Project not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this project
            if ($project->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($project);
            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse('Project deleted successfully');
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to delete project',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Duplicate an existing project
     * 
     * Creates a copy of an existing project with all its designs and settings.
     * Only allows duplication of projects owned by the authenticated user or public projects.
     * The duplicated project is always private and owned by the authenticated user.
     * 
     * @param int $id The project ID to duplicate
     * @param DuplicateProjectRequestDTO $dto Duplication options including:
     *                                        - name: Name for the duplicated project (optional, defaults to "Copy of {original name}")
     *                                        - includeDesigns: Whether to include designs (default: true)
     *                                        - includeMedia: Whether to include media files (default: true)
     * @return JsonResponse<ProjectResponseDTO|ErrorResponseDTO> Duplicated project details or error response
     */
    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'])]
    public function duplicate(int $id, DuplicateProjectRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $originalProject = $this->projectRepository->find($id);
            if (!$originalProject) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Project not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this project
            if ($originalProject->getUser() !== $user && !$originalProject->getIsPublic()) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $newName = $dto->name ?? $originalProject->getName() . ' (Copy)';
            $duplicatedProject = $this->projectRepository->duplicateProject($originalProject, $user, $newName);

            $projectResponse = $this->responseDTOFactory->createProjectResponse(
                $duplicatedProject,
                'Project duplicated successfully'
            );
            return $this->projectResponse($projectResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to duplicate project',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Toggle project sharing status
     * 
     * Toggles the public/private status of a project.
     * Only allows modification of projects owned by the authenticated user.
     * Updates the project's visibility and sharing settings.
     * 
     * @param int $id The project ID to toggle sharing for
     * @return JsonResponse<ProjectResponseDTO|ErrorResponseDTO> Updated project details or error response
     */
    #[Route('/{id}/share', name: 'toggle_share', methods: ['POST'])]
    public function toggleShare(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $project = $this->projectRepository->find($id);
            if (!$project) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Project not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this project
            if ($project->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $project->setIsPublic(!$project->getIsPublic());
            $this->entityManager->flush();

            $message = $project->getIsPublic() ? 'Project is now public' : 'Project is now private';
            $projectResponse = $this->responseDTOFactory->createProjectResponse($project, $message);
            return $this->projectResponse($projectResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to toggle project visibility',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
