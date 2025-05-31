<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\CreateProjectRequestDTO;
use App\DTO\DuplicateProjectRequestDTO;
use App\DTO\Response\ProjectResponseDTO;
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
                $total = count($this->projectRepository->findByUser($user));
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
            $project->setTags($dto->tags);
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
                $project->setTags($dto->tags);
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
