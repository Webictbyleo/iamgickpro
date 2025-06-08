<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:media:clear',
    description: 'Clear all media records and uploaded files',
)]
class ClearMediaCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaRepository $mediaRepository,
        private readonly string $uploadDirectory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clear all media records and uploaded files')
            ->addOption('confirm', 'c', InputOption::VALUE_NONE, 'Skip confirmation prompt')
            ->addOption('files-only', 'f', InputOption::VALUE_NONE, 'Only clear files, keep database records')
            ->addOption('database-only', 'd', InputOption::VALUE_NONE, 'Only clear database records, keep files')
            ->setHelp('This command clears all media records from the database and removes uploaded files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $io->title('Media Cleanup Command');

        // Safety confirmation
        if (!$input->getOption('confirm')) {
            $confirmed = $io->confirm(
                'This will permanently delete ALL media records and files. Are you sure?',
                false
            );
            
            if (!$confirmed) {
                $io->warning('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $filesOnly = $input->getOption('files-only');
        $databaseOnly = $input->getOption('database-only');

        if ($filesOnly && $databaseOnly) {
            $io->error('Cannot use both --files-only and --database-only options together.');
            return Command::FAILURE;
        }

        try {
            // Clear database records
            if (!$filesOnly) {
                $io->section('Clearing Database Records');
                
                $totalMedia = $this->mediaRepository->count([]);
                $io->text("Found {$totalMedia} media records to delete.");

                if ($totalMedia > 0) {
                    // Use raw SQL for efficiency with large datasets
                    $connection = $this->entityManager->getConnection();
                    $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
                    $connection->executeStatement('TRUNCATE TABLE media');
                    $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
                    
                    $io->success("Deleted {$totalMedia} media records from database.");
                } else {
                    $io->info('No media records found to delete.');
                }
            }

            // Clear uploaded files
            if (!$databaseOnly) {
                $io->section('Clearing Uploaded Files');
                
                $uploadPaths = [
                    'media' => $this->uploadDirectory . '/media',
                    'thumbnails' => $this->uploadDirectory . '/thumbnails',
                    'avatars' => $this->uploadDirectory . '/avatars',
                    'plugins' => $this->uploadDirectory . '/plugins',
                ];

                $totalFilesRemoved = 0;

                foreach ($uploadPaths as $type => $path) {
                    if ($filesystem->exists($path)) {
                        $files = glob($path . '/*');
                        $fileCount = 0;
                        
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                $filesystem->remove($file);
                                $fileCount++;
                            } elseif (is_dir($file)) {
                                $filesystem->remove($file);
                            }
                        }
                        
                        $totalFilesRemoved += $fileCount;
                        $io->text("Removed {$fileCount} {$type} files.");
                    } else {
                        $io->text("Directory {$path} does not exist.");
                    }
                    
                    // Recreate directory
                    $filesystem->mkdir($path, 0755);
                }

                $io->success("Total files removed: {$totalFilesRemoved}");
            }

            // Final verification
            $io->section('Verification');
            
            if (!$filesOnly) {
                $remainingRecords = $this->mediaRepository->count([]);
                $io->text("Database records remaining: {$remainingRecords}");
            }
            
            if (!$databaseOnly) {
                $remainingFiles = 0;
                foreach (['media', 'thumbnails', 'avatars', 'plugins'] as $type) {
                    $path = $this->uploadDirectory . '/' . $type;
                    if ($filesystem->exists($path)) {
                        $files = glob($path . '/*');
                        $fileCount = count(array_filter($files, 'is_file'));
                        $remainingFiles += $fileCount;
                        $io->text("Remaining {$type} files: {$fileCount}");
                    }
                }
                $io->text("Total remaining files: {$remainingFiles}");
            }

            $io->success('Media cleanup completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error during cleanup: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
