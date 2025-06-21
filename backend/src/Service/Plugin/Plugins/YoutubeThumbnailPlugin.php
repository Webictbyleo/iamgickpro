<?php

declare(strict_types=1);

namespace App\Service\Plugin\Plugins;

use App\Entity\Layer;
use App\Entity\User;
use App\Message\ProcessYoutubeThumbnailMessage;
use App\Service\Plugin\Plugins\AbstractStandalonePlugin;
use App\Service\Plugin\PluginService;
use App\Service\Plugin\SecureRequestBuilder;
use App\Service\MediaProcessing\MediaProcessingService;
use App\Service\MediaProcessing\AsyncMediaProcessingService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * YouTube Thumbnail Generator Plugin
 * 
 * Implements YouTube video analysis and AI-powered thumbnail generation using OpenAI.
 * Uses the original YouTube thumbnail as reference and generates improved versions
 * using OpenAI's gpt-image-1 model with the edits endpoint.
 * 
 * This is a standalone plugin that doesn't require a design layer.
 */
class YoutubeThumbnailPlugin extends AbstractStandalonePlugin
{
    private const YOUTUBE_API_URL = 'https://www.googleapis.com/youtube/v3';
    private const OPENAI_API_URL = 'https://api.openai.com/v1';
    private const REPLICATE_API_URL = 'https://api.replicate.com/v1';
    
    public function __construct(
        private readonly SecureRequestBuilder $requestBuilder,
        PluginService $pluginService,
        private readonly MediaProcessingService $mediaProcessingService,
        private readonly AsyncMediaProcessingService $asyncService,
        private readonly MessageBusInterface $messageBus,
        private readonly RequestStack $requestStack,
        LoggerInterface $logger,
        private readonly CacheItemPoolInterface $cache,
        #[Autowire('%kernel.environment%')]
        string $environment,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir
    ) {
        parent::__construct($pluginService, $logger, $environment, $projectDir);
    }

    protected function executeStandaloneCommand(User $user, string $command, array $parameters = [], array $options = []): array
    {
        if (!$this->validateRequirements($user)) {
            throw new \RuntimeException('OpenAI API key not configured. Please configure your API key in settings.');
        }

        return match ($command) {
            'analyze_video' => $this->analyzeVideo($user, $parameters, $options),
            'generate_thumbnail_variations' => $this->generateThumbnailVariations($user, $parameters, $options),
            'generate_thumbnail_variations_async' => $this->generateThumbnailVariationsAsync($user, $parameters, $options),
            'get_job_status' => $this->getJobStatus($user, $parameters, $options),
            'cancel_job' => $this->cancelJob($user, $parameters, $options),
            'get_video_info' => $this->getVideoInfo($user, $parameters, $options),
            'clear_cache' => $this->clearCachedResults($user, $parameters, $options),
            default => throw new \RuntimeException(sprintf('Unsupported command: %s', $command))
        };
    }

    public function validateRequirements(User $user): bool
    {
        // Check if user has OpenAI integration configured or if Replicate is enabled
        // This is handled by SecureRequestBuilder for OpenAI, and we check env for Replicate
        return true;
    }

    /**
     * Check if Replicate API is available
     */
    private function isReplicateEnabled(): bool
    {
        return !empty($_ENV['REPLICATE_API_TOKEN'] ?? '');
    }

    /**
     * Analyze YouTube video and extract basic information
     */
    private function analyzeVideo(User $user, array $parameters, array $options): array
    {
        $videoUrl = $parameters['video_url'] ?? null;
        if (!$videoUrl) {
            throw new \RuntimeException('YouTube video URL is required');
        }

        $videoId = $this->extractVideoId($videoUrl);
        if (!$videoId) {
            throw new \RuntimeException('Invalid YouTube video URL');
        }

        try {
            // Check cache first
            $cacheKey = sprintf('youtube_analysis_%s', $videoId);
            $cachedItem = $this->cache->getItem($cacheKey);
            
            if ($cachedItem->isHit()) {
                $this->logger->info('Returning cached YouTube video analysis', ['video_id' => $videoId]);
                return $cachedItem->get();
            }

            // Get video info from YouTube (using oEmbed API which doesn't require API key)
            $videoInfo = $this->getYouTubeVideoInfo($user, $videoId);
            
            // Store in cache for 1 hour
            $cachedItem->set($videoInfo);
            $cachedItem->expiresAfter(3600);
            $this->cache->save($cachedItem);

            $this->logger->info('YouTube video analyzed successfully', ['video_id' => $videoId]);

            return [
                'success' => true,
                'video_info' => $videoInfo,
                'video_id' => $videoId
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to analyze YouTube video', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to analyze YouTube video: ' . $e->getMessage());
        }
    }

    /**
     * Generate thumbnail variations using either Replicate (preferred) or OpenAI
     */
    private function generateThumbnailVariations(User $user, array $parameters, array $options): array
    {
        $videoUrl = $parameters['video_url'] ?? null;
        $customPrompt = $parameters['custom_prompt'] ?? null;
        $thumbnailCount = $parameters['thumbnail_count'] ?? 4;
        $style = $parameters['style'] ?? 'modern';

        if (!$videoUrl) {
            throw new \RuntimeException('YouTube video URL is required');
        }

        $videoId = $this->extractVideoId($videoUrl);
        if (!$videoId) {
            throw new \RuntimeException('Invalid YouTube video URL');
        }

        // Validate thumbnail generation parameters
        $validationErrors = $this->validateThumbnailParameters($parameters);
        if (!empty($validationErrors)) {
            throw new \RuntimeException(implode(', ', $validationErrors));
        }

        try {
            $this->logger->info('Starting thumbnail generation', [
                'video_id' => $videoId,
                'thumbnail_count' => $thumbnailCount,
                'style' => $style,
                'custom_prompt' => $customPrompt ? substr($customPrompt, 0, 100) . '...' : null,
                'using_replicate' => $this->isReplicateEnabled()
            ]);

            // Get video info first
            $videoInfo = $this->getYouTubeVideoInfo($user, $videoId);
            
            if (!isset($videoInfo['thumbnail_url'])) {
                throw new \RuntimeException('Original thumbnail URL not found for video');
            }
            
            $this->logger->info('Video info retrieved', [
                'video_id' => $videoId,
                'title' => $videoInfo['title'] ?? 'Unknown',
                'thumbnail_url' => $videoInfo['thumbnail_url']
            ]);
            
            // Choose generation method based on availability
            if ($this->isReplicateEnabled()) {
                $thumbnailVariations = $this->generateThumbnailVariationsWithReplicate($user, $videoInfo, $customPrompt, $thumbnailCount, $style);
                $generationMethod = 'replicate';
            } else {
                // Download original thumbnail for OpenAI (requires reference image)
                $originalThumbnailPath = $this->downloadOriginalThumbnail($videoInfo['thumbnail_url'], $videoId);
                if (!file_exists($originalThumbnailPath)) {
                    throw new \RuntimeException('Failed to download original thumbnail image');
                }
                
                $this->logger->info('Original thumbnail downloaded for OpenAI', [
                    'video_id' => $videoId,
                    'path' => $originalThumbnailPath,
                    'file_size' => filesize($originalThumbnailPath)
                ]);
                
                $thumbnailVariations = $this->generateThumbnailVariationsWithOpenAI($user, $videoInfo, $originalThumbnailPath, $customPrompt, $thumbnailCount, $style);
                $generationMethod = 'openai';
            }
            
            $this->logger->info('Thumbnail variations generated successfully', [
                'video_id' => $videoId,
                'thumbnail_count' => count($thumbnailVariations),
                'generation_method' => $generationMethod
            ]);

            return [
                'success' => true,
                'thumbnail_variations' => $thumbnailVariations,
                'video_info' => $videoInfo,
                'generation_method' => $generationMethod,
                'generation_parameters' => [
                    'custom_prompt' => $customPrompt,
                    'thumbnail_count' => $thumbnailCount,
                    'style' => $style,
                    'generated_at' => (new \DateTimeImmutable())->format('c')
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate thumbnail variations', [
                'video_id' => $videoId,
                'error' => $e->getMessage(),
                'using_replicate' => $this->isReplicateEnabled()
            ]);
            throw new \RuntimeException('Failed to generate thumbnail variations: ' . $e->getMessage());
        }
    }

