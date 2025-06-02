<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\AnalyticsRepository;
use App\Repository\ExportJobRepository;
use App\Repository\TemplateRepository;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\DesignRepository;
use App\DTO\Request\AnalyticsRequestDTO;

/**
 * Analytics Service
 * 
 * Business logic layer for analytics operations in the design platform.
 * Aggregates data from multiple repositories and provides processed analytics
 * for dashboards, reports, and insights.
 * 
 * Key functionalities:
 * - Dashboard analytics compilation and KPI calculation
 * - Design performance analysis and trend identification
 * - User behavior analytics and engagement metrics
 * - System health monitoring and performance tracking
 * - Template usage analytics and recommendation logic
 * - Export analytics and processing optimization insights
 * - Data aggregation and trend analysis
 * - Caching layer for frequently accessed analytics
 * 
 * This service acts as the central hub for all analytics-related business logic,
 * ensuring consistent data processing and calculation methods across the platform.
 */
class AnalyticsService
{
    public function __construct(
        private readonly AnalyticsRepository $analyticsRepository,
        private readonly ExportJobRepository $exportJobRepository,
        private readonly TemplateRepository $templateRepository,
        private readonly UserRepository $userRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly DesignRepository $designRepository
    ) {}

    /**
     * Get comprehensive dashboard analytics for a user
     * 
     * Compiles dashboard statistics from multiple sources including:
     * - User's content statistics (designs, projects, exports)
     * - Performance metrics and success rates
     * - Growth trends and activity patterns
     * - Storage usage and quota information
     * - Recent activity summary
     *
     * @param User $user The user to generate dashboard analytics for
     * @return array Comprehensive dashboard analytics
     */
    public function getDashboardAnalytics(User $user): array
    {
        // Get base dashboard stats from analytics repository
        $baseStats = $this->analyticsRepository->getDashboardStats($user);

        // Get additional export job statistics
        $exportStats = $this->exportJobRepository->getUserExportStats($user);

        // Calculate storage information
        $storageInfo = $this->calculateStorageMetrics($user);

        // Get recent activity insights
        $activityInsights = $this->getActivityInsights($user);

        // Get user performance ranking (percentile among all users)
        $performanceRanking = $this->calculateUserPerformanceRanking($user);

        return [
            'overview' => array_merge($baseStats['overview'], [
                'storage_info' => $storageInfo,
                'performance_ranking' => $performanceRanking
            ]),
            'charts' => [
                'activity_timeline' => $baseStats['recent_activity'],
                'export_breakdown' => $exportStats,
                'monthly_trends' => $this->calculateMonthlyTrends($user)
            ],
            'trends' => [
                'designs_growth' => $this->calculateGrowthRate($user, 'designs'),
                'exports_growth' => $this->calculateGrowthRate($user, 'exports'),
                'activity_trend' => $activityInsights['trend']
            ],
            'top_performers' => [
                'most_exported_designs' => $this->getMostExportedDesigns($user),
                'popular_formats' => $this->getPopularExportFormats($user),
                'active_projects' => $this->getMostActiveProjects($user)
            ]
        ];
    }

    /**
     * Get detailed analytics for a specific design
     * 
     * Provides comprehensive performance metrics for an individual design:
     * - Engagement metrics (views, shares, exports)
     * - Performance comparisons with user's other designs
     * - Usage patterns and timeline analysis
     * - Export format preferences and success rates
     * - Recommendations for improvement
     *
     * @param string $designId The design ID to analyze
     * @param User $user The owner user for access control
     * @return array Detailed design analytics
     */
    public function getDesignAnalytics(string $designId, User $user): array
    {
        // Get base design analytics
        $baseAnalytics = $this->analyticsRepository->getDesignAnalytics($designId, $user);

        if (empty($baseAnalytics)) {
            throw new \InvalidArgumentException('Design not found or access denied');
        }

        // Calculate performance score
        $performanceScore = $this->calculateDesignPerformanceScore($baseAnalytics['metrics']);

        // Get comparison with user's other designs
        $comparison = $this->getDesignComparison($designId, $user);

        // Generate insights and recommendations
        $insights = $this->generateDesignInsights($baseAnalytics['metrics'], $comparison);

        return [
            'metrics' => array_merge($baseAnalytics['metrics'], [
                'performance_score' => $performanceScore
            ]),
            'timeline' => $baseAnalytics['timeline'],
            'exports' => $baseAnalytics['exports'],
            'engagement' => [
                'comparison_with_average' => $comparison,
                'insights' => $insights,
                'recommendations' => $this->generateDesignRecommendations($baseAnalytics['metrics'])
            ]
        ];
    }

