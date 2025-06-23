<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Yaml\Yaml;

/**
 * Service for managing subscription plans and features
 */
readonly class PlanService
{
    private array $plansConfig;

    public function __construct(
        private string $plansConfigFile
    ) {
        $this->plansConfig = Yaml::parseFile($this->plansConfigFile);
    }

    public function getAllPlans(): array
    {
        return $this->plansConfig['plans'] ?? [];
    }

    public function getPlan(string $planName): ?array
    {
        return $this->plansConfig['plans'][$planName] ?? null;
    }

    public function getPlanLimits(string $planName): array
    {
        $plan = $this->getPlan($planName);
        return $plan['limits'] ?? $this->getDefaultLimits();
    }

    public function getPlanFeatures(string $planName): array
    {
        $plan = $this->getPlan($planName);
        return $plan['features'] ?? $this->getDefaultFeatures();
    }

    public function getPlanPricing(string $planName): array
    {
        $plan = $this->getPlan($planName);
        return $plan['pricing'] ?? $this->getDefaultPricing();
    }

    public function getPlanDisplayName(string $planName): string
    {
        $plan = $this->getPlan($planName);
        return $plan['name'] ?? ucfirst($planName);
    }

    public function getPlanDescription(string $planName): string
    {
        $plan = $this->getPlan($planName);
        return $plan['description'] ?? 'Basic plan with essential features';
    }

    public function getDefaultPlan(): string
    {
        return $this->plansConfig['default_plan'] ?? 'free';
    }

    public function isValidPlan(string $planName): bool
    {
        return isset($this->plansConfig['plans'][$planName]);
    }

    public function hasFeature(string $planName, string $featureName): bool
    {
        $features = $this->getPlanFeatures($planName);
        return $features[$featureName] ?? false;
    }

    public function getLimit(string $planName, string $limitName): int
    {
        $limits = $this->getPlanLimits($planName);
        return $limits[$limitName] ?? 0;
    }

    public function isUnlimited(string $planName, string $limitName): bool
    {
        return $this->getLimit($planName, $limitName) === -1;
    }

    public function getUpgradeRules(): array
    {
        return $this->plansConfig['upgrade_rules'] ?? [];
    }

    public function getGlobalFeatures(): array
    {
        return $this->plansConfig['global_features'] ?? [];
    }

    public function isGlobalFeatureEnabled(string $featureName): bool
    {
        $globalFeatures = $this->getGlobalFeatures();
        return $globalFeatures[$featureName] ?? false;
    }

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

    private function getDefaultPricing(): array
    {
        return [
            'monthly' => 0,
            'yearly' => 0,
            'currency' => 'USD',
        ];
    }
}
