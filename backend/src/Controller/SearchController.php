<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\Entity\User;
use App\Service\ResponseDTOFactory;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Search Controller
 * 
 * Provides comprehensive search functionality across all platform content types.
 * Handles global search, content-specific searches, and search suggestions.
 * Supports full-text search with filtering, pagination, and relevance scoring.
 * All searches respect user permissions and visibility settings.
 */
#[Route('/api/search')]
#[IsGranted('ROLE_USER')]
class SearchController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly SearchService $searchService,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    /**
     * Perform a global search across multiple content types
     * 
     * Searches across templates, media, projects based on the query and type filter.
     * Supports pagination and returns results in a structured format.
     * 
     * @param Request $request HTTP request containing search parameters
     * @return JsonResponse Search results with pagination metadata
     */
    #[Route('', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $query = $request->query->get('q', '');
            $type = $request->query->get('type', 'all'); // all, templates, media, projects
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            
            if (empty($query)) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Search query is required'),
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            $results = $this->searchService->search($user, $query, $type, $page, $limit);
            
            return $this->searchResponse(
                $this->responseDTOFactory->createSearchResponse(
                    $results['items'],
                    $results['total'],
                    $page,
                    $limit,
                    $query
                )
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Search failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Search specifically for templates
     * 
     * Performs targeted template search with support for category and tag filtering.
     * Returns structured template results with pagination.
     * 
     * @param Request $request HTTP request containing search and filter parameters
     * @return JsonResponse Template search results with pagination metadata
     */
    #[Route('/templates', name: 'search_templates', methods: ['GET'])]
    public function searchTemplates(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $query = $request->query->get('q', '');
            $category = $request->query->get('category', '');
            $tags = $request->query->get('tags', '');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            
            $results = $this->searchService->searchTemplates($user, $query, $category, $tags, $page, $limit);
            
            return $this->searchResponse(
                $this->responseDTOFactory->createSearchResponse(
                    $results['items'],
                    $results['total'],
                    $page,
                    $limit,
                    $query
                )
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Template search failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Search specifically for media files
     * 
     * Performs targeted media search with support for media type filtering (image, video, audio).
     * Returns structured media results with pagination.
     * 
     * @param Request $request HTTP request containing search and filter parameters
     * @return JsonResponse Media search results with pagination metadata
     */
    #[Route('/media', name: 'search_media', methods: ['GET'])]
    public function searchMedia(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $query = $request->query->get('q', '');
            $type = $request->query->get('type', ''); // image, video, audio
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            
            $results = $this->searchService->searchMedia($user, $query, $type, $page, $limit);
            
            return $this->searchResponse(
                $this->responseDTOFactory->createSearchResponse(
                    $results['items'],
                    $results['total'],
                    $page,
                    $limit,
                    $query
                )
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Media search failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Search specifically for user projects
     * 
     * Performs targeted project search within the user's own projects.
     * Returns structured project results with pagination.
     * 
     * @param Request $request HTTP request containing search parameters
     * @return JsonResponse Project search results with pagination metadata
     */
    #[Route('/projects', name: 'search_projects', methods: ['GET'])]
    public function searchProjects(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $query = $request->query->get('q', '');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            
            $results = $this->searchService->searchProjects($user, $query, $page, $limit);
            
            return $this->searchResponse(
                $this->responseDTOFactory->createSearchResponse(
                    $results['items'],
                    $results['total'],
                    $page,
                    $limit,
                    $query
                )
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Project search failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get search suggestions based on user query
     * 
     * Returns search suggestions to help users find relevant content.
     * Used for autocomplete and search assistance features.
     * 
     * @param Request $request HTTP request containing query parameters
     * @return JsonResponse Search suggestions array
     */
    #[Route('/suggestions', name: 'search_suggestions', methods: ['GET'])]
    public function getSearchSuggestions(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $query = $request->query->get('q', '');
            $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
            
            $suggestions = $this->searchService->getSearchSuggestions($user, $query, $limit);
            
            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('Suggestions retrieved successfully', [
                    'suggestions' => $suggestions
                ])
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to get suggestions'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
