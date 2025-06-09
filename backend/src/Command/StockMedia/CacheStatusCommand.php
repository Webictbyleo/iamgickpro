<?php

declare(strict_types=1);

namespace App\Command\StockMedia;

use App\Service\StockMedia\StockMediaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'stock-media:cache:status',
    description: 'Show stock media cache status and metrics'
)]
class CacheStatusCommand extends Command
{
    public function __construct(
        private readonly StockMediaService $stockMediaService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Stock Media Cache Status');

        // Check if caching is enabled
        if (!$this->stockMediaService->isCacheEnabled()) {
            $io->warning('Stock media caching is NOT enabled.');
            $io->info('To enable caching, make sure the StockMediaCacheService is properly configured.');
            return Command::SUCCESS;
        }

        $io->success('Stock media caching is ENABLED.');

        // Get cache metrics
        try {
            $metrics = $this->stockMediaService->getCacheMetrics();
            
            $io->section('Cache Configuration');
            $io->definitionList(
                ['Cache Enabled' => $metrics['cache_enabled'] ? 'Yes' : 'No'],
                ['Total Items' => $metrics['total_items'] ?? 'N/A'],
                ['Memory Usage' => $metrics['memory_usage'] ?? 'N/A'],
                ['Hit Rate' => $metrics['hit_rate'] ?? 'N/A']
            );

            if (isset($metrics['ttl_settings']) && is_array($metrics['ttl_settings'])) {
                $io->section('Cache TTL Settings');
                $ttlData = [];
                foreach ($metrics['ttl_settings'] as $type => $ttl) {
                    $ttlData[] = [
                        ucfirst(str_replace('_', ' ', $type)),
                        $this->formatTtl($ttl)
                    ];
                }
                $io->table(['Cache Type', 'TTL'], $ttlData);
            }

            // Show available providers
            $providers = $this->stockMediaService->getAvailableProviders();
            if (!empty($providers)) {
                $io->section('Available Providers');
                $providerData = [];
                foreach ($providers as $name => $provider) {
                    $providerData[] = [
                        $name,
                        $provider->getName(),
                        implode(', ', $provider->getSupportedTypes())
                    ];
                }
                $io->table(['Key', 'Name', 'Supported Types'], $providerData);
            }

            // Show supported types
            $supportedTypes = $this->stockMediaService->getSupportedTypes();
            if (!empty($supportedTypes)) {
                $io->section('Supported Media Types');
                $io->listing($supportedTypes);
            }

        } catch (\Exception $e) {
            $io->error(sprintf('Failed to retrieve cache metrics: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function formatTtl(int $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%d seconds', $seconds);
        } elseif ($seconds < 3600) {
            return sprintf('%d minutes', $seconds / 60);
        } elseif ($seconds < 86400) {
            return sprintf('%d hours', $seconds / 3600);
        } else {
            return sprintf('%d days', $seconds / 86400);
        }
    }
}
