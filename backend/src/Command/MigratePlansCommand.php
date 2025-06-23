<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\SubscriptionPlan;
use App\Entity\PlanLimit;
use App\Entity\PlanFeature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'app:migrate-plans-to-database',
    description: 'Migrate subscription plans from YAML configuration to database',
)]
class MigratePlansCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $plansConfigFile
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Check if plans already exist in database
            $existingPlansCount = $this->entityManager->getRepository(SubscriptionPlan::class)->count([]);
            
            if ($existingPlansCount > 0) {
                $io->warning("Found {$existingPlansCount} existing plans in database.");
                if (!$io->confirm('Do you want to continue? This will clear existing plans.', false)) {
                    return Command::SUCCESS;
                }
                
                // Clear existing plans
                $this->clearExistingPlans($io);
            }

            // Load YAML configuration
            if (!file_exists($this->plansConfigFile)) {
                $io->error("Plans configuration file not found: {$this->plansConfigFile}");
                return Command::FAILURE;
            }

            $config = Yaml::parseFile($this->plansConfigFile);

            if (!isset($config['plans'])) {
                $io->error('No plans found in configuration file');
                return Command::FAILURE;
            }

            $io->info('Migrating plans from YAML to database...');

            $sortOrder = 0;
            foreach ($config['plans'] as $planCode => $planData) {
                $this->createPlanFromConfig($planCode, $planData, $sortOrder++, $config['default_plan'] ?? 'free');
                $io->writeln("✓ Created plan: {$planCode}");
            }

            $this->entityManager->flush();

            $totalPlans = count($config['plans']);
            $io->success("Successfully migrated {$totalPlans} subscription plans to database!");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function clearExistingPlans(SymfonyStyle $io): void
    {
        $io->info('Clearing existing plans...');
        
        // Clear all plan limits
        $this->entityManager->createQuery('DELETE FROM App\Entity\PlanLimit')->execute();
        
        // Clear all plan features
        $this->entityManager->createQuery('DELETE FROM App\Entity\PlanFeature')->execute();
        
        // Clear all subscription plans
        $this->entityManager->createQuery('DELETE FROM App\Entity\SubscriptionPlan')->execute();
        
        $io->writeln('✓ Cleared existing plans');
    }

    private function createPlanFromConfig(string $planCode, array $planData, int $sortOrder, string $defaultPlan): void
    {
        $plan = new SubscriptionPlan();
        $plan->setCode($planCode);
        $plan->setName($planData['name'] ?? ucfirst($planCode));
        $plan->setDescription($planData['description'] ?? '');
        $plan->setMonthlyPrice((string) ($planData['pricing']['monthly'] ?? 0));
        $plan->setYearlyPrice((string) ($planData['pricing']['yearly'] ?? 0));
        $plan->setCurrency($planData['pricing']['currency'] ?? 'USD');
        $plan->setIsActive(true);
        $plan->setIsDefault($planCode === $defaultPlan);
        $plan->setSortOrder($sortOrder);

        $this->entityManager->persist($plan);

        // Add limits
        if (isset($planData['limits'])) {
            foreach ($planData['limits'] as $limitType => $limitValue) {
                $limit = new PlanLimit();
                $limit->setPlan($plan);
                $limit->setType($limitType);
                $limit->setValue((int) $limitValue);
                
                // Add descriptions for common limit types
                $descriptions = [
                    'projects' => 'Maximum number of projects',
                    'storage' => 'Storage limit in bytes',
                    'exports' => 'Maximum exports per month',
                    'collaborators' => 'Maximum number of collaborators',
                    'templates' => 'Maximum number of templates'
                ];
                
                if (isset($descriptions[$limitType])) {
                    $limit->setDescription($descriptions[$limitType]);
                }

                $plan->addLimit($limit);
                $this->entityManager->persist($limit);
            }
        }

        // Add features
        if (isset($planData['features'])) {
            foreach ($planData['features'] as $featureCode => $isEnabled) {
                $feature = new PlanFeature();
                $feature->setPlan($plan);
                $feature->setCode($featureCode);
                $feature->setName(ucwords(str_replace('_', ' ', $featureCode)));
                $feature->setEnabled((bool) $isEnabled);
                
                // Add descriptions for common features
                $descriptions = [
                    'basic_templates' => 'Access to basic templates',
                    'premium_templates' => 'Access to premium templates',
                    'basic_export' => 'Basic export functionality',
                    'advanced_export' => 'Advanced export options',
                    'cloud_storage' => 'Cloud storage capability',
                    'collaboration' => 'Team collaboration features',
                    'api_access' => 'API access for integrations',
                    'priority_support' => 'Priority customer support',
                    'custom_branding' => 'Custom branding options',
                    'team_management' => 'Team management features'
                ];
                
                if (isset($descriptions[$featureCode])) {
                    $feature->setDescription($descriptions[$featureCode]);
                }

                $plan->addFeature($feature);
                $this->entityManager->persist($feature);
            }
        }
    }
}
