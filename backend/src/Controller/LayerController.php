<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\BulkUpdateLayersRequestDTO;
use App\DTO\CreateLayerRequestDTO;
use App\DTO\DuplicateLayerRequestDTO;
use App\DTO\MoveLayerRequestDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\LayerResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
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

/**
 * Layer Controller
 * 
 * Manages design layer operations including creation, modification, deletion, and organization.
 * Handles layer positioning, duplication, and bulk operations for design elements.
 * All operations enforce design ownership validation and proper layer hierarchy management.
 * Layers are the core building blocks of designs in the canvas editor system.
 */
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

    /**
     * Create a new layer in a design
     * 
     * Creates a new layer with specified properties and adds it to the target design.
     * Automatically assigns appropriate z-index and validates design ownership.
     * Supports various layer types including text, shape, image, svg, and group layers.
     * 
     * @param CreateLayerRequestDTO $dto Layer creation data including:
     *                                  - designId: Target design UUID (required)
     *                                  - type: Layer type (text, shape, image, svg, group, etc.)
     *                                  - name: Display name for the layer
     *                                  - properties: Layer-specific properties as JSON
     *                                  - position: Layer position coordinates (x, y)
     *                                  - dimensions: Layer dimensions (width, height)
     *                                  - rotation: Layer rotation angle in degrees
     *                                  - opacity: Layer opacity (0-1)
     *                                  - visible: Whether layer is visible
     *                                  - locked: Whether layer is locked for editing
     *                                  - zIndex: Optional z-index for layer ordering
     * @return JsonResponse<LayerResponseDTO|ErrorResponseDTO> Created layer data or error response
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(CreateLayerRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Find design by UUID if designId is string, by ID if numeric
            if (is_string($dto->designId)) {
                $design = $this->designRepository->findOneBy(['uuid' => $dto->designId]);
            } else {
                $design = $this->designRepository->find($dto->designId);
            }
            
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
            $layer->setProperties($dto->getPropertiesArray());
            $layer->setTransform($dto->getTransformArray());
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

    /**
     * Bulk update multiple layers
     * 
     * Updates multiple layers in a single operation for performance efficiency.
     * Processes each layer individually with proper validation and permission checks.
     * Returns detailed results including successful updates and any failures.
     * 
     * @param BulkUpdateLayersRequestDTO $dto Bulk update data including:
     *                                       - layers: Array of layer update objects, each containing:
     *                                         - id: Layer ID to update (required)
     *                                         - properties: Properties to update
     *                                         - position: New position coordinates
     *                                         - dimensions: New dimensions
     *                                         - visible: Visibility state
     *                                         - locked: Lock state
     * @return JsonResponse<SuccessResponseDTO|ErrorResponseDTO> Bulk operation results with success/failure details or error response
     */
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

            foreach ($dto->layers as $layerUpdate) {
                $layer = $this->layerRepository->find($layerUpdate->id);
                if (!$layer) {
                    $errors[] = "Layer with ID {$layerUpdate->id} not found";
                    continue;
                }

                // Check if user owns this layer
                $design = $layer->getDesign();
                if ($design->getProject()->getUser() !== $user) {
                    $errors[] = "Access denied for layer {$layerUpdate->id}";
                    continue;
                }

                // Update layer properties using the LayerUpdate object
                if ($layerUpdate->name !== null) {
                    $layer->setName($layerUpdate->name);
                }
                if ($layerUpdate->opacity !== null) {
                    $layer->setOpacity($layerUpdate->opacity);
                }
                if ($layerUpdate->visible !== null) {
                    $layer->setVisible($layerUpdate->visible);
                }
                if ($layerUpdate->locked !== null) {
                    $layer->setLocked($layerUpdate->locked);
                }
                if ($layerUpdate->zIndex !== null) {
                    $layer->setZIndex($layerUpdate->zIndex);
                }

                // Update transform properties if provided
                if ($layerUpdate->transform !== null) {
                    $currentTransform = $layer->getTransform();
                    $newTransform = [
                        'x' => $layerUpdate->transform->x,
                        'y' => $layerUpdate->transform->y,
                        'width' => $layerUpdate->transform->width,
                        'height' => $layerUpdate->transform->height,
                        'rotation' => $layerUpdate->transform->rotation,
                        'scaleX' => $layerUpdate->transform->scaleX,
                        'scaleY' => $layerUpdate->transform->scaleY,
                        'skewX' => $layerUpdate->transform->skewX ?? $currentTransform['skewX'] ?? 0,
                        'skewY' => $layerUpdate->transform->skewY ?? $currentTransform['skewY'] ?? 0,
                        'opacity' => $currentTransform['opacity'] ?? 1,
                    ];
                    $layer->setTransform($newTransform);
                }

                // Update properties if provided
                if ($layerUpdate->properties !== null) {
                    $layer->setProperties($layerUpdate->properties->toArray());
                }

                // Update parent layer if provided
                if ($layerUpdate->parentLayerId !== null) {
                    $parent = $this->layerRepository->findOneBy(['uuid' => $layerUpdate->parentLayerId]);
                    if ($parent && $parent->getDesign() === $design) {
                        $layer->setParent($parent);
                    }
                }

                $validationErrors = $this->validator->validate($layer);
                if (count($validationErrors) > 0) {
                    foreach ($validationErrors as $error) {
                        $errors[] = "Layer {$layerUpdate->id}: " . $error->getMessage();
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

    /**
     * Get details of a specific layer
     * 
     * Returns comprehensive information about a single layer including all properties,
     * position, styling, and metadata. Validates access permissions through design ownership.
     * 
     * @param int $id The layer ID to retrieve
     * @return JsonResponse<LayerResponseDTO|ErrorResponseDTO> Layer details or error response
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $layerId = (int)$id;
            $layer = $this->layerRepository->find($layerId);
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

    /**
     * Update an existing layer
     * 
     * Modifies layer properties including position, dimensions, styling, and content.
     * Supports partial updates with validation and maintains layer hierarchy integrity.
     * Users can only update layers in designs they own.
     * 
     * @param int $id The layer ID to update
     * @param UpdateLayerRequestDTO $dto Layer update data including:
     *                                  - name: Updated display name
     *                                  - properties: Updated layer-specific properties
     *                                  - position: New position coordinates (x, y)
     *                                  - dimensions: New dimensions (width, height)
     *                                  - rotation: Updated rotation angle
     *                                  - opacity: Updated opacity value
     *                                  - visible: Updated visibility state
     *                                  - locked: Updated lock state
     *                                  - zIndex: New z-index for reordering
     * @return JsonResponse<LayerResponseDTO|ErrorResponseDTO> Updated layer data or error response
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, UpdateLayerRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }
            
            $layerId = (int)$id;
            /**  @var Layer */
            $layer = $this->layerRepository->find($layerId);
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
                $layer->setProperties($dto->getPropertiesArray());
            }

            if ($dto->transform !== null) {
                $layer->setTransform($dto->getTransformArray());
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

    /**
     * Delete a layer from a design
     * 
     * Permanently removes a layer and all its associated data from the design.
     * Automatically adjusts z-indexes of remaining layers to maintain proper ordering.
     * Users can only delete layers in designs they own.
     * 
     * @param int $id The layer ID to delete
     * @return JsonResponse<SuccessResponseDTO|ErrorResponseDTO> Success confirmation or error response
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $layerId = (int)$id;
            $layer = $this->layerRepository->find($layerId);
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

    /**
     * Duplicate a layer within a design
     * 
     * Creates an exact copy of an existing layer with all properties and styling.
     * The duplicated layer is positioned slightly offset from the original and
     * assigned a new z-index to appear on top. Maintains all layer relationships.
     * 
     * @param int $id The layer ID to duplicate
     * @param DuplicateLayerRequestDTO $dto Duplication options including:
     *                                     - offset: Position offset for the duplicate (optional)
     *                                     - namePrefix: Prefix for the duplicated layer name
     * @return JsonResponse<LayerResponseDTO|ErrorResponseDTO> Duplicated layer data or error response
     */
    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'])]
    public function duplicate(string $id, DuplicateLayerRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $layerId = (int)$id;
            $originalLayer = $this->layerRepository->find($layerId);
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

    /**
     * Move a layer within the design hierarchy
     * 
     * Changes the layer's position in the z-index stack or moves it to a specific position.
     * Supports moving layers up/down in the stack or to absolute positions.
     * Automatically adjusts other layers' z-indexes to maintain proper ordering.
     * 
     * @param int $id The layer ID to move
     * @param MoveLayerRequestDTO $dto Movement instructions including:
     *                               - direction: Movement direction (up, down, top, bottom)
     *                               - targetZIndex: Specific z-index to move to
     *                               - steps: Number of positions to move (for relative moves)
     * @return JsonResponse<LayerResponseDTO|ErrorResponseDTO> Updated layer with new position or error response
     */
    #[Route('/{id}/move', name: 'move', methods: ['PUT'])]
    public function move(string $id, MoveLayerRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $layerId = (int)$id;
            $layer = $this->layerRepository->find($layerId);
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
                'Layer moved successfully'
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

}
