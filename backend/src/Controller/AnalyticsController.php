<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Request\AnalyticsRequestDTO;
use App\DTO\Response\AnalyticsResponseDTO;
use App\DTO\Response\DashboardAnalyticsResponseDTO;
use App\DTO\Response\DesignAnalyticsResponseDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\SystemAnalyticsResponseDTO;
use App\Entity\User;
use App\Service\AnalyticsService;
use App\Service\ResponseDTOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Analytics Controller
 * 
 * Provides comprehensive analytics endpoints for the design platform.
 * Handles dashboard statistics, design performance metrics, user behavior analysis,
 * template usage analytics, and system-wide performance monitoring.
 * 
 * All endpoints require authentication, with some restricted to admin users.
 * Returns detailed analytics data for dashboards, reports, and insights.
 * 
 * Endpoints:
 * - Dashboard analytics (user-specific)
 * - Design performance analytics
 * - Template usage analytics
 * - User behavior analytics
 * - System analytics (admin only)
 * - Export analytics
 * - Platform statistics
 */
#[Route('/api/analytics', name: 'api_analytics_')]
#[IsGranted('ROLE_USER')]
class AnalyticsController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly ValidatorInterface $validator,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    /**
     * Get dashboard analytics for authenticated user
     * 
     * Returns comprehensive dashboard statistics including:
     * - User overview metrics (designs, projects, exports)
     * - Activity charts and timeline data
     * - Growth trends and performance indicators
     * - Top performing content and insights
     * 
     * @return JsonResponse<DashboardAnalyticsResponseDTO|ErrorResponseDTO> Dashboard analytics or error response
     */
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $analyticsData = $this->analyticsService->getDashboardAnalytics($user);

            $response = DashboardAnalyticsResponseDTO::fromData($analyticsData);

            return new JsonResponse([
                'success' => true,
                'message' => 'Dashboard analytics retrieved successfully',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse(
                    'Failed to retrieve dashboard analytics',
                    [$e->getMessage()]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get analytics for a specific design
     * 
     * Returns detailed performance metrics for an individual design including:
     * - Engagement metrics (views, shares, exports)
     * - Timeline and usage patterns
     * - Export format breakdown and success rates
     * - Performance comparison and recommendations
     * 
     * @param string $designId The design ID to analyze
     * @return JsonResponse<DesignAnalyticsResponseDTO|ErrorResponseDTO> Design analytics or error response
     */
    #[Route('/designs/{designId}', name: 'design_analytics', methods: ['GET'])]
    public function designAnalytics(string $designId): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $analyticsData = $this->analyticsService->getDesignAnalytics($designId, $user);

            $response = DesignAnalyticsResponseDTO::fromData($designId, $analyticsData);

            return new JsonResponse([
                'success' => true,
                'message' => 'Design analytics retrieved successfully',
                'data' => $response
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse($e->getMessage()),
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse(
                    'Failed to retrieve design analytics',
                    [$e->getMessage()]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get template usage analytics
     * 
     * Returns comprehensive template performance and usage statistics including:
     * - Most popular templates by usage count
     * - Category performance analysis
     * - Template conversion rates and effectiveness
     * - Usage trends and recommendations
     * 
     * @param Request $request HTTP request with optional parameters
     * @return JsonResponse<array|ErrorResponseDTO> Template analytics or error response
     */
    #[Route('/templates', name: 'template_analytics', methods: ['GET'])]
    public function templateAnalytics(Request $request): JsonResponse
    {
        try {
            $limit = min(100, max(1, (int) $request->query->get('limit', 50)));

            $analyticsData = $this->analyticsService->getTemplateAnalytics($limit);

            return new JsonResponse([
                'success' => true,
                'message' => 'Template analytics retrieved successfully',
                'data' => $analyticsData
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse(
                    'Failed to retrieve template analytics',
                    [$e->getMessage()]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get user behavior analytics
     * 
     * Returns detailed user behavior analysis including:
     * - Login patterns and session analytics
     * - Feature usage and adoption rates
     * - Content creation patterns and preferences
     * - Engagement trends and behavioral insights
     * 
     * @return JsonResponse<array|ErrorResponseDTO> User behavior analytics or error response
     */
    #[Route('/user-behavior', name: 'user_behavior', methods: ['GET'])]
    public function userBehavior(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $analyticsData = $this->analyticsService->getUserBehaviorAnalytics($user);

            return new JsonResponse([
                'success' => true,
                'message' => 'User behavior analytics retrieved successfully',
                'data' => $analyticsData
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse(
                    'Failed to retrieve user behavior analytics',
                    [$e->getMessage()]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get export analytics for authenticated user
     * 
     * Returns comprehensive export job analytics including:
     * - Export volume and success rates
     * - Format breakdown and preferences
     * - Processing time statistics
     * - Failure analysis and insights
     * 
     * @param Request $request HTTP request with optional filter parameters
     * @return JsonResponse<array|ErrorResponseDTO> Export analytics or error response
     */
    #[Route('/exports', name: 'export_analytics', methods: ['GET'])]
    public function exportAnalytics(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $dto = AnalyticsRequestDTO::fromRequest($request);

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse(
                        'Invalid request parameters',
                        array_map(fn($error) => $error->getMessage(), iterator_to_array($errors))
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Get export analytics using existing export repository methods
            $exportStats = $this->analyticsService->getExportAnalytics($user, $dto);

            return new JsonResponse([
                'success' => true,
                'message' => 'Export analytics retrieved successfully',
                'data' => $exportStats
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse(
                    'Failed to retrieve export analytics',
                    [$e->getMessage()]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get platform usage trends
     * 
     * Returns platform-wide usage trends and statistics including:
     * - Content creation trends over time
     * - User activity patterns
     * - Popular features and tools
     * - Growth metrics and insights
     * 
     * @param Request $request HTTP request with optional time range parameters
     * @return JsonResponse<array|ErrorResponseDTO> Platform trends or error response
     */
    #[Route('/trends', name: 'platform_trends', methods: ['GET'])]
    public function platformTrends(Request $request): JsonResponse
    {
        try {
            $dto = AnalyticsRequestDTO::fromRequest($request);

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse(
                        'Invalid request parameters',
                        array_map(fn($error) => $error->getMessage(), iterator_to_array($errors))
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $trendsData = $this->analyticsService->getPlatformTrends($dto);

            return new JsonResponse([
                'success' => true,
                'message' => 'Platform trends retrieved successfully',
                'data' => $trendsData
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse(
                    'Failed to retrieve platform trends',
                    [$e->getMessage()]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get system-wide analytics (Admin only)
     * 
     * Returns comprehensive system analytics including:
     * - Platform growth and user acquisition metrics
     * - System performance and health indicators
     * - Resource usage and optimization opportunities
     * - Administrative insights and recommendations
     * 
     * @return JsonResponse<SystemAnalyticsResponseDTO|ErrorResponseDTO> System analytics or error response
     */
    #[Route('/system', name: 'system_analytics', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function systemAnalytics(): JsonResponse
    {
        try {
            $analyticsData = $this->analyticsService->getSystemAnalytics();

            $response = SystemAnalyticsResponseDTO::fromData($analyticsData);

            return new JsonResponse([
                'success' => true,
                'message' => 'System analytics retrieved successfully',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse(
                    'Failed to retrieve system analytics',
                    [$e->getMessage()]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get user engagement metrics (Admin only)
     * 
     * Returns detailed user engagement analytics including:
     * - User activity and session metrics
     * - Feature adoption and usage patterns
     * - Retention and churn analysis
     * - Engagement scoring and segmentation
     * 
     * @param Request $request HTTP request with optional filter parameters
     * @return JsonResponse<array|ErrorResponseDTO> User engagement metrics or error response
     */
    #[Route('/engagement', name: 'user_engagement', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function userEngagement(Request $request): JsonResponse
    {
        try {
            $dto = AnalyticsRequestDTO::fromRequest($request);

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse(
                        'Invalid request parameters',
                        array_map(fn($error) => $error->getMessage(), iterator_to_array($errors))
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $engagementData = $this->analyticsService->getUserEngagementMetrics($dto);

            return new JsonResponse([
                'success' => true,
                'message' => 'User engagement metrics retrieved successfully',
                'data' => $engagementData
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse(
                    'Failed to retrieve user engagement metrics',
                    [$e->getMessage()]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get performance monitoring data (Admin only)
     * 
     * Returns system performance monitoring data including:
     * - Response time metrics
     * - Error rates and failure analysis
     * - Resource utilization statistics
     * - Queue health and processing metrics
     * 
     * @return JsonResponse<array|ErrorResponseDTO> Performance monitoring data or error response
     */
    #[Route('/performance', name: 'performance_monitoring', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function performanceMonitoring(): JsonResponse
    {
        try {
            $performanceData = $this->analyticsService->getPerformanceMonitoringData();

            return new JsonResponse([
                'success' => true,
                'message' => 'Performance monitoring data retrieved successfully',
                'data' => $performanceData
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse(
                    'Failed to retrieve performance monitoring data',
                    [$e->getMessage()]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