    /**
     * Get basic video information
     */
    private function getVideoInfo(User $user, array $parameters, array $options): array
    {
        $videoUrl = $parameters['video_url'] ?? null;
        if (!$videoUrl) {
            throw new \RuntimeException('YouTube video URL is required');
        }

        $videoId = $this->extractVideoId($videoUrl);
        if (!$videoId) {
            throw new \RuntimeException('Invalid YouTube video URL');
        }

        try {
            $videoInfo = $this->getYouTubeVideoInfo($user, $videoId);
            
            return [
                'success' => true,
                'video_info' => $videoInfo,
                'video_id' => $videoId
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to get video info', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to get video info: ' . $e->getMessage());
        }
    }

    /**
     * Clear cached results
     */
    private function clearCachedResults(User $user, array $parameters, array $options): array
    {
        $videoUrl = $parameters['video_url'] ?? null;
        
        if ($videoUrl) {
            $videoId = $this->extractVideoId($videoUrl);
            if ($videoId) {
                $cacheKey = sprintf('youtube_analysis_%s', $videoId);
                $this->cache->deleteItem($cacheKey);
            }
        }

        return [
            'success' => true,
            'message' => 'Cache cleared successfully'
        ];
    }

    /**
     * Extract video ID from YouTube URL
     */
    private function extractVideoId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Get video information from YouTube using oEmbed API
     */
    private function getYouTubeVideoInfo(User $user, string $videoId): array
    {
        try {
            // Use YouTube oEmbed API (no API key required)
            $oembedUrl = sprintf('https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=%s&format=json', $videoId);
            
            $response = $this->requestBuilder->forService($user, 'youtube', $this->getInternetConfig())
                ->get($oembedUrl, [
                    'headers' => [
                        'User-Agent' => 'IGPro/1.0'
                    ]
                ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Failed to fetch video information from YouTube');
            }

            $data = $response->toArray();
            
            return [
                'video_id' => $videoId,
                'title' => $data['title'] ?? 'Unknown Title',
                'author_name' => $data['author_name'] ?? 'Unknown Channel',
                'author_url' => $data['author_url'] ?? null,
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
                'thumbnail_width' => $data['thumbnail_width'] ?? null,
                'thumbnail_height' => $data['thumbnail_height'] ?? null,
                'duration' => null, // oEmbed doesn't provide duration
                'view_count' => null, // oEmbed doesn't provide view count
                'description' => null, // oEmbed doesn't provide description
                'fetched_at' => (new \DateTimeImmutable())->format('c')
            ];

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to get video information: ' . $e->getMessage());
        }
    }

    /**
     * Download original YouTube thumbnail to use as reference
     */
    private function downloadOriginalThumbnail(string $thumbnailUrl, string $videoId): string
    {
        try {
            // Create directory for this video's thumbnails
            $pluginDir = $this->getPluginDirectory();
            $videoDir = $pluginDir . '/' . $videoId;
            if (!is_dir($videoDir)) {
                mkdir($videoDir, 0755, true);
            }
            
            // Download original thumbnail
            $originalThumbnailPath = $videoDir . '/original.jpg';
            
            // Use file_get_contents to download the image
            $imageData = file_get_contents($thumbnailUrl);
            if ($imageData === false) {
                throw new \RuntimeException('Failed to download original thumbnail');
            }
            
            file_put_contents($originalThumbnailPath, $imageData);
            
            return $originalThumbnailPath;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to download original thumbnail: ' . $e->getMessage());
        }
    }

    /**
     * Convert thumbnail to PNG format for better OpenAI compatibility
     */
    private function convertThumbnailToPng(string $originalPath, string $videoId): string
    {
        try {
            // If already PNG, just return the path
            $imageInfo = getimagesize($originalPath);
            if ($imageInfo && $imageInfo[2] === IMAGETYPE_PNG) {
                return $originalPath;
            }
            
            // Create PNG version
            $pngPath = str_replace(['.jpg', '.jpeg'], '.png', $originalPath);
            
            // Load the image based on type
            $image = null;
            if ($imageInfo) {
                switch ($imageInfo[2]) {
                    case IMAGETYPE_JPEG:
                        $image = imagecreatefromjpeg($originalPath);
                        break;
                    case IMAGETYPE_GIF:
                        $image = imagecreatefromgif($originalPath);
                        break;
                    case IMAGETYPE_WEBP:
                        $image = imagecreatefromwebp($originalPath);
                        break;
                }
            }
            
            if (!$image) {
                // If we can't convert, just use the original
                $this->logger->warning('Could not convert thumbnail to PNG, using original', [
                    'video_id' => $videoId,
                    'original_path' => $originalPath
                ]);
                return $originalPath;
            }
            
            // Save as PNG
            if (imagepng($image, $pngPath)) {
                imagedestroy($image);
                return $pngPath;
            } else {
                imagedestroy($image);
                return $originalPath;
            }
            
        } catch (\Exception $e) {
            $this->logger->warning('Failed to convert thumbnail to PNG', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return $originalPath;
        }
    }

    /**
     * Generate thumbnail variations using OpenAI gpt-image-1 with edits endpoint
     * Uses correct OpenAI parameters and processes base64 responses with media processing
     */
    private function generateThumbnailVariationsWithOpenAI(User $user, array $videoInfo, string $originalThumbnailPath, ?string $customPrompt, int $count, string $style): array
    {
        try {
            // Ensure count is within OpenAI limits (1-10 images)
            $count = max(1, min(10, $count));
            
            $prompt = $this->buildEditPrompt($videoInfo, $customPrompt, $style);
            
            $this->logger->info('Preparing OpenAI request', [
                'prompt_length' => strlen($prompt),
                'image_count' => $count,
                'original_thumbnail_size' => filesize($originalThumbnailPath),
                'prompt_preview' => substr($prompt, 0, 200) . '...'
            ]);
            
            // Prepare multipart form data for the edits endpoint (corrected according to OpenAI docs)
            
            $multipartData = [
                'image' => fopen($originalThumbnailPath, 'r'),
                'prompt' => $prompt,
                'model' => 'gpt-image-1',
                'size' => '1536x1024', // gpt-image-1 supports: 1024x1024, 1536x1024 (landscape), 1024x1536 (portrait), or auto
                'n' => (string)$count,
                'output_format' => 'png', // gpt-image-1 supports: png, jpeg, webp
                'quality' => 'high', // gpt-image-1 supports: high, medium, low
                'background' => 'opaque' // gpt-image-1 supports: transparent, opaque, auto
            ];

            $this->logger->info('Sending request to OpenAI', [
                'endpoint' => self::OPENAI_API_URL . '/images/edits',
                'parameters' => [
                    'model' => 'gpt-image-1',
                    'size' => '1536x1024',
                    'n' => $count,
                    'output_format' => 'png',
                    'quality' => 'high',
                    'background' => 'opaque'
                ]
            ]);
            
            $response = $this->requestBuilder->forService($user, 'openai', $this->getInternetConfig())
                ->post(self::OPENAI_API_URL . '/images/edits', [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'body' => $multipartData
                ]);

            if ($response->getStatusCode() !== 200) {
                $errorContent = $response->getContent(false);
                $this->logger->error('OpenAI image edit generation failed', [
                    'status_code' => $response->getStatusCode(),
                    'response' => $errorContent,
                    'request_url' => self::OPENAI_API_URL . '/images/edits',
                    'prompt' => $prompt,
                    'count' => $count,
                    'style' => $style
                ]);
                
                // Try to parse error details from OpenAI response
                $errorDetails = 'Unknown error';
                try {
                    $errorData = json_decode($errorContent, true);
                    if (isset($errorData['error']['message'])) {
                        $errorDetails = $errorData['error']['message'];
                    }
                } catch (\Exception $e) {
                    // Ignore JSON parsing errors
                }
                
                throw new \RuntimeException(sprintf('OpenAI API Error (HTTP %d): %s', $response->getStatusCode(), $errorDetails));
            }

            $data = $response->toArray();
            $thumbnailVariations = [];
            
            // Process all generated images (gpt-image-1 returns base64, not URLs)
            foreach ($data['data'] as $index => $imageData) {
                // gpt-image-1 returns base64-encoded images
                $base64Image = $imageData['b64_json'] ?? null;
                
                if ($base64Image) {
                    // Process the base64 image and create variations
                    $processedImages = $this->processAndStoreImage($base64Image, $videoInfo['video_id'], $index + 1);
                    
                    $thumbnailVariations[] = [
                        'id' => uniqid('thumb_'),
                        'title' => sprintf('Thumbnail Variation %d', $index + 1),
                        'prompt' => $prompt,
                        'image_url' => $processedImages['full_size']['url'],
                        'local_path' => $processedImages['full_size']['path'],
                        'preview_url' => $processedImages['preview']['url'],
                        'preview_path' => $processedImages['preview']['path'],
                        'thumbnail_url' => $processedImages['thumbnail']['url'],
                        'thumbnail_path' => $processedImages['thumbnail']['path'],
                        'original_size' => '1536x1024', // OpenAI generated size
                        'youtube_size' => '1792x1024',  // YouTube optimal size
                        'style' => $style,
                        'created_at' => (new \DateTimeImmutable())->format('c')
                    ];
                }
            }
            
            return $thumbnailVariations;

        } catch (ClientExceptionInterface | TransportExceptionInterface $e) {
            throw new \RuntimeException('Failed to generate thumbnail variations with OpenAI: ' . $e->getMessage());
        }
    }

    /**
     * Generate thumbnail variations using Replicate Google Imagen 4 Ultra
     * Uses sequential processing with extended timeouts to avoid browser/server timeouts
     * Note: This operation can take 5-10 minutes for multiple thumbnails
     */
    private function generateThumbnailVariationsWithReplicate(User $user, array $videoInfo, ?string $customPrompt, int $count, string $style): array
    {
        try {
            $count = max(1, min(10, $count)); // Reasonable limits
            
            // Increase PHP execution time for this operation
            $originalTimeLimit = ini_get('max_execution_time');
            ini_set('max_execution_time', 600); // 10 minutes
            
            $this->logger->info('Starting Replicate thumbnail generation', [
                'video_id' => $videoInfo['video_id'],
                'count' => $count,
                'style' => $style,
                'execution_time_limit' => 600,
                'original_time_limit' => $originalTimeLimit
            ]);

            $thumbnailVariations = [];
            
            // Use a single prompt for all variations to test if Replicate generates different images
            $prompt = $this->buildReplicatePrompt($videoInfo, $customPrompt, $style);
            
            $this->logger->info('Using single prompt for all variations', [
                'video_id' => $videoInfo['video_id'],
                'prompt_length' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 200) . '...'
            ]);
            
            // Use sequential processing instead of concurrent to avoid timeouts
            for ($i = 1; $i <= $count; $i++) {
                $this->sendProgressUpdate("Processing thumbnail variation $i of $count", [
                    'video_id' => $videoInfo['video_id'],
                    'variation' => $i,
                    'total_count' => $count
                ]);
                
                try {
                    $imageUrl = $this->callReplicateAPISingle($user, $prompt, $i);
                    
                    if ($imageUrl) {
                        $this->sendProgressUpdate("Downloading and processing image for variation $i");
                        
                        // Download and process the generated image
                        $processedImages = $this->downloadAndProcessReplicateImage($imageUrl, $videoInfo['video_id'], $i);
                        
                        $thumbnailVariations[] = [
                            'id' => uniqid('thumb_replicate_'),
                            'title' => sprintf('Thumbnail Variation %d (Replicate)', $i),
                            'prompt' => $prompt,
                            'image_url' => $processedImages['full_size']['url'],
                            'local_path' => $processedImages['full_size']['path'],
                            'preview_url' => $processedImages['preview']['url'],
                            'preview_path' => $processedImages['preview']['path'],
                            'thumbnail_url' => $processedImages['thumbnail']['url'],
                            'thumbnail_path' => $processedImages['thumbnail']['path'],
                            'original_size' => '1536x864', // 16:9 aspect ratio from Imagen 4
                            'youtube_size' => '1792x1024',  // YouTube optimal size
                            'style' => $style,
                            'generation_method' => 'replicate-imagen4',
                            'created_at' => (new \DateTimeImmutable())->format('c')
                        ];
                        
                        $this->sendProgressUpdate("Successfully completed variation $i");
                    } else {
                        $this->sendProgressUpdate("Failed to generate variation $i - skipping");
                    }
                } catch (\Exception $e) {
                    $this->sendProgressUpdate("Error in variation $i: " . $e->getMessage());
                    // Continue with next variation instead of failing completely
                }
                
                // Add small delay between requests to be respectful
                if ($i < $count) {
                    $this->sendProgressUpdate("Waiting before next request...");
                    sleep(2); // 2 second delay
                }
            }
            
            $this->logger->info('Replicate thumbnail generation completed', [
                'video_id' => $videoInfo['video_id'],
                'variations_generated' => count($thumbnailVariations),
                'requested_count' => $count
            ]);

            // Restore original time limit
            ini_set('max_execution_time', $originalTimeLimit);

            return $thumbnailVariations;

        } catch (\Exception $e) {
            // Restore original time limit in case of error
            if (isset($originalTimeLimit)) {
                ini_set('max_execution_time', $originalTimeLimit);
            }
            
            $this->logger->error('Failed to generate thumbnails with Replicate', [
                'video_id' => $videoInfo['video_id'],
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to generate thumbnails with Replicate: ' . $e->getMessage());
        }
    }

    /**
     * Call Replicate API to generate a single thumbnail
     */
    private function callReplicateAPI(User $user, string $prompt): ?string
    {
        try {
            $replicateToken = $_ENV['REPLICATE_API_TOKEN'] ?? '';
            if (empty($replicateToken)) {
                throw new \RuntimeException('REPLICATE_API_TOKEN not configured');
            }

            $requestData = [
                'input' => [
                    'prompt' => $prompt,
                    'aspect_ratio' => '16:9'
                ]
            ];

            $this->logger->info('Calling Replicate API', [
                'model' => 'google/imagen-4-ultra',
                'prompt_length' => strlen($prompt),
                'aspect_ratio' => '16:9'
            ]);

            // Since we need to use the system token (not user credentials), we'll use curl directly
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => self::REPLICATE_API_URL . '/models/google/imagen-4-ultra/predictions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $replicateToken,
                    'Content-Type: application/json',
                    'Prefer: wait'
                ],
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_TIMEOUT => 120, // 2 minute timeout for image generation
                CURLOPT_USERAGENT => 'IGPro/1.0'
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($response === false || !empty($error)) {
                $this->logger->error('cURL error in Replicate API request', [
                    'error' => $error,
                    'http_code' => $httpCode
                ]);
                throw new \RuntimeException('HTTP request failed: ' . $error);
            }

            if ($httpCode !== 200 && $httpCode !== 201) {
                $this->logger->error('Replicate API request failed', [
                    'status_code' => $httpCode,
                    'response' => $response
                ]);
                throw new \RuntimeException(sprintf('Replicate API Error (HTTP %d): %s', $httpCode, $response));
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Failed to decode JSON response from Replicate API');
            }
            
            // Extract the image URL from the response
            // Imagen 4 Ultra returns the image URL directly as a string in the output field
            $imageUrl = null;
            if (isset($data['output']) && is_string($data['output'])) {
                $imageUrl = $data['output'];
            } elseif (isset($data['urls']['get'])) {
                // If the prediction is still processing, we might need to poll
                $this->logger->warning('Replicate prediction requires polling - not implemented yet', [
                    'prediction_id' => $data['id'] ?? 'unknown',
                    'status' => $data['status'] ?? 'unknown'
                ]);
            }

            if (!$imageUrl) {
                $this->logger->error('No image URL found in Replicate response', [
                    'response_data' => $data
                ]);
                return null;
            }

            $this->logger->info('Successfully generated image with Replicate', [
                'image_url' => $imageUrl,
                'prediction_id' => $data['id'] ?? 'unknown'
            ]);

            return $imageUrl;

        } catch (\Exception $e) {
            $this->logger->error('Replicate API call failed', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt)
            ]);
            return null;
        }
    }

