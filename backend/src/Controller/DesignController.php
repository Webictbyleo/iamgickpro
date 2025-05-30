<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Design;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\DesignRepository;
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

#[Route('/api/designs', name: 'api_designs_')]
#[IsGranted('ROLE_USER')]
class DesignController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DesignRepository $designRepository,
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

            $projectId = $request->query->get('project');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $offset = ($page - 1) * $limit;

            if ($projectId) {
                $project = $this->projectRepository->find($projectId);
                if (!$project || ($project->getUser() !== $user && !$project->getIsPublic())) {
                    return $this->json(['error' => 'Project not found or access denied'], Response::HTTP_FORBIDDEN);
                }
                $designs = $this->designRepository->findByProject($project, $limit, $offset);
                $total = count($this->designRepository->findByProject($project));
            } else {
                $designs = $this->designRepository->findByUser($user, $limit, $offset);
                $total = count($this->designRepository->findByUser($user));
            }

            $designData = array_map(function (Design $design) {
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
                    'project' => [
                        'id' => $design->getProject()->getId(),
                        'name' => $design->getProject()->getName(),
                    ],
                ];
            }, $designs);

            return $this->json([
                'designs' => $designData,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => ceil($total / $limit),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch designs',
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

            if (!isset($data['projectId'])) {
                return $this->json(['error' => 'Project ID is required'], Response::HTTP_BAD_REQUEST);
            }

            $project = $this->projectRepository->find($data['projectId']);
            if (!$project || $project->getUser() !== $user) {
                return $this->json(['error' => 'Project not found or access denied'], Response::HTTP_FORBIDDEN);
            }

            $design = new Design();
            $design->setName($data['name'] ?? 'Untitled Design');
            $design->setCanvasWidth($data['canvasWidth'] ?? 1920);
            $design->setCanvasHeight($data['canvasHeight'] ?? 1080);
            $design->setBackground($data['background'] ?? ['type' => 'color', 'color' => '#ffffff']);
            $design->setProject($project);

            if (isset($data['thumbnail'])) {
                $design->setThumbnail($data['thumbnail']);
            }

            if (isset($data['animationSettings'])) {
                $design->setAnimationSettings($data['animationSettings']);
            }

            // Validate design
            $errors = $this->validator->validate($design);
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

            $this->entityManager->persist($design);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Design created successfully',
                'design' => [
                    'id' => $design->getId(),
                    'name' => $design->getName(),
                    'canvasWidth' => $design->getCanvasWidth(),
                    'canvasHeight' => $design->getCanvasHeight(),
                    'background' => $design->getBackground(),
                    'thumbnail' => $design->getThumbnail(),
                    'createdAt' => $design->getCreatedAt()->format('c'),
                    'updatedAt' => $design->getUpdatedAt()?->format('c'),
                    'layerCount' => 0,
                    'hasAnimations' => false,
                    'animationSettings' => $design->getAnimationSettings(),
                ]
            ], Response::HTTP_CREATED);

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
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $design = $this->designRepository->find($id);
            if (!$design) {
                return $this->json(['error' => 'Design not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this design
            $project = $design->getProject();
            if ($project->getUser() !== $user && !$project->getIsPublic()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $layers = array_map(function ($layer) {
                return [
                    'id' => $layer->getId(),
                    'name' => $layer->getName(),
                    'type' => $layer->getType(),
                    'data' => $layer->getData(),
                    'x' => $layer->getX(),
                    'y' => $layer->getY(),
                    'width' => $layer->getWidth(),
                    'height' => $layer->getHeight(),
                    'rotation' => $layer->getRotation(),
                    'scaleX' => $layer->getScaleX(),
                    'scaleY' => $layer->getScaleY(),
                    'opacity' => $layer->getOpacity(),
                    'visible' => $layer->isVisible(),
                    'locked' => $layer->isLocked(),
                    'zIndex' => $layer->getZIndex(),
                    'parentId' => $layer->getParent()?->getId(),
                    'animations' => $layer->getAnimations(),
                    'mask' => $layer->getMask(),
                    'createdAt' => $layer->getCreatedAt()->format('c'),
                    'updatedAt' => $layer->getUpdatedAt()?->format('c'),
                ];
            }, $design->getLayers()->toArray());

            return $this->json([
                'design' => [
                    'id' => $design->getId(),
                    'name' => $design->getName(),
                    'canvasWidth' => $design->getCanvasWidth(),
                    'canvasHeight' => $design->getCanvasHeight(),
                    'background' => $design->getBackground(),
                    'thumbnail' => $design->getThumbnail(),
                    'createdAt' => $design->getCreatedAt()->format('c'),
                    'updatedAt' => $design->getUpdatedAt()?->format('c'),
                    'hasAnimations' => $design->getHasAnimations(),
                    'animationSettings' => $design->getAnimationSettings(),
                    'project' => [
                        'id' => $project->getId(),
                        'name' => $project->getName(),
                        'user' => [
                            'id' => $project->getUser()->getId(),
                            'name' => $project->getUser()->getName(),
                            'username' => $project->getUser()->getUsername(),
                        ],
                    ],
                    'layers' => $layers,
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch design',
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

            $design = $this->designRepository->find($id);
            if (!$design) {
                return $this->json(['error' => 'Design not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this design
            if ($design->getProject()->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
            }

            // Update allowed fields
            if (isset($data['name'])) {
                $design->setName($data['name']);
            }

            if (isset($data['canvasWidth'])) {
                $design->setCanvasWidth($data['canvasWidth']);
            }

            if (isset($data['canvasHeight'])) {
                $design->setCanvasHeight($data['canvasHeight']);
            }

            if (isset($data['background'])) {
                $design->setBackground($data['background']);
            }

            if (isset($data['thumbnail'])) {
                $design->setThumbnail($data['thumbnail']);
            }

            if (isset($data['animationSettings'])) {
                $design->setAnimationSettings($data['animationSettings']);
            }

            // Update hasAnimations based on animation settings or layer animations
            $hasAnimations = !empty($data['animationSettings']) || 
                            $design->getLayers()->exists(function($key, $layer) {
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
                
                return $this->json([
                    'error' => 'Validation failed',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Design updated successfully',
                'design' => [
                    'id' => $design->getId(),
                    'name' => $design->getName(),
                    'canvasWidth' => $design->getCanvasWidth(),
                    'canvasHeight' => $design->getCanvasHeight(),
                    'background' => $design->getBackground(),
                    'thumbnail' => $design->getThumbnail(),
                    'hasAnimations' => $design->getHasAnimations(),
                    'animationSettings' => $design->getAnimationSettings(),
                    'updatedAt' => $design->getUpdatedAt()?->format('c'),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update design',
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

            $design = $this->designRepository->find($id);
            if (!$design) {
                return $this->json(['error' => 'Design not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this design
            if ($design->getProject()->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($design);
            $this->entityManager->flush();

            return $this->json(['message' => 'Design deleted successfully']);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete design',
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

            $originalDesign = $this->designRepository->find($id);
            if (!$originalDesign) {
                return $this->json(['error' => 'Design not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this design
            $project = $originalDesign->getProject();
            if ($project->getUser() !== $user && !$project->getIsPublic()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            $newName = $data['name'] ?? $originalDesign->getName() . ' (Copy)';
            $targetProjectId = $data['projectId'] ?? $project->getId();

            // Verify target project access
            $targetProject = $this->projectRepository->find($targetProjectId);
            if (!$targetProject || $targetProject->getUser() !== $user) {
                return $this->json(['error' => 'Target project not found or access denied'], Response::HTTP_FORBIDDEN);
            }

            $duplicatedDesign = $this->designRepository->duplicateDesign($originalDesign, $targetProject, $newName);

            return $this->json([
                'message' => 'Design duplicated successfully',
                'design' => [
                    'id' => $duplicatedDesign->getId(),
                    'name' => $duplicatedDesign->getName(),
                    'canvasWidth' => $duplicatedDesign->getCanvasWidth(),
                    'canvasHeight' => $duplicatedDesign->getCanvasHeight(),
                    'background' => $duplicatedDesign->getBackground(),
                    'thumbnail' => $duplicatedDesign->getThumbnail(),
                    'createdAt' => $duplicatedDesign->getCreatedAt()->format('c'),
                    'updatedAt' => $duplicatedDesign->getUpdatedAt()?->format('c'),
                    'layerCount' => count($duplicatedDesign->getLayers()),
                    'hasAnimations' => $duplicatedDesign->getHasAnimations(),
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to duplicate design',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/thumbnail', name: 'update_thumbnail', methods: ['PUT'])]
    public function updateThumbnail(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $design = $this->designRepository->find($id);
            if (!$design) {
                return $this->json(['error' => 'Design not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this design
            if ($design->getProject()->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data || !isset($data['thumbnail'])) {
                return $this->json(['error' => 'Thumbnail data is required'], Response::HTTP_BAD_REQUEST);
            }

            $design->setThumbnail($data['thumbnail']);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Thumbnail updated successfully',
                'thumbnail' => $design->getThumbnail()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update thumbnail',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $query = $request->query->get('q');
            if (!$query) {
                return $this->json(['error' => 'Search query is required'], Response::HTTP_BAD_REQUEST);
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

            $designData = array_map(function (Design $design) {
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
                    'project' => [
                        'id' => $design->getProject()->getId(),
                        'name' => $design->getProject()->getName(),
                        'isPublic' => $design->getProject()->getIsPublic(),
                        'user' => [
                            'id' => $design->getProject()->getUser()->getId(),
                            'name' => $design->getProject()->getUser()->getName(),
                            'username' => $design->getProject()->getUser()->getUsername(),
                        ],
                    ],
                ];
            }, $accessibleDesigns);

            return $this->json([
                'designs' => array_values($designData),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($accessibleDesigns),
                    'totalPages' => ceil(count($accessibleDesigns) / $limit),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to search designs',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