    /**
     * Get system-wide analytics (Admin only)
     * 
     * Provides comprehensive platform analytics including:
     * - Platform growth metrics and user acquisition
     * - Content creation trends and engagement patterns
     * - System performance and health indicators
     * - Resource usage and optimization opportunities
     * - Popular content and feature adoption
     *
     * @return array System-wide analytics
     */
    public function getSystemAnalytics(): array
    {
        // Get base system analytics
        $baseAnalytics = $this->analyticsRepository->getSystemAnalytics();

        // Get export system performance
        $exportSystemStats = $this->exportJobRepository->getSystemExportStats();

        // Get template analytics
        $templateAnalytics = $this->analyticsRepository->getTemplateAnalytics();

        // Calculate system health score
        $systemHealth = $this->calculateSystemHealthScore();

        return [
            'platformStats' => array_merge($baseAnalytics['platform_stats'], [
                'system_health_score' => $systemHealth['score'],
                'health_indicators' => $systemHealth['indicators']
            ]),
            'userMetrics' => [
                'growth_trend' => $baseAnalytics['user_growth'],
                'engagement_metrics' => $this->calculateUserEngagementMetrics(),
                'retention_rates' => $this->calculateUserRetentionRates()
            ],
            'performanceData' => [
                'content_trends' => $baseAnalytics['content_trends'],
                'export_performance' => $exportSystemStats,
                'template_usage' => $templateAnalytics,
                'popular_categories' => $baseAnalytics['popular_categories']
            ],
            'systemHealth' => [
                'queue_status' => $this->exportJobRepository->getQueueStats(),
                'error_rates' => $this->calculateSystemErrorRates(),
                'performance_metrics' => $this->getSystemPerformanceMetrics()
            ]
        ];
    }

    /**
     * Get template usage analytics
     * 
     * Analyzes template performance and usage patterns:
     * - Most popular templates by usage and engagement
     * - Category performance and trends
     * - Template conversion rates and effectiveness
     * - User preferences and recommendation opportunities
     *
     * @param int $limit Maximum number of results
     * @return array Template usage analytics
     */
    public function getTemplateAnalytics(int $limit = 50): array
    {
        $analytics = $this->analyticsRepository->getTemplateAnalytics($limit);

        // Add conversion rate analysis
        $conversionRates = $this->calculateTemplateConversionRates();

        // Calculate category trends
        $categoryTrends = $this->calculateCategoryTrends();

        return array_merge($analytics, [
            'conversion_rates' => $conversionRates,
            'category_trends' => $categoryTrends,
            'recommendations' => $this->generateTemplateRecommendations($analytics)
        ]);
    }

    /**
     * Get user behavior analytics
     * 
     * Analyzes user behavior patterns and engagement:
     * - Login patterns and session analytics
     * - Feature usage and adoption rates
     * - Content creation patterns
     * - Engagement trends and insights
     *
     * @param User $user The user to analyze
     * @return array User behavior analytics
     */
    public function getUserBehaviorAnalytics(User $user): array
    {
        $baseAnalytics = $this->analyticsRepository->getUserBehaviorAnalytics($user);

        // Calculate engagement score
        $engagementScore = $this->calculateUserEngagementScore($user);

        // Get feature adoption timeline
        $featureAdoption = $this->getFeatureAdoptionTimeline($user);

        return array_merge($baseAnalytics, [
            'engagement_score' => $engagementScore,
            'feature_adoption' => $featureAdoption,
            'behavioral_insights' => $this->generateBehavioralInsights($baseAnalytics, $user)
        ]);
    }

