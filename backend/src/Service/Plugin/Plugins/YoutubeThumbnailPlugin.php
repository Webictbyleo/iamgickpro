<?php

declare(strict_types=1);

namespace App\Service\Plugin\Plugins;

use App\Entity\Layer;
use App\Entity\User;
use App\Service\Plugin\Plugins\PluginInterface;
use App\Service\Plugin\PluginService;
use App\Service\Plugin\SecureRequestBuilder;
use App\Service\MediaProcessing\MediaProcessingService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * YouTube Thumbnail Generator Plugin
 * 
 * Implements YouTube video analysis and AI-powered thumbnail generation using OpenAI.
 * Uses the original YouTube thumbnail as reference and generates improved versions
 * using OpenAI's gpt-image-1 model with the edits endpoint.
 */
class YoutubeThumbnailPlugin implements PluginInterface
{
    private const YOUTUBE_API_URL = 'https://www.googleapis.com/youtube/v3';
    private const OPENAI_API_URL = 'https://api.openai.com/v1';
    
    public function __construct(
        private readonly SecureRequestBuilder $requestBuilder,
        private readonly PluginService $pluginService,
        private readonly MediaProcessingService $mediaProcessingService,
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
        return 'YouTube Thumbnail Generator';
    }

    public function getDescription(): string
    {
        return 'Generate 1-10 AI-powered thumbnail variations using the original YouTube thumbnail as reference with OpenAI gpt-image-1';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getIcon(): string
    {
        return '/icons/plugins/youtube-thumbnail.svg';
    }

    public function getSupportedCommands(): array
    {
        return [
            'analyze_video',
            'generate_thumbnail_variations',
            'get_video_info',
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
            throw new \RuntimeException('OpenAI API key not configured. Please configure your API key in settings.');
        }

        return match ($command) {
            'analyze_video' => $this->analyzeVideo($user, $layer, $parameters, $options),
            'generate_thumbnail_variations' => $this->generateThumbnailVariations($user, $layer, $parameters, $options),
            'get_video_info' => $this->getVideoInfo($user, $layer, $parameters, $options),
            'clear_cache' => $this->clearCachedResults($user, $layer, $parameters, $options),
            default => throw new \RuntimeException(sprintf('Unsupported command: %s', $command))
        };
    }

    public function isAvailableForUser(User $user): bool
    {
        return true; // Available to all users, but requires OpenAI API key configuration
    }

    public function getRequirements(): array
    {
        return [
            'integrations' => ['openai'],
            'layer_types' => ['image'],
            'permissions' => ['layer.edit']
        ];
    }

    public function validateRequirements(User $user): bool
    {
        // Check if user has OpenAI integration configured
        // This is handled by SecureRequestBuilder, so we return true here
        return true;
    }

    /**
     * Analyze YouTube video and extract basic information
     */
    private function analyzeVideo(User $user, Layer $layer, array $parameters, array $options): array
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
            $videoInfo = $this->getYouTubeVideoInfo($videoId);
            
            // Store in cache for 1 hour
            $cachedItem->set($videoInfo);
            $cachedItem->expiresAfter(3600);
            $this->cache->save($cachedItem);

            // Store video info in plugin data
            $pluginData = $layer->getPluginData('youtube_thumbnail') ?? [];
            $pluginData['video_info'] = $videoInfo;
            $pluginData['analyzed_at'] = (new \DateTimeImmutable())->format('c');
            $layer->addPluginData('youtube_thumbnail', $pluginData);

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
     * Generate thumbnail variations using OpenAI with original thumbnail as reference
     */
    private function generateThumbnailVariations(User $user, Layer $layer, array $parameters, array $options): array
    {
        $videoUrl = $parameters['video_url'] ?? null;
        $customPrompt = $parameters['custom_prompt'] ?? null;
        $thumbnailCount = $parameters['thumbnail_count'] ?? 4; // OpenAI gpt-image-1 supports 1-10 images
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
            // Get video info first (includes original thumbnail URL)
            $videoInfo = $this->getYouTubeVideoInfo($videoId);
            
            if (!isset($videoInfo['thumbnail_url'])) {
                throw new \RuntimeException('Original thumbnail URL not found for video');
            }
            
            // Download original thumbnail to use as reference
            $originalThumbnailPath = $this->downloadOriginalThumbnail($videoInfo['thumbnail_url'], $videoId);
            
            // Generate thumbnail variations using OpenAI edits endpoint with original as reference
            $thumbnailVariations = $this->generateThumbnailVariationsWithAI($user, $videoInfo, $originalThumbnailPath, $customPrompt, $thumbnailCount, $style);
            
            // Store results in plugin data
            $pluginData = $layer->getPluginData('youtube_thumbnail') ?? [];
            $pluginData['thumbnail_variations'] = $thumbnailVariations;
            $pluginData['original_thumbnail'] = $originalThumbnailPath;
            $pluginData['generation_parameters'] = [
                'custom_prompt' => $customPrompt,
                'thumbnail_count' => $thumbnailCount,
                'style' => $style,
                'generated_at' => (new \DateTimeImmutable())->format('c')
            ];
            $layer->addPluginData('youtube_thumbnail', $pluginData);

            $this->logger->info('Thumbnail variations generated successfully', [
                'video_id' => $videoId,
                'thumbnail_count' => count($thumbnailVariations)
            ]);

            return [
                'success' => true,
                'thumbnail_variations' => $thumbnailVariations,
                'original_thumbnail' => $originalThumbnailPath,
                'video_info' => $videoInfo
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate thumbnail images', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to generate thumbnail images: ' . $e->getMessage());
        }
    }

