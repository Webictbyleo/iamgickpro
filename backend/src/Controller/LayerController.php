<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\BulkUpdateLayersRequestDTO;
use App\DTO\CreateLayerRequestDTO;
use App\DTO\DuplicateLayerRequestDTO;
use App\DTO\MoveLayerRequestDTO;
use App\DTO\UpdateLayerRequestDTO;
use App\Entity\Layer;
use App\Entity\Design;
use App\Entity\User;
use App\Repository\LayerRepository;
use App\Repository\DesignRepository;
use App\Service\ResponseDTOFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/layers', name: 'api_layers_')]
#[IsGranted('ROLE_USER')]
class LayerController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LayerRepository $layerRepository,
        private readonly DesignRepository $designRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(CreateLayerRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $design = $this->designRepository->findOneBy(['uuid' => $dto->designId]);
            if (!$design) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Design not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this design
            if ($design->getProject()->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $layer = new Layer();
            $layer->setName($dto->name);
            $layer->setType($dto->type);
            $layer->setProperties($dto->properties);
            $layer->setTransform($dto->transform);
            $layer->setVisible($dto->visible);
            $layer->setLocked($dto->locked);
            $layer->setDesign($design);

            // Set parent if specified
            if ($dto->parentLayerId) {
                $parent = $this->layerRepository->findOneBy(['uuid' => $dto->parentLayerId]);
                if ($parent && $parent->getDesign() === $design) {
                    $layer->setParent($parent);
                }
            }

            // Set z-index (auto-increment if not specified)
            if ($dto->zIndex !== null) {
                $layer->setZIndex($dto->zIndex);
            } else {
                $maxZIndex = $this->layerRepository->getMaxZIndex($design);
                $layer->setZIndex($maxZIndex + 1);
            }

            $errors = $this->validator->validate($layer);
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

            $this->entityManager->persist($layer);
            $this->entityManager->flush();

            $layerResponse = $this->responseDTOFactory->createLayerResponse(
                $layer,
                'Layer created successfully'
            );
            return $this->layerResponse($layerResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to create layer',
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

            $layer = $this->layerRepository->find($id);
            if (!$layer) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Layer not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this layer
            $design = $layer->getDesign();
            $project = $design->getProject();
            if ($project->getUser() !== $user && !$project->getIsPublic()) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $layerResponse = $this->responseDTOFactory->createLayerResponse(
                $layer,
                'Layer retrieved successfully'
            );
            return $this->layerResponse($layerResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch layer',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, UpdateLayerRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $layer = $this->layerRepository->find($id);
            if (!$layer) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Layer not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this layer
            $design = $layer->getDesign();
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
                $layer->setName($dto->name);
            }

            if ($dto->properties !== null) {
                $layer->setProperties($dto->properties);
            }

            if ($dto->transform !== null) {
                $layer->setTransform($dto->transform);
            }

            if ($dto->visible !== null) {
                $layer->setVisible($dto->visible);
            }

            if ($dto->locked !== null) {
                $layer->setLocked($dto->locked);
            }

            if ($dto->zIndex !== null) {
                $layer->setZIndex($dto->zIndex);
            }

            if ($dto->parentLayerId !== null) {
                $parent = $this->layerRepository->findOneBy(['uuid' => $dto->parentLayerId]);
                if ($parent && $parent->getDesign() === $design) {
                    $layer->setParent($parent);
                }
            }

            $errors = $this->validator->validate($layer);
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

            $layerResponse = $this->responseDTOFactory->createLayerResponse(
                $layer,
                'Layer updated successfully'
            );
            return $this->layerResponse($layerResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to update layer',
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

            $layer = $this->layerRepository->find($id);
            if (!$layer) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Layer not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this layer
            $design = $layer->getDesign();
            if ($design->getProject()->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($layer);
            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse('Layer deleted successfully');
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to delete layer',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'])]
    public function duplicate(int $id, DuplicateLayerRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $originalLayer = $this->layerRepository->find($id);
            if (!$originalLayer) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Layer not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this layer
            $design = $originalLayer->getDesign();
            if ($design->getProject()->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $newName = $dto->name ?? $originalLayer->getName() . ' Copy';
            $offsetX = $dto->offsetX ?? 10;
            $offsetY = $dto->offsetY ?? 10;

            $duplicatedLayer = $this->layerRepository->duplicateLayer($originalLayer, $newName, $offsetX, $offsetY);

            $layerResponse = $this->responseDTOFactory->createLayerResponse(
                $duplicatedLayer,
                'Layer duplicated successfully'
            );
            return $this->layerResponse($layerResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to duplicate layer',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/move', name: 'move', methods: ['PUT'])]
    public function move(int $id, MoveLayerRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $layer = $this->layerRepository->find($id);
            if (!$layer) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Layer not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this layer
            $design = $layer->getDesign();
            if ($design->getProject()->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            if ($dto->direction) {
                // Move up/down in z-index
                switch ($dto->direction) {
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
                        $errorResponse = $this->responseDTOFactory->createErrorResponse('Invalid direction');
                        return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
                }
            } elseif ($dto->targetZIndex !== null) {
                // Move to specific z-index
                $this->layerRepository->moveLayerToZIndex($layer, $dto->targetZIndex);
            } else {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Direction or targetZIndex is required');
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse(
                'Layer moved successfully',
                ['zIndex' => $layer->getZIndex()]
            );
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to move layer',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/bulk-update', name: 'bulk_update', methods: ['PUT'])]
    public function bulkUpdate(BulkUpdateLayersRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $updatedLayers = [];
            $errors = [];

            foreach ($dto->layers as $layerData) {
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

                // Update layer properties from the updates array
                if (isset($layerData['updates']) && is_array($layerData['updates'])) {
                    foreach (['name', 'x', 'y', 'width', 'height', 'rotation', 'scaleX', 'scaleY', 'opacity', 'visible', 'locked', 'zIndex', 'data', 'animations', 'mask'] as $property) {
                        if (isset($layerData['updates'][$property])) {
                            $setter = 'set' . ucfirst($property);
                            if (method_exists($layer, $setter)) {
                                $layer->$setter($layerData['updates'][$property]);
                            }
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
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Some layers could not be updated',
                    $errors
                );
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse(
                'Layers updated successfully',
                ['updatedLayers' => $updatedLayers]
            );
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to update layers',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
