<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Request\CreateTemplateRequestDTO;
use App\DTO\Request\SearchTemplateRequestDTO;
use App\Entity\Template;
use App\Entity\User;
use App\Repository\TemplateRepository;
use App\Service\ResponseDTOFactory;
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
 * Template Controller
 * 
 * Manages design templates including browsing, creation, usage tracking, and categorization.
 * Provides template marketplace functionality with search, filtering, and category management.
 * Handles template usage analytics and supports both public and user-created templates.
 * Templates serve as starting points for new design projects.
 */
#[Route('/api/templates', name: 'api_templates_')]
class TemplateController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TemplateRepository $templateRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $category = $request->query->get('category');
            $offset = ($page - 1) * $limit;

            if ($category) {
                $templates = $this->templateRepository->findByCategory($category, $limit, $offset);
                // For category-specific count, we'll count manually for now
                $total = count($this->templateRepository->findByCategory($category, 1000, 0));
            } else {
                // If no category, find all public templates
                $templates = $this->templateRepository->findBy(
                    ['isPublic' => true, 'deletedAt' => null],
                    ['usageCount' => 'DESC'],
                    $limit,
                    $offset
                );
                $total = $this->templateRepository->count(['isPublic' => true, 'deletedAt' => null]);
            }

            $templateResponse = $this->responseDTOFactory->createTemplateListResponse(
                $templates,
                $total,
                $page,
                $limit
            );
            return $this->templateResponse($templateResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch templates',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $template = $this->templateRepository->findOneBy(['uuid' => $uuid, 'isActive' => true]);
            if (!$template) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Template not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Increment view count if method exists
            if (method_exists($this->templateRepository, 'incrementViewCount')) {
                $this->templateRepository->incrementViewCount($template);
            }

            $templateResponse = $this->responseDTOFactory->createTemplateResponse($template);
            return $this->templateResponse($templateResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch template',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(CreateTemplateRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $template = new Template();
            $template->setName($dto->name);
            $template->setDescription($dto->description);
            $template->setCategory($dto->category);
            $template->setTags($dto->tags);
            $template->setWidth($dto->width);
            $template->setHeight($dto->height);
            $template->setCanvasSettings($dto->canvasSettings);
            $template->setLayers($dto->layers);
            $template->setThumbnailUrl($dto->thumbnailUrl);
            $template->setPreviewUrl($dto->previewUrl);
            $template->setIsPremium($dto->isPremium);
            $template->setIsActive($dto->isActive);
            $template->setCreatedBy($user);

            $errors = $this->validator->validate($template);
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

            $this->entityManager->persist($template);
            $this->entityManager->flush();

            $templateResponse = $this->responseDTOFactory->createTemplateResponse(
                $template,
                'Template created successfully'
            );
            return $this->templateResponse($templateResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to create template',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(SearchTemplateRequestDTO $dto): JsonResponse
    {
        try {
            $offset = ($dto->page - 1) * $dto->limit;
            $query = $dto->q ?? '';
            $category = $dto->category;

            // Use manual query building since repository method has different signature
            $qb = $this->templateRepository->createQueryBuilder('t')
                ->andWhere('t.isPublic = :public')
                ->andWhere('t.deletedAt IS NULL')
                ->setParameter('public', true);

            if ($query) {
                $qb->andWhere('t.name LIKE :query OR t.description LIKE :query OR JSON_CONTAINS(t.tags, :queryJson)')
                   ->setParameter('query', '%' . $query . '%')
                   ->setParameter('queryJson', json_encode($query));
            }

            if ($category) {
                $qb->andWhere('t.category = :category')
                   ->setParameter('category', $category);
            }

            $templates = $qb->orderBy('t.usageCount', 'DESC')
                            ->setMaxResults($dto->limit)
                            ->setFirstResult($offset)
                            ->getQuery()
                            ->getResult();

            // Count total results
            $totalQb = $this->templateRepository->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->andWhere('t.isPublic = :public')
                ->andWhere('t.deletedAt IS NULL')
                ->setParameter('public', true);

            if ($query) {
                $totalQb->andWhere('t.name LIKE :query OR t.description LIKE :query OR JSON_CONTAINS(t.tags, :queryJson)')
                        ->setParameter('query', '%' . $query . '%')
                        ->setParameter('queryJson', json_encode($query));
            }

            if ($category) {
                $totalQb->andWhere('t.category = :category')
                        ->setParameter('category', $category);
            }

            $total = (int) $totalQb->getQuery()->getSingleScalarResult();

            $templateResponse = $this->responseDTOFactory->createTemplateListResponse(
                $templates,
                $total,
                $dto->page,
                $dto->limit,
                $query ? "Search results for: {$query}" : 'Template search results'
            );
            return $this->templateResponse($templateResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to search templates',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{uuid}/use', name: 'use', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function useTemplate(string $uuid): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $template = $this->templateRepository->findOneBy(['uuid' => $uuid, 'isActive' => true]);
            if (!$template) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Template not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to premium templates (simplified check)
            if ($template->isIsPremium() && method_exists($user, 'getPlan') && $user->getPlan() !== 'premium') {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Premium template access required');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            // Increment usage count if method exists
            if (method_exists($this->templateRepository, 'incrementUsageCount')) {
                $this->templateRepository->incrementUsageCount($template);
            }

            $successResponse = $this->responseDTOFactory->createSuccessResponse(
                'Template usage recorded',
                [
                    'templateData' => [
                        'canvasSettings' => $template->getCanvasSettings(),
                        'layers' => $template->getLayers(),
                        'width' => $template->getWidth(),
                        'height' => $template->getHeight(),
                    ]
                ]
            );
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to use template',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
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

            $successResponse = $this->responseDTOFactory->createSuccessResponse(
                'Categories retrieved successfully',
                ['categories' => $categories]
            );
            return $this->successResponse($successResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch categories',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
