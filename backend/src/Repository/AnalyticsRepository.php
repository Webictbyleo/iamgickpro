<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Analytics Repository
 * 
 * This repository provides comprehensive analytics queries for the design platform.
 * It aggregates data from multiple entities to provide insights into user behavior,
 * platform performance, content usage, and system metrics.
 * 
 * Key functionalities:
 * - Dashboard statistics and KPIs
 * - User behavior analytics and engagement metrics
 * - Design performance tracking (views, exports, shares)
 * - Template usage analytics and popularity rankings
 * - Export job analytics and processing statistics
 * - System performance monitoring and health metrics
 * - Time-series data for trend analysis
 * - Platform-wide statistics for admin reporting
 * 
 * The repository uses optimized native SQL queries for complex aggregations
 * and time-series data to ensure fast response times for analytics dashboards.
 */
class AnalyticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Get comprehensive dashboard statistics for a user
     * 
     * Provides key metrics for the user dashboard including:
     * - Total designs, projects, templates used
     * - Storage usage and export statistics
     * - Monthly growth trends
     * - Recent activity summary
     *
     * @param User $user The user to generate dashboard stats for
     * @return array Dashboard statistics with KPIs and trends
     */
    public function getDashboardStats(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $userId = $user->getId();

        // Main dashboard statistics
        $mainStatsSQL = "
            SELECT 
                (SELECT COUNT(*) FROM designs d 
                 INNER JOIN projects p ON d.project_id = p.id 
                 WHERE p.user_id = :user_id AND d.deleted_at IS NULL) as total_designs,
                
                (SELECT COUNT(*) FROM projects p 
                 WHERE p.user_id = :user_id AND p.deleted_at IS NULL) as total_projects,
                 
                (SELECT COUNT(*) FROM export_jobs ej 
                 WHERE ej.user_id = :user_id) as total_exports,
                 
                (SELECT COUNT(*) FROM export_jobs ej 
                 WHERE ej.user_id = :user_id AND ej.status = 'completed') as completed_exports,
                 
                (SELECT SUM(COALESCE(m.size, 0)) FROM media m 
                 WHERE m.user_id = :user_id AND m.deleted_at IS NULL) as storage_used,
                 
                (SELECT COUNT(*) FROM designs d 
                 INNER JOIN projects p ON d.project_id = p.id 
                 WHERE p.user_id = :user_id AND d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 AND d.deleted_at IS NULL) as designs_this_month,
                 
                (SELECT COUNT(*) FROM export_jobs ej 
                 WHERE ej.user_id = :user_id AND ej.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as exports_this_month
        ";

        $result = $conn->executeQuery($mainStatsSQL, ['user_id' => $userId])->fetchAssociative();

        // Recent activity (last 7 days)
        $activitySQL = "
            SELECT 
                DATE(activity_date) as date,
                designs_created,
                exports_completed,
                projects_created
            FROM (
                SELECT 
                    d.created_at as activity_date,
                    COUNT(d.id) as designs_created,
                    0 as exports_completed,
                    0 as projects_created
                FROM designs d
                INNER JOIN projects p ON d.project_id = p.id
                WHERE p.user_id = :user_id 
                AND d.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND d.deleted_at IS NULL
                GROUP BY DATE(d.created_at)
                
                UNION ALL
                
                SELECT 
                    ej.completed_at as activity_date,
                    0 as designs_created,
                    COUNT(ej.id) as exports_completed,
                    0 as projects_created
                FROM export_jobs ej
                WHERE ej.user_id = :user_id 
                AND ej.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND ej.status = 'completed'
                GROUP BY DATE(ej.completed_at)
                
                UNION ALL
                
                SELECT 
                    p.created_at as activity_date,
                    0 as designs_created,
                    0 as exports_completed,
                    COUNT(p.id) as projects_created
                FROM projects p
                WHERE p.user_id = :user_id 
                AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND p.deleted_at IS NULL
                GROUP BY DATE(p.created_at)
            ) activity
            ORDER BY date DESC
        ";

        $activityData = $conn->executeQuery($activitySQL, ['user_id' => $userId])->fetchAllAssociative();

        return [
            'overview' => [
                'total_designs' => (int) $result['total_designs'],
                'total_projects' => (int) $result['total_projects'],
                'total_exports' => (int) $result['total_exports'],
                'completed_exports' => (int) $result['completed_exports'],
                'storage_used' => (int) ($result['storage_used'] ?? 0),
                'success_rate' => $result['total_exports'] > 0 
                    ? round(($result['completed_exports'] / $result['total_exports']) * 100, 1)
                    : 0
            ],
            'monthly_growth' => [
                'designs' => (int) $result['designs_this_month'],
                'exports' => (int) $result['exports_this_month']
            ],
            'recent_activity' => $activityData
        ];
    }

    /**
     * Get analytics for a specific design
     * 
     * Provides detailed performance metrics for an individual design including:
     * - View counts and engagement metrics
     * - Export statistics by format
     * - Usage timeline and patterns
     * - Performance comparisons
     *
     * @param string $designId The design ID to analyze
     * @param User $user The owner user for access control
     * @return array Design analytics with performance metrics
     */
    public function getDesignAnalytics(string $designId, User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Design basic metrics
        $metricsSQL = "
            SELECT 
                d.id,
                d.name,
                d.created_at,
                d.updated_at,
                COALESCE(d.view_count, 0) as views,
                COALESCE(d.share_count, 0) as shares,
                (SELECT COUNT(*) FROM export_jobs ej WHERE ej.design_id = d.id) as total_exports,
                (SELECT COUNT(*) FROM export_jobs ej WHERE ej.design_id = d.id AND ej.status = 'completed') as completed_exports,
                TIMESTAMPDIFF(HOUR, d.created_at, d.updated_at) as total_edit_time
            FROM designs d
            INNER JOIN projects p ON d.project_id = p.id
            WHERE d.id = :design_id AND p.user_id = :user_id
            AND d.deleted_at IS NULL
        ";

        $metrics = $conn->executeQuery($metricsSQL, [
            'design_id' => $designId,
            'user_id' => $user->getId()
        ])->fetchAssociative();

        if (!$metrics) {
            return [];
        }

        // Export breakdown by format
        $exportSQL = "
            SELECT 
                format,
                COUNT(*) as count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed
            FROM export_jobs 
            WHERE design_id = :design_id
            GROUP BY format
            ORDER BY count DESC
        ";

        $exports = $conn->executeQuery($exportSQL, ['design_id' => $designId])->fetchAllAssociative();

        // Activity timeline (last 30 days)
        $timelineSQL = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as exports
            FROM export_jobs 
            WHERE design_id = :design_id
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";

        $timeline = $conn->executeQuery($timelineSQL, ['design_id' => $designId])->fetchAllAssociative();

        return [
            'metrics' => [
                'views' => (int) $metrics['views'],
                'shares' => (int) $metrics['shares'],
                'total_exports' => (int) $metrics['total_exports'],
                'completed_exports' => (int) $metrics['completed_exports'],
                'total_edit_time' => (int) $metrics['total_edit_time'],
                'success_rate' => $metrics['total_exports'] > 0 
                    ? round(($metrics['completed_exports'] / $metrics['total_exports']) * 100, 1)
                    : 0,
                'created_at' => $metrics['created_at'],
                'last_modified' => $metrics['updated_at']
            ],
            'exports' => $exports,
            'timeline' => $timeline
        ];
    }

    /**
     * Get system-wide analytics (Admin only)
     * 
     * Provides comprehensive platform statistics including:
     * - User growth and engagement metrics
     * - Content creation and usage patterns
     * - System performance and health indicators
     * - Platform-wide trends and insights
     *
     * @return array System analytics with platform-wide metrics
     */
    public function getSystemAnalytics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Platform overview statistics
        $overviewSQL = "
            SELECT 
                (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) as total_users,
                (SELECT COUNT(*) FROM users WHERE is_verified = 1 AND deleted_at IS NULL) as verified_users,
                (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND deleted_at IS NULL) as new_users_month,
                (SELECT COUNT(*) FROM users WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND deleted_at IS NULL) as active_users_week,
                (SELECT COUNT(*) FROM designs d INNER JOIN projects p ON d.project_id = p.id WHERE d.deleted_at IS NULL) as total_designs,
                (SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL) as total_projects,
                (SELECT COUNT(*) FROM templates WHERE deleted_at IS NULL) as total_templates,
                (SELECT COUNT(*) FROM export_jobs) as total_exports,
                (SELECT COUNT(*) FROM export_jobs WHERE status = 'completed') as completed_exports,
                (SELECT SUM(COALESCE(size, 0)) FROM media WHERE deleted_at IS NULL) as total_storage_used
        ";

        $overview = $conn->executeQuery($overviewSQL)->fetchAssociative();

        // User growth over last 12 months
        $growthSQL = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_users
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            AND deleted_at IS NULL
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ";

        $userGrowth = $conn->executeQuery($growthSQL)->fetchAllAssociative();

        // Content creation trends
        $contentSQL = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                'designs' as type
            FROM designs d
            INNER JOIN projects p ON d.project_id = p.id
            WHERE d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND d.deleted_at IS NULL
            GROUP BY DATE(d.created_at)
            
            UNION ALL
            
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                'projects' as type
            FROM projects
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND deleted_at IS NULL
            GROUP BY DATE(created_at)
            
            ORDER BY date DESC
        ";

        $contentTrends = $conn->executeQuery($contentSQL)->fetchAllAssociative();

        // Popular template categories
        $categoriesSQL = "
            SELECT 
                category,
                COUNT(*) as template_count,
                SUM(COALESCE(usage_count, 0)) as total_usage
            FROM templates 
            WHERE deleted_at IS NULL
            GROUP BY category
            ORDER BY total_usage DESC
            LIMIT 10
        ";

        $popularCategories = $conn->executeQuery($categoriesSQL)->fetchAllAssociative();

        return [
            'platform_stats' => [
                'total_users' => (int) $overview['total_users'],
                'verified_users' => (int) $overview['verified_users'],
                'new_users_month' => (int) $overview['new_users_month'],
                'active_users_week' => (int) $overview['active_users_week'],
                'total_designs' => (int) $overview['total_designs'],
                'total_projects' => (int) $overview['total_projects'],
                'total_templates' => (int) $overview['total_templates'],
                'total_exports' => (int) $overview['total_exports'],
                'completed_exports' => (int) $overview['completed_exports'],
                'total_storage_used' => (int) $overview['total_storage_used'],
                'export_success_rate' => $overview['total_exports'] > 0 
                    ? round(($overview['completed_exports'] / $overview['total_exports']) * 100, 1)
                    : 0
            ],
            'user_growth' => $userGrowth,
            'content_trends' => $contentTrends,
            'popular_categories' => $popularCategories
        ];
    }

    /**
     * Get template usage analytics
     * 
     * Provides insights into template performance including:
     * - Most popular templates by usage count
     * - Category performance analysis
     * - Template conversion rates
     * - Trending templates over time
     *
     * @param int $limit Maximum number of results to return
     * @return array Template usage analytics
     */
    public function getTemplateAnalytics(int $limit = 50): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Most popular templates
        $popularSQL = "
            SELECT 
                t.id,
                t.uuid,
                t.name,
                t.category,
                COALESCE(t.usage_count, 0) as usage_count,
                t.created_at
            FROM templates t
            WHERE t.is_public = 1 AND t.deleted_at IS NULL
            ORDER BY t.usage_count DESC
            LIMIT :limit
        ";

        $popularTemplates = $conn->executeQuery($popularSQL, ['limit' => $limit])->fetchAllAssociative();

        // Category breakdown
        $categorySQL = "
            SELECT 
                category,
                COUNT(*) as template_count,
                SUM(COALESCE(usage_count, 0)) as total_usage,
                AVG(COALESCE(usage_count, 0)) as avg_usage
            FROM templates 
            WHERE is_public = 1 AND deleted_at IS NULL
            GROUP BY category
            ORDER BY total_usage DESC
        ";

        $categoryBreakdown = $conn->executeQuery($categorySQL)->fetchAllAssociative();

        return [
            'popular_templates' => $popularTemplates,
            'category_breakdown' => $categoryBreakdown
        ];
    }

    /**
     * Get user behavior analytics
     * 
     * Analyzes user behavior patterns including:
     * - Login frequency and patterns
     * - Feature usage statistics
     * - User engagement metrics
     * - Session duration analysis
     *
     * @param User $user The user to analyze
     * @return array User behavior analytics
     */
    public function getUserBehaviorAnalytics(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $userId = $user->getId();

        // User activity patterns
        $activitySQL = "
            SELECT 
                DAYNAME(last_login_at) as day_of_week,
                HOUR(last_login_at) as hour_of_day,
                COUNT(*) as login_count
            FROM users 
            WHERE id = :user_id AND last_login_at IS NOT NULL
            GROUP BY DAYNAME(last_login_at), HOUR(last_login_at)
            ORDER BY login_count DESC
        ";

        $activityPatterns = $conn->executeQuery($activitySQL, ['user_id' => $userId])->fetchAllAssociative();

        // Feature usage statistics
        $featureUsageSQL = "
            SELECT 
                'designs' as feature,
                COUNT(*) as usage_count,
                MAX(d.created_at) as last_used
            FROM designs d
            INNER JOIN projects p ON d.project_id = p.id
            WHERE p.user_id = :user_id AND d.deleted_at IS NULL
            
            UNION ALL
            
            SELECT 
                'exports' as feature,
                COUNT(*) as usage_count,
                MAX(created_at) as last_used
            FROM export_jobs
            WHERE user_id = :user_id
            
            UNION ALL
            
            SELECT 
                'projects' as feature,
                COUNT(*) as usage_count,
                MAX(created_at) as last_used
            FROM projects
            WHERE user_id = :user_id AND deleted_at IS NULL
        ";

        $featureUsage = $conn->executeQuery($featureUsageSQL, ['user_id' => $userId])->fetchAllAssociative();

        return [
            'activity_patterns' => $activityPatterns,
            'feature_usage' => $featureUsage
        ];
    }
}
