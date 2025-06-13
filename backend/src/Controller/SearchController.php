<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\GlobalSearchResponseDTO;
use App\DTO\Response\MediaSearchResponseDTO;
use App\DTO\Response\ProjectSearchResponseDTO;
use App\DTO\Response\SearchResponseDTO;
use App\DTO\Response\SearchSuggestionResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\DTO\Response\TemplateSearchResponseDTO;
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
 * Handles global search, content-specific searches, and intelligent search suggestions.
 * Supports full-text search with filtering, pagination, and relevance scoring.
 * All searches respect user permissions and visibility settings for security.
 * 
 * Features:
 * - Global search across multiple content types (templates, media, projects)
 * - Targeted searches for specific content types with advanced filtering
 * - Intelligent search suggestions for autocomplete functionality
 * - Pagination and result limiting for performance
 * - User permission-aware search results
 * - Search relevance scoring and result ranking
 * 
 * All endpoints require user authentication and implement proper error handling.
 * Search results are returned in a consistent, paginated format with metadata.
 */
#[Route('/api/search')]
#[IsGranted('ROLE_USER')]
class SearchController extends AbstractController
{
    use TypedResponseTrait;

    /**
     * SearchController constructor
     * 
     * @param SearchService $searchService Service for performing search operations across content types
     * @param ResponseDTOFactory $responseDTOFactory Factory for creating standardized response DTOs
     */
    public function __construct(
        private readonly SearchService $searchService,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    /**
     * Perform a global search across multiple content types
     * 
     * Searches across templates, media, projects based on the query and type filter.
     * Supports pagination and returns results in a structured format with relevance scoring.
     * Respects user permissions and only returns content the user has access to.
     * 
     * @param Request $request HTTP request containing search parameters:
     *                        - q: Search query string (required)
     *                        - type: Content type filter (all, templates, media, projects) (default: all)
     *                        - page: Page number (default: 1, min: 1)
     *                        - limit: Items per page (default: 20, max: 50)
     * @return JsonResponse<GlobalSearchResponseDTO|ErrorResponseDTO> Search results with pagination metadata or error response
     */
    #[Route('', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            // Extract and validate query parameters
            $query = $request->query->get('q', '');
            $type = $request->query->get('type', 'all'); // all, templates, media, projects
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            
            // Validate required search query
            if (empty($query)) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Search query is required'),
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            // Perform search operation
            $results = $this->searchService->search($user, $query, $type, $page, $limit);
            
            // Return successful search response
            return $this->globalSearchResponse(
                $this->responseDTOFactory->createGlobalSearchResponse(
                    $results['items'],
                    $query,
                    $page,
                    $limit,
                    $results['total']
                )
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse($e->getMessage()),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Search specifically for templates
     * 
     * Performs targeted template search with support for category and tag filtering.
     * Returns structured template results with pagination and relevance scoring.
     * Includes both public templates and user-created templates based on permissions.
     * 
     * @param Request $request HTTP request containing search and filter parameters:
     *                        - q: Search query string (optional, searches in name/description)
     *                        - category: Template category filter (optional)
     *                        - tags: Comma-separated list of tags to filter by (optional)
     *                        - page: Page number (default: 1, min: 1)
     *                        - limit: Items per page (default: 20, max: 50)
     * @return JsonResponse<TemplateSearchResponseDTO|ErrorResponseDTO> Template search results with pagination metadata or error response
     */
    #[Route('/templates', name: 'search_templates', methods: ['GET'])]
    public function searchTemplates(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            // Extract and validate query parameters
            $query = $request->query->get('q', '');
            $category = $request->query->get('category', '');
            $tags = $request->query->get('tags', '');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            
            // Perform template search operation
            $results = $this->searchService->searchTemplates($user, $query, $category, $tags, $page, $limit);
            
            // Return successful search response
            return $this->templateSearchResponse(
                $this->responseDTOFactory->createTemplateSearchResponse(
                    $results['items'],
                    $page,
                    $limit,
                    $results['total']
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
     * Returns structured media results with pagination and includes file metadata.
     * Only returns media files that the user has access to view or use.
     * 
     * @param Request $request HTTP request containing search and filter parameters:
     *                        - q: Search query string (optional, searches in name/description/tags)
     *                        - type: Media type filter (image, video, audio) (optional)
     *                        - page: Page number (default: 1, min: 1)
     *                        - limit: Items per page (default: 20, max: 50)
     * @return JsonResponse<MediaSearchResponseDTO|ErrorResponseDTO> Media search results with pagination metadata or error response
     */
    #[Route('/media', name: 'search_media', methods: ['GET'])]
    public function searchMedia(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            // Extract and validate query parameters
            $query = $request->query->get('q', '');
            $type = $request->query->get('type', ''); // image, video, audio
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            
            // Perform media search operation
            $results = $this->searchService->searchMedia($user, $query, $type, $page, $limit);
            
            // Return successful search response
            return $this->mediaSearchResponse(
                $this->responseDTOFactory->createMediaSearchResponse(
                    $results['items'],
                    $page,
                    $limit,
                    $results['total']
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
     * Returns structured project results with pagination and includes project metadata.
     * Only searches through projects owned by the authenticated user for privacy.
     * 
     * @param Request $request HTTP request containing search parameters:
     *                        - q: Search query string (optional, searches in name/description/tags)
     *                        - page: Page number (default: 1, min: 1)
     *                        - limit: Items per page (default: 20, max: 50)
     * @return JsonResponse<ProjectSearchResponseDTO|ErrorResponseDTO> Project search results with pagination metadata or error response
     */
    #[Route('/projects', name: 'search_projects', methods: ['GET'])]
    public function searchProjects(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            // Extract and validate query parameters
            $query = $request->query->get('q', '');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            
            // Perform project search operation
            $results = $this->searchService->searchProjects($user, $query, $page, $limit);
            
            // Return successful search response
            return $this->projectSearchResponse(
                $this->responseDTOFactory->createProjectSearchResponse(
                    $results['items'],
                    $page,
                    $limit,
                    $results['total']
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
     * Used for autocomplete and search assistance features in the UI.
     * Suggestions are personalized based on user's content and search history.
     * 
     * @param Request $request HTTP request containing query parameters:
     *                        - q: Partial search query string (optional)
     *                        - limit: Maximum number of suggestions (default: 10, max: 50)
     * @return JsonResponse<SearchSuggestionResponseDTO|ErrorResponseDTO> Search suggestions array or error response
     */
    #[Route('/suggestions', name: 'search_suggestions', methods: ['GET'])]
    public function getSearchSuggestions(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            // Extract and validate query parameters
            $query = $request->query->get('q', '');
            $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
            
            // Get personalized search suggestions
            $suggestions = $this->searchService->getSearchSuggestions($user, $query, $limit);
            
            // Return successful response with suggestions
            return $this->searchSuggestionResponse(
                $this->responseDTOFactory->createSearchSuggestionResponse(
                    $suggestions,
                    $query,
                    'Search suggestions retrieved successfully'
                )
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to get suggestions'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
