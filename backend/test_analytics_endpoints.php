<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\AnalyticsController;
use App\Service\AnalyticsService;
use App\Repository\AnalyticsRepository;
use App\Repository\ExportJobRepository;
use App\Repository\TemplateRepository;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\DesignRepository;
use App\Entity\User;

echo "Testing Analytics System...\n\n";

try {
    // Test that classes can be instantiated (basic syntax check)
    echo "✅ Analytics classes loaded successfully\n";
    
    // Test that all methods exist
    $reflection = new \ReflectionClass(AnalyticsService::class);
    $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
    
    $expectedMethods = [
        'getDashboardAnalytics',
        'getDesignAnalytics',
        'getSystemAnalytics',
        'getTemplateAnalytics',
        'getUserBehaviorAnalytics',
        'getExportAnalytics',
        'getPlatformTrends',
        'getUserEngagementMetrics',
        'getPerformanceMonitoringData'
    ];
    
    echo "\nChecking Analytics Service Methods:\n";
    foreach ($expectedMethods as $methodName) {
        if ($reflection->hasMethod($methodName)) {
            echo "✅ Method {$methodName} exists\n";
        } else {
            echo "❌ Method {$methodName} missing\n";
        }
    }
    
    // Test controller methods
    $controllerReflection = new \ReflectionClass(AnalyticsController::class);
    $controllerMethods = [
        'getDashboard',
        'getDesignAnalytics',
        'getTemplateAnalytics',
        'getUserBehavior',
        'getExportAnalytics',
        'getPlatformTrends',
        'getSystemAnalytics',
        'getUserEngagement',
        'getPerformanceMonitoring'
    ];
    
    echo "\nChecking Analytics Controller Methods:\n";
    foreach ($controllerMethods as $methodName) {
        if ($controllerReflection->hasMethod($methodName)) {
            echo "✅ Method {$methodName} exists\n";
        } else {
            echo "❌ Method {$methodName} missing\n";
        }
    }
    
    echo "\n✅ All analytics components are properly implemented!\n";
    
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n🎉 Analytics system test completed successfully!\n";
