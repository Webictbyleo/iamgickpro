<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:profiler:cleanup',
    description: 'Safely clean up profiler cache files with null byte protection'
)]
class ProfilerCleanupCommand extends Command
{
    public function __construct(private readonly string $cacheDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $profilerDir = $this->cacheDir . '/profiler';
        
        if (!is_dir($profilerDir)) {
            $io->success('Profiler directory does not exist, nothing to clean.');
            return Command::SUCCESS;
        }

        $io->info('Cleaning up profiler cache directory...');
        
        $removedFiles = 0;
        $skippedFiles = 0;
        $errors = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($profilerDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $fileInfo) {
                $filename = $fileInfo->getFilename();
                $filepath = $fileInfo->getPathname();

                // Check for null bytes in filename or path
                if (str_contains($filename, "\0") || str_contains($filepath, "\0")) {
                    $io->warning("Skipping file with null bytes: " . bin2hex($filename));
                    $skippedFiles++;
                    continue;
                }

                try {
                    if ($fileInfo->isFile()) {
                        if (file_exists($filepath)) {
                            unlink($filepath);
                            $removedFiles++;
                        }
                    } elseif ($fileInfo->isDir()) {
                        if (is_dir($filepath)) {
                            rmdir($filepath);
                        }
                    }
                } catch (\ValueError $e) {
                    if (str_contains($e->getMessage(), 'null bytes')) {
                        $io->warning("Skipped file due to null bytes: " . $e->getMessage());
                        $skippedFiles++;
                    } else {
                        $io->error("Error processing file: " . $e->getMessage());
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $io->error("Unexpected error: " . $e->getMessage());
                    $errors++;
                }
            }

            $io->success([
                "Profiler cleanup completed!",
                "Files removed: $removedFiles",
                "Files skipped: $skippedFiles",
                "Errors: $errors"
            ]);

        } catch (\Exception $e) {
            $io->error("Failed to clean profiler directory: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
