<?php

declare(strict_types=1);

/**
 * Add missing methods to ExportJobRepository
 */

echo "🔧 ADDING MISSING REPOSITORY METHODS\n";
echo "====================================\n\n";

$filePath = '/var/www/html/iamgickpro/backend/src/Repository/ExportJobRepository.php';

// Read the current file content
$content = file_get_contents($filePath);

// Check if the method already exists
if (strpos($content, 'getExportStatsForUser') !== false) {
    echo "✅ getExportStatsForUser method already exists\n";
} else {
    echo "❌ getExportStatsForUser method missing, adding...\n";
    
    // Find the last method before the closing brace
    $insertPosition = strrpos($content, '    }' . "\n" . '}');
    if ($insertPosition === false) {
        echo "❌ Could not find insertion point\n";
        exit(1);
    }
    
    $newMethod = '
    /**
     * Get export statistics for a user with date range
     * 
     * Provides export statistics for analytics including timeline data,
     * success rates, and format breakdown within a specific date range.
     *
     * @param User $user The user to generate statistics for
     * @param \DateTimeInterface|null $startDate Start date for filtering (optional)
     * @param \DateTimeInterface|null $endDate End date for filtering (optional)
     * @return array Export statistics with timeline and breakdown data
     */
    public function getExportStatsForUser(User $user, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $userId = $user->getId();
        
        $params = [$userId];
        $whereClause = "WHERE user_id = ?";
        
        if ($startDate) {
            $whereClause .= " AND created_at >= ?";
            $params[] = $startDate->format(\'Y-m-d H:i:s\');
        }
        
        if ($endDate) {
            $whereClause .= " AND created_at <= ?";
            $params[] = $endDate->format(\'Y-m-d H:i:s\');
        }

        // Get overall statistics
        $overallStats = $conn->executeQuery(
            "SELECT 
                COUNT(*) as total_exports,
                COUNT(CASE WHEN status = \'completed\' THEN 1 END) as successful_exports,
                COUNT(CASE WHEN status = \'failed\' THEN 1 END) as failed_exports
            FROM export_jobs 
            $whereClause",
            $params
        )->fetchAssociative();

        // Get timeline data
        $timelineParams = [$userId];
        $timelineWhere = "WHERE user_id = ?";
        
        if (!$startDate && !$endDate) {
            $timelineWhere .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        } elseif ($startDate) {
            $timelineWhere .= " AND created_at >= ?";
            $timelineParams[] = $startDate->format(\'Y-m-d H:i:s\');
            if ($endDate) {
                $timelineWhere .= " AND created_at <= ?";
                $timelineParams[] = $endDate->format(\'Y-m-d H:i:s\');
            }
        }

        $timeline = $conn->executeQuery(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                COUNT(CASE WHEN status = \'completed\' THEN 1 END) as completed,
                COUNT(CASE WHEN status = \'failed\' THEN 1 END) as failed
            FROM export_jobs 
            $timelineWhere
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $timelineParams
        )->fetchAllAssociative();

        return [
            \'total_exports\' => (int) $overallStats[\'total_exports\'],
            \'successful_exports\' => (int) $overallStats[\'successful_exports\'],
            \'failed_exports\' => (int) $overallStats[\'failed_exports\'],
            \'timeline\' => array_map(function($row) {
                return [
                    \'date\' => $row[\'date\'],
                    \'count\' => (int) $row[\'count\'],
                    \'completed\' => (int) $row[\'completed\'],
                    \'failed\' => (int) $row[\'failed\']
                ];
            }, $timeline)
        ];
    }

    /**
     * Get format breakdown statistics for a user
     * 
     * Returns the distribution of export formats used by a specific user.
     * Used for analytics and usage pattern analysis.
     *
     * @param User $user The user to analyze
     * @return array Format breakdown with counts for each format
     */
    public function getFormatBreakdownForUser(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $formatStats = $conn->executeQuery(
            "SELECT 
                format,
                COUNT(*) as count,
                COUNT(CASE WHEN status = \'completed\' THEN 1 END) as completed
            FROM export_jobs 
            WHERE user_id = ?
            GROUP BY format
            ORDER BY count DESC",
            [$user->getId()]
        )->fetchAllAssociative();

        $breakdown = [];
        foreach ($formatStats as $stat) {
            $breakdown[$stat[\'format\']] = [
                \'total\' => (int) $stat[\'count\'],
                \'completed\' => (int) $stat[\'completed\']
            ];
        }

        return $breakdown;
    }

    ';
    
    // Insert the new methods before the last closing brace
    $newContent = substr($content, 0, $insertPosition) . $newMethod . substr($content, $insertPosition);
    
    // Write the updated content
    if (file_put_contents($filePath, $newContent)) {
        echo "✅ Added missing methods to ExportJobRepository\n";
    } else {
        echo "❌ Failed to write updated file\n";
        exit(1);
    }
}

echo "\n🏁 Repository enhancement completed\n";
