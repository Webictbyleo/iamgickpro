<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Exception\SubscriptionLimitExceededException;
use App\Exception\FeatureNotAvailableException;
use Psr\Log\LoggerInterface;

/**
 * Service for enforcing subscription constraints (limits and features)
 * This service should be used in business logic to check subscription constraints
 */
readonly class SubscriptionConstraintService
{
    public function __construct(
        private DatabasePlanService $planService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Check if user can create a new project
     */
    public function canCreateProject(User $user): bool
    {
        $limit = $this->planService->getUserLimit($user, 'projects');
        
        // Unlimited projects
        if ($this->planService->isUserLimitUnlimited($user, 'projects')) {
            return true;
        }

        $currentCount = $user->getProjects()->count();
        return $currentCount < $limit;
    }

    /**
     * Enforce project creation constraint
     * 
     * @throws SubscriptionLimitExceededException
     */
    public function enforceProjectCreationLimit(User $user): void
    {
        if (!$this->canCreateProject($user)) {
            $limit = $this->planService->getUserLimit($user, 'projects');
            $currentCount = $user->getProjects()->count();
            
            $this->logger->warning('Project creation limit exceeded', [
                'user_id' => $user->getId(),
                'current_count' => $currentCount,
                'limit' => $limit
            ]);
            
            throw new SubscriptionLimitExceededException(
                "Project limit exceeded. Your current plan allows {$limit} projects, but you have {$currentCount}."
            );
        }
    }

    /**
     * Check if user can upload more files based on storage limit
     */
    public function canUploadFile(User $user, int $fileSizeBytes): bool
    {
        $storageLimit = $this->planService->getUserLimit($user, 'storage');
        
        // Unlimited storage
        if ($this->planService->isUserLimitUnlimited($user, 'storage')) {
            return true;
        }

        $currentUsage = $this->calculateUserStorageUsage($user);
        return ($currentUsage + $fileSizeBytes) <= $storageLimit;
    }

    /**
     * Enforce file upload constraint
     * 
     * @throws SubscriptionLimitExceededException
     */
    public function enforceFileUploadLimit(User $user, int $fileSizeBytes): void
    {
        if (!$this->canUploadFile($user, $fileSizeBytes)) {
            $storageLimit = $this->planService->getUserLimit($user, 'storage');
            $currentUsage = $this->calculateUserStorageUsage($user);
            
            $this->logger->warning('Storage limit exceeded', [
                'user_id' => $user->getId(),
                'current_usage' => $currentUsage,
                'file_size' => $fileSizeBytes,
                'limit' => $storageLimit
            ]);
            
            throw new SubscriptionLimitExceededException(
                "Storage limit exceeded. Your current plan allows " . $this->formatBytes($storageLimit) . 
                " of storage, but you're trying to upload " . $this->formatBytes($fileSizeBytes) . 
                " which would exceed your limit."
            );
        }
    }

    /**
     * Check if user can perform an export
     */
    public function canPerformExport(User $user): bool
    {
        $limit = $this->planService->getUserLimit($user, 'exports');
        
        // Unlimited exports
        if ($this->planService->isUserLimitUnlimited($user, 'exports')) {
            return true;
        }

        // For simplicity, counting exports this month
        $currentCount = $this->getMonthlyExportCount($user);
        return $currentCount < $limit;
    }

    /**
     * Enforce export limit constraint
     * 
     * @throws SubscriptionLimitExceededException
     */
    public function enforceExportLimit(User $user): void
    {
        if (!$this->canPerformExport($user)) {
            $limit = $this->planService->getUserLimit($user, 'exports');
            $currentCount = $this->getMonthlyExportCount($user);
            
            $this->logger->warning('Export limit exceeded', [
                'user_id' => $user->getId(),
                'monthly_exports' => $currentCount,
                'limit' => $limit
            ]);
            
            throw new SubscriptionLimitExceededException(
                "Monthly export limit exceeded. Your current plan allows {$limit} exports per month, but you have already used {$currentCount}."
            );
        }
    }

    /**
     * Check if user can add more collaborators
     */
    public function canAddCollaborator(User $user): bool
    {
        $limit = $this->planService->getUserLimit($user, 'collaborators');
        
        // Unlimited collaborators
        if ($this->planService->isUserLimitUnlimited($user, 'collaborators')) {
            return true;
        }

        // Count current collaborators across all projects
        $currentCount = $this->getUserCollaboratorCount($user);
        return $currentCount < $limit;
    }

    /**
     * Enforce collaborator limit constraint
     * 
     * @throws SubscriptionLimitExceededException
     */
    public function enforceCollaboratorLimit(User $user): void
    {
        if (!$this->canAddCollaborator($user)) {
            $limit = $this->planService->getUserLimit($user, 'collaborators');
            $currentCount = $this->getUserCollaboratorCount($user);
            
            $this->logger->warning('Collaborator limit exceeded', [
                'user_id' => $user->getId(),
                'current_collaborators' => $currentCount,
                'limit' => $limit
            ]);
            
            throw new SubscriptionLimitExceededException(
                "Collaborator limit exceeded. Your current plan allows {$limit} collaborators, but you already have {$currentCount}."
            );
        }
    }

    /**
     * Check if user has access to a specific feature
     * 
     * @throws FeatureNotAvailableException
     */
    public function enforceFeatureAccess(User $user, string $featureName): void
    {
        if (!$this->planService->userHasFeature($user, $featureName)) {
            $planName = $this->planService->getUserPlanName($user);
            
            $this->logger->warning('Feature access denied', [
                'user_id' => $user->getId(),
                'feature' => $featureName,
                'plan' => $planName
            ]);
            
            throw new FeatureNotAvailableException(
                "Feature '{$featureName}' is not available in your current plan ({$planName}). Please upgrade your subscription."
            );
        }
    }

    /**
     * Check if user can access premium templates
     */
    public function canAccessPremiumTemplates(User $user): bool
    {
        return $this->planService->userHasFeature($user, 'premium_templates');
    }

    /**
     * Check if user can use advanced export features
     */
    public function canUseAdvancedExport(User $user): bool
    {
        return $this->planService->userHasFeature($user, 'advanced_export');
    }

    /**
     * Check if user can use collaboration features
     */
    public function canUseCollaboration(User $user): bool
    {
        return $this->planService->userHasFeature($user, 'collaboration');
    }

    /**
     * Check if user can use API access
     */
    public function canUseApiAccess(User $user): bool
    {
        return $this->planService->userHasFeature($user, 'api_access');
    }

    /**
     * Check if user can use custom branding
     */
    public function canUseCustomBranding(User $user): bool
    {
        return $this->planService->userHasFeature($user, 'custom_branding');
    }

    /**
     * Get user's subscription limits summary
     */
    public function getUserLimitsSummary(User $user): array
    {
        $planName = $this->planService->getUserPlanName($user);
        
        return [
            'plan' => $planName,
            'limits' => [
                'projects' => [
                    'limit' => $this->planService->getUserLimit($user, 'projects'),
                    'used' => $user->getProjects()->count(),
                    'unlimited' => $this->planService->isUserLimitUnlimited($user, 'projects'),
                ],
                'storage' => [
                    'limit' => $this->planService->getUserLimit($user, 'storage'),
                    'used' => $this->calculateUserStorageUsage($user),
                    'unlimited' => $this->planService->isUserLimitUnlimited($user, 'storage'),
                ],
                'exports' => [
                    'limit' => $this->planService->getUserLimit($user, 'exports'),
                    'used' => $this->getMonthlyExportCount($user),
                    'unlimited' => $this->planService->isUserLimitUnlimited($user, 'exports'),
                ],
                'collaborators' => [
                    'limit' => $this->planService->getUserLimit($user, 'collaborators'),
                    'used' => $this->getUserCollaboratorCount($user),
                    'unlimited' => $this->planService->isUserLimitUnlimited($user, 'collaborators'),
                ],
            ],
            'features' => [
                'premium_templates' => $this->canAccessPremiumTemplates($user),
                'advanced_export' => $this->canUseAdvancedExport($user),
                'collaboration' => $this->canUseCollaboration($user),
                'api_access' => $this->canUseApiAccess($user),
                'custom_branding' => $this->canUseCustomBranding($user),
            ],
        ];
    }

    /**
     * Calculate user's total storage usage in bytes
     */
    private function calculateUserStorageUsage(User $user): int
    {
        $totalSize = 0;
        
        foreach ($user->getMediaFiles() as $mediaFile) {
            $totalSize += $mediaFile->getSize() ?? 0;
        }
        
        return $totalSize;
    }

    /**
     * Get user's export count for current month
     */
    private function getMonthlyExportCount(User $user): int
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth = new \DateTimeImmutable('last day of this month 23:59:59');
        
        $count = 0;
        foreach ($user->getExportJobs() as $exportJob) {
            $createdAt = $exportJob->getCreatedAt();
            if ($createdAt >= $startOfMonth && $createdAt <= $endOfMonth) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Count user's total collaborators across all projects
     */
    private function getUserCollaboratorCount(User $user): int
    {
        $collaboratorCount = 0;
        
        foreach ($user->getProjects() as $project) {
            // Assuming project has collaborators relationship
            if (method_exists($project, 'getCollaborators')) {
                $collaboratorCount += $project->getCollaborators()->count();
            }
        }
        
        return $collaboratorCount;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
