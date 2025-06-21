<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Shape\ShapeImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import SVG shapes from the design-vector-shapes repository into the database.
 */
#[AsCommand(
    name: 'app:shapes:import',
    description: 'Import SVG shapes from the design-vector-shapes repository',
    aliases: ['shapes:import']
)]
class ImportShapesCommand extends Command
{
    public function __construct(
        private readonly ShapeImportService $shapeImportService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force reimport (clear existing shapes and import fresh)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be imported without actually importing'
            )
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command imports SVG shapes from the design-vector-shapes 
repository into the database for use in the stock media system.

<info>Usage:</info>
  # Import new shapes (skip existing)
  <info>php %command.full_name%</info>

  # Force reimport (clear existing and import fresh)  
  <info>php %command.full_name% --force</info>

  # Show what would be imported
  <info>php %command.full_name% --dry-run</info>

The shapes are imported from the master index JSON file and stored in the database
with searchable metadata including categories, keywords, and descriptions.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        $io->title('Shape Import Tool');

        // Check if shapes data is available
        if (!$this->shapeImportService->isShapesDataAvailable()) {
            $io->error([
                'Shapes data is not available.',
                'Please ensure the design-vector-shapes repository is cloned to storage/shapes/',
                'Run: git clone -b output-only https://github.com/Webictbyleo/design-vector-shapes.git storage/shapes'
            ]);
            return Command::FAILURE;
        }

        // Show current status
        $dataInfo = $this->shapeImportService->getShapesDataInfo();
        $importStats = $this->shapeImportService->getImportStatistics();

        $io->section('Current Status');
        $io->horizontalTable(
            ['Property', 'Value'],
            [
                ['Available Files', $dataInfo['total_files'] ?? 0],
                ['Available Categories', count($dataInfo['categories'] ?? [])],
                ['Shapes in Database', $importStats['total'] ?? 0],
                ['Database Categories', $importStats['categories'] ?? 0],
                ['Average File Size', round($importStats['averageSize'] ?? 0) . ' bytes'],
            ]
        );

        if ($dryRun) {
            $io->note('DRY RUN MODE - No changes will be made');
            if ($force) {
                $io->info('Would clear existing shapes and import fresh');
            } else {
                $io->info('Would import new shapes (skip existing)');
            }
            return Command::SUCCESS;
        }

        // Confirm force operation
        if ($force && $importStats['total'] > 0) {
            $confirmed = $io->confirm(
                sprintf(
                    'This will delete %d existing shapes and reimport all shapes. Continue?',
                    $importStats['total']
                ),
                false
            );

            if (!$confirmed) {
                $io->info('Import cancelled');
                return Command::SUCCESS;
            }
        }

        // Perform import
        $io->section('Importing Shapes');
        
        try {
            if ($force) {
                $io->info('Clearing existing shapes and importing fresh...');
                $result = $this->shapeImportService->reimportShapes();
                
                $io->success([
                    sprintf('Successfully reimported shapes!'),
                    sprintf('Cleared: %d shapes', $result['cleared']),
                    sprintf('Imported: %d shapes', $result['imported']),
                    sprintf('Skipped: %d shapes', $result['skipped']),
                    sprintf('Errors: %d shapes', $result['errors']),
                    sprintf('Duration: %.2f seconds', $result['duration']),
                ]);
            } else {
                $io->info('Importing new shapes (skipping existing)...');
                $result = $this->shapeImportService->importShapes();
                
                $io->success([
                    sprintf('Successfully imported shapes!'),
                    sprintf('Imported: %d shapes', $result['imported']),
                    sprintf('Skipped: %d shapes', $result['skipped']),
                    sprintf('Errors: %d shapes', $result['errors']),
                    sprintf('Duration: %.2f seconds', $result['duration']),
                ]);
            }

            // Show final statistics
            $finalStats = $this->shapeImportService->getImportStatistics();
            $io->section('Final Statistics');
            $io->horizontalTable(
                ['Property', 'Value'],
                [
                    ['Total Shapes', $finalStats['total']],
                    ['Categories', $finalStats['categories']],
                    ['Average Size', round($finalStats['averageSize']) . ' bytes'],
                    ['Available Files', $finalStats['available_files'] ?? 'N/A'],
                    ['Import Coverage', $finalStats['available_files'] > 0 
                        ? round(($finalStats['total'] / $finalStats['available_files']) * 100, 1) . '%'
                        : 'N/A'
                    ],
                ]
            );

            if ($result['errors'] > 0) {
                $io->warning(sprintf(
                    '%d shapes had errors during import. Check the logs for details.',
                    $result['errors']
                ));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Import failed with error:',
                $e->getMessage()
            ]);
            
            if ($output->isVerbose()) {
                $io->text('Stack trace:');
                $io->text($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }
}
