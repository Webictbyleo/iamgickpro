<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * Safe profiler storage that handles file operations with null byte protection
 */
class SafeProfilerStorage extends FileProfilerStorage
{
    public function __construct(string $dsn)
    {
        parent::__construct($dsn);
    }

    /**
     * {@inheritdoc}
     */
    public function write(Profile $profile): bool
    {
        try {
            return parent::write($profile);
        } catch (\ValueError $e) {
            // Log the error but don't crash the application
            if (str_contains($e->getMessage(), 'null bytes')) {
                error_log('Profiler storage: Skipping profile due to null bytes in filename');
                return false;
            }
            throw $e;
        }
    }

    /**
     * Override the removeExpiredProfiles method to handle null bytes safely
     */
    protected function removeExpiredProfiles(): void
    {
        $dir = $this->getProfilerStorageDir();
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \DirectoryIterator($dir);
        $time = time() - $this->getLifetime();

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) {
                continue;
            }

            $filename = $fileInfo->getFilename();
            
            // Check for null bytes in filename before processing
            if (str_contains($filename, "\0")) {
                error_log("Profiler storage: Skipping file with null bytes: " . bin2hex($filename));
                continue;
            }

            if ($fileInfo->getMTime() < $time) {
                try {
                    $filepath = $fileInfo->getPathname();
                    
                    // Double-check the full path for null bytes
                    if (str_contains($filepath, "\0")) {
                        error_log("Profiler storage: Skipping filepath with null bytes: " . bin2hex($filepath));
                        continue;
                    }
                    
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                } catch (\ValueError $e) {
                    error_log('Profiler storage: Error removing expired profile: ' . $e->getMessage());
                } catch (\Exception $e) {
                    error_log('Profiler storage: Unexpected error removing expired profile: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Get the profiler storage directory
     */
    private function getProfilerStorageDir(): string
    {
        // Extract directory from DSN
        $dsn = $this->getDsn();
        if (str_starts_with($dsn, 'file:')) {
            return substr($dsn, 5);
        }
        return $dsn;
    }

    /**
     * Get the DSN (we need to access the private property)
     */
    private function getDsn(): string
    {
        $reflection = new \ReflectionClass(parent::class);
        $property = $reflection->getProperty('folder');
        $property->setAccessible(true);
        return $property->getValue($this);
    }

    /**
     * Get the lifetime setting
     */
    private function getLifetime(): int
    {
        // Default to 24 hours if not configured
        return 86400;
    }
}
