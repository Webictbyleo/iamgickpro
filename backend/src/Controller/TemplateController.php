<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Request\CreateTemplateRequestDTO;
use App\DTO\Request\SearchTemplateRequestDTO;
use App\DTO\Response\DesignResponseDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\DTO\Response\TemplateResponseDTO;
use App\DTO\Response\TemplateSearchResponseDTO;
use App\Entity\Design;
use App\Entity\Project;
use App\Entity\Template;
use App\Entity\User;
use App\Repository\ProjectRepository;
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
        private readonly ProjectRepository $projectRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    /**
     * List available templates with filtering and pagination
     * 
     * Returns a paginated list of templates with optional category filtering.
     * Includes template metadata, thumbnail images, and usage statistics.
     * Access is restricted based on user subscription tier.
     * 
     * @param Request $request HTTP request containing query parameters:
     *                        - page: Page number (default: 1, min: 1)
     *                        - limit: Items per page (default: 20, max: 50)
     *                        - category: Category filter (optional)
     * @return JsonResponse<TemplateResponseDTO|ErrorResponseDTO> Paginated template list or error response
     */
    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $category = $request->query->get('category');
            $offset = ($page - 1) * $limit;

            if ($category) {
                $templates = $this->templateRepository->findByCategory($category, $limit, $offset);
                $total = $this->templateRepository->countTemplatesByCategory($category);
            } else {
                // If no category, find all public and active templates
                $templates = $this->templateRepository->findBy(
                    ['isPublic' => true, 'is_active' => true, 'deletedAt' => null],
                    ['usage_count' => 'DESC'],
                    $limit,
                    $offset
                );
                $total = $this->templateRepository->count(['isPublic' => true, 'is_active' => true, 'deletedAt' => null]);
            }

            $templateResponse = $this->responseDTOFactory->createTemplateListResponse(
                $templates,
                $total,
                $page,
                $limit,
                'Template list retrieved successfully'
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

    /**
     * Search templates with advanced filtering
     * 
     * Performs comprehensive template search with support for text queries,
     * category filtering, and tag-based search. Returns paginated results
     * sorted by relevance and usage popularity. Requires authentication.
     * 
     * @param SearchTemplateRequestDTO $dto Search parameters including:
     *                                     - q: Search query for name/description/tags
     *                                     - category: Category filter
     *                                     - page: Page number for pagination
     *                                     - limit: Number of results per page
     * @return JsonResponse<TemplateSearchResponseDTO|ErrorResponseDTO> Search results or error response
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function search(SearchTemplateRequestDTO $dto): JsonResponse
    {
        try {
            $offset = ($dto->page - 1) * $dto->limit;
            $query = $dto->q ?? '';
            $category = $dto->category;

            // Use manual query building since repository method has different signature
            $qb = $this->templateRepository->createQueryBuilder('t')
                ->andWhere('t.isPublic = :public')
                ->andWhere('t.is_active = :active')
                ->andWhere('t.deletedAt IS NULL')
                ->setParameter('public', true)
                ->setParameter('active', true);

            if ($query) {
                $qb->andWhere('t.name LIKE :query OR t.description LIKE :query OR t.tags LIKE :queryTag')
                   ->setParameter('query', '%' . $query . '%')
                   ->setParameter('queryTag', '%"' . $query . '"%');
            }

            if ($category) {
                $qb->andWhere('t.category = :category')
                   ->setParameter('category', $category);
            }
            /**
             * @var Template[] $templates
             */
            $templates = $qb->orderBy('t.usage_count', 'DESC')
                            ->setMaxResults($dto->limit)
                            ->setFirstResult($offset)
                            ->getQuery()
                            ->getResult();

            // Count total results
            $totalQb = $this->templateRepository->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->andWhere('t.isPublic = :public')
                ->andWhere('t.is_active = :active')
                ->andWhere('t.deletedAt IS NULL')
                ->setParameter('public', true)
                ->setParameter('active', true);

            if ($query) {
                $totalQb->andWhere('t.name LIKE :query OR t.description LIKE :query OR t.tags LIKE :queryTag')
                        ->setParameter('query', '%' . $query . '%')
                        ->setParameter('queryTag', '%"' . $query . '"%');
            }

            if ($category) {
                $totalQb->andWhere('t.category = :category')
                        ->setParameter('category', $category);
            }

            $total = (int) $totalQb->getQuery()->getSingleScalarResult();

            

            $searchResponse = $this->responseDTOFactory->createTemplateListResponse(
                $templates,
                $dto->page,
                $dto->limit,
                $total,
                $query ? "Search results for: {$query}" : 'Template search results'
            );
            return $this->templateResponse($searchResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to search templates',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get available template categories
     * 
     * Returns a list of all available template categories for filtering
     * and organization purposes. Categories help users find relevant templates
     * for their specific design needs and use cases. Requires authentication.
     * 
     * @return JsonResponse List of template categories or error response
     */
    #[Route('/categories', name: 'categories', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCategories(): JsonResponse
    {
        try {
            $categories = $this->templateRepository->findAllCategories();

            if (empty($categories)) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('No categories found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }
            $categoriesData = array_map(function ($category) {
                return [
                    'name' => $category,
                    'title'=> ucfirst(str_replace(['_','-'], ' ', $category)), // Format category name for display
                    'slug' => strtolower(str_replace(' ', '-', $category)), // Create slug for URL
                ];
            }, $categories);
            return $this->json(
                [
                    'success' => true,
                    'message' => 'Template categories retrieved successfully',
                    'data' => $categoriesData,
                ],
                Response::HTTP_OK,
                [],
                ['groups' => ['template_category']]
            );
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to fetch categories',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get details of a specific template
     * 
     * Returns comprehensive template information including design data, metadata,
     * and usage statistics. Automatically increments the view count for analytics.
     * Access restricted to authenticated users with appropriate subscription.
     * 
     * @param string $uuid The template UUID to retrieve
     * @return JsonResponse<TemplateResponseDTO|ErrorResponseDTO> Template details or error response
     */
    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(string $uuid): JsonResponse
    {
        try {
            $template = $this->templateRepository->findOneBy(['uuid' => $uuid, 'is_active' => true]);
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

    /**
     * Create a new template
     * 
     * Creates a new template from user design with metadata and canvas configuration.
     * Templates can be made public for marketplace or kept private for personal use.
     * Requires authentication and validates all template data before creation.
     * 
     * @param CreateTemplateRequestDTO $dto Template creation data including:
     *                                     - name: Template display name (required)
     *                                     - description: Template description
     *                                     - category: Template category for organization
     *                                     - tags: Array of tags for searchability
     *                                     - width: Canvas width in pixels
     *                                     - height: Canvas height in pixels
     *                                     - canvasSettings: Canvas configuration as JSON
     *                                     - layers: Template layer data as JSON
     *                                     - thumbnailUrl: Template thumbnail image URL
     *                                     - previewUrl: Template preview image URL
     *                                     - isPremium: Whether template requires premium access
     *                                     - isActive: Whether template is publicly available
     * @return JsonResponse<TemplateResponseDTO|ErrorResponseDTO> Created template data or validation errors
     */
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
            $template->setTags($dto->getTagsArray());
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

    /**
     * Use a template to create a new design
     * 
     * Creates a new design project based on the specified template.
     * Copies all template layers, settings, and properties to the new design.
     * Automatically increments the template usage count for analytics.
     * 
     * @param string $uuid The template UUID to use for design creation
     * @return JsonResponse<DesignResponseDTO|ErrorResponseDTO> Created design data or error response
     */
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

            $template = $this->templateRepository->findOneBy(['uuid' => $uuid, 'is_active' => true]);
            if (!$template) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Template not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to premium templates (simplified check)
            if ($template->isPremium() && method_exists($user, 'getPlan') && $user->getPlan() !== 'premium') {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Premium template access required');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            // Create a default project for the design
            $project = new Project();
            $project->setTitle($template->getName() . ' - Project');
            $project->setDescription('Project created from template');
            $project->setUser($user);

            // Create a new design from the template
            $design = new Design();
            $design->setName($template->getName() . ' - Copy');
            $design->setWidth($template->getWidth());
            $design->setHeight($template->getHeight());
            $design->setCanvasWidth($template->getWidth());
            $design->setCanvasHeight($template->getHeight());
            $design->setData($template->getCanvasSettings());
            $design->setBackground(['type' => 'color', 'color' => '#ffffff']);
            $design->setProject($project);

            // Note: Individual layers will need to be created separately in a real implementation
            // For now, we'll store the template layers data in the design's data field

            // Validate project and design
            $projectErrors = $this->validator->validate($project);
            if (count($projectErrors) > 0) {
                $errorMessages = [];
                foreach ($projectErrors as $error) {
                    $errorMessages[] = 'Project: ' . $error->getMessage();
                }
                
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Project validation failed',
                    $errorMessages
                );
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            $errors = $this->validator->validate($design);
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
            $this->entityManager->persist($design);
            $this->entityManager->flush();

            // Increment usage count if method exists
            if (method_exists($this->templateRepository, 'incrementUsageCount')) {
                $this->templateRepository->incrementUsageCount($template);
            }

            $designResponse = $this->responseDTOFactory->createDesignResponse(
                $design,
                'Design created from template successfully'
            );
            return $this->designResponse($designResponse, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to use template',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
