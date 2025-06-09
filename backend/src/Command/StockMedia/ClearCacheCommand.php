<?php

declare(strict_types=1);

namespace App\Command\StockMedia;

use App\Service\StockMedia\StockMediaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'stock-media:cache:clear',
    description: 'Clear stock media cache'
)]
class ClearCacheCommand extends Command
{
    public function __construct(
        private readonly StockMediaService $stockMediaService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clear stock media cache')
            ->addOption(
                'provider',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Clear cache for specific provider only (unsplash, pexels, iconfinder)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force clear without confirmation'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        if (!$this->stockMediaService->isCacheEnabled()) {
            $io->warning('Stock media caching is not enabled. No cache to clear.');
            return Command::SUCCESS;
        }

        $provider = $input->getOption('provider');
        $force = $input->getOption('force');

        if ($provider) {
            $availableProviders = array_keys($this->stockMediaService->getAvailableProviders());
            if (!in_array($provider, $availableProviders)) {
                $io->error(sprintf(
                    'Provider "%s" not found. Available providers: %s',
                    $provider,
                    implode(', ', $availableProviders)
                ));
                return Command::FAILURE;
            }
        }

        // Show cache metrics before clearing
        $metrics = $this->stockMediaService->getCacheMetrics();
        if (isset($metrics['cache_enabled']) && $metrics['cache_enabled']) {
            $io->section('Current Cache Metrics');
            foreach ($metrics as $key => $value) {
                if (is_array($value)) {
                    $io->writeln(sprintf('%s:', ucfirst(str_replace('_', ' ', $key))));
                    foreach ($value as $subKey => $subValue) {
                        $io->writeln(sprintf('  %s: %s', $subKey, $subValue));
                    }
                } else {
                    $io->writeln(sprintf('%s: %s', ucfirst(str_replace('_', ' ', $key)), $value));
                }
            }
        }

        // Confirmation
        if (!$force) {
            $message = $provider 
                ? sprintf('Are you sure you want to clear cache for provider "%s"?', $provider)
                : 'Are you sure you want to clear ALL stock media cache?';
                
            if (!$io->confirm($message, false)) {
                $io->info('Cache clearing cancelled.');
                return Command::SUCCESS;
            }
        }

        // Clear cache
        try {
            if ($provider) {
                $this->stockMediaService->invalidateProviderCache($provider);
                $io->success(sprintf('Cache cleared for provider "%s".', $provider));
            } else {
                $this->stockMediaService->invalidateAllCache();
                $io->success('All stock media cache cleared.');
            }
        } catch (\Exception $e) {
            $io->error(sprintf('Failed to clear cache: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
