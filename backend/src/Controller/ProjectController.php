<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
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
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectRepository $projectRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
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

            $projectData = array_map(function (Project $project) {
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
                ];
            }, $projects);

            return $this->json([
                'projects' => $projectData,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => ceil($total / $limit),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch projects',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
            }

            $project = new Project();
            $project->setName($data['name'] ?? 'Untitled Project');
            $project->setDescription($data['description'] ?? '');
            $project->setTags($data['tags'] ?? []);
            $project->setIsPublic($data['isPublic'] ?? false);
            $project->setUser($user);

            if (isset($data['thumbnail'])) {
                $project->setThumbnail($data['thumbnail']);
            }

            // Validate project
            $errors = $this->validator->validate($project);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                return $this->json([
                    'error' => 'Validation failed',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($project);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Project created successfully',
                'project' => [
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                    'description' => $project->getDescription(),
                    'tags' => $project->getTags(),
                    'isPublic' => $project->getIsPublic(),
                    'createdAt' => $project->getCreatedAt()->format('c'),
                    'updatedAt' => $project->getUpdatedAt()?->format('c'),
                    'designCount' => 0,
                    'thumbnail' => $project->getThumbnail(),
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create project',
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
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $project = $this->projectRepository->find($id);
            if (!$project) {
                return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this project
            if ($project->getUser() !== $user && !$project->getIsPublic()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $designs = array_map(function ($design) {
                return [
                    'id' => $design->getId(),
                    'name' => $design->getName(),
                    'canvasWidth' => $design->getCanvasWidth(),
                    'canvasHeight' => $design->getCanvasHeight(),
                    'background' => $design->getBackground(),
                    'thumbnail' => $design->getThumbnail(),
                    'createdAt' => $design->getCreatedAt()->format('c'),
                    'updatedAt' => $design->getUpdatedAt()?->format('c'),
                    'layerCount' => count($design->getLayers()),
                    'hasAnimations' => $design->getHasAnimations(),
                ];
            }, $project->getDesigns()->toArray());

            return $this->json([
                'project' => [
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                    'description' => $project->getDescription(),
                    'tags' => $project->getTags(),
                    'isPublic' => $project->getIsPublic(),
                    'createdAt' => $project->getCreatedAt()->format('c'),
                    'updatedAt' => $project->getUpdatedAt()?->format('c'),
                    'thumbnail' => $project->getThumbnail(),
                    'user' => [
                        'id' => $project->getUser()->getId(),
                        'name' => $project->getUser()->getName(),
                        'username' => $project->getUser()->getUsername(),
                    ],
                    'designs' => $designs,
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch project',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $project = $this->projectRepository->find($id);
            if (!$project) {
                return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this project
            if ($project->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
            }

            // Update allowed fields
            if (isset($data['name'])) {
                $project->setName($data['name']);
            }

            if (isset($data['description'])) {
                $project->setDescription($data['description']);
            }

            if (isset($data['tags']) && is_array($data['tags'])) {
                $project->setTags($data['tags']);
            }

            if (isset($data['isPublic'])) {
                $project->setIsPublic($data['isPublic']);
            }

            if (isset($data['thumbnail'])) {
                $project->setThumbnail($data['thumbnail']);
            }

            // Validate project
            $errors = $this->validator->validate($project);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                return $this->json([
                    'error' => 'Validation failed',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Project updated successfully',
                'project' => [
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                    'description' => $project->getDescription(),
                    'tags' => $project->getTags(),
                    'isPublic' => $project->getIsPublic(),
                    'createdAt' => $project->getCreatedAt()->format('c'),
                    'updatedAt' => $project->getUpdatedAt()?->format('c'),
                    'thumbnail' => $project->getThumbnail(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update project',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $project = $this->projectRepository->find($id);
            if (!$project) {
                return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this project
            if ($project->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($project);
            $this->entityManager->flush();

            return $this->json(['message' => 'Project deleted successfully']);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete project',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'])]
    public function duplicate(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $originalProject = $this->projectRepository->find($id);
            if (!$originalProject) {
                return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this project
            if ($originalProject->getUser() !== $user && !$originalProject->getIsPublic()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            $newName = $data['name'] ?? $originalProject->getName() . ' (Copy)';

            $duplicatedProject = $this->projectRepository->duplicateProject($originalProject, $user, $newName);

            return $this->json([
                'message' => 'Project duplicated successfully',
                'project' => [
                    'id' => $duplicatedProject->getId(),
                    'name' => $duplicatedProject->getName(),
                    'description' => $duplicatedProject->getDescription(),
                    'tags' => $duplicatedProject->getTags(),
                    'isPublic' => $duplicatedProject->getIsPublic(),
                    'createdAt' => $duplicatedProject->getCreatedAt()->format('c'),
                    'updatedAt' => $duplicatedProject->getUpdatedAt()?->format('c'),
                    'designCount' => count($duplicatedProject->getDesigns()),
                    'thumbnail' => $duplicatedProject->getThumbnail(),
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to duplicate project',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/public', name: 'public', methods: ['GET'])]
    public function publicProjects(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $offset = ($page - 1) * $limit;

            $search = $request->query->get('search');
            $tags = $request->query->get('tags');

            if ($search) {
                $projects = $this->projectRepository->findPublicProjects($search, null, $limit, $offset);
                $total = count($this->projectRepository->findPublicProjects($search));
            } elseif ($tags) {
                $tagArray = explode(',', $tags);
                $projects = $this->projectRepository->findPublicProjects(null, $tagArray, $limit, $offset);
                $total = count($this->projectRepository->findPublicProjects(null, $tagArray));
            } else {
                $projects = $this->projectRepository->findPublicProjects(null, null, $limit, $offset);
                $total = count($this->projectRepository->findPublicProjects());
            }

            $projectData = array_map(function (Project $project) {
                return [
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                    'description' => $project->getDescription(),
                    'tags' => $project->getTags(),
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

            return $this->json([
                'projects' => $projectData,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => ceil($total / $limit),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch public projects',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/share', name: 'toggle_share', methods: ['POST'])]
    public function toggleShare(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $project = $this->projectRepository->find($id);
            if (!$project) {
                return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this project
            if ($project->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $project->setIsPublic(!$project->getIsPublic());
            $this->entityManager->flush();

            return $this->json([
                'message' => $project->getIsPublic() ? 'Project is now public' : 'Project is now private',
                'isPublic' => $project->getIsPublic()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to toggle project visibility',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