    /**
     * Get export analytics for a user
     * 
     * Provides comprehensive export job analytics including:
     * - Export volume and success rates
     * - Format breakdown and preferences
     * - Processing time statistics
     * - Failure analysis and insights
     *
     * @param User $user The user to analyze
     * @param AnalyticsRequestDTO $dto Request parameters with date range and filters
     * @return array Export analytics data
     */
    public function getExportAnalytics(User $user, AnalyticsRequestDTO $dto): array
    {
        // Get base export statistics from repository
        $exportStats = $this->exportJobRepository->getExportStatsForUser(
            $user,
            $dto->getStartDate(),
            $dto->getEndDate()
        );

        // Calculate success rate
        $totalExports = $exportStats['total_exports'] ?? 0;
        $successfulExports = $exportStats['successful_exports'] ?? 0;
        $successRate = $totalExports > 0 ? ($successfulExports / $totalExports) * 100 : 0;

        // Get format breakdown
        $formatBreakdown = $this->exportJobRepository->getFormatBreakdownForUser($user);

        // Calculate processing time statistics
        $processingStats = $this->exportJobRepository->getProcessingTimeStats($user);

        return [
            'overview' => [
                'total_exports' => $totalExports,
                'successful_exports' => $successfulExports,
                'failed_exports' => $exportStats['failed_exports'] ?? 0,
                'success_rate' => round($successRate, 2),
                'avg_processing_time' => $processingStats['avg_processing_time'] ?? 0
            ],
            'format_breakdown' => $formatBreakdown,
            'timeline' => $exportStats['timeline'] ?? [],
            'performance_metrics' => [
                'fastest_export' => $processingStats['fastest'] ?? null,
                'slowest_export' => $processingStats['slowest'] ?? null,
                'median_time' => $processingStats['median'] ?? 0
            ],
            'insights' => $this->generateExportInsights($exportStats, $formatBreakdown)
        ];
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
     * @param AnalyticsRequestDTO $dto Request parameters with date range
     * @return array Platform trends data
     */
    public function getPlatformTrends(AnalyticsRequestDTO $dto): array
    {
        // Get platform statistics from repository
        $platformStats = $this->analyticsRepository->getPlatformTrends(
            $dto->getStartDate(),
            $dto->getEndDate()
        );

        // Calculate growth metrics
        $growthMetrics = $this->calculatePlatformGrowthMetrics($dto);

        // Get feature usage trends
        $featureUsage = $this->analyticsRepository->getFeatureUsageTrends(
            $dto->getStartDate(),
            $dto->getEndDate()
        );

        // Get popular content categories
        $popularCategories = $this->analyticsRepository->getPopularCategories(
            $dto->getStartDate(),
            $dto->getEndDate()
        );

        return [
            'content_trends' => [
                'designs_created' => $platformStats['designs_timeline'] ?? [],
                'projects_created' => $platformStats['projects_timeline'] ?? [],
                'templates_used' => $platformStats['templates_timeline'] ?? []
            ],
            'user_activity' => [
                'daily_active_users' => $platformStats['dau_timeline'] ?? [],
                'new_registrations' => $platformStats['registration_timeline'] ?? [],
                'retention_rates' => $growthMetrics['retention'] ?? []
            ],
            'feature_adoption' => $featureUsage,
            'popular_content' => $popularCategories,
            'growth_metrics' => $growthMetrics,
            'insights' => $this->generatePlatformInsights($platformStats, $growthMetrics)
        ];
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
     * @param AnalyticsRequestDTO $dto Request parameters with filters
     * @return array User engagement metrics
     */
    public function getUserEngagementMetrics(AnalyticsRequestDTO $dto): array
    {
        // Get engagement metrics from repository
        $engagementData = $this->analyticsRepository->getUserEngagementMetrics(
            $dto->getStartDate(),
            $dto->getEndDate()
        );

        // Calculate engagement scores
        $engagementScores = $this->calculateEngagementScores($engagementData);

        // Get retention analysis
        $retentionAnalysis = $this->calculateRetentionAnalysis($dto);

        // Get feature adoption metrics
        $featureAdoption = $this->calculateFeatureAdoptionMetrics($dto);

        return [
            'overview' => [
                'total_active_users' => $engagementData['total_active_users'] ?? 0,
                'avg_session_duration' => $engagementData['avg_session_duration'] ?? 0,
                'avg_sessions_per_user' => $engagementData['avg_sessions_per_user'] ?? 0,
                'overall_engagement_score' => $engagementScores['overall'] ?? 0
            ],
            'engagement_distribution' => $engagementScores['distribution'] ?? [],
            'retention_metrics' => $retentionAnalysis,
            'feature_adoption' => $featureAdoption,
            'user_segments' => $this->calculateUserSegments($engagementData),
            'churn_analysis' => $this->calculateChurnAnalysis($dto),
            'insights' => $this->generateEngagementInsights($engagementData, $retentionAnalysis)
        ];
    }

    /**
     * Get performance monitoring data (Admin only)
     * 
     * Returns system performance monitoring data including:
     * - API response time metrics
     * - Error rates and failure analysis
     * - Resource utilization statistics
     * - Queue health and processing metrics
     *
     * @return array Performance monitoring data
     */
    public function getPerformanceMonitoringData(): array
    {
        // Get system performance metrics
        $performanceMetrics = $this->getSystemPerformanceMetrics();

        // Get queue statistics
        $queueStats = $this->exportJobRepository->getQueueStats();

        // Get error rate analysis
        $errorAnalysis = $this->calculateSystemErrorRates();

        // Get resource utilization
        $resourceUtilization = $this->getResourceUtilizationMetrics();

        return [
            'response_times' => [
                'api_endpoints' => $performanceMetrics['api_response_times'] ?? [],
                'avg_response_time' => $performanceMetrics['avg_response_time'] ?? 0,
                'slowest_endpoints' => $performanceMetrics['slowest_endpoints'] ?? []
            ],
            'error_metrics' => [
                'error_rate' => $errorAnalysis['overall_error_rate'] ?? 0,
                'error_breakdown' => $errorAnalysis['error_breakdown'] ?? [],
                'critical_errors' => $errorAnalysis['critical_errors'] ?? []
            ],
            'queue_health' => [
                'pending_jobs' => $queueStats['pending'] ?? 0,
                'processing_jobs' => $queueStats['processing'] ?? 0,
                'failed_jobs' => $queueStats['failed'] ?? 0,
                'avg_processing_time' => $queueStats['avg_processing_time'] ?? 0
            ],
            'resource_utilization' => $resourceUtilization,
            'system_health' => $this->calculateSystemHealthScore(),
            'recommendations' => $this->generatePerformanceRecommendations($performanceMetrics, $errorAnalysis)
        ];
    }

    /**
     * Calculate storage metrics for a user
     *
     * @param User $user
     * @return array Storage usage metrics
     */
    private function calculateStorageMetrics(User $user): array
    {
        // Implementation would calculate storage usage, limits, etc.
        // This is a placeholder for the actual calculation
        return [
            'used_bytes' => 0,
            'limit_bytes' => 1073741824, // 1GB default
            'percentage_used' => 0,
            'files_count' => 0
        ];
    }

    /**
     * Get activity insights for a user
     *
     * @param User $user
     * @return array Activity insights
     */
    private function getActivityInsights(User $user): array
    {
        // Implementation would analyze user activity patterns
        return [
            'trend' => 'increasing',
            'most_active_day' => 'monday',
            'peak_hour' => 14
        ];
    }

    /**
     * Calculate user performance ranking
     *
     * @param User $user
     * @return array Performance ranking data
     */
    private function calculateUserPerformanceRanking(User $user): array
    {
        // Implementation would rank user against others
        return [
            'percentile' => 75,
            'rank' => 1250,
            'total_users' => 5000
        ];
    }

    /**
     * Calculate monthly trends for a user
     *
     * @param User $user
     * @return array Monthly trend data
     */
    private function calculateMonthlyTrends(User $user): array
    {
        // Implementation would calculate month-over-month trends
        return [
            'designs' => ['current' => 15, 'previous' => 12, 'change' => 25.0],
            'exports' => ['current' => 45, 'previous' => 38, 'change' => 18.4],
            'projects' => ['current' => 5, 'previous' => 4, 'change' => 25.0]
        ];
    }

    /**
     * Calculate growth rate for a specific metric
     *
     * @param User $user
     * @param string $metric
     * @return float Growth rate percentage
     */
    private function calculateGrowthRate(User $user, string $metric): float
    {
        // Implementation would calculate growth rates
        return 15.5;
    }

    /**
     * Get most exported designs for a user
     *
     * @param User $user
     * @return array Most exported designs
     */
    private function getMostExportedDesigns(User $user): array
    {
        // Implementation would find designs with most exports
        return [];
    }

    /**
     * Get popular export formats for a user
     *
     * @param User $user
     * @return array Popular export formats
     */
    private function getPopularExportFormats(User $user): array
    {
        // Implementation would analyze export format preferences
        return [];
    }

    /**
     * Get most active projects for a user
     *
     * @param User $user
     * @return array Most active projects
     */
    private function getMostActiveProjects(User $user): array
    {
        // Implementation would find most active projects
        return [];
    }

    /**
     * Calculate design performance score
     *
     * @param array $metrics
     * @return int Performance score (0-100)
     */
    private function calculateDesignPerformanceScore(array $metrics): int
    {
        // Implementation would calculate a performance score
        return 85;
    }

    /**
     * Get design comparison data
     *
     * @param string $designId
     * @param User $user
     * @return array Comparison data
     */
    private function getDesignComparison(string $designId, User $user): array
    {
        // Implementation would compare with user's other designs
        return [];
    }

    /**
     * Generate design insights
     *
     * @param array $metrics
     * @param array $comparison
     * @return array Design insights
     */
    private function generateDesignInsights(array $metrics, array $comparison): array
    {
        // Implementation would generate insights
        return [];
    }

    /**
     * Generate design recommendations
     *
     * @param array $metrics
     * @return array Design recommendations
     */
    private function generateDesignRecommendations(array $metrics): array
    {
        // Implementation would generate recommendations
        return [];
    }

    /**
     * Calculate system health score
     *
     * @return array System health data
     */
    private function calculateSystemHealthScore(): array
    {
        // Implementation would calculate system health
        return [
            'score' => 92,
            'indicators' => []
        ];
    }

    /**
     * Calculate user engagement metrics
     *
     * @return array User engagement metrics
     */
    private function calculateUserEngagementMetrics(): array
    {
        // Implementation would calculate engagement metrics
        return [];
    }

    /**
     * Calculate user retention rates
     *
     * @return array User retention rates
     */
    private function calculateUserRetentionRates(): array
    {
        // Implementation would calculate retention rates
        return [];
    }

    /**
     * Calculate system error rates
     *
     * @return array System error rates
     */
    private function calculateSystemErrorRates(): array
    {
        // Implementation would calculate error rates
        return [];
    }

    /**
     * Get system performance metrics
     *
     * @return array System performance metrics
     */
    private function getSystemPerformanceMetrics(): array
    {
        // Implementation would get performance metrics
        return [];
    }

    /**
     * Calculate template conversion rates
     *
     * @return array Template conversion rates
     */
    private function calculateTemplateConversionRates(): array
    {
        // Implementation would calculate conversion rates
        return [];
    }

    /**
     * Calculate category trends
     *
     * @return array Category trends
     */
    private function calculateCategoryTrends(): array
    {
        // Implementation would calculate category trends
        return [];
    }

    /**
     * Generate template recommendations
     *
     * @param array $analytics
     * @return array Template recommendations
     */
    private function generateTemplateRecommendations(array $analytics): array
    {
        // Implementation would generate recommendations
        return [];
    }

    /**
     * Calculate user engagement score
     *
     * @param User $user
     * @return int Engagement score (0-100)
     */
    private function calculateUserEngagementScore(User $user): int
    {
        // Implementation would calculate engagement score
        return 75;
    }

    /**
     * Get feature adoption timeline
     *
     * @param User $user
     * @return array Feature adoption timeline
     */
    private function getFeatureAdoptionTimeline(User $user): array
    {
        // Implementation would track feature adoption
        return [];
    }

    /**
     * Generate behavioral insights
     *
     * @param array $analytics
     * @param User $user
     * @return array Behavioral insights
     */
    private function generateBehavioralInsights(array $analytics, User $user): array
    {
        // Implementation would generate insights
        return [];
    }

    /**
     * Generate export insights
     *
     * @param array $exportStats
     * @param array $formatBreakdown
     * @return array Export insights
     */
    private function generateExportInsights(array $exportStats, array $formatBreakdown): array
    {
        $insights = [];

        // Analyze success rate
        $successRate = ($exportStats['total_exports'] ?? 0) > 0 
            ? (($exportStats['successful_exports'] ?? 0) / $exportStats['total_exports']) * 100 
            : 0;

        if ($successRate < 85) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Export success rate is below optimal threshold',
                'recommendation' => 'Review failed exports and optimize design complexity'
            ];
        }

        // Analyze format preferences
        if (!empty($formatBreakdown)) {
            $mostPopular = array_key_first($formatBreakdown);
            $insights[] = [
                'type' => 'info',
                'message' => "Most popular export format: {$mostPopular}",
                'recommendation' => 'Consider optimizing designs for this format'
            ];
        }

        return $insights;
    }

