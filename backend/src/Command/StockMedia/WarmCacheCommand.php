<?php

declare(strict_types=1);

namespace App\Command\StockMedia;

use App\Service\StockMedia\StockMediaCacheService;
use App\Service\StockMedia\StockMediaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'stock-media:cache:warm',
    description: 'Warm up the stock media cache with popular searches'
)]
class WarmCacheCommand extends Command
{
    public function __construct(
        private readonly StockMediaService $stockMediaService,
        private readonly StockMediaCacheService $cacheService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Warm up the stock media cache with popular searches')
            ->addOption(
                'searches',
                's',
                InputOption::VALUE_OPTIONAL,
                'JSON file with popular searches or comma-separated list',
                'business,technology,nature,people,design,abstract,office,travel,food,music'
            )
            ->addOption(
                'types',
                't',
                InputOption::VALUE_OPTIONAL,
                'Media types to warm up (comma-separated)',
                'image,icon'
            )
            ->addOption(
                'pages',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Number of pages to cache per search',
                '2'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        if (!$this->stockMediaService->isCacheEnabled()) {
            $io->warning('Stock media caching is not enabled. No cache to warm up.');
            return Command::SUCCESS;
        }

        $searchesInput = $input->getOption('searches');
        $typesInput = $input->getOption('types');
        $pages = (int) $input->getOption('pages');

        // Parse searches
        $searches = $this->parseSearches($searchesInput);
        $types = array_map('trim', explode(',', $typesInput));

        $io->title('Stock Media Cache Warm-up');
        $io->info(sprintf('Warming cache for %d searches across %d media types', count($searches), count($types)));

        $totalOperations = count($searches) * count($types) * $pages;
        $progressBar = $io->createProgressBar($totalOperations);
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($searches as $query) {
            foreach ($types as $type) {
                for ($page = 1; $page <= $pages; $page++) {
                    try {
                        $this->stockMediaService->search($query, $type, $page, 20);
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $io->writeln(sprintf(
                            "\n<error>Failed to cache: %s (%s, page %d): %s</error>",
                            $query,
                            $type,
                            $page,
                            $e->getMessage()
                        ));
                    }
                    
                    $progressBar->advance();
                }
            }
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf(
            'Cache warm-up completed! %d successful operations, %d errors.',
            $successCount,
            $errorCount
        ));

        // Show cache metrics
        $metrics = $this->stockMediaService->getCacheMetrics();
        if (isset($metrics['cache_enabled']) && $metrics['cache_enabled']) {
            $io->section('Cache Metrics');
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

        return Command::SUCCESS;
    }

    private function parseSearches(string $input): array
    {
        // If it looks like a JSON file path
        if (str_contains($input, '.json') && file_exists($input)) {
            $content = file_get_contents($input);
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }

        // Try to parse as JSON string
        $json = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }

        // Parse as comma-separated values
        return array_map('trim', explode(',', $input));
    }
}
