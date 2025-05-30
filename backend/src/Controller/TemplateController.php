<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Template;
use App\Entity\User;
use App\Repository\TemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/templates', name: 'api_templates_')]
class TemplateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TemplateRepository $templateRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $category = $request->query->get('category');

            $templates = $this->templateRepository->findByCategory($category, $page, $limit);
            $total = $this->templateRepository->countByCategory($category);

            $templatesData = array_map(function (Template $template) {
                return [
                    'id' => $template->getId(),
                    'uuid' => $template->getUuid()->toRfc4122(),
                    'name' => $template->getName(),
                    'description' => $template->getDescription(),
                    'category' => $template->getCategory(),
                    'tags' => $template->getTags(),
                    'thumbnailUrl' => $template->getThumbnailUrl(),
                    'previewUrl' => $template->getPreviewUrl(),
                    'width' => $template->getWidth(),
                    'height' => $template->getHeight(),
                    'isPremium' => $template->isIsPremium(),
                    'isActive' => $template->isIsActive(),
                    'rating' => (float) $template->getRating(),
                    'ratingCount' => $template->getRatingCount(),
                    'usageCount' => $template->getUsageCount(),
                    'createdAt' => $template->getCreatedAt()->format('c'),
                    'updatedAt' => $template->getUpdatedAt()?->format('c'),
                ];
            }, $templates);

            return $this->json([
                'templates' => $templatesData,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil((float) $total / (float) $limit),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch templates: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $template = $this->templateRepository->findOneBy(['uuid' => $uuid, 'is_active' => true]);
            if (!$template) {
                return $this->json(['error' => 'Template not found'], Response::HTTP_NOT_FOUND);
            }

            // Increment view count
            $this->templateRepository->incrementViewCount($template);

            return $this->json([
                'template' => [
                    'id' => $template->getId(),
                    'uuid' => $template->getUuid()->toRfc4122(),
                    'name' => $template->getName(),
                    'description' => $template->getDescription(),
                    'category' => $template->getCategory(),
                    'tags' => $template->getTags(),
                    'thumbnailUrl' => $template->getThumbnailUrl(),
                    'previewUrl' => $template->getPreviewUrl(),
                    'width' => $template->getWidth(),
                    'height' => $template->getHeight(),
                    'canvasSettings' => $template->getCanvasSettings(),
                    'layers' => $template->getLayers(),
                    'isPremium' => $template->isIsPremium(),
                    'isActive' => $template->isIsActive(),
                    'rating' => (float) $template->getRating(),
                    'ratingCount' => $template->getRatingCount(),
                    'usageCount' => $template->getUsageCount(),
                    'createdBy' => $template->getCreatedBy() ? [
                        'id' => $template->getCreatedBy()->getId(),
                        'username' => $template->getCreatedBy()->getUsername(),
                    ] : null,
                    'createdAt' => $template->getCreatedAt()->format('c'),
                    'updatedAt' => $template->getUpdatedAt()?->format('c'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch template: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
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

            $template = new Template();
            $template->setName($data['name'] ?? '');
            $template->setDescription($data['description'] ?? '');
            $template->setCategory($data['category'] ?? 'social-media');
            $template->setTags($data['tags'] ?? []);
            $template->setWidth($data['width'] ?? 800);
            $template->setHeight($data['height'] ?? 600);
            $template->setCanvasSettings($data['canvasSettings'] ?? []);
            $template->setLayers($data['layers'] ?? []);
            $template->setThumbnailUrl($data['thumbnailUrl'] ?? '');
            $template->setPreviewUrl($data['previewUrl'] ?? null);
            $template->setIsPremium($data['isPremium'] ?? false);
            $template->setIsActive($data['isActive'] ?? true);
            $template->setCreatedBy($user);

            $errors = $this->validator->validate($template);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($template);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Template created successfully',
                'template' => [
                    'id' => $template->getId(),
                    'uuid' => $template->getUuid()->toRfc4122(),
                    'name' => $template->getName(),
                    'description' => $template->getDescription(),
                    'category' => $template->getCategory(),
                    'tags' => $template->getTags(),
                    'thumbnailUrl' => $template->getThumbnailUrl(),
                    'width' => $template->getWidth(),
                    'height' => $template->getHeight(),
                    'isPremium' => $template->isIsPremium(),
                    'isActive' => $template->isIsActive(),
                    'createdAt' => $template->getCreatedAt()->format('c'),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create template: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');
            $category = $request->query->get('category');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

            $templates = $this->templateRepository->search($query, $category, $page, $limit);
            $total = $this->templateRepository->countSearch($query, $category);

            $templatesData = array_map(function (Template $template) {
                return [
                    'id' => $template->getId(),
                    'uuid' => $template->getUuid()->toRfc4122(),
                    'name' => $template->getName(),
                    'description' => $template->getDescription(),
                    'category' => $template->getCategory(),
                    'tags' => $template->getTags(),
                    'thumbnailUrl' => $template->getThumbnailUrl(),
                    'width' => $template->getWidth(),
                    'height' => $template->getHeight(),
                    'isPremium' => $template->isIsPremium(),
                    'rating' => (float) $template->getRating(),
                    'ratingCount' => $template->getRatingCount(),
                    'usageCount' => $template->getUsageCount(),
                    'createdAt' => $template->getCreatedAt()->format('c'),
                ];
            }, $templates);

            return $this->json([
                'templates' => $templatesData,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil((float) $total / (float) $limit),
                ],
                'query' => $query,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to search templates: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}/use', name: 'use', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function useTemplate(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $template = $this->templateRepository->findOneBy(['uuid' => $uuid, 'is_active' => true]);
            if (!$template) {
                return $this->json(['error' => 'Template not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to premium templates (simplified check)
            if ($template->isIsPremium() && $user->getPlan() !== 'premium') {
                return $this->json(['error' => 'Premium template access required'], Response::HTTP_FORBIDDEN);
            }

            // Increment usage count
            $this->templateRepository->incrementUsageCount($template);

            return $this->json([
                'message' => 'Template usage recorded',
                'templateData' => [
                    'canvasSettings' => $template->getCanvasSettings(),
                    'layers' => $template->getLayers(),
                    'width' => $template->getWidth(),
                    'height' => $template->getHeight(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to use template: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        try {
            $categories = [
                'social-media',
                'presentation',
                'print',
                'marketing',
                'document',
                'logo',
                'web-graphics',
                'video',
                'animation'
            ];

            return $this->json([
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch categories: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