    /**
     * Call Replicate API for a single thumbnail with longer timeout
     */
    private function callReplicateAPISingle(User $user, string $prompt, int $variationNumber): ?string
    {
        try {
            $replicateToken = $_ENV['REPLICATE_API_TOKEN'] ?? '';
            if (empty($replicateToken)) {
                throw new \RuntimeException('REPLICATE_API_TOKEN not configured');
            }

            $requestData = [
                'input' => [
                    'prompt' => $prompt,
                    'aspect_ratio' => '16:9'
                ]
            ];

            $this->logger->info('Calling Replicate API (single request)', [
                'variation' => $variationNumber,
                'prompt_length' => strlen($prompt),
                'aspect_ratio' => '16:9'
            ]);

            // Use longer timeout for individual requests
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => self::REPLICATE_API_URL . '/models/google/imagen-4-ultra/predictions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $replicateToken,
                    'Content-Type: application/json',
                    'Prefer: wait'
                ],
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_TIMEOUT => 300, // 5 minute timeout for single request
                CURLOPT_CONNECTTIMEOUT => 30, // 30 seconds to connect
                CURLOPT_USERAGENT => 'IGPro/1.0',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_VERBOSE => false
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            $totalTime = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
            curl_close($curl);

            $this->logger->info('Replicate API response received', [
                'variation' => $variationNumber,
                'http_code' => $httpCode,
                'total_time' => $totalTime,
                'has_error' => !empty($error)
            ]);

            if ($response === false || !empty($error)) {
                $this->logger->error('cURL error in Replicate API request', [
                    'variation' => $variationNumber,
                    'error' => $error,
                    'http_code' => $httpCode
                ]);
                return null;
            }

            if ($httpCode !== 200 && $httpCode !== 201) {
                $this->logger->error('Replicate API request failed', [
                    'variation' => $variationNumber,
                    'status_code' => $httpCode,
                    'response' => substr($response, 0, 500)
                ]);
                return null;
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Failed to decode JSON response from Replicate API', [
                    'variation' => $variationNumber,
                    'json_error' => json_last_error_msg()
                ]);
                return null;
            }

            // Extract the image URL from the response
            $imageUrl = null;
            if (isset($data['output']) && is_string($data['output'])) {
                $imageUrl = $data['output'];
            } elseif (isset($data['urls']['get'])) {
                // If the prediction is still processing, we might need to poll
                $this->logger->warning('Replicate prediction requires polling - not implemented yet', [
                    'variation' => $variationNumber,
                    'prediction_id' => $data['id'] ?? 'unknown',
                    'status' => $data['status'] ?? 'unknown'
                ]);
                return null;
            }

            if (!$imageUrl) {
                $this->logger->error('No image URL found in Replicate response', [
                    'variation' => $variationNumber,
                    'response_keys' => array_keys($data)
                ]);
                return null;
            }

            $this->logger->info('Successfully generated image with Replicate', [
                'variation' => $variationNumber,
                'image_url' => substr($imageUrl, 0, 100) . '...',
                'prediction_id' => $data['id'] ?? 'unknown'
            ]);

            return $imageUrl;

        } catch (\Exception $e) {
            $this->logger->error('Replicate API call failed', [
                'variation' => $variationNumber,
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt)
            ]);
            return null;
        }
    }

    /**
     * Call Replicate API multiple times concurrently to generate multiple thumbnails
     */
    private function callReplicateAPIMultiple(User $user, array $prompts): array
    {
        try {
            $replicateToken = $_ENV['REPLICATE_API_TOKEN'] ?? '';
            if (empty($replicateToken)) {
                throw new \RuntimeException('REPLICATE_API_TOKEN not configured');
            }

            $this->logger->info('Starting concurrent Replicate API calls', [
                'count' => count($prompts),
                'model' => 'google/imagen-4-ultra'
            ]);

            // Initialize curl multi handle
            $multiHandle = curl_multi_init();
            $curlHandles = [];
            $imageUrls = [];

            // Create curl handles for each request
            foreach ($prompts as $index => $prompt) {
                $requestData = [
                    'input' => [
                        'prompt' => $prompt,
                        'aspect_ratio' => '16:9'
                    ]
                ];

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => self::REPLICATE_API_URL . '/models/google/imagen-4-ultra/predictions',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $replicateToken,
                        'Content-Type: application/json',
                        'Prefer: wait'
                    ],
                    CURLOPT_POSTFIELDS => json_encode($requestData),
                    CURLOPT_TIMEOUT => 180, // 3 minute timeout for image generation
                    CURLOPT_USERAGENT => 'IGPro/1.0'
                ]);

                curl_multi_add_handle($multiHandle, $curl);
                $curlHandles[$index] = $curl;
            }

            // Execute all requests concurrently
            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while ($running > 0);

            // Collect responses
            foreach ($curlHandles as $index => $curl) {
                $response = curl_multi_getcontent($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $error = curl_error($curl);

                curl_multi_remove_handle($multiHandle, $curl);
                curl_close($curl);

                if ($response === false || !empty($error)) {
                    $this->logger->error('cURL error in Replicate API request', [
                        'index' => $index,
                        'error' => $error,
                        'http_code' => $httpCode
                    ]);
                    $imageUrls[$index] = null;
                    continue;
                }

                if ($httpCode !== 200 && $httpCode !== 201) {
                    $this->logger->error('Replicate API request failed', [
                        'index' => $index,
                        'status_code' => $httpCode,
                        'response' => substr($response, 0, 500)
                    ]);
                    $imageUrls[$index] = null;
                    continue;
                }

                $data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logger->error('Failed to decode JSON response from Replicate API', [
                        'index' => $index,
                        'json_error' => json_last_error_msg()
                    ]);
                    $imageUrls[$index] = null;
                    continue;
                }

                // Extract the image URL (Imagen 4 Ultra returns a string, not array)
                if (isset($data['output']) && is_string($data['output'])) {
                    $imageUrls[$index] = $data['output'];
                    $this->logger->info('Successfully generated image with Replicate', [
                        'index' => $index,
                        'image_url' => substr($data['output'], 0, 100) . '...',
                        'prediction_id' => $data['id'] ?? 'unknown'
                    ]);
                } else {
                    $this->logger->error('No valid image URL found in Replicate response', [
                        'index' => $index,
                        'response_data' => $data
                    ]);
                    $imageUrls[$index] = null;
                }
            }

            curl_multi_close($multiHandle);

            $successCount = count(array_filter($imageUrls, fn($url) => $url !== null));
            $this->logger->info('Completed concurrent Replicate API calls', [
                'total_requests' => count($prompts),
                'successful_requests' => $successCount,
                'failed_requests' => count($prompts) - $successCount
            ]);

            return $imageUrls;

        } catch (\Exception $e) {
            $this->logger->error('Multiple Replicate API calls failed', [
                'error' => $e->getMessage(),
                'prompt_count' => count($prompts)
            ]);
            // Return array with nulls if everything fails
            return array_fill_keys(array_keys($prompts), null);
        }
    }

    /**
     * Validate thumbnail generation parameters
     */
    private function validateThumbnailParameters(array $parameters): array
    {
        $errors = [];
        
        // Validate thumbnail count
        $thumbnailCount = $parameters['thumbnail_count'] ?? 4;
        if (!is_int($thumbnailCount) && !is_numeric($thumbnailCount)) {
            $errors[] = 'thumbnail_count must be a number';
        } else {
            $thumbnailCount = (int) $thumbnailCount;
            if ($thumbnailCount < 1 || $thumbnailCount > 10) {
                $errors[] = 'thumbnail_count must be between 1 and 10';
            }
        }
        
        // Validate style
        $style = $parameters['style'] ?? 'modern';
        $validStyles = ['modern', 'dramatic', 'minimalist', 'colorful', 'professional'];
        if (!in_array($style, $validStyles)) {
            $errors[] = 'style must be one of: ' . implode(', ', $validStyles);
        }
        
        // Validate custom prompt length
        $customPrompt = $parameters['custom_prompt'] ?? null;
        if ($customPrompt !== null && strlen($customPrompt) > 500) {
            $errors[] = 'custom_prompt must be less than 500 characters';
        }
        
        return $errors;
    }

    /**
     * Process base64 image from OpenAI and create multiple sizes
     */
    private function processAndStoreImage(string $base64Image, string $videoId, int $variationNumber): array
    {
        try {
            // Create directory for this video
            $pluginDir = $this->getPluginDirectory();
            $videoDir = $pluginDir . '/' . $videoId;
            if (!is_dir($videoDir)) {
                mkdir($videoDir, 0755, true);
            }
            
            // Decode base64 image
            $imageData = base64_decode($base64Image);
            if ($imageData === false) {
                throw new \RuntimeException('Failed to decode base64 image');
            }
            
            // Generate filename
            $filename = sprintf('thumbnail_%s_openai_%d_%s.png', $videoId, $variationNumber, uniqid());
            $fullSizePath = $videoDir . '/' . $filename;
            
            // Save the full-size image
            if (file_put_contents($fullSizePath, $imageData) === false) {
                throw new \RuntimeException('Failed to save full-size image');
            }
            
            // Create different sizes for YouTube
            $sizes = [
                'preview' => ['width' => 640, 'height' => 360],    // Preview size
                'thumbnail' => ['width' => 320, 'height' => 180],  // Thumbnail size
                'youtube' => ['width' => 1792, 'height' => 1024]   // YouTube optimal size
            ];
            
            $processedImages = [
                'full_size' => [
                    'path' => $fullSizePath,
                    'url' => $this->getFileUrl($fullSizePath),
                    'width' => 1536,
                    'height' => 1024
                ]
            ];
            
            // Create resized versions using GD
            $sourceImage = imagecreatefrompng($fullSizePath);
            if (!$sourceImage) {
                throw new \RuntimeException('Failed to create image resource from PNG');
            }
            
            foreach ($sizes as $sizeName => $dimensions) {
                $resizedPath = str_replace('.png', '_' . $sizeName . '.png', $fullSizePath);
                
                // Create resized image
                $resizedImage = imagecreatetruecolor($dimensions['width'], $dimensions['height']);
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                
                if (imagecopyresampled(
                    $resizedImage, $sourceImage,
                    0, 0, 0, 0,
                    $dimensions['width'], $dimensions['height'],
                    1536, 1024  // Source dimensions from OpenAI
                )) {
                    imagepng($resizedImage, $resizedPath);
                    
                    $processedImages[$sizeName] = [
                        'path' => $resizedPath,
                        'url' => $this->getFileUrl($resizedPath),
                        'width' => $dimensions['width'],
                        'height' => $dimensions['height']
                    ];
                }
                
                imagedestroy($resizedImage);
            }
            
            imagedestroy($sourceImage);
            
            return $processedImages;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to process and store OpenAI image', [
                'video_id' => $videoId,
                'variation' => $variationNumber,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to process image: ' . $e->getMessage());
        }
    }

    /**
     * Convert file path to accessible URL
     */
    private function getFileUrl(string $filePath): string
    {
        // Convert absolute path to relative URL
        $relativePath = str_replace($this->projectDir . '/public', '', $filePath);
        $request = $this->requestStack->getCurrentRequest();
        
        if ($request) {
            $baseUrl = $request->getSchemeAndHttpHost();
            return $baseUrl . $relativePath;
        }
        
        return $relativePath;
    }

    protected function getDefaultName(): string
    {
        return 'YouTube Thumbnail Generator';
    }

    protected function getDefaultDescription(): string
    {
        return 'AI-powered YouTube thumbnail generator using Replicate (Google Imagen 4 Ultra) or OpenAI';
    }

    protected function getDefaultVersion(): string
    {
        return '1.0.0';
    }

    protected function getDefaultIcon(): string
    {
        return 'youtube';
    }

    protected function getDefaultSupportedCommands(): array
    {
        return ['analyze_video', 'generate_thumbnail_variations', 'get_video_info', 'clear_cache'];
    }

    protected function getDefaultRequirements(): array
    {
        return ['openai_api_key'];
    }

    /**
     * Build edit prompt for OpenAI image edits
     */
    private function buildEditPrompt(array $videoInfo, ?string $customPrompt, string $style): string
    {
        $title = $videoInfo['title'] ?? 'Unknown Title';
        $channel = $videoInfo['author_name'] ?? 'Unknown Channel';
        
        // Base prompt for YouTube thumbnail
        $basePrompt = sprintf(
            "Create compelling YouTube thumbnail variations for a video titled '%s' by '%s'. ",
            $title,
            $channel
        );

        // Style-specific prompts
        $stylePrompts = [
            'modern' => 'Modern design with bold typography, vibrant colors, clean layout, and professional look.',
            'dramatic' => 'Dramatic lighting, high contrast, intense colors, emotional expressions, and cinematic feel.',
            'minimalist' => 'Minimalist design with simple elements, clean typography, lots of white space, and subtle colors.',
            'colorful' => 'Bright, eye-catching colors, dynamic compositions, energetic feel, and bold visual elements.',
            'professional' => 'Professional, polished look with sophisticated typography, balanced composition, and premium feel.'
        ];

        $styleInstruction = $stylePrompts[$style] ?? $stylePrompts['modern'];
        $prompt = $basePrompt . $styleInstruction;
        
        // Add variation instructions for multiple thumbnails
        $prompt .= ' Generate different creative variations with diverse compositions: some focusing on the main subject with large text overlays, others using split-screen layouts, action-packed scenes, emotional expressions, and comparison-style compositions.';
        
        if ($customPrompt) {
            $prompt .= sprintf(' Additional requirements: %s', $customPrompt);
        }

        // Add technical specifications for thumbnails
        $prompt .= ' Make sure all designs will be clearly visible at small sizes, with high contrast text that\'s easy to read. Include engaging visual elements that would make someone want to click. Aspect ratio should be 16:9 for YouTube thumbnails.';

        // Ensure prompt doesn't exceed gpt-image-1 limits (32000 characters max)
        if (strlen($prompt) > 31000) {
            $prompt = substr($prompt, 0, 31000) . '...';
        }

        return $prompt;
    }

    /**
     * Build prompt for Replicate Google Imagen 4 Ultra
     * Note: Replicate/Imagen does not support image variations or reference images
     */
    private function buildReplicatePrompt(array $videoInfo, ?string $customPrompt, string $style, ?int $variationIndex = null, ?int $totalVariations = null): string
    {
        $title = $videoInfo['title'] ?? 'Unknown Title';
        $channel = $videoInfo['author_name'] ?? 'Unknown Channel';
        
        // Base prompt for YouTube thumbnail - more descriptive since no reference image
        $basePrompt = sprintf(
            "Create a high-quality YouTube thumbnail image for a video titled '%s' by '%s'. ",
            $title,
            $channel
        );

        // Style-specific prompts adapted for Imagen 4 Ultra
        $stylePrompts = [
            'modern' => 'Modern, clean design with bold sans-serif typography, vibrant gradient colors, minimalist layout, and professional aesthetic. Sharp focus, high contrast.',
            'dramatic' => 'Dramatic cinematic lighting with deep shadows and bright highlights, intense saturated colors, emotional facial expressions, and dynamic composition. Film-like quality.',
            'minimalist' => 'Minimalist design with simple geometric elements, clean sans-serif typography, plenty of white space, and muted color palette. Elegant and refined.',
            'colorful' => 'Bright, vivid colors with rainbow gradients, dynamic energetic composition, bold graphic elements, and playful design. Eye-catching and vibrant.',
            'professional' => 'Professional, corporate aesthetic with sophisticated color scheme, balanced composition, clean typography, and premium visual quality. Polished and refined.'
        ];

        $styleInstruction = $stylePrompts[$style] ?? $stylePrompts['modern'];
        $prompt = $basePrompt . $styleInstruction;
        
        if ($customPrompt) {
            $prompt .= sprintf(' Additional requirements: %s', $customPrompt);
        }

        // Add variation specifics to create different compositions for each iteration (only if specified)
        if ($variationIndex !== null && $totalVariations !== null) {
            $variationPrompts = [
                1 => ' Focus on large, bold text overlay with the title prominently displayed.',
                2 => ' Feature split-screen or comparison-style layout with contrasting elements.',
                3 => ' Emphasize action or movement with dynamic angles and energy.',
                4 => ' Create emotional appeal with expressive faces or compelling scenes.',
                5 => ' Use geometric shapes and modern graphic design elements.',
            ];
            
            $variationPrompt = $variationPrompts[$variationIndex] ?? $variationPrompts[($variationIndex % 5) + 1];
            $prompt .= $variationPrompt;
        }

        // Add technical specifications for YouTube thumbnails
        $prompt .= ' High resolution, 16:9 aspect ratio, optimized for small viewing sizes with high contrast and readability. Professional quality suitable for YouTube platform.';

        // Imagen 4 Ultra has shorter prompt limits compared to OpenAI
        if (strlen($prompt) > 500) {
            $prompt = substr($prompt, 0, 500);
        }

        return $prompt;
    }

    /**
     * Process image from Replicate and create multiple sizes
     */
    private function processReplicateImage(string $imagePath, string $videoId, int $variationNumber): array
    {
        try {
            // Get image dimensions
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                throw new \RuntimeException('Failed to get image information');
            }
            
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            
            // Create different sizes for YouTube
            $sizes = [
                'preview' => ['width' => 640, 'height' => 360],    // Preview size
                'thumbnail' => ['width' => 320, 'height' => 180],  // Thumbnail size
                'youtube' => ['width' => 1792, 'height' => 1024]   // YouTube optimal size
            ];
            
            $processedImages = [
                'full_size' => [
                    'path' => $imagePath,
                    'url' => $this->getFileUrl($imagePath),
                    'width' => $originalWidth,
                    'height' => $originalHeight
                ]
            ];
            
            // Load source image based on type
            $sourceImage = null;
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = imagecreatefrompng($imagePath);
                    break;
                case IMAGETYPE_WEBP:
                    $sourceImage = imagecreatefromwebp($imagePath);
                    break;
                default:
                    throw new \RuntimeException('Unsupported image format');
            }
            
            if (!$sourceImage) {
                throw new \RuntimeException('Failed to create image resource');
            }
            
            // Create resized versions
            foreach ($sizes as $sizeName => $dimensions) {
                $resizedPath = str_replace('.png', '_' . $sizeName . '.png', $imagePath);
                
                // Create resized image
                $resizedImage = imagecreatetruecolor($dimensions['width'], $dimensions['height']);
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                
                if (imagecopyresampled(
                    $resizedImage, $sourceImage,
                    0, 0, 0, 0,
                    $dimensions['width'], $dimensions['height'],
                    $originalWidth, $originalHeight
                )) {
                    imagepng($resizedImage, $resizedPath);
                    
                    $processedImages[$sizeName] = [
                        'path' => $resizedPath,
                        'url' => $this->getFileUrl($resizedPath),
                        'width' => $dimensions['width'],
                        'height' => $dimensions['height']
                    ];
                }
                
                imagedestroy($resizedImage);
            }
            
            imagedestroy($sourceImage);
            
            return $processedImages;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to process Replicate image', [
                'image_path' => $imagePath,
                'video_id' => $videoId,
                'variation' => $variationNumber,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to process Replicate image: ' . $e->getMessage());
        }
    }

    /**
     * Download and process image from Replicate
     */
    private function downloadAndProcessReplicateImage(string $imageUrl, string $videoId, int $variationNumber): array
    {
        try {
            // Create directory for this video
            $pluginDir = $this->getPluginDirectory();
            $videoDir = $pluginDir . '/' . $videoId;
            if (!is_dir($videoDir)) {
                mkdir($videoDir, 0755, true);
            }
            
            // Download the image
            $imageContent = file_get_contents($imageUrl);
            if ($imageContent === false) {
                throw new \RuntimeException('Failed to download image from Replicate');
            }
            
            // Generate filename
            $filename = sprintf('thumbnail_%s_replicate_%d_%s.png', $videoId, $variationNumber, uniqid());
            $localPath = $videoDir . '/' . $filename;
            
            // Save locally
            if (file_put_contents($localPath, $imageContent) === false) {
                throw new \RuntimeException('Failed to save image locally');
            }
            
            // Process the image to create different sizes
            return $this->processReplicateImage($localPath, $videoId, $variationNumber);

        } catch (\Exception $e) {
            $this->logger->error('Failed to download and process Replicate image', [
                'image_url' => $imageUrl,
                'video_id' => $videoId,
                'variation' => $variationNumber,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to process Replicate image: ' . $e->getMessage());
        }
    }

    /**
     * Call Replicate API multiple times with the same prompt for variations
     */
    private function callMultipleReplicateAPI(User $user, string $prompt, int $count): array
    {
        try {
            $replicateToken = $_ENV['REPLICATE_API_TOKEN'] ?? '';
            if (empty($replicateToken)) {
                throw new \RuntimeException('REPLICATE_API_TOKEN not configured');
            }

            $requestData = [
                'input' => [
                    'prompt' => $prompt,
                    'aspect_ratio' => '16:9'
                ]
            ];

            $this->logger->info('Making multiple concurrent Replicate API calls', [
                'count' => $count,
                'prompt_length' => strlen($prompt),
                'aspect_ratio' => '16:9'
            ]);

            // Prepare multiple curl handles
            $multiHandle = curl_multi_init();
            $curlHandles = [];
            $imageUrls = [];

            // Initialize all curl handles
            for ($i = 0; $i < $count; $i++) {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => self::REPLICATE_API_URL . '/models/google/imagen-4-ultra/predictions',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $replicateToken,
                        'Content-Type: application/json',
                        'Prefer: wait'
                    ],
                    CURLOPT_POSTFIELDS => json_encode($requestData),
                    CURLOPT_TIMEOUT => 120, // 2 minute timeout for image generation
                    CURLOPT_USERAGENT => 'IGPro/1.0'
                ]);
                
                curl_multi_add_handle($multiHandle, $curl);
                $curlHandles[$i] = $curl;
            }

            // Execute all requests concurrently
            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while ($running > 0);

            // Collect all responses
            for ($i = 0; $i < $count; $i++) {
                $response = curl_multi_getcontent($curlHandles[$i]);
                $httpCode = curl_getinfo($curlHandles[$i], CURLINFO_HTTP_CODE);
                $error = curl_error($curlHandles[$i]);
                
                if ($response !== false && empty($error) && ($httpCode === 200 || $httpCode === 201)) {
                    $data = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Extract the image URL from the response
                        $imageUrl = null;
                        if (isset($data['output']) && is_string($data['output'])) {
                            $imageUrl = $data['output'];
                        }
                        
                        if ($imageUrl) {
                            $imageUrls[] = $imageUrl;
                            $this->logger->info('Successfully generated image with Replicate', [
                                'request_index' => $i,
                                'image_url' => substr($imageUrl, 0, 100) . '...',
                                'prediction_id' => $data['id'] ?? 'unknown'
                            ]);
                        } else {
                            $this->logger->warning('No image URL found in Replicate response', [
                                'request_index' => $i,
                                'response_data' => $data
                            ]);
                        }
                    } else {
                        $this->logger->error('Failed to decode JSON response from Replicate API', [
                            'request_index' => $i,
                            'response' => substr($response, 0, 500)
                        ]);
                    }
                } else {
                    $this->logger->error('Replicate API request failed', [
                        'request_index' => $i,
                        'http_code' => $httpCode,
                        'error' => $error,
                        'response' => substr($response ?: '', 0, 500)
                    ]);
                }
                
                curl_multi_remove_handle($multiHandle, $curlHandles[$i]);
                curl_close($curlHandles[$i]);
            }

            curl_multi_close($multiHandle);

            $this->logger->info('Completed multiple Replicate API calls', [
                'requested_count' => $count,
                'successful_count' => count($imageUrls)
            ]);

            return $imageUrls;

        } catch (\Exception $e) {
            $this->logger->error('Multiple Replicate API calls failed', [
                'error' => $e->getMessage(),
                'count' => $count,
                'prompt_length' => strlen($prompt)
            ]);
            return [];
        }
    }

    /**
     * Generate thumbnail variations asynchronously using background jobs
     */
    private function generateThumbnailVariationsAsync(User $user, array $parameters, array $options): array
    {
        $validationErrors = $this->validateThumbnailParameters($parameters);
        if (!empty($validationErrors)) {
            throw new \RuntimeException(implode(', ', $validationErrors));
        }

        $videoUrl = $parameters['video_url'];
        $thumbnailCount = $parameters['thumbnail_count'] ?? 3;
        $style = $parameters['style'] ?? 'professional';
        $customPrompt = $parameters['custom_prompt'] ?? null;

        try {
            // Generate unique job ID
            $jobId = 'yt_thumb_' . uniqid() . '_' . time();

            // Create and dispatch async message
            $message = new ProcessYoutubeThumbnailMessage(
                jobId: $jobId,
                userId: $user->getId(),
                videoUrl: $videoUrl,
                thumbnailCount: $thumbnailCount,
                style: $style,
                customPrompt: $customPrompt
            );

            $this->messageBus->dispatch($message);

            // Initialize job status
            $this->asyncService->updateJobStatus($jobId, [
                'status' => 'queued',
                'message' => 'YouTube thumbnail generation job queued',
                'progress' => 0,
                'total_variations' => $thumbnailCount,
                'current_variation' => 0,
                'generation_method' => 'determining...',
                'queued_at' => date('c')
            ]);

            $this->logger->info('YouTube thumbnail generation job queued', [
                'job_id' => $jobId,
                'user_id' => $user->getId(),
                'video_url' => $videoUrl,
                'thumbnail_count' => $thumbnailCount
            ]);

            return [
                'success' => true,
                'job_id' => $jobId,
                'status' => 'queued',
                'message' => 'Thumbnail generation started in background',
                'estimated_completion_time' => 'varies (2-15 minutes depending on method)',
                'poll_url' => '/api/plugins/youtube-thumbnail/job-status/' . $jobId
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to queue YouTube thumbnail generation job', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to start thumbnail generation: ' . $e->getMessage());
        }
    }

    /**
     * Get status of an async thumbnail generation job
     */
    private function getJobStatus(User $user, array $parameters, array $options): array
    {
        $jobId = $parameters['job_id'] ?? null;
        if (!$jobId) {
            throw new \RuntimeException('Job ID is required');
        }

        try {
            $status = $this->asyncService->getJobStatus($jobId);
            
            $this->logger->info('Retrieved job status', [
                'job_id' => $jobId,
                'user_id' => $user->getId(),
                'status' => $status['status'] ?? 'unknown'
            ]);

            return [
                'success' => true,
                'job_id' => $jobId,
                ...$status
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to get job status', [
                'job_id' => $jobId,
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to get job status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel an async thumbnail generation job
     */
    private function cancelJob(User $user, array $parameters, array $options): array
    {
        $jobId = $parameters['job_id'] ?? null;
        if (!$jobId) {
            throw new \RuntimeException('Job ID is required');
        }

        try {
            $cancelled = $this->asyncService->cancelJob($jobId);
            
            $this->logger->info('Job cancellation attempted', [
                'job_id' => $jobId,
                'user_id' => $user->getId(),
                'cancelled' => $cancelled
            ]);

            return [
                'success' => $cancelled,
                'job_id' => $jobId,
                'message' => $cancelled ? 'Job cancelled successfully' : 'Failed to cancel job'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel job', [
                'job_id' => $jobId,
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to cancel job: ' . $e->getMessage()
            ];
        }
    }

    // ...existing code...

    /**
     * You can override this method if you need to perform any actions after the plugin is executed
     */
    protected function onAfterExecute(User $user, string $command, array $parameters, array $options, array $result): void
    {
        // Example: Send a progress update after thumbnail generation
        if ($command === 'generate_thumbnail_variations' && !empty($result['thumbnail_variations'])) {
            $this->sendProgressUpdate('Thumbnail generation completed', [
                'video_id' => $result['video_id'],
                'thumbnail_count' => count($result['thumbnail_variations']),
                'generation_method' => $result['generation_method']
            ]);
        }
    }

    /**
     * Send progress update to prevent browser timeout and update async job status
     */
    private function sendProgressUpdate(string $message, array $data = []): void
    {
        // Check if we're running in async mode
        $asyncJobId = $this->getCurrentAsyncJobId();
        
        if ($asyncJobId) {
            // Update async job status with progress
            $progress = $this->calculateProgress($data);
            $this->asyncService->updateJobStatus($asyncJobId, [
                'status' => 'processing',
                'message' => $message,
                'progress' => $progress,
                'current_variation' => $data['variation'] ?? null,
                'total_variations' => $data['total_count'] ?? null,
                'generation_method' => $data['generation_method'] ?? 'unknown',
                'updated_at' => date('c')
            ]);
        } else {
            // Send HTML comment for direct sync requests (backward compatibility)
            if (ob_get_level()) {
                echo "<!-- Progress: $message -->\n";
                ob_flush();
                flush();
            }
        }
        
        $this->logger->info($message, $data);
    }

    /**
     * Get current async job ID from request context
     */
    private function getCurrentAsyncJobId(): ?string
    {
        // This would be set in the options when called from async handler
        return $this->currentAsyncJobId ?? null;
    }

    /**
     * Calculate progress percentage from data
     */
    private function calculateProgress(array $data): int
    {
        if (isset($data['variation'], $data['total_count'])) {
            return min(95, (int)(($data['variation'] / $data['total_count']) * 100));
        }
        return 0;
    }

    private ?string $currentAsyncJobId = null;

    /**
     * Set the current async job ID for progress tracking
     */
    public function setAsyncJobId(?string $jobId): void
    {
        $this->currentAsyncJobId = $jobId;
    }
}