<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\BulkDeleteMediaRequestDTO;
use App\DTO\CreateMediaRequestDTO;
use App\DTO\DuplicateMediaRequestDTO;
use App\DTO\SearchMediaRequestDTO;
use App\DTO\StockSearchRequestDTO;
use App\DTO\UpdateMediaRequestDTO;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use App\Service\ResponseDTOFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/media', name: 'api_media_')]
#[IsGranted('ROLE_USER')]
class MediaController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaRepository $mediaRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(SearchMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $filters = $dto->getFilters();
            $offset = $dto->getOffset();

            $media = $this->mediaRepository->findByFilters($filters, $dto->page, $dto->limit, $dto->search);
            $total = $this->mediaRepository->countByFilters($filters, $dto->search);

            $mediaData = array_map(function (Media $media) {
                return [
                    'id' => $media->getId(),
                    'uuid' => $media->getUuid()->toRfc4122(),
                    'name' => $media->getName(),
                    'type' => $media->getType(),
                    'mimeType' => $media->getMimeType(),
                    'size' => $media->getSize(),
                    'url' => $media->getUrl(),
                    'thumbnailUrl' => $media->getThumbnailUrl(),
                    'width' => $media->getWidth(),
                    'height' => $media->getHeight(),
                    'duration' => $media->getDuration(),
                    'source' => $media->getSource(),
                    'metadata' => $media->getMetadata(),
                    'tags' => $media->getTags(),
                    'isPremium' => $media->isIsPremium(),
                    'isActive' => $media->isIsActive(),
                    'createdAt' => $media->getCreatedAt()->format('c'),
                    'updatedAt' => $media->getUpdatedAt()?->format('c'),
                ];
            }, $media);

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $mediaData,
                $dto->page,
                $dto->limit,
                $total,
                'Media list retrieved successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch media list',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can access this media (assume all media is accessible for now)
            // TODO: Implement proper access control based on media visibility settings

            $mediaResponse = $this->responseDTOFactory->createMediaResponse(
                $media,
                'Media retrieved successfully'
            );
            return $this->mediaResponse($mediaResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(CreateMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = new Media();
            $media->setName($dto->name);
            $media->setType($dto->type);
            $media->setMimeType($dto->mimeType);
            $media->setSize($dto->size);
            $media->setUrl($dto->url);
            $media->setThumbnailUrl($dto->thumbnailUrl);
            $media->setWidth($dto->width);
            $media->setHeight($dto->height);
            $media->setDuration($dto->duration);
            $media->setSource($dto->source);
            $media->setSourceId($dto->sourceId);
            $media->setMetadata($dto->metadata ?? []);
            $media->setTags($dto->tags ?? []);
            $media->setAttribution($dto->attribution);
            $media->setLicense($dto->license);
            $media->setIsPremium($dto->isPremium);
            $media->setIsActive($dto->isActive);
            $media->setUser($user);

            $errors = $this->validator->validate($media);
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

            $this->entityManager->persist($media);
            $this->entityManager->flush();

            $mediaResponse = $this->responseDTOFactory->createMediaResponse(
                $media,
                'Media created successfully'
            );
            return $this->mediaResponse($mediaResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to create media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}', name: 'update', methods: ['PUT'])]
    public function update(string $uuid, UpdateMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can edit this media
            if ($media->getUser() !== $user) {
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
                $media->setName($dto->name);
            }
            if ($dto->metadata !== null) {
                $media->setMetadata($dto->metadata);
            }
            if ($dto->tags !== null) {
                $media->setTags($dto->tags);
            }
            if ($dto->isPremium !== null) {
                $media->setIsPremium($dto->isPremium);
            }
            if ($dto->isActive !== null) {
                $media->setIsActive($dto->isActive);
            }

            $errors = $this->validator->validate($media);
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

            $mediaResponse = $this->responseDTOFactory->createMediaResponse(
                $media,
                'Media updated successfully'
            );
            return $this->mediaResponse($mediaResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to update media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can delete this media
            if ($media->getUser() !== $user) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Access denied');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($media);
            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse('Media deleted successfully');
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to delete media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(SearchMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $filters = $dto->getFilters();
            $media = $this->mediaRepository->findByFilters($filters, $dto->page, $dto->limit, $dto->search);
            $total = $this->mediaRepository->countByFilters($filters, $dto->search);

            $mediaData = array_map(function (Media $media) {
                return [
                    'id' => $media->getId(),
                    'uuid' => $media->getUuid()->toRfc4122(),
                    'name' => $media->getName(),
                    'type' => $media->getType(),
                    'mimeType' => $media->getMimeType(),
                    'size' => $media->getSize(),
                    'url' => $media->getUrl(),
                    'thumbnailUrl' => $media->getThumbnailUrl(),
                    'source' => $media->getSource(),
                    'metadata' => $media->getMetadata(),
                    'tags' => $media->getTags(),
                    'isPremium' => $media->isIsPremium(),
                    'isActive' => $media->isIsActive(),
                    'createdAt' => $media->getCreatedAt()->format('c'),
                ];
            }, $media);

            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                $mediaData,
                $dto->page,
                $dto->limit,
                $total,
                'Media search completed successfully'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to search media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/duplicate/{uuid}', name: 'duplicate', methods: ['POST'])]
    public function duplicate(string $uuid, DuplicateMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $originalMedia = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$originalMedia) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Media not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user can access this media (assume all media is accessible for duplication)
            // TODO: Implement proper access control based on media visibility settings

            $duplicatedMedia = $this->mediaRepository->duplicateMedia($originalMedia, $user);

            $mediaResponse = $this->responseDTOFactory->createMediaResponse(
                $duplicatedMedia,
                'Media duplicated successfully'
            );
            return $this->mediaResponse($mediaResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to duplicate media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/stock/search', name: 'stock_search', methods: ['GET'])]
    public function stockSearch(StockSearchRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // TODO: Implement stock media API integration
            // This would integrate with Unsplash, Pexels, Pixabay, etc.
            
            $paginatedResponse = $this->responseDTOFactory->createPaginatedResponse(
                [], // Empty media array for now
                $dto->page,
                $dto->limit,
                0, // Total count
                'Stock media search not yet implemented'
            );

            return $this->paginatedResponse($paginatedResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to search stock media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/bulk/delete', name: 'bulk_delete', methods: ['DELETE'])]
    public function bulkDelete(BulkDeleteMediaRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $deleted = 0;
            $failed = [];

            foreach ($dto->uuids as $uuid) {
                $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
                if (!$media) {
                    $failed[] = ['uuid' => $uuid, 'reason' => 'Media not found'];
                    continue;
                }

                if ($media->getUser() !== $user) {
                    $failed[] = ['uuid' => $uuid, 'reason' => 'Access denied'];
                    continue;
                }

                $this->entityManager->remove($media);
                $deleted++;
            }

            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse(
                sprintf('Bulk delete completed: %d deleted, %d failed', $deleted, count($failed)),
                [
                    'deleted' => $deleted,
                    'failed' => $failed,
                ]
            );
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to bulk delete media',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
