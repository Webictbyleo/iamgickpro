<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\SubscriptionPlan;
use App\Entity\PlanLimit;
use App\Entity\PlanFeature;
use App\Entity\UserSubscription;
use App\Entity\User;
use App\Repository\SubscriptionPlanRepository;
use App\Repository\UserSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing database-driven subscription plans and features
 * This service replaces the YAML-based PlanService for dynamic plan management
 */
readonly class DatabasePlanService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionPlanRepository $planRepository,
        private UserSubscriptionRepository $subscriptionRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get all available subscription plans
     */
    public function getAllPlans(): array
    {
        $plans = $this->planRepository->findActivePlans();
        
        return array_map(function (SubscriptionPlan $plan) {
            return $this->formatPlanData($plan);
        }, $plans);
    }

    /**
     * Get a specific plan by name
     */
    public function getPlan(string $planName): ?array
    {
        $plan = $this->planRepository->findOneBy(['name' => $planName, 'isActive' => true]);
        
        if (!$plan) {
            return null;
        }

        return $this->formatPlanData($plan);
    }

    /**
     * Get plan entity by name or code
     */
    public function getPlanEntity(string $planName): ?SubscriptionPlan
    {
        // Try by code first, then by name
        $plan = $this->planRepository->findByCode($planName);
        if (!$plan) {
            $plan = $this->planRepository->findByName($planName);
        }
        return $plan;
    }

    /**
     * Get plan limits for a specific plan
     */
    public function getPlanLimits(string $planName): array
    {
        $plan = $this->getPlanEntity($planName);
        
        if (!$plan) {
            return $this->getDefaultLimits();
        }

        $limits = [];
        foreach ($plan->getLimits() as $limit) {
            $limits[$limit->getType()] = $limit->getValue();
        }

        return array_merge($this->getDefaultLimits(), $limits);
    }

    /**
     * Get plan features for a specific plan
     */
    public function getPlanFeatures(string $planName): array
    {
        $plan = $this->getPlanEntity($planName);
        
        if (!$plan) {
            return $this->getDefaultFeatures();
        }

        $features = [];
        foreach ($plan->getFeatures() as $feature) {
            $features[$feature->getCode()] = $feature->isEnabled();
        }

        return array_merge($this->getDefaultFeatures(), $features);
    }

    /**
     * Get plan pricing information
     */
    public function getPlanPricing(string $planName): array
    {
        $plan = $this->getPlanEntity($planName);
        
        if (!$plan) {
            return $this->getDefaultPricing();
        }

        return [
            'monthly' => $plan->getMonthlyPrice(),
            'yearly' => $plan->getYearlyPrice(),
            'currency' => $plan->getCurrency(),
        ];
    }

    /**
     * Get plan display name
     */
    public function getPlanDisplayName(string $planName): string
    {
        $plan = $this->getPlanEntity($planName);
        
        return $plan?->getName() ?? ucfirst($planName);
    }

    /**
     * Get plan description
     */
    public function getPlanDescription(string $planName): string
    {
        $plan = $this->getPlanEntity($planName);
        
        return $plan?->getDescription() ?? 'Basic plan with essential features';
    }

    /**
     * Get the default plan name
     */
    public function getDefaultPlan(): string
    {
        $defaultPlan = $this->planRepository->findDefaultPlan();
        
        return $defaultPlan?->getCode() ?? 'free';
    }

    /**
     * Get the default subscription plan entity
     */
    public function getDefaultPlanEntity(): ?SubscriptionPlan
    {
        return $this->planRepository->findDefaultPlan();
    }

    /**
     * Check if a plan name is valid
     */
    public function isValidPlan(string $planName): bool
    {
        return $this->getPlanEntity($planName) !== null;
    }

    /**
     * Check if a plan has a specific feature
     */
    public function hasFeature(string $planName, string $featureName): bool
    {
        $features = $this->getPlanFeatures($planName);
        return $features[$featureName] ?? false;
    }

    /**
     * Get a specific limit value for a plan
     */
    public function getLimit(string $planName, string $limitName): int
    {
        $limits = $this->getPlanLimits($planName);
        return $limits[$limitName] ?? 0;
    }

    /**
     * Check if a limit is unlimited (-1 value)
     */
    public function isUnlimited(string $planName, string $limitName): bool
    {
        return $this->getLimit($planName, $limitName) === -1;
    }

    /**
     * Get user's current subscription
     */
    public function getUserSubscription(User $user): ?UserSubscription
    {
        return $this->subscriptionRepository->findActiveSubscriptionForUser($user);
    }

    /**
     * Get user's current plan name
     */
    public function getUserPlanName(User $user): string
    {
        $subscription = $this->getUserSubscription($user);
        
        if ($subscription && $subscription->isActive()) {
            return $subscription->getSubscriptionPlan()->getCode();
        }

        return $this->getDefaultPlan();
    }

    /**
     * Check if user has access to a feature
     */
    public function userHasFeature(User $user, string $featureName): bool
    {
        $planName = $this->getUserPlanName($user);
        return $this->hasFeature($planName, $featureName);
    }

    /**
     * Get user's limit for a specific constraint
     */
    public function getUserLimit(User $user, string $limitName): int
    {
        $planName = $this->getUserPlanName($user);
        return $this->getLimit($planName, $limitName);
    }

    /**
     * Check if user's limit is unlimited
     */
    public function isUserLimitUnlimited(User $user, string $limitName): bool
    {
        $planName = $this->getUserPlanName($user);
        return $this->isUnlimited($planName, $limitName);
    }

    /**
     * Assign a plan to a user (create subscription)
     */
    public function assignPlanToUser(User $user, string $planName, ?\DateTimeImmutable $expiresAt = null): UserSubscription
    {
        $plan = $this->getPlanEntity($planName);
        
        if (!$plan) {
            throw new \InvalidArgumentException("Plan '{$planName}' not found");
        }

        // Cancel any existing active subscriptions
        $this->cancelUserSubscriptions($user);

        // Create new subscription
        $subscription = new UserSubscription();
        $subscription->setUser($user);
        $subscription->setSubscriptionPlan($plan);
        $subscription->setStatus('active');
        $subscription->setStartDate(new \DateTimeImmutable());
        
        if ($expiresAt) {
            $subscription->setEndDate($expiresAt);
        }

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $this->logger->info('User subscription created', [
            'user_id' => $user->getId(),
            'plan_name' => $planName,
            'expires_at' => $expiresAt?->format('Y-m-d H:i:s')
        ]);

        return $subscription;
    }

    /**
     * Cancel all active subscriptions for a user
     */
    public function cancelUserSubscriptions(User $user): void
    {
        $activeSubscriptions = $this->subscriptionRepository->findActiveSubscriptionsForUser($user);
        
        foreach ($activeSubscriptions as $subscription) {
            $subscription->setStatus('cancelled');
            $subscription->setEndDate(new \DateTimeImmutable());
        }

        if (!empty($activeSubscriptions)) {
            $this->entityManager->flush();
            
            $this->logger->info('User subscriptions cancelled', [
                'user_id' => $user->getId(),
                'cancelled_count' => count($activeSubscriptions)
            ]);
        }
    }

    /**
     * Format plan data for API responses
     */
    private function formatPlanData(SubscriptionPlan $plan): array
    {
        $limits = [];
        foreach ($plan->getLimits() as $limit) {
            $limits[$limit->getType()] = $limit->getValue();
        }

        $features = [];
        foreach ($plan->getFeatures() as $feature) {
            $features[$feature->getCode()] = $feature->isEnabled();
        }

        return [
            'code' => $plan->getCode(),
            'name' => $plan->getName(),
            'display_name' => $plan->getName(),
            'description' => $plan->getDescription(),
            'pricing' => [
                'monthly' => $plan->getMonthlyPrice(),
                'yearly' => $plan->getYearlyPrice(),
                'currency' => $plan->getCurrency(),
            ],
            'limits' => array_merge($this->getDefaultLimits(), $limits),
            'features' => array_merge($this->getDefaultFeatures(), $features),
            'is_default' => $plan->isDefault(),
            'sort_order' => $plan->getSortOrder(),
        ];
    }

    /**
     * Get default limits (fallback values)
     */
    private function getDefaultLimits(): array
    {
        return [
            'projects' => 5,
            'storage' => 104857600, // 100MB
            'exports' => 10,
            'collaborators' => 1,
            'templates' => 10,
        ];
    }

    /**
     * Get default features (fallback values)
     */
    private function getDefaultFeatures(): array
    {
        return [
            'basic_templates' => true,
            'basic_export' => true,
            'cloud_storage' => true,
            'premium_templates' => false,
            'advanced_export' => false,
            'collaboration' => false,
            'api_access' => false,
            'priority_support' => false,
            'custom_branding' => false,
            'team_management' => false,
        ];
    }

    /**
     * Get default pricing (fallback values)
     */
    private function getDefaultPricing(): array
    {
        return [
            'monthly' => 0,
            'yearly' => 0,
            'currency' => 'USD',
        ];
    }
}