    /**
     * Get basic video information
     */
    private function getVideoInfo(User $user, Layer $layer, array $parameters, array $options): array
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
            $videoInfo = $this->getYouTubeVideoInfo($videoId);
            
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
    private function clearCachedResults(User $user, Layer $layer, array $parameters, array $options): array
    {
        $videoUrl = $parameters['video_url'] ?? null;
        
        if ($videoUrl) {
            $videoId = $this->extractVideoId($videoUrl);
            if ($videoId) {
                $cacheKey = sprintf('youtube_analysis_%s', $videoId);
                $this->cache->deleteItem($cacheKey);
            }
        }

        // Clear plugin data
        $layer->removePluginData('youtube_thumbnail');

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
    private function getYouTubeVideoInfo(string $videoId): array
    {
        try {
            // Use YouTube oEmbed API (no API key required)
            $oembedUrl = sprintf('https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=%s&format=json', $videoId);
            
            $response = $this->requestBuilder->forService($this->getSystemUser(), 'youtube')
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
            $pluginDir = $this->pluginService->getPluginDirectory('youtube_thumbnail');
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
     * Generate thumbnail variations using OpenAI gpt-image-1 with edits endpoint
     * Uses correct OpenAI parameters and processes base64 responses with media processing
     */
    private function generateThumbnailVariationsWithAI(User $user, array $videoInfo, string $originalThumbnailPath, ?string $customPrompt, int $count, string $style): array
    {
        try {
            // Ensure count is within OpenAI limits (1-10 images)
            $count = max(1, min(10, $count));
            
            $prompt = $this->buildEditPrompt($videoInfo, $customPrompt, $style);
            
            // Prepare multipart form data for the edits endpoint (based on OpenAI docs)
            $multipartData = [
                [
                    'name' => 'image',
                    'contents' => fopen($originalThumbnailPath, 'r'),
                    'filename' => 'original.jpg'
                ],
                [
                    'name' => 'prompt',
                    'contents' => $prompt
                ],
                [
                    'name' => 'model',
                    'contents' => 'gpt-image-1'
                ],
                [
                    'name' => 'size',
                    'contents' => '1536x1024' // OpenAI landscape format (not 1792x1024)
                ],
                [
                    'name' => 'n',
                    'contents' => (string)$count
                ],
                [
                    'name' => 'output_format',
                    'contents' => 'png' // Best quality for thumbnails
                ],
                [
                    'name' => 'quality',
                    'contents' => 'high' // High quality for better thumbnails
                ],
                [
                    'name' => 'background',
                    'contents' => 'opaque' // Solid background for YouTube thumbnails
                ]
            ];

            $response = $this->requestBuilder->forService($user, 'openai')
                ->post(self::OPENAI_API_URL . '/images/edits', [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'body' => $multipartData
                ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('OpenAI image edit generation failed', [
                    'status_code' => $response->getStatusCode(),
                    'response' => $response->getContent(false)
                ]);
                throw new \RuntimeException('Failed to generate thumbnail variations with OpenAI');
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

        // Ensure prompt doesn't exceed DALL-E limits (around 4000 characters)
        if (strlen($prompt) > 3500) {
            $prompt = substr($prompt, 0, 3500) . '...';
        }

        return $prompt;
    }

    /**
     * Download image from OpenAI and store locally
     */
    private function downloadAndStoreImage(string $imageUrl, string $videoId, int $variationNumber): array
    {
        try {
            // Create directory for this plugin
            $pluginDir = $this->pluginService->getPluginDirectory('youtube_thumbnail');
            $videoDir = $pluginDir . '/' . $videoId;
            if (!is_dir($videoDir)) {
                mkdir($videoDir, 0755, true);
            }
            
            // Download the image
            $imageContent = file_get_contents($imageUrl);
            if ($imageContent === false) {
                throw new \RuntimeException('Failed to download image from OpenAI');
            }
            
            // Generate filename
            $filename = sprintf('thumbnail_%s_concept_%d_%s.png', $videoId, $variationNumber, uniqid());
            $localPath = $videoDir . '/' . $filename;
            
            // Save locally
            if (file_put_contents($localPath, $imageContent) === false) {
                throw new \RuntimeException('Failed to save image locally');
            }
            
            // Generate public URL
            $relativePath = 'uploads/plugins/youtube_thumbnail/' . $videoId . '/' . $filename;
            $publicUrl = '/' . $relativePath;
            
            return [
                'path' => $localPath,
                'url' => $publicUrl,
                'filename' => $filename
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to download and store image', [
                'image_url' => $imageUrl,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to download and store image: ' . $e->getMessage());
        }
    }

    /**
     * Process base64 image from OpenAI and create multiple sizes for different uses
     */
    private function processAndStoreImage(string $base64Image, string $videoId, int $variationNumber): array
    {
        try {
            // Create directory for this video's thumbnails
            $pluginDir = $this->pluginService->getPluginDirectory('youtube_thumbnail');
            $videoDir = $pluginDir . '/' . $videoId;
            if (!is_dir($videoDir)) {
                mkdir($videoDir, 0755, true);
            }
            
            // Decode base64 image
            $imageData = base64_decode($base64Image);
            if ($imageData === false) {
                throw new \RuntimeException('Failed to decode base64 image from OpenAI');
            }
            
            // Save original OpenAI image (1536x1024)
            $originalFilename = sprintf('openai_original_%s_var_%d_%s.png', $videoId, $variationNumber, uniqid());
            $originalPath = $videoDir . '/' . $originalFilename;
            file_put_contents($originalPath, $imageData);
            
            // Process different sizes using MediaProcessingService
            $sizes = [
                'full_size' => ['width' => 1792, 'height' => 1024], // YouTube optimal size
                'preview' => ['width' => 896, 'height' => 512],     // Preview size
                'thumbnail' => ['width' => 320, 'height' => 180]    // Small thumbnail
            ];
            
            $processedImages = [];
            
            foreach ($sizes as $sizeKey => $dimensions) {
                $filename = sprintf('thumb_%s_var_%d_%s_%s.webp', $videoId, $variationNumber, $sizeKey, uniqid());
                $outputPath = $videoDir . '/' . $filename;
                
                // Use MediaProcessingService to resize and optimize
                $result = $this->mediaProcessingService->processImage(
                    $originalPath,
                    $outputPath,
                    new \App\Service\MediaProcessing\Config\ImageProcessingConfig(
                        $dimensions['width'],
                        $dimensions['height'],
                        85, // Quality
                        'webp', // Format for better compression
                        true // Maintain aspect ratio
                    )
                );
                
                if ($result->isSuccess()) {
                    // Generate public URL
                    $relativePath = 'uploads/plugins/youtube_thumbnail/' . $videoId . '/' . $filename;
                    $publicUrl = '/' . $relativePath;
                    
                    $processedImages[$sizeKey] = [
                        'path' => $outputPath,
                        'url' => $publicUrl,
                        'filename' => $filename,
                        'width' => $dimensions['width'],
                        'height' => $dimensions['height']
                    ];
                } else {
                    $this->logger->error('Failed to process image size', [
                        'size_key' => $sizeKey,
                        'error' => $result->getErrorMessage()
                    ]);
                }
            }
            
            // Clean up original temporary file
            if (file_exists($originalPath)) {
                unlink($originalPath);
            }
            
            return $processedImages;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to process and store image', [
                'video_id' => $videoId,
                'variation_number' => $variationNumber,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to process and store image: ' . $e->getMessage());
        }
    }

    /**
     * Get system user for API calls that don't require user credentials
     */
    private function getSystemUser(): User
    {
        // For oEmbed calls, we create a temporary user object
        // In a real implementation, you might want to have a dedicated system user
        $systemUser = new User();
        return $systemUser;
    }

    /**
     * Validate thumbnail generation parameters
     */
    private function validateThumbnailParameters(array $parameters): array
    {
        $errors = [];
        
        // Validate video URL
        if (empty($parameters['video_url'])) {
            $errors[] = 'YouTube video URL is required';
        } elseif (!$this->extractVideoId($parameters['video_url'])) {
            $errors[] = 'Invalid YouTube video URL format';
        }
        
        // Validate thumbnail count
        if (isset($parameters['thumbnail_count'])) {
            $count = (int)$parameters['thumbnail_count'];
            if ($count < 1 || $count > 10) {
                $errors[] = 'Thumbnail count must be between 1 and 10 (OpenAI gpt-image-1 limit)';
            }
        }
        
        // Validate style
        if (isset($parameters['style'])) {
            $validStyles = ['modern', 'dramatic', 'minimalist', 'colorful', 'professional'];
            if (!in_array($parameters['style'], $validStyles, true)) {
                $errors[] = sprintf('Invalid style. Must be one of: %s', implode(', ', $validStyles));
            }
        }
        
        return $errors;
    }
}