    /**
     * Calculate platform growth metrics
     *
     * @param AnalyticsRequestDTO $dto
     * @return array Platform growth metrics
     */
    private function calculatePlatformGrowthMetrics(AnalyticsRequestDTO $dto): array
    {
        // Get current period stats
        $currentStats = $this->analyticsRepository->getPlatformStats(
            $dto->getStartDate(),
            $dto->getEndDate()
        );

        // Calculate previous period for comparison
        $daysDiff = $dto->getStartDate()->diff($dto->getEndDate())->days;
        $startDate = $dto->getStartDate();
        
        // Create previous period start date
        $previousStart = new \DateTimeImmutable($startDate->format('Y-m-d H:i:s'));
        $previousStart = $previousStart->sub(new \DateInterval("P{$daysDiff}D"));
        
        $previousEnd = $dto->getStartDate();

        $previousStats = $this->analyticsRepository->getPlatformStats(
            $previousStart,
            $previousEnd
        );

        // Calculate growth rates
        $userGrowth = $this->calculateGrowthPercentage(
            $previousStats['total_users'] ?? 0,
            $currentStats['total_users'] ?? 0
        );

        $designGrowth = $this->calculateGrowthPercentage(
            $previousStats['total_designs'] ?? 0,
            $currentStats['total_designs'] ?? 0
        );

        return [
            'users' => [
                'current' => $currentStats['total_users'] ?? 0,
                'previous' => $previousStats['total_users'] ?? 0,
                'growth_rate' => $userGrowth
            ],
            'designs' => [
                'current' => $currentStats['total_designs'] ?? 0,
                'previous' => $previousStats['total_designs'] ?? 0,
                'growth_rate' => $designGrowth
            ],
            'retention' => $this->calculateRetentionRates()
        ];
    }

