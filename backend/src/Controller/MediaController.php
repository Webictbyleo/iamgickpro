<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/media', name: 'api_media_')]
#[IsGranted('ROLE_USER')]
class MediaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaRepository $mediaRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $type = $request->query->get('type');
            $source = $request->query->get('source');
            $search = $request->query->get('search');

            $filters = [];
            if ($type) {
                $filters['type'] = $type;
            }
            if ($source) {
                $filters['source'] = $source;
            }

            $media = $this->mediaRepository->findByFilters($filters, $page, $limit, $search);
            $total = $this->mediaRepository->countByFilters($filters, $search);

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

            return $this->json([
                'media' => $mediaData,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil($total / $limit),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch media: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                return $this->json(['error' => 'Media not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can access this media (assume all media is accessible for now)
            // TODO: Implement proper access control based on media visibility settings

            return $this->json([
                'media' => [
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
                    'sourceId' => $media->getSourceId(),
                    'metadata' => $media->getMetadata(),
                    'tags' => $media->getTags(),
                    'attribution' => $media->getAttribution(),
                    'license' => $media->getLicense(),
                    'isPremium' => $media->isIsPremium(),
                    'isActive' => $media->isIsActive(),
                    'uploadedBy' => $media->getUser() ? [
                        'id' => $media->getUser()->getId(),
                        'username' => $media->getUser()->getUsername(),
                    ] : null,
                    'createdAt' => $media->getCreatedAt()->format('c'),
                    'updatedAt' => $media->getUpdatedAt()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch media: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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

            $media = new Media();
            $media->setName($data['name'] ?? '');
            $media->setType($data['type'] ?? 'image');
            $media->setMimeType($data['mimeType'] ?? '');
            $media->setSize($data['size'] ?? 0);
            $media->setUrl($data['url'] ?? '');
            $media->setThumbnailUrl($data['thumbnailUrl'] ?? null);
            $media->setWidth($data['width'] ?? null);
            $media->setHeight($data['height'] ?? null);
            $media->setDuration($data['duration'] ?? null);
            $media->setSource($data['source'] ?? 'upload');
            $media->setSourceId($data['sourceId'] ?? null);
            $media->setMetadata($data['metadata'] ?? []);
            $media->setTags($data['tags'] ?? []);
            $media->setAttribution($data['attribution'] ?? null);
            $media->setLicense($data['license'] ?? null);
            $media->setIsPremium($data['isPremium'] ?? false);
            $media->setIsActive($data['isActive'] ?? true);
            $media->setUser($user);

            $errors = $this->validator->validate($media);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($media);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Media created successfully',
                'media' => [
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
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create media: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}', name: 'update', methods: ['PUT'])]
    public function update(string $uuid, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                return $this->json(['error' => 'Media not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can edit this media
            if ($media->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
            }

            if (isset($data['name'])) {
                $media->setName($data['name']);
            }
            if (isset($data['metadata'])) {
                $media->setMetadata($data['metadata']);
            }
            if (isset($data['tags'])) {
                $media->setTags($data['tags']);
            }
            if (isset($data['isPremium'])) {
                $media->setIsPremium($data['isPremium']);
            }
            if (isset($data['isActive'])) {
                $media->setIsActive($data['isActive']);
            }

            $errors = $this->validator->validate($media);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Media updated successfully',
                'media' => [
                    'id' => $media->getId(),
                    'uuid' => $media->getUuid(),
                    'filename' => $media->getFilename(),
                    'originalName' => $media->getOriginalName(),
                    'type' => $media->getType(),
                    'mimeType' => $media->getMimeType(),
                    'size' => $media->getSize(),
                    'url' => $media->getUrl(),
                    'thumbnailUrl' => $media->getThumbnailUrl(),
                    'source' => $media->getSource(),
                    'metadata' => $media->getMetadata(),
                    'tags' => $media->getTags(),
                    'isPublic' => $media->isPublic(),
                    'updatedAt' => $media->getUpdatedAt()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to update media: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $media = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$media) {
                return $this->json(['error' => 'Media not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can delete this media
            if ($media->getUser() !== $user) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($media);
            $this->entityManager->flush();

            return $this->json(['message' => 'Media deleted successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete media: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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

            $query = $request->query->get('q', '');
            $type = $request->query->get('type');
            $source = $request->query->get('source');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

            $filters = [];
            if ($type) {
                $filters['type'] = $type;
            }
            if ($source) {
                $filters['source'] = $source;
            }

            $media = $this->mediaRepository->findByFilters($filters, $page, $limit, $query);
            $total = $this->mediaRepository->countByFilters($filters, $query);

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

            return $this->json([
                'media' => $mediaData,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil($total / $limit),
                ],
                'query' => $query,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to search media: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/duplicate/{uuid}', name: 'duplicate', methods: ['POST'])]
    public function duplicate(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $originalMedia = $this->mediaRepository->findOneBy(['uuid' => $uuid]);
            if (!$originalMedia) {
                return $this->json(['error' => 'Media not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user can access this media (assume all media is accessible for duplication)
            // TODO: Implement proper access control based on media visibility settings

            $duplicatedMedia = $this->mediaRepository->duplicateMedia($originalMedia, $user);

            return $this->json([
                'message' => 'Media duplicated successfully',
                'media' => [
                    'id' => $duplicatedMedia->getId(),
                    'uuid' => $duplicatedMedia->getUuid(),
                    'filename' => $duplicatedMedia->getFilename(),
                    'originalName' => $duplicatedMedia->getOriginalName(),
                    'type' => $duplicatedMedia->getType(),
                    'mimeType' => $duplicatedMedia->getMimeType(),
                    'size' => $duplicatedMedia->getSize(),
                    'url' => $duplicatedMedia->getUrl(),
                    'thumbnailUrl' => $duplicatedMedia->getThumbnailUrl(),
                    'source' => $duplicatedMedia->getSource(),
                    'metadata' => $duplicatedMedia->getMetadata(),
                    'tags' => $duplicatedMedia->getTags(),
                    'isPublic' => $duplicatedMedia->isPublic(),
                    'createdAt' => $duplicatedMedia->getCreatedAt()->format('c'),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to duplicate media: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/stock/search', name: 'stock_search', methods: ['GET'])]
    public function stockSearch(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $query = $request->query->get('q', '');
            $provider = $request->query->get('provider', 'unsplash');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

            // TODO: Implement stock media API integration
            // This would integrate with Unsplash, Pexels, Pixabay, etc.
            
            return $this->json([
                'message' => 'Stock media search not yet implemented',
                'query' => $query,
                'provider' => $provider,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => 0,
                    'pages' => 0,
                ],
                'media' => [],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to search stock media: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/bulk/delete', name: 'bulk_delete', methods: ['DELETE'])]
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            if (!$data || !isset($data['uuids']) || !is_array($data['uuids'])) {
                return $this->json(['error' => 'Invalid data: uuids array required'], Response::HTTP_BAD_REQUEST);
            }

            $deleted = 0;
            $failed = [];

            foreach ($data['uuids'] as $uuid) {
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

            return $this->json([
                'message' => sprintf('Bulk delete completed: %d deleted, %d failed', $deleted, count($failed)),
                'deleted' => $deleted,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to bulk delete media: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
