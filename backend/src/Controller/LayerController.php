<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Layer;
use App\Entity\Design;
use App\Entity\User;
use App\Repository\LayerRepository;
use App\Repository\DesignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/layers', name: 'api_layers_')]
#[IsGranted('ROLE_USER')]
class LayerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LayerRepository $layerRepository,
        private readonly DesignRepository $designRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

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

            if (!isset($data['designId'])) {
                return $this->json(['error' => 'Design ID is required'], Response::HTTP_BAD_REQUEST);
            }

            $design = $this->designRepository->find($data['designId']);
            if (!$design) {
                return $this->json(['error' => 'Design not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this design
            if ($design->getProject()->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $layer = new Layer();
            $layer->setName($data['name'] ?? 'Layer');
            $layer->setType($data['type'] ?? 'rectangle');
            $layer->setData($data['data'] ?? []);
            $layer->setX($data['x'] ?? 0);
            $layer->setY($data['y'] ?? 0);
            $layer->setWidth($data['width'] ?? 100);
            $layer->setHeight($data['height'] ?? 100);
            $layer->setRotation($data['rotation'] ?? 0);
            $layer->setScaleX($data['scaleX'] ?? 1);
            $layer->setScaleY($data['scaleY'] ?? 1);
            $layer->setOpacity($data['opacity'] ?? 1);
            $layer->setVisible($data['visible'] ?? true);
            $layer->setLocked($data['locked'] ?? false);
            $layer->setDesign($design);

            // Set parent if specified
            if (isset($data['parentId'])) {
                $parent = $this->layerRepository->find($data['parentId']);
                if ($parent && $parent->getDesign() === $design) {
                    $layer->setParent($parent);
                }
            }

            // Set z-index (auto-increment if not specified)
            if (isset($data['zIndex'])) {
                $layer->setZIndex($data['zIndex']);
            } else {
                $maxZIndex = $this->layerRepository->getMaxZIndex($design);
                $layer->setZIndex($maxZIndex + 1);
            }

            // Set animations if provided
            if (isset($data['animations'])) {
                $layer->setAnimations($data['animations']);
                // Update design animation status
                if (!empty($data['animations'])) {
                    $design->setHasAnimations(true);
                }
            }

            // Set mask if provided
            if (isset($data['mask'])) {
                $layer->setMask($data['mask']);
            }

            // Validate layer
            $errors = $this->validator->validate($layer);
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

            $this->entityManager->persist($layer);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Layer created successfully',
                'layer' => [
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
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create layer',
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

            $layer = $this->layerRepository->find($id);
            if (!$layer) {
                return $this->json(['error' => 'Layer not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this layer
            $design = $layer->getDesign();
            $project = $design->getProject();
            if ($project->getUser() !== $user && !$project->getIsPublic()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            return $this->json([
                'layer' => [
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
                    'children' => array_map(function ($child) {
                        return [
                            'id' => $child->getId(),
                            'name' => $child->getName(),
                            'type' => $child->getType(),
                        ];
                    }, $layer->getChildren()->toArray()),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch layer',
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

            $layer = $this->layerRepository->find($id);
            if (!$layer) {
                return $this->json(['error' => 'Layer not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this layer
            $design = $layer->getDesign();
            if ($design->getProject()->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
            }

            // Update allowed fields
            if (isset($data['name'])) {
                $layer->setName($data['name']);
            }

            if (isset($data['type'])) {
                $layer->setType($data['type']);
            }

            if (isset($data['data'])) {
                $layer->setData($data['data']);
            }

            if (isset($data['x'])) {
                $layer->setX($data['x']);
            }

            if (isset($data['y'])) {
                $layer->setY($data['y']);
            }

            if (isset($data['width'])) {
                $layer->setWidth($data['width']);
            }

            if (isset($data['height'])) {
                $layer->setHeight($data['height']);
            }

            if (isset($data['rotation'])) {
                $layer->setRotation($data['rotation']);
            }

            if (isset($data['scaleX'])) {
                $layer->setScaleX($data['scaleX']);
            }

            if (isset($data['scaleY'])) {
                $layer->setScaleY($data['scaleY']);
            }

            if (isset($data['opacity'])) {
                $layer->setOpacity($data['opacity']);
            }

            if (isset($data['visible'])) {
                $layer->setVisible($data['visible']);
            }

            if (isset($data['locked'])) {
                $layer->setLocked($data['locked']);
            }

            if (isset($data['zIndex'])) {
                $layer->setZIndex($data['zIndex']);
            }

            if (isset($data['parentId'])) {
                if ($data['parentId'] === null) {
                    $layer->setParent(null);
                } else {
                    $parent = $this->layerRepository->find($data['parentId']);
                    if ($parent && $parent->getDesign() === $design) {
                        $layer->setParent($parent);
                    }
                }
            }

            if (isset($data['animations'])) {
                $layer->setAnimations($data['animations']);
                // Update design animation status
                $hasAnimations = !empty($data['animations']) || 
                               $design->getLayers()->exists(function($key, $l) use ($layer) {
                                   return $l !== $layer && !empty($l->getAnimations());
                               });
                $design->setHasAnimations($hasAnimations);
            }

            if (isset($data['mask'])) {
                $layer->setMask($data['mask']);
            }

            // Validate layer
            $errors = $this->validator->validate($layer);
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
                'message' => 'Layer updated successfully',
                'layer' => [
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
                    'updatedAt' => $layer->getUpdatedAt()?->format('c'),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update layer',
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

            $layer = $this->layerRepository->find($id);
            if (!$layer) {
                return $this->json(['error' => 'Layer not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this layer
            $design = $layer->getDesign();
            if ($design->getProject()->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($layer);
            $this->entityManager->flush();

            return $this->json(['message' => 'Layer deleted successfully']);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete layer',
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

            $originalLayer = $this->layerRepository->find($id);
            if (!$originalLayer) {
                return $this->json(['error' => 'Layer not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this layer
            $design = $originalLayer->getDesign();
            if ($design->getProject()->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            $newName = $data['name'] ?? $originalLayer->getName() . ' Copy';
            $offsetX = $data['offsetX'] ?? 10;
            $offsetY = $data['offsetY'] ?? 10;

            $duplicatedLayer = $this->layerRepository->duplicateLayer($originalLayer, $newName, $offsetX, $offsetY);

            return $this->json([
                'message' => 'Layer duplicated successfully',
                'layer' => [
                    'id' => $duplicatedLayer->getId(),
                    'name' => $duplicatedLayer->getName(),
                    'type' => $duplicatedLayer->getType(),
                    'data' => $duplicatedLayer->getData(),
                    'x' => $duplicatedLayer->getX(),
                    'y' => $duplicatedLayer->getY(),
                    'width' => $duplicatedLayer->getWidth(),
                    'height' => $duplicatedLayer->getHeight(),
                    'rotation' => $duplicatedLayer->getRotation(),
                    'scaleX' => $duplicatedLayer->getScaleX(),
                    'scaleY' => $duplicatedLayer->getScaleY(),
                    'opacity' => $duplicatedLayer->getOpacity(),
                    'visible' => $duplicatedLayer->isVisible(),
                    'locked' => $duplicatedLayer->isLocked(),
                    'zIndex' => $duplicatedLayer->getZIndex(),
                    'parentId' => $duplicatedLayer->getParent()?->getId(),
                    'animations' => $duplicatedLayer->getAnimations(),
                    'mask' => $duplicatedLayer->getMask(),
                    'createdAt' => $duplicatedLayer->getCreatedAt()->format('c'),
                    'updatedAt' => $duplicatedLayer->getUpdatedAt()?->format('c'),
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to duplicate layer',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/move', name: 'move', methods: ['PUT'])]
    public function move(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $layer = $this->layerRepository->find($id);
            if (!$layer) {
                return $this->json(['error' => 'Layer not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this layer
            $design = $layer->getDesign();
            if ($design->getProject()->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
            }

            $direction = $data['direction'] ?? null;
            $targetZIndex = $data['targetZIndex'] ?? null;

            if ($direction) {
                // Move up/down in z-index
                switch ($direction) {
                    case 'up':
                        $this->layerRepository->moveLayerUp($layer);
                        break;
                    case 'down':
                        $this->layerRepository->moveLayerDown($layer);
                        break;
                    case 'top':
                        $this->layerRepository->moveLayerToTop($layer);
                        break;
                    case 'bottom':
                        $this->layerRepository->moveLayerToBottom($layer);
                        break;
                    default:
                        return $this->json(['error' => 'Invalid direction'], Response::HTTP_BAD_REQUEST);
                }
            } elseif ($targetZIndex !== null) {
                // Move to specific z-index
                $this->layerRepository->moveLayerToZIndex($layer, $targetZIndex);
            } else {
                return $this->json(['error' => 'Direction or targetZIndex is required'], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Layer moved successfully',
                'zIndex' => $layer->getZIndex()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to move layer',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/bulk-update', name: 'bulk_update', methods: ['PUT'])]
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data || !isset($data['layers']) || !is_array($data['layers'])) {
                return $this->json(['error' => 'Invalid data: layers array is required'], Response::HTTP_BAD_REQUEST);
            }

            $updatedLayers = [];
            $errors = [];

            foreach ($data['layers'] as $layerData) {
                if (!isset($layerData['id'])) {
                    $errors[] = 'Layer ID is required for each layer';
                    continue;
                }

                $layer = $this->layerRepository->find($layerData['id']);
                if (!$layer) {
                    $errors[] = "Layer with ID {$layerData['id']} not found";
                    continue;
                }

                // Check if user owns this layer
                $design = $layer->getDesign();
                if ($design->getProject()->getUser() !== $user) {
                    $errors[] = "Access denied for layer {$layerData['id']}";
                    continue;
                }

                // Update layer properties
                foreach (['name', 'x', 'y', 'width', 'height', 'rotation', 'scaleX', 'scaleY', 'opacity', 'visible', 'locked', 'zIndex', 'data', 'animations', 'mask'] as $property) {
                    if (isset($layerData[$property])) {
                        $setter = 'set' . ucfirst($property);
                        if (method_exists($layer, $setter)) {
                            $layer->$setter($layerData[$property]);
                        }
                    }
                }

                $validationErrors = $this->validator->validate($layer);
                if (count($validationErrors) > 0) {
                    foreach ($validationErrors as $error) {
                        $errors[] = "Layer {$layerData['id']}: " . $error->getMessage();
                    }
                    continue;
                }

                $updatedLayers[] = $layer->getId();
            }

            if (!empty($errors)) {
                return $this->json([
                    'error' => 'Some layers could not be updated',
                    'details' => $errors,
                    'updatedLayers' => $updatedLayers
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Layers updated successfully',
                'updatedLayers' => $updatedLayers
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update layers',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
