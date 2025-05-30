<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Plugin;
use App\Entity\User;
use App\Repository\PluginRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use ZipArchive;

readonly class PluginService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PluginRepository $pluginRepository,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        private string $pluginDirectory,
        private string $extractDirectory,
        private int $maxFileSize,
        private array $allowedMimeTypes
    ) {
        // Ensure directories exist
        $this->ensureDirectoryExists($this->pluginDirectory);
        $this->ensureDirectoryExists($this->extractDirectory);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }
    }

    public function uploadPlugin(UploadedFile $file, User $developer): Plugin
    {
        $this->validateFile($file);

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.zip';

        try {
            $file->move($this->pluginDirectory, $fileName);
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload plugin file', [
                'filename' => $fileName,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to upload plugin file');
        }

        $filePath = $this->pluginDirectory . '/' . $fileName;
        $extractPath = $this->extractDirectory . '/' . pathinfo($fileName, PATHINFO_FILENAME);

        // Extract and validate plugin
        $manifest = $this->extractAndValidatePlugin($filePath, $extractPath);
        
        // Add file paths to manifest
        $manifest['file_path'] = $filePath;
        $manifest['extracted_path'] = $extractPath;

        $plugin = new Plugin();
        $plugin->setName($manifest['name'])
              ->setIdentifier((string) $this->slugger->slug($manifest['name']))
              ->setVersion($manifest['version'])
              ->setDescription($manifest['description'] ?? '')
              ->setManifest($manifest)
              ->setEntryPoint($extractPath . '/' . $manifest['entry_point'])
              ->setUser($developer)
              ->setStatus('pending');

        // Set optional fields from manifest
        if (isset($manifest['icon'])) {
            $plugin->setIconUrl($manifest['icon']);
        }
        if (isset($manifest['banner'])) {
            $plugin->setBannerUrl($manifest['banner']);
        }
        if (isset($manifest['permissions']) && is_array($manifest['permissions'])) {
            $plugin->setPermissions($manifest['permissions']);
        }
        if (isset($manifest['categories']) && is_array($manifest['categories'])) {
            $plugin->setCategories($manifest['categories']);
        }
        if (isset($manifest['tags']) && is_array($manifest['tags'])) {
            $plugin->setTags($manifest['tags']);
        }

        $this->entityManager->persist($plugin);
        $this->entityManager->flush();

        // Run security scan
        $this->runSecurityScan($plugin);

        $this->logger->info('Plugin uploaded', [
            'plugin_id' => $plugin->getId(),
            'name' => $plugin->getName(),
            'version' => $plugin->getVersion(),
            'developer_id' => $developer->getId()
        ]);

        return $plugin;
    }

    public function approvePlugin(Plugin $plugin, User $admin): void
    {
        if ($plugin->getStatus() !== 'pending') {
            throw new \InvalidArgumentException('Plugin is not pending approval');
        }

        $plugin->setStatus('approved')
              ->setReviewedBy($admin)
              ->setReviewedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Plugin approved', [
            'plugin_id' => $plugin->getId(),
            'name' => $plugin->getName(),
            'admin_id' => $admin->getId()
        ]);
    }

    public function rejectPlugin(Plugin $plugin, User $admin, string $reason): void
    {
        if ($plugin->getStatus() !== 'pending') {
            throw new \InvalidArgumentException('Plugin is not pending approval');
        }

        $plugin->setStatus('rejected')
              ->setReviewedBy($admin)
              ->setReviewedAt(new \DateTimeImmutable())
              ->setReviewNotes($reason);

        $this->entityManager->flush();

        $this->logger->info('Plugin rejected', [
            'plugin_id' => $plugin->getId(),
            'name' => $plugin->getName(),
            'admin_id' => $admin->getId(),
            'reason' => $reason
        ]);
    }

    public function installPlugin(Plugin $plugin, User $user): array
    {
        if ($plugin->getStatus() !== 'approved') {
            throw new \InvalidArgumentException('Plugin is not approved for installation');
        }

        // Use manifest to track installations
        $manifest = $plugin->getManifest();
        $installedBy = $manifest['installed_by'] ?? [];
        
        if (in_array($user->getId(), $installedBy, true)) {
            throw new \InvalidArgumentException('Plugin is already installed by this user');
        }

        // Add user to installed list
        $installedBy[] = $user->getId();
        $manifest['installed_by'] = $installedBy;
        $manifest['install_count'] = count($installedBy);
        $plugin->setManifest($manifest);

        // Increment total installations
        $plugin->incrementInstallCount();

        $this->entityManager->flush();

        $this->logger->info('Plugin installed', [
            'plugin_id' => $plugin->getId(),
            'name' => $plugin->getName(),
            'user_id' => $user->getId()
        ]);

        // Return plugin configuration
        return [
            'plugin_id' => $plugin->getId(),
            'name' => $plugin->getName(),
            'version' => $plugin->getVersion(),
            'manifest' => $plugin->getManifest(),
            'permissions' => $plugin->getPermissions(),
            'assets_url' => '/plugins/' . $plugin->getIdentifier() . '/' . $plugin->getVersion()
        ];
    }

    public function uninstallPlugin(Plugin $plugin, User $user): void
    {
        $manifest = $plugin->getManifest();
        $installedBy = $manifest['installed_by'] ?? [];
        
        $userIndex = array_search($user->getId(), $installedBy, true);
        if ($userIndex === false) {
            throw new \InvalidArgumentException('Plugin is not installed by this user');
        }

        // Remove user from installed list
        unset($installedBy[$userIndex]);
        $installedBy = array_values($installedBy); // Re-index array
        $manifest['installed_by'] = $installedBy;
        $manifest['install_count'] = count($installedBy);
        $plugin->setManifest($manifest);

        $this->entityManager->flush();

        $this->logger->info('Plugin uninstalled', [
            'plugin_id' => $plugin->getId(),
            'name' => $plugin->getName(),
            'user_id' => $user->getId()
        ]);
    }

    public function getMarketplacePlugins(
        ?string $query = null,
        ?string $category = null,
        string $sort = 'popularity',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->pluginRepository->findApproved([
            'query' => $query,
            'category' => $category,
            'sort' => $sort,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    public function getUserInstalledPlugins(User $user): array
    {
        $allPlugins = $this->pluginRepository->findApproved();
        $installedPlugins = [];

        foreach ($allPlugins as $plugin) {
            $manifest = $plugin->getManifest();
            $installedBy = $manifest['installed_by'] ?? [];
            
            if (in_array($user->getId(), $installedBy, true)) {
                $installedPlugins[] = [
                    'plugin_id' => $plugin->getId(),
                    'name' => $plugin->getName(),
                    'version' => $plugin->getVersion(),
                    'identifier' => $plugin->getIdentifier(),
                    'manifest' => $plugin->getManifest(),
                    'permissions' => $plugin->getPermissions(),
                    'installed_at' => $manifest['installed_at'] ?? null
                ];
            }
        }

        return $installedPlugins;
    }

    public function getDeveloperPlugins(User $developer): array
    {
        return $this->pluginRepository->findBy(['user' => $developer]);
    }

    public function getPendingPlugins(): array
    {
        return $this->pluginRepository->findPendingApproval();
    }

    public function updatePlugin(Plugin $plugin, array $data): Plugin
    {
        if (isset($data['name'])) {
            $plugin->setName($data['name']);
        }
        if (isset($data['description'])) {
            $plugin->setDescription($data['description']);
        }
        if (isset($data['icon_url'])) {
            $plugin->setIconUrl($data['icon_url']);
        }
        if (isset($data['banner_url'])) {
            $plugin->setBannerUrl($data['banner_url']);
        }
        if (isset($data['categories']) && is_array($data['categories'])) {
            $plugin->setCategories($data['categories']);
        }
        if (isset($data['tags']) && is_array($data['tags'])) {
            $plugin->setTags($data['tags']);
        }

        $this->entityManager->flush();

        return $plugin;
    }

    public function deletePlugin(Plugin $plugin): void
    {
        // For uploaded plugins, we store file paths in manifest
        $manifest = $plugin->getManifest();
        
        if (isset($manifest['file_path']) && file_exists($manifest['file_path'])) {
            unlink($manifest['file_path']);
        }

        if (isset($manifest['extracted_path']) && is_dir($manifest['extracted_path'])) {
            $this->deleteDirectory($manifest['extracted_path']);
        }

        $this->entityManager->remove($plugin);
        $this->entityManager->flush();

        $this->logger->info('Plugin deleted', [
            'plugin_id' => $plugin->getId(),
            'name' => $plugin->getName()
        ]);
    }

    public function getPluginStatistics(Plugin $plugin): array
    {
        $manifest = $plugin->getManifest();
        
        return [
            'total_installations' => $plugin->getInstallCount(),
            'active_installations' => count($manifest['installed_by'] ?? []),
            'average_rating' => (float) $plugin->getRating(),
            'total_ratings' => $plugin->getRatingCount(),
            'created_at' => $plugin->getCreatedAt()->format('Y-m-d'),
            'last_updated' => $plugin->getUpdatedAt()->format('Y-m-d'),
            'security_status' => $manifest['security_status'] ?? 'unknown'
        ];
    }

    public function runSecurityScan(Plugin $plugin): array
    {
        $results = [
            'status' => 'safe',
            'issues' => [],
            'scanned_at' => (new \DateTimeImmutable())->format('c')
        ];

        try {
            $manifest = $plugin->getManifest();
            $extractedPath = $manifest['extracted_path'] ?? null;
            
            if (!$extractedPath || !is_dir($extractedPath)) {
                $results['status'] = 'error';
                $results['issues'][] = 'Plugin files not found for scanning';
                return $results;
            }

            // Scan for dangerous patterns
            $dangerousPatterns = [
                '/eval\s*\(/i' => 'eval() function detected',
                '/exec\s*\(/i' => 'exec() function detected',
                '/shell_exec\s*\(/i' => 'shell_exec() function detected',
                '/system\s*\(/i' => 'system() function detected',
                '/file_get_contents\s*\(\s*["\']https?:\/\//i' => 'Remote file inclusion detected',
                '/\$_GET\[.*\]\s*\(/i' => 'Potential code injection via GET parameter',
                '/\$_POST\[.*\]\s*\(/i' => 'Potential code injection via POST parameter'
            ];

            $this->scanDirectoryForPatterns($extractedPath, $dangerousPatterns, $results);

            // Update plugin manifest with scan results
            $manifest['security_scan'] = $results;
            $manifest['security_status'] = $results['status'];
            $plugin->setManifest($manifest);

            $this->entityManager->flush();

        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['issues'][] = 'Security scan failed: ' . $e->getMessage();
            
            $this->logger->error('Plugin security scan failed', [
                'plugin_id' => $plugin->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    private function extractAndValidatePlugin(string $filePath, string $extractPath): array
    {
        // Create extraction directory
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        // Extract ZIP file
        $zip = new ZipArchive();
        $result = $zip->open($filePath);
        
        if ($result !== true) {
            throw new \RuntimeException('Failed to open plugin ZIP file');
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Look for manifest file
        $manifestPath = $extractPath . '/manifest.json';
        if (!file_exists($manifestPath)) {
            throw new \RuntimeException('Plugin manifest.json not found');
        }

        $manifestContent = file_get_contents($manifestPath);
        $manifest = json_decode($manifestContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid manifest.json format');
        }

        // Validate required fields
        $requiredFields = ['name', 'version', 'description', 'entry_point'];
        foreach ($requiredFields as $field) {
            if (!isset($manifest[$field])) {
                throw new \RuntimeException("Missing required field in manifest: {$field}");
            }
        }

        // Validate entry point exists
        $entryPointPath = $extractPath . '/' . $manifest['entry_point'];
        if (!file_exists($entryPointPath)) {
            throw new \RuntimeException('Plugin entry point file not found: ' . $manifest['entry_point']);
        }

        return $manifest;
    }

    private function scanDirectoryForPatterns(string $directory, array $patterns, array &$results): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(js|php|html|htm)$/i', $file->getFilename())) {
                $content = file_get_contents($file->getPathname());
                
                foreach ($patterns as $pattern => $description) {
                    if (preg_match($pattern, $content)) {
                        $results['status'] = 'warning';
                        $results['issues'][] = [
                            'file' => str_replace($directory, '', $file->getPathname()),
                            'issue' => $description,
                            'severity' => 'high'
                        ];
                    }
                }
            }
        }
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($directory);
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException(sprintf(
                'File size (%s) exceeds maximum allowed size (%s)',
                $this->formatFileSize($file->getSize()),
                $this->formatFileSize($this->maxFileSize)
            ));
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new \InvalidArgumentException(sprintf(
                'File type "%s" is not allowed. Only ZIP files are accepted.',
                $mimeType
            ));
        }
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