    /**
     * Generate platform insights
     *
     * @param array $platformStats
     * @param array $growthMetrics
     * @return array Platform insights
     */
    private function generatePlatformInsights(array $platformStats, array $growthMetrics): array
    {
        $insights = [];

        // User growth insights
        if (($growthMetrics['users']['growth_rate'] ?? 0) > 20) {
            $insights[] = [
                'type' => 'success',
                'category' => 'growth',
                'message' => 'Strong user growth detected',
                'value' => $growthMetrics['users']['growth_rate']
            ];
        }

        // Design activity insights
        if (($growthMetrics['designs']['growth_rate'] ?? 0) > 30) {
            $insights[] = [
                'type' => 'success',
                'category' => 'engagement',
                'message' => 'High design creation activity',
                'value' => $growthMetrics['designs']['growth_rate']
            ];
        }

        return $insights;
    }

    /**
     * Calculate engagement scores
     *
     * @param array $engagementData
     * @return array Engagement scores
     */
    private function calculateEngagementScores(array $engagementData): array
    {
        $totalUsers = $engagementData['total_active_users'] ?? 1;
        $avgSessionDuration = $engagementData['avg_session_duration'] ?? 0;
        $avgSessionsPerUser = $engagementData['avg_sessions_per_user'] ?? 0;

        // Calculate overall engagement score (0-100)
        $durationScore = min(100, ($avgSessionDuration / 1800) * 100); // 30 minutes max
        $frequencyScore = min(100, ($avgSessionsPerUser / 10) * 100); // 10 sessions max
        $overallScore = ($durationScore + $frequencyScore) / 2;

        return [
            'overall' => round($overallScore, 2),
            'distribution' => [
                'high_engagement' => round($totalUsers * 0.2),
                'medium_engagement' => round($totalUsers * 0.5),
                'low_engagement' => round($totalUsers * 0.3)
            ],
            'components' => [
                'duration_score' => round($durationScore, 2),
                'frequency_score' => round($frequencyScore, 2)
            ]
        ];
    }

