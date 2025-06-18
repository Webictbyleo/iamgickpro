<?php

declare(strict_types=1);

namespace App\Service\Plugin\Plugins;

use App\Entity\Layer;
use App\Entity\User;
use App\Service\Plugin\PluginService;
use App\Service\Plugin\SecureRequestBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * RemoveBG Plugin
 * 
 * Implements background removal functionality using the remove.bg API.
 * Provides commands to remove and restore image backgrounds while maintaining
 * the original image data for reversible operations.
 */
class RemoveBgPlugin implements PluginInterface
{
    private const API_URL = 'https://api.remove.bg/v1.0/removebg';
    
    public function __construct(
        private readonly SecureRequestBuilder $requestBuilder,
        private readonly PluginService $pluginService,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly CacheItemPoolInterface $cache,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {}

    public function getName(): string
    {
        return 'Background Remover';
    }

    public function getDescription(): string
    {
        return 'Remove and restore image backgrounds using AI-powered background removal';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getIcon(): string
    {
        return '/icons/plugins/removebg.svg';
    }

    public function getSupportedCommands(): array
    {
        return [
            'remove_background',
            'restore_background',
            'preview_removal',
            'get_status',
            'clear_cache'
        ];
    }

    public function supportsCommand(string $command): bool
    {
        return in_array($command, $this->getSupportedCommands(), true);
    }

    public function executeCommand(User $user, Layer $layer, string $command, array $parameters = [], array $options = []): array
    {
        if (!$this->validateRequirements($user)) {
            throw new \RuntimeException('RemoveBG API key not configured. Please configure your API key in settings.');
        }

        return match ($command) {
            'remove_background' => $this->removeBackground($user, $layer, $parameters, $options),
            'restore_background' => $this->restoreBackground($user, $layer, $parameters, $options),
            'preview_removal' => $this->previewRemoval($user, $layer, $parameters, $options),
            'get_status' => $this->getStatus($user, $layer, $parameters, $options),
            'clear_cache' => $this->clearCachedResults($user, $layer, $parameters, $options),
            default => throw new \RuntimeException(sprintf('Unsupported command: %s', $command))
        };
    }

    public function isAvailableForUser(User $user): bool
    {
        return true; // Available to all users, but requires API key configuration
    }

    public function getRequirements(): array
    {
        return [
            'integrations' => ['removebg'],
            'layer_types' => ['image'],
            'permissions' => ['layer.edit']
        ];
    }

    public function validateRequirements(User $user): bool
    {
        // Here we'd check if user has removebg integration configured
        // This is handled by SecureRequestBuilder, so we return true here
        return true;
    }

    /**
     * Remove background from image layer
     */
    private function removeBackground(User $user, Layer $layer, array $parameters, array $options): array
    {
        if ($layer->getType() !== 'image') {
            throw new \RuntimeException('Background removal only supported for image layers');
        }

        $properties = $layer->getProperties();
        $imageUrl = $properties['src'] ?? null;

        if (!$imageUrl) {
            throw new \RuntimeException('Image layer must have a valid image source');
        }

        // Normalize the image URL for external API access
        $normalizedImageUrl = $this->normalizeImageUrl($imageUrl);

        try {
            // Store original image data if not already stored
            $pluginData = $layer->getPluginData('removebg') ?? [];
            if (!isset($pluginData['original_image'])) {
                $pluginData['original_image'] = [
                    'src' => $imageUrl,
                    'properties' => $properties,
                    'stored_at' => (new \DateTimeImmutable())->format('c')
                ];
            }

            // Check if we already have a processed image with compatible parameters
            $forceReprocess = $options['force_reprocess'] ?? false;
            
            if (!$forceReprocess && isset($pluginData['processed_images']['removed_bg'])) {
                $existingProcessed = $pluginData['processed_images']['removed_bg'];
                $existingParameters = $existingProcessed['parameters'] ?? [];
                
                // Compare key parameters to see if we can reuse the existing processed image
                $parametersMatch = $this->parametersMatch($existingParameters, $parameters);
                
                // If parameters match and processed file exists, return cached result
                if ($parametersMatch && file_exists($this->projectDir . '/public' . $existingProcessed['src'])) {
                    $this->logger->info('Returning cached background removal result', [
                        'layer_id' => $layer->getId(),
                        'user_id' => $user->getId(),
                        'cached_file' => $existingProcessed['src'],
                        'created_at' => $existingProcessed['created_at']
                    ]);
                    
                    // Update layer state and properties to background removed
                    $pluginData['current_state'] = 'background_removed';
                    $pluginData['last_updated'] = (new \DateTimeImmutable())->format('c');
                    
                    $newProperties = $properties;
                    $newProperties['src'] = $existingProcessed['src'];
                    $layer->setProperties($newProperties);
                    $layer->addPluginData('removebg', $pluginData);
                    
                    return [
                        'success' => true,
                        'message' => 'Background removed successfully (cached)',
                        'processed_image' => $existingProcessed['src'],
                        'credits_used' => 0, // No credits used for cached result
                        'can_restore' => true,
                        'cached' => true
                    ];
                } else {
                    $this->logger->info('Existing processed image found but parameters differ or file missing, processing new image', [
                        'layer_id' => $layer->getId(),
                        'user_id' => $user->getId(),
                        'parameters_match' => $parametersMatch,
                        'file_exists' => file_exists($this->projectDir . '/public' . $existingProcessed['src']),
                        'existing_params' => $existingParameters,
                        'requested_params' => $parameters
                    ]);
                }
            } elseif ($forceReprocess) {
                $this->logger->info('Force reprocessing requested, skipping cache', [
                    'layer_id' => $layer->getId(),
                    'user_id' => $user->getId()
                ]);
            }

            // No compatible processed image found, proceed with API call
            $this->logger->info('Processing new background removal request', [
                'layer_id' => $layer->getId(),
                'user_id' => $user->getId(),
                'parameters' => $parameters
            ]);
           

            // Make API request to remove.bg
            // Check if we need to upload file directly (development environment)
            if (str_starts_with($normalizedImageUrl, 'FILE_UPLOAD:')) {
                $relativePath = substr($normalizedImageUrl, strlen('FILE_UPLOAD:'));
                $fileData = $this->getLocalFileContent($relativePath);
                
                // Use multipart form data for file upload
                $response = $this->requestBuilder->forService($user, 'removebg')->post(self::API_URL, [
                    'body' => [
                        'image_file' => $fileData['content'],
                        'size' => $parameters['size'] ?? 'auto',
                        'format' => $parameters['format'] ?? 'png',
                        'roi' => $parameters['roi'] ?? null,
                        'crop' => $parameters['crop'] ?? null,
                        'scale' => $parameters['scale'] ?? null,
                        'position' => $parameters['position'] ?? null,
                        'channels' => $parameters['channels'] ?? 'rgba',
                        'add_shadow' => $parameters['add_shadow'] ?? false,
                        'semitransparency' => $parameters['semitransparency'] ?? true,
                    ],
                    'headers' => [
                        'Content-Type' => 'multipart/form-data'
                    ]
                ]);
            } else {
                // Use URL-based processing (production/staging environment)
                $response = $this->requestBuilder->forService($user, 'removebg')->post(self::API_URL, [
                    'body' => [
                        'image_url' => $normalizedImageUrl,
                        'size' => $parameters['size'] ?? 'auto',
                        'format' => $parameters['format'] ?? 'png',
                        'roi' => $parameters['roi'] ?? null,
                        'crop' => $parameters['crop'] ?? null,
                        'scale' => $parameters['scale'] ?? null,
                        'position' => $parameters['position'] ?? null,
                        'channels' => $parameters['channels'] ?? 'rgba',
                        'add_shadow' => $parameters['add_shadow'] ?? false,
                        'semitransparency' => $parameters['semitransparency'] ?? true,
                    ]
                ]);
            }

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Failed to remove background: ' . $response->getContent(false));
            }

            // Save processed image
            $processedImageData = $response->getContent();
            $processedImagePath = $this->saveProcessedImage($layer, $processedImageData, 'removed_bg');

            // Update plugin data
            $pluginData['processed_images'] = $pluginData['processed_images'] ?? [];
            $pluginData['processed_images']['removed_bg'] = [
                'src' => $processedImagePath,
                'created_at' => (new \DateTimeImmutable())->format('c'),
                'parameters' => $parameters,
                'api_response_info' => [
                    'detected_type' => $response->getHeaders()['x-type'][0] ?? null,
                    'width' => $response->getHeaders()['x-width'][0] ?? null,
                    'height' => $response->getHeaders()['x-height'][0] ?? null,
                    'credits_charged' => $response->getHeaders()['x-credits-charged'][0] ?? null,
                ]
            ];
            $pluginData['current_state'] = 'background_removed';
            $pluginData['last_updated'] = (new \DateTimeImmutable())->format('c');

            // Update layer with processed image
            $newProperties = $properties;
            $newProperties['src'] = $processedImagePath;
            $layer->setProperties($newProperties);
            $layer->addPluginData('removebg', $pluginData);

            // Clean up any temporary files created from cached content
            $this->cleanupTempCachedFile($normalizedImageUrl);

            return [
                'success' => true,
                'message' => 'Background removed successfully',
                'processed_image' => $processedImagePath,
                'credits_used' => $pluginData['processed_images']['removed_bg']['api_response_info']['credits_charged'] ?? 1,
                'can_restore' => true,
                'cached' => false
            ];

        } catch (ClientExceptionInterface | TransportExceptionInterface $e) {
            // Clean up any temporary files before throwing exception
            $this->cleanupTempCachedFile($normalizedImageUrl);
            throw new \RuntimeException('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore original background
     */
    private function restoreBackground(User $user, Layer $layer, array $parameters, array $options): array
    {
        $pluginData = $layer->getPluginData('removebg');
        
        if (!$pluginData || !isset($pluginData['original_image'])) {
            throw new \RuntimeException('No original image data found for restoration');
        }

        // Restore original image properties
        $originalImage = $pluginData['original_image'];
        $layer->setProperties($originalImage['properties']);

        // Update plugin data state
        $pluginData['current_state'] = 'background_original';
        $pluginData['last_updated'] = (new \DateTimeImmutable())->format('c');
        $layer->addPluginData('removebg', $pluginData);

        return [
            'success' => true,
            'message' => 'Background restored successfully',
            'restored_image' => $originalImage['src'],
            'can_remove' => true
        ];
    }

    /**
     * Preview background removal without applying changes
     */
    private function previewRemoval(User $user, Layer $layer, array $parameters, array $options): array
    {
        // For preview, we could return cached result if exists
        $pluginData = $layer->getPluginData('removebg');
        
        if ($pluginData && isset($pluginData['processed_images']['removed_bg'])) {
            return [
                'success' => true,
                'preview_url' => $pluginData['processed_images']['removed_bg']['src'],
                'cached' => true
            ];
        }

        return [
            'success' => false,
            'message' => 'No preview available. Remove background first to see preview.',
            'cached' => false
        ];
    }

    /**
     * Get current plugin status for layer
     */
    private function getStatus(User $user, Layer $layer, array $parameters, array $options): array
    {
        $pluginData = $layer->getPluginData('removebg');
        
        if (!$pluginData) {
            return [
                'status' => 'not_processed',
                'can_remove' => true,
                'can_restore' => false
            ];
        }

        $currentState = $pluginData['current_state'] ?? 'unknown';
        
        return [
            'status' => $currentState,
            'can_remove' => $currentState !== 'background_removed',
            'can_restore' => isset($pluginData['original_image']),
            'processed_at' => $pluginData['last_updated'] ?? null,
            'available_images' => array_keys($pluginData['processed_images'] ?? [])
        ];
    }

    /**
     * Save processed image to plugin directory
     */
    private function saveProcessedImage(Layer $layer, string $imageData, string $suffix): string
    {
        $pluginDir = $this->pluginService->getPluginDirectory('removebg');
        $filename = sprintf(
            'layer_%d_%s_%s.png',
            $layer->getId(),
            $suffix,
            uniqid()
        );
        
        $filePath = $pluginDir . '/' . $filename;
        file_put_contents($filePath, $imageData);
        
        // Return relative path for web access
        return '/uploads/plugins/removebg/' . $filename;
    }

    /**
     * Normalize image URL for external API access
     * 
     * Handles edge cases:
     * - Converts relative URLs to absolute URLs
     * - Decodes proxied media URLs to get the original external URL
     * - In development environment, creates a publicly accessible URL or uploads image data directly
     */
    private function normalizeImageUrl(string $imageUrl): string
    {
        $this->logger->info('Normalizing image URL for RemoveBG', [
            'original_url' => $imageUrl,
            'environment' => $this->environment
        ]);

        // Handle proxied media URLs first (before checking for absolute URLs)
        if ($this->isProxiedMediaUrl($imageUrl)) {
            $originalUrl = $this->extractOriginalUrlFromProxy($imageUrl);
            $this->logger->info('Detected proxied media URL, extracted original URL', [
                'proxied_url' => $imageUrl,
                'original_url' => $originalUrl
            ]);
            return $originalUrl;
        }

        // If already an absolute URL (starts with http:// or https://), return as-is
        if (preg_match('/^https?:\/\//', $imageUrl)) {
            // Check if it's a localhost URL in production - this should be converted
            if ($this->environment === 'prod' && (str_contains($imageUrl, 'localhost') || str_contains($imageUrl, '127.0.0.1'))) {
                throw new \RuntimeException('Localhost URLs are not accessible by external APIs in production environment');
            }
            
            $this->logger->info('Using absolute URL as-is', ['url' => $imageUrl]);
            return $imageUrl;
        }

        // Handle relative URLs
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('Cannot resolve relative URL without request context');
        }

        // Build absolute URL from relative path
        $scheme = $request->isSecure() ? 'https' : 'http';
        $host = $request->getHttpHost();
        $absoluteUrl = $scheme . '://' . $host . $imageUrl;
        
        $this->logger->info('Converted relative URL to absolute', [
            'relative_url' => $imageUrl,
            'absolute_url' => $absoluteUrl,
            'host' => $host
        ]);

        // In development environment, external APIs can't access localhost
        // We need to handle this case specially - upload file directly
        if ($this->environment === 'dev' && (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1'))) {
            // Instead of returning a URL, we'll trigger direct file upload
            // This is a special marker that tells the calling method to use file upload
            $this->logger->info('Development environment detected, will use direct file upload', [
                'host' => $host,
                'relative_path' => $imageUrl
            ]);
            return 'FILE_UPLOAD:' . $imageUrl;
        }

        return $absoluteUrl;
    }

    /**
     * Get local file content for direct upload
     */
    private function getLocalFileContent(string $relativePath): array
    {
        $publicDir = $this->projectDir . '/public';
        $localFilePath = $publicDir . $relativePath;
        
        $this->logger->info('Reading local file for direct upload', [
            'relative_path' => $relativePath,
            'full_path' => $localFilePath
        ]);
        
        if (!file_exists($localFilePath)) {
            $this->logger->error('Local file not found', ['path' => $localFilePath]);
            throw new \RuntimeException(sprintf(
                'Local file not found: %s. Cannot process image in development environment.',
                $localFilePath
            ));
        }

        if (!is_readable($localFilePath)) {
            $this->logger->error('Local file not readable', ['path' => $localFilePath]);
            throw new \RuntimeException(sprintf(
                'Local file not readable: %s. Check file permissions.',
                $localFilePath
            ));
        }

        $fileContent = file_get_contents($localFilePath);
        if ($fileContent === false) {
            $this->logger->error('Failed to read file content', ['path' => $localFilePath]);
            throw new \RuntimeException(sprintf('Failed to read file content: %s', $localFilePath));
        }

        // Detect MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $localFilePath);
        finfo_close($finfo);

        $this->logger->info('Successfully read local file', [
            'path' => $localFilePath,
            'size' => strlen($fileContent),
            'mime_type' => $mimeType
        ]);

        return [
            'content' => $fileContent,
            'mime_type' => $mimeType,
            'filename' => basename($localFilePath)
        ];
    }

    /**
     * Check if the URL is a proxied media URL
     */
    private function isProxiedMediaUrl(string $url): bool
    {
        return str_contains($url, '/api/media/proxy/') || preg_match('#/api/media/proxy/[A-Za-z0-9+/=]+$#', $url);
    }

    /**
     * Extract the original external URL from a proxied media URL
     * Also checks cache and creates temporary accessible URL if cached content exists
     */
    private function extractOriginalUrlFromProxy(string $proxiedUrl): string
    {
        // Extract the base64 encoded part from the URL
        if (preg_match('#/api/media/proxy/([A-Za-z0-9+/=]+)$#', $proxiedUrl, $matches)) {
            $encodedUrl = $matches[1];
            $originalUrl = base64_decode($encodedUrl);
            
            if (!$originalUrl || !filter_var($originalUrl, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException(sprintf(
                    'Invalid proxied URL: Unable to decode or validate original URL from %s',
                    $proxiedUrl
                ));
            }
            
            // Check if we have cached content for this URL
            $cachedUrl = $this->getCachedProxyUrl($originalUrl);
            if ($cachedUrl) {
                $this->logger->info('Using cached content URL instead of original external URL', [
                    'proxied_url' => $proxiedUrl,
                    'original_url' => $originalUrl,
                    'cached_url' => $cachedUrl
                ]);
                return $cachedUrl;
            }
            
            $this->logger->info('Successfully extracted original URL from proxy (no cache)', [
                'proxied_url' => $proxiedUrl,
                'encoded_part' => $encodedUrl,
                'original_url' => $originalUrl
            ]);
            
            return $originalUrl;
        }
        
        throw new \RuntimeException(sprintf(
            'Invalid proxy URL format: %s. Expected format: /api/media/proxy/{base64_encoded_url}',
            $proxiedUrl
        ));
    }

    /**
     * Check if proxied media content is cached and create a temporary accessible URL
     * Returns null if not cached, otherwise returns a URL that RemoveBG can access
     */
    private function getCachedProxyUrl(string $originalUrl): ?string
    {
        try {
            // Use the same cache key format as PublicMediaController
            $cacheKey = 'media_proxy_' . md5($originalUrl);
            $cacheItem = $this->cache->getItem($cacheKey);
            
            if (!$cacheItem->isHit()) {
                $this->logger->debug('No cached content found for proxied media', [
                    'original_url' => $originalUrl,
                    'cache_key' => $cacheKey
                ]);
                return null;
            }
            
            $cachedData = $cacheItem->get();
            if (!isset($cachedData['content']) || !isset($cachedData['contentType'])) {
                $this->logger->warning('Invalid cached data structure for proxied media', [
                    'original_url' => $originalUrl,
                    'cache_key' => $cacheKey
                ]);
                return null;
            }
            
            // Create a temporary file from cached content
            $tempUrl = $this->createTempFileFromCache($cachedData, $originalUrl);
            
            $this->logger->info('Created temporary URL from cached proxied media', [
                'original_url' => $originalUrl,
                'temp_url' => $tempUrl,
                'content_type' => $cachedData['contentType'],
                'content_size' => strlen($cachedData['content'])
            ]);
            
            return $tempUrl;
            
        } catch (\Exception $e) {
            $this->logger->error('Error checking cached proxied media', [
                'original_url' => $originalUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create a temporary accessible file from cached content
     */
    private function createTempFileFromCache(array $cachedData, string $originalUrl): string
    {
        // Get file extension from content type or original URL
        $extension = $this->getFileExtensionFromContentType($cachedData['contentType']);
        if (!$extension) {
            $extension = pathinfo(parse_url($originalUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        }
        
        // Create temporary file in plugin directory
        $tempDir = $this->pluginService->getPluginDirectory('removebg') . '/temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $tempFilename = 'cached_' . md5($originalUrl) . '_' . time() . '.' . $extension;
        $tempFilePath = $tempDir . '/' . $tempFilename;
        
        // Write cached content to temporary file
        file_put_contents($tempFilePath, $cachedData['content']);
        
        // Return URL that can be accessed by external APIs
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $scheme = $request->isSecure() ? 'https' : 'http';
            $host = $request->getHttpHost();
            $webPath = '/uploads/plugins/removebg/temp/' . $tempFilename;
            
            // In development, this might still be localhost, but we'll let the 
            // normalizeImageUrl handle that case with file upload
            return $scheme . '://' . $host . $webPath;
        }
        
        // Fallback to relative path
        return '/uploads/plugins/removebg/temp/' . $tempFilename;
    }

    /**
     * Get file extension from content type
     */
    private function getFileExtensionFromContentType(string $contentType): ?string
    {
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tiff'
        ];
        
        return $mimeToExt[$contentType] ?? null;
    }

    /**
     * Clean up temporary cached file if it was created from cache
     */
    private function cleanupTempCachedFile(string $imageUrl): void
    {
        // Only clean up if this was a temporary cached file
        if (str_contains($imageUrl, '/uploads/plugins/removebg/temp/cached_')) {
            try {
                // Extract the file path from URL
                $urlPath = parse_url($imageUrl, PHP_URL_PATH);
                if ($urlPath && str_starts_with($urlPath, '/uploads/plugins/removebg/temp/')) {
                    $filePath = $this->projectDir . '/public' . $urlPath;
                    
                    if (file_exists($filePath)) {
                        unlink($filePath);
                        $this->logger->info('Cleaned up temporary cached file', [
                            'file_path' => $filePath,
                            'url' => $imageUrl
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to cleanup temporary cached file', [
                    'url' => $imageUrl,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Compare processing parameters to determine if cached result can be used
     */
    private function parametersMatch(array $existingParams, array $requestedParams): bool
    {
        $keyParams = ['size', 'format', 'add_shadow', 'semitransparency'];
        
        foreach ($keyParams as $param) {
            $existingValue = $existingParams[$param] ?? null;
            $requestedValue = $requestedParams[$param] ?? null;
            
            // Normalize defaults for comparison
            switch ($param) {
                case 'size':
                    if ($requestedValue === null) $requestedValue = 'auto';
                    break;
                case 'format':
                    if ($requestedValue === null) $requestedValue = 'png';
                    break;
                case 'add_shadow':
                    if ($requestedValue === null) $requestedValue = false;
                    break;
                case 'semitransparency':
                    if ($requestedValue === null) $requestedValue = true;
                    break;
            }
            
            if ($existingValue !== $requestedValue) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Clear cached background removal results
     */
    private function clearCachedResults(User $user, Layer $layer, array $parameters, array $options): array
    {
        $pluginData = $layer->getPluginData('removebg') ?? [];
        
        if (!isset($pluginData['processed_images']['removed_bg'])) {
            return [
                'success' => true,
                'message' => 'No cached results to clear',
                'cleared' => false
            ];
        }
        
        $processedImage = $pluginData['processed_images']['removed_bg'];
        $imagePath = $this->projectDir . '/public' . $processedImage['src'];
        
        // Remove the physical file if it exists
        if (file_exists($imagePath)) {
            unlink($imagePath);
            $this->logger->info('Deleted cached processed image file', [
                'layer_id' => $layer->getId(),
                'user_id' => $user->getId(),
                'file_path' => $processedImage['src']
            ]);
        }
        
        // Remove from plugin data
        unset($pluginData['processed_images']['removed_bg']);
        if (empty($pluginData['processed_images'])) {
            unset($pluginData['processed_images']);
        }
        
        // Reset state if no processed images remain
        if (!isset($pluginData['processed_images']) || empty($pluginData['processed_images'])) {
            $pluginData['current_state'] = 'not_processed';
        }
        
        $pluginData['last_updated'] = (new \DateTimeImmutable())->format('c');
        $layer->addPluginData('removebg', $pluginData);
        
        return [
            'success' => true,
            'message' => 'Cached background removal results cleared',
            'cleared' => true
        ];
    }
}