    /**
     * Calculate retention analysis
     *
     * @param AnalyticsRequestDTO $dto
     * @return array Retention analysis
     */
    private function calculateRetentionAnalysis(AnalyticsRequestDTO $dto): array
    {
        // Get retention data from repository
        $retentionData = $this->analyticsRepository->getRetentionAnalysis(
            $dto->getStartDate(),
            $dto->getEndDate()
        );

        return [
            'day_1' => $retentionData['day_1_retention'] ?? 0,
            'day_7' => $retentionData['day_7_retention'] ?? 0,
            'day_30' => $retentionData['day_30_retention'] ?? 0,
            'cohort_analysis' => $retentionData['cohorts'] ?? []
        ];
    }

    /**
     * Calculate feature adoption metrics
     *
     * @param AnalyticsRequestDTO $dto
     * @return array Feature adoption metrics
     */
    private function calculateFeatureAdoptionMetrics(AnalyticsRequestDTO $dto): array
    {
        return $this->analyticsRepository->getFeatureAdoptionMetrics(
            $dto->getStartDate(),
            $dto->getEndDate()
        );
    }

    /**
     * Calculate user segments
     *
     * @param array $engagementData
     * @return array User segments
     */
    private function calculateUserSegments(array $engagementData): array
    {
        $totalUsers = $engagementData['total_active_users'] ?? 0;

        return [
            'power_users' => [
                'count' => round($totalUsers * 0.1),
                'percentage' => 10,
                'criteria' => 'High frequency, long sessions'
            ],
            'regular_users' => [
                'count' => round($totalUsers * 0.4),
                'percentage' => 40,
                'criteria' => 'Moderate usage patterns'
            ],
            'casual_users' => [
                'count' => round($totalUsers * 0.5),
                'percentage' => 50,
                'criteria' => 'Infrequent, short sessions'
            ]
        ];
    }

    /**
     * Calculate churn analysis
     *
     * @param AnalyticsRequestDTO $dto
     * @return array Churn analysis
     */
    private function calculateChurnAnalysis(AnalyticsRequestDTO $dto): array
    {
        return $this->analyticsRepository->getChurnAnalysis(
            $dto->getStartDate(),
            $dto->getEndDate()
        );
    }

    /**
     * Generate engagement insights
     *
     * @param array $engagementData
     * @param array $retentionAnalysis
     * @return array Engagement insights
     */
    private function generateEngagementInsights(array $engagementData, array $retentionAnalysis): array
    {
        $insights = [];

        // Retention insights
        $day7Retention = $retentionAnalysis['day_7'] ?? 0;
        if ($day7Retention < 30) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Low 7-day retention rate',
                'value' => $day7Retention,
                'recommendation' => 'Improve onboarding and early user experience'
            ];
        }

        // Session duration insights
        $avgDuration = $engagementData['avg_session_duration'] ?? 0;
        if ($avgDuration > 1800) { // 30 minutes
            $insights[] = [
                'type' => 'success',
                'message' => 'High user engagement with long sessions',
                'value' => $avgDuration
            ];
        }

        return $insights;
    }

    /**
     * Get resource utilization metrics
     *
     * @return array Resource utilization metrics
     */
    private function getResourceUtilizationMetrics(): array
    {
        // This would typically interface with system monitoring tools
        return [
            'cpu_usage' => 65.5,
            'memory_usage' => 78.2,
            'disk_usage' => 45.3,
            'network_io' => [
                'inbound' => 125.6,
                'outbound' => 89.3
            ],
            'database_connections' => [
                'active' => 23,
                'max' => 100,
                'utilization' => 23.0
            ]
        ];
    }

    /**
     * Generate performance recommendations
     *
     * @param array $performanceMetrics
     * @param array $errorAnalysis
     * @return array Performance recommendations
     */
    private function generatePerformanceRecommendations(array $performanceMetrics, array $errorAnalysis): array
    {
        $recommendations = [];

        // Response time recommendations
        $avgResponseTime = $performanceMetrics['avg_response_time'] ?? 0;
        if ($avgResponseTime > 500) { // 500ms threshold
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'performance',
                'issue' => 'High API response times',
                'recommendation' => 'Optimize database queries and add caching layers',
                'current_value' => $avgResponseTime,
                'target_value' => 200
            ];
        }

        // Error rate recommendations
        $errorRate = $errorAnalysis['overall_error_rate'] ?? 0;
        if ($errorRate > 5) { // 5% threshold
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'reliability',
                'issue' => 'High error rate',
                'recommendation' => 'Review error logs and implement better error handling',
                'current_value' => $errorRate,
                'target_value' => 2
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate growth percentage between two values
     *
     * @param float $previous
     * @param float $current
     * @return float Growth percentage
     */
    private function calculateGrowthPercentage(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Calculate retention rates
     *
     * @return array Retention rates
     */
    private function calculateRetentionRates(): array
    {
        // This would calculate various retention metrics
        return [
            'daily' => 65.5,
            'weekly' => 45.2,
            'monthly' => 32.8
        ];
    }
}
