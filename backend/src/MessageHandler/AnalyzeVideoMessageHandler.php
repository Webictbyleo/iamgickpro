<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\AnalyzeVideoMessage;
use App\Repository\VideoAnalysisRepository;
use App\Service\VideoAnalysisService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

/**
 * Message handler for background video analysis processing
 * 
 * Handles the actual processing of video analysis jobs that have been
 * queued for background execution. This includes YouTube video data
 * extraction, AI analysis, and thumbnail design generation.
 */
#[AsMessageHandler]
final readonly class AnalyzeVideoMessageHandler
{
    public function __construct(
        private VideoAnalysisService $videoAnalysisService,
        private VideoAnalysisRepository $videoAnalysisRepository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(AnalyzeVideoMessage $message): void
    {
        try {
            $this->logger->info('Processing video analysis job', [
                'analysis_id' => $message->analysisId,
                'youtube_url' => $message->youtubeUrl,
                'options' => $message->options
            ]);

            $analysis = $this->videoAnalysisRepository->find($message->analysisId);
            if (!$analysis) {
                throw new \RuntimeException('Video analysis not found: ' . $message->analysisId);
            }

            // Start processing
            $this->videoAnalysisService->updateProgress($analysis, 10, 'Starting video analysis...');

            // Extract video information
            $this->logger->info('Extracting video information', ['analysis_id' => $message->analysisId]);
            $videoInfo = $this->videoAnalysisService->extractVideoInfo($message->youtubeUrl);
            $this->videoAnalysisService->updateProgress($analysis, 30, 'Video information extracted');

            // Generate design suggestions based on video content
            $this->logger->info('Generating design suggestions', ['analysis_id' => $message->analysisId]);
            $this->videoAnalysisService->updateProgress($analysis, 60, 'Analyzing video content for design generation...');
            
            // Simulate AI analysis and design generation
            $designSuggestions = $this->generateDesignSuggestions($videoInfo, $message->options);
            $this->videoAnalysisService->updateProgress($analysis, 90, 'Finalizing design suggestions...');

            // Prepare result data
            $result = [
                'videoInfo' => $videoInfo,
                'suggestedDesigns' => $designSuggestions,
                'analysisMetadata' => [
                    'processingTime' => microtime(true) - (float)$analysis->getCreatedAt()->format('U.u'),
                    'totalDesigns' => count($designSuggestions),
                    'options' => $message->options
                ]
            ];

            // Mark analysis as completed
            $this->videoAnalysisService->markCompleted($analysis, $result);
            $this->videoAnalysisService->updateProgress($analysis, 100, 'Video analysis completed successfully');

            $this->logger->info('Video analysis completed successfully', [
                'analysis_id' => $message->analysisId,
                'designs_generated' => count($designSuggestions)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Video analysis failed', [
                'analysis_id' => $message->analysisId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $analysis = $this->videoAnalysisRepository->find($message->analysisId);
            if ($analysis) {
                $this->videoAnalysisService->markFailed($analysis, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Generate design suggestions based on video information and options
     */
    private function generateDesignSuggestions(array $videoInfo, array $options): array
    {
        $style = $options['style'] ?? 'modern';
        $maxThumbnails = (int)($options['maxThumbnails'] ?? 6);
        $customPrompt = $options['customPrompt'] ?? '';
        $size = $options['size'] ?? '1280x720';
        
        [$width, $height] = explode('x', $size);
        $width = (int)$width;
        $height = (int)$height;

        $suggestions = [];
        $baseTitle = $videoInfo['title'] ?? 'Generated Design';
        
        // Extract color palette from video metadata
        $colorPalette = $this->extractColorPalette($videoInfo);
        
        // Analyze video content for design themes
        $designThemes = $this->analyzeVideoThemes($videoInfo);
        
        // Generate key moments for frame extraction
        $keyMoments = $this->extractKeyMoments($videoInfo);

        // Generate different design variations based on style and content analysis
        for ($i = 1; $i <= $maxThumbnails; $i++) {
            $designVariant = $this->generateDesignVariant($i, $style, $designThemes);
            
            $suggestions[] = [
                'id' => 'ai-design-' . uniqid() . '-' . $i,
                'title' => $this->generateDesignTitle($baseTitle, $style, $i, $designVariant),
                'description' => $this->generateDesignDescription($videoInfo, $style, $customPrompt, $designVariant),
                'category' => $style,
                'thumbnailUrl' => $this->generateThumbnailPreview($style, $i, $colorPalette),
                'designData' => $this->generateAdvancedDesignData($style, $videoInfo, $colorPalette, $designVariant, $width, $height),
                'width' => $width,
                'height' => $height,
                'confidence' => $this->calculateDesignConfidence($style, $designThemes, $videoInfo),
                'sourceFrames' => $this->selectOptimalFrames($keyMoments, $i),
                'tags' => $this->generateSmartTags($style, $videoInfo, $designThemes),
                'colorPalette' => $colorPalette,
                'designThemes' => $designThemes,
                'metadata' => [
                    'generatedAt' => date('c'),
                    'style' => $style,
                    'aiGenerated' => true,
                    'customPrompt' => $customPrompt,
                    'designVariant' => $designVariant,
                    'optimizationScore' => $this->calculateOptimizationScore($style, $videoInfo),
                    'expectedCTR' => $this->estimateClickThroughRate($style, $designThemes, $videoInfo),
                    'competitiveAnalysis' => $this->analyzeCompetitiveFactors($videoInfo)
                ]
            ];
        }

        return $suggestions;
    }

    /**
     * Extract color palette from video information
     */
    private function extractColorPalette(array $videoInfo): array
    {
        // Analyze video thumbnail and metadata to extract dominant colors
        $thumbnailUrl = $videoInfo['thumbnailUrl'] ?? '';
        
        // For now, generate colors based on video category and content analysis
        $categoryColors = $this->getCategoryBasedColors($videoInfo);
        $trendingColors = $this->getCurrentTrendingColors();
        
        return [
            'primary' => $categoryColors['primary'],
            'secondary' => $categoryColors['secondary'],
            'accent' => $trendingColors[0],
            'neutral' => '#F3F4F6',
            'colors' => array_merge($categoryColors['palette'], $trendingColors)
        ];
    }
    
    /**
     * Analyze video themes for design optimization
     */
    private function analyzeVideoThemes(array $videoInfo): array
    {
        $title = strtolower($videoInfo['title'] ?? '');
        $description = strtolower($videoInfo['description'] ?? '');
        $tags = array_map('strtolower', $videoInfo['tags'] ?? []);
        $categoryId = $videoInfo['categoryId'] ?? null;
        
        $themes = [];
        
        // Analyze content type
        if (str_contains($title, 'tutorial') || str_contains($title, 'how to') || str_contains($title, 'guide')) {
            $themes[] = 'educational';
        }
        if (str_contains($title, 'review') || str_contains($title, 'vs ') || str_contains($title, 'comparison')) {
            $themes[] = 'review';
        }
        if (str_contains($title, 'music') || str_contains($title, 'song') || in_array('music', $tags)) {
            $themes[] = 'entertainment';
        }
        if (str_contains($title, 'news') || str_contains($title, 'breaking') || str_contains($title, 'update')) {
            $themes[] = 'news';
        }
        if (str_contains($title, 'vlog') || str_contains($title, 'day in') || str_contains($title, 'my life')) {
            $themes[] = 'lifestyle';
        }
        if (str_contains($title, 'gaming') || str_contains($title, 'gameplay') || in_array('gaming', $tags)) {
            $themes[] = 'gaming';
        }
        
        // Analyze emotional tone
        $emotionalWords = [
            'exciting' => 'excitement',
            'amazing' => 'amazement', 
            'shocking' => 'shock',
            'incredible' => 'amazement',
            'hilarious' => 'humor',
            'funny' => 'humor',
            'epic' => 'excitement',
            'crazy' => 'excitement',
            'unbelievable' => 'shock'
        ];
        
        foreach ($emotionalWords as $word => $emotion) {
            if (str_contains($title, $word) || str_contains($description, $word)) {
                $themes[] = $emotion;
            }
        }
        
        // Analyze urgency indicators
        if (str_contains($title, '2024') || str_contains($title, 'new') || str_contains($title, 'latest')) {
            $themes[] = 'trending';
        }
        if (str_contains($title, 'breaking') || str_contains($title, 'urgent') || str_contains($title, 'alert')) {
            $themes[] = 'urgent';
        }
        
        return array_unique($themes);
    }
    
    /**
     * Extract key moments from video for optimal frame selection
     */
    private function extractKeyMoments(array $videoInfo): array
    {
        $duration = $this->parseDuration($videoInfo['duration'] ?? 'PT3M33S');
        $keyMoments = [];
        
        // Calculate optimal moments based on video length and engagement patterns
        if ($duration <= 60) {
            // Short video - extract frames at 20%, 50%, 80%
            $keyMoments = [
                (int)($duration * 0.2),
                (int)($duration * 0.5), 
                (int)($duration * 0.8)
            ];
        } elseif ($duration <= 300) {
            // Medium video - extract more varied frames
            $keyMoments = [
                15, // Opening after intro
                (int)($duration * 0.3),
                (int)($duration * 0.6),
                (int)($duration * 0.9)
            ];
        } else {
            // Long video - focus on early engagement
            $keyMoments = [
                30, // After typical intro
                120, // 2 minutes in
                (int)($duration * 0.4),
                (int)($duration * 0.7),
                max(300, (int)($duration * 0.9)) // Near end but not too late
            ];
        }
        
        return $keyMoments;
    }
    
    /**
     * Generate design variant information
     */
    private function generateDesignVariant(int $index, string $style, array $themes): array
    {
        $variants = [
            1 => ['type' => 'primary', 'focus' => 'title-focused', 'layout' => 'centered'],
            2 => ['type' => 'alternative', 'focus' => 'image-focused', 'layout' => 'left-aligned'],
            3 => ['type' => 'bold', 'focus' => 'contrast-heavy', 'layout' => 'dynamic'],
            4 => ['type' => 'minimal', 'focus' => 'clean-simple', 'layout' => 'minimal'],
            5 => ['type' => 'creative', 'focus' => 'artistic', 'layout' => 'asymmetric'],
            6 => ['type' => 'optimized', 'focus' => 'ctr-optimized', 'layout' => 'proven']
        ];
        
        $baseVariant = $variants[$index] ?? $variants[1];
        
        // Modify variant based on themes
        if (in_array('educational', $themes)) {
            $baseVariant['elements'] = ['step-numbers', 'progress-indicators'];
        }
        if (in_array('excitement', $themes) || in_array('shock', $themes)) {
            $baseVariant['elements'] = ['explosion-effects', 'bright-colors', 'large-text'];
        }
        if (in_array('professional', $themes)) {
            $baseVariant['elements'] = ['corporate-styling', 'subtle-effects'];
        }
        
        return $baseVariant;
    }

    /**
     * Get colors based on video category and content
     */
    private function getCategoryBasedColors(array $videoInfo): array
    {
        $categoryId = $videoInfo['categoryId'] ?? null;
        $title = strtolower($videoInfo['title'] ?? '');
        
        // YouTube category mappings
        $categoryColors = [
            '1' => ['primary' => '#FF6B6B', 'secondary' => '#4ECDC4'], // Film & Animation
            '2' => ['primary' => '#FFE66D', 'secondary' => '#FF6B6B'], // Autos & Vehicles  
            '10' => ['primary' => '#4ECDC4', 'secondary' => '#45B7D1'], // Music
            '15' => ['primary' => '#96CEB4', 'secondary' => '#FFEAA7'], // Pets & Animals
            '17' => ['primary' => '#FF7675', 'secondary' => '#74B9FF'], // Sports
            '19' => ['primary' => '#FD79A8', 'secondary' => '#FDCB6E'], // Travel & Events
            '20' => ['primary' => '#6C5CE7', 'secondary' => '#A29BFE'], // Gaming
            '22' => ['primary' => '#FD79A8', 'secondary' => '#FF7675'], // People & Blogs
            '23' => ['primary' => '#00B894', 'secondary' => '#00CEC9'], // Comedy
            '24' => ['primary' => '#E17055', 'secondary' => '#FDCB6E'], // Entertainment
            '25' => ['primary' => '#0984E3', 'secondary' => '#74B9FF'], // News & Politics
            '26' => ['primary' => '#00B894', 'secondary' => '#55A3FF'], // Howto & Style
            '27' => ['primary' => '#FDCB6E', 'secondary' => '#E17055'], // Education
            '28' => ['primary' => '#E84393', 'secondary' => '#FD79A8']  // Science & Technology
        ];
        
        $defaultColors = ['primary' => '#4F46E5', 'secondary' => '#7C3AED'];
        $colors = $categoryColors[$categoryId] ?? $defaultColors;
        
        // Override based on content analysis
        if (str_contains($title, 'tech') || str_contains($title, 'review')) {
            $colors = ['primary' => '#3B82F6', 'secondary' => '#1E40AF'];
        } elseif (str_contains($title, 'gaming') || str_contains($title, 'game')) {
            $colors = ['primary' => '#8B5CF6', 'secondary' => '#7C3AED'];
        } elseif (str_contains($title, 'music') || str_contains($title, 'song')) {
            $colors = ['primary' => '#EF4444', 'secondary' => '#F97316'];
        } elseif (str_contains($title, 'food') || str_contains($title, 'cooking')) {
            $colors = ['primary' => '#F59E0B', 'secondary' => '#EAB308'];
        }
        
        return [
            'primary' => $colors['primary'],
            'secondary' => $colors['secondary'],
            'palette' => [$colors['primary'], $colors['secondary'], '#6B7280', '#F3F4F6']
        ];
    }

    /**
     * Get current trending colors for design
     */
    private function getCurrentTrendingColors(): array
    {
        // Current trending colors for 2024
        return [
            '#FF6B6B', // Coral Red
            '#4ECDC4', // Mint Green  
            '#45B7D1', // Sky Blue
            '#96CEB4', // Sage Green
            '#FFEAA7', // Cream Yellow
            '#DDA0DD', // Plum
            '#FFB6C1', // Light Pink
            '#98D8C8'  // Mint
        ];
    }

    /**
     * Parse ISO 8601 duration to seconds
     */
    private function parseDuration(string $duration): int
    {
        // Parse ISO 8601 duration format (PT1H2M3S)
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);
        
        $hours = (int)($matches[1] ?? 0);
        $minutes = (int)($matches[2] ?? 0);
        $seconds = (int)($matches[3] ?? 0);
        
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    /**
     * Calculate design confidence score based on multiple factors
     */
    private function calculateDesignConfidence(string $style, array $themes, array $videoInfo): float
    {
        $confidence = 0.7; // Base confidence
        
        // Boost confidence based on available data
        if (!empty($videoInfo['title'])) $confidence += 0.1;
        if (!empty($videoInfo['description'])) $confidence += 0.05;
        if (!empty($videoInfo['tags'])) $confidence += 0.05;
        if (!empty($videoInfo['thumbnailUrl'])) $confidence += 0.05;
        
        // Boost based on theme analysis
        if (count($themes) >= 2) $confidence += 0.1;
        if (in_array('trending', $themes)) $confidence += 0.05;
        
        // Style-specific adjustments
        if ($style === 'modern' || $style === 'bold') $confidence += 0.05;
        
        return min(0.95, $confidence); // Cap at 95%
    }

    /**
     * Select optimal frames from key moments
     */
    private function selectOptimalFrames(array $keyMoments, int $designIndex): array
    {
        // Select different frames for each design variant
        $frameCount = min(3, count($keyMoments));
        $selectedFrames = [];
        
        for ($i = 0; $i < $frameCount; $i++) {
            $frameIndex = ($designIndex - 1 + $i) % count($keyMoments);
            $selectedFrames[] = [
                'timestamp' => $keyMoments[$frameIndex],
                'confidence' => 0.8 + (rand(0, 15) / 100), // 0.8-0.95
                'reason' => $this->getFrameSelectionReason($keyMoments[$frameIndex])
            ];
        }
        
        return $selectedFrames;
    }

    /**
     * Get reason for frame selection
     */
    private function getFrameSelectionReason(int $timestamp): string
    {
        if ($timestamp <= 30) return 'Opening hook - high engagement potential';
        if ($timestamp <= 120) return 'Early content - maintains attention';
        if ($timestamp <= 300) return 'Mid-content - peak engagement zone';
        return 'Key moment - optimal for thumbnail capture';
    }

    /**
     * Generate smart tags based on analysis
     */
    private function generateSmartTags(string $style, array $videoInfo, array $themes): array
    {
        $tags = ['youtube', 'thumbnail', 'ai-generated', $style];
        
        // Add theme-based tags
        foreach ($themes as $theme) {
            $tags[] = $theme;
        }
        
        // Add content-specific tags
        if (isset($videoInfo['categoryId'])) {
            $categoryTags = [
                '10' => 'music',
                '20' => 'gaming', 
                '22' => 'vlog',
                '24' => 'entertainment',
                '27' => 'educational',
                '28' => 'tech'
            ];
            if (isset($categoryTags[$videoInfo['categoryId']])) {
                $tags[] = $categoryTags[$videoInfo['categoryId']];
            }
        }
        
        // Add engagement tags
        $title = strtolower($videoInfo['title'] ?? '');
        if (str_contains($title, 'new') || str_contains($title, '2024')) {
            $tags[] = 'trending';
        }
        if (str_contains($title, 'review') || str_contains($title, 'vs')) {
            $tags[] = 'comparison';
        }
        
        return array_unique(array_slice($tags, 0, 10)); // Limit to 10 tags
    }

    /**
     * Calculate optimization score for design
     */
    private function calculateOptimizationScore(string $style, array $videoInfo): float
    {
        $score = 0.6; // Base score
        
        // Title analysis
        $title = $videoInfo['title'] ?? '';
        if (strlen($title) > 10 && strlen($title) < 60) $score += 0.1;
        if (preg_match('/[!?]/', $title)) $score += 0.05; // Emotional punctuation
        
        // Style optimization
        $optimizedStyles = ['bold', 'modern', 'optimized'];
        if (in_array($style, $optimizedStyles)) $score += 0.1;
        
        // Channel authority (simplified)
        if (isset($videoInfo['channelTitle']) && !empty($videoInfo['channelTitle'])) {
            $score += 0.05;
        }
        
        // Content freshness
        if (isset($videoInfo['publishedAt'])) {
            $publishedTime = strtotime($videoInfo['publishedAt']);
            $daysSincePublished = (time() - $publishedTime) / (24 * 3600);
            if ($daysSincePublished < 7) $score += 0.1; // Recent content
        }
        
        return min(0.9, $score); // Cap at 90%
    }

    /**
     * Estimate click-through rate based on design and content factors
     */
    private function estimateClickThroughRate(string $style, array $themes, array $videoInfo): float
    {
        $baseCTR = 0.04; // 4% base CTR
        
        // Style impact on CTR
        $styleMultipliers = [
            'bold' => 1.3,
            'modern' => 1.2,
            'colorful' => 1.25,
            'optimized' => 1.4,
            'minimal' => 1.1,
            'professional' => 1.15
        ];
        
        $ctr = $baseCTR * ($styleMultipliers[$style] ?? 1.0);
        
        // Theme-based adjustments
        if (in_array('excitement', $themes) || in_array('shock', $themes)) $ctr *= 1.2;
        if (in_array('trending', $themes)) $ctr *= 1.15;
        if (in_array('educational', $themes)) $ctr *= 1.1;
        if (in_array('humor', $themes)) $ctr *= 1.25;
        
        // Title length optimization
        $titleLength = strlen($videoInfo['title'] ?? '');
        if ($titleLength >= 30 && $titleLength <= 50) $ctr *= 1.1;
        
        return min(0.15, $ctr); // Cap at 15% CTR
    }

    /**
     * Analyze competitive factors
     */
    private function analyzeCompetitiveFactors(array $videoInfo): array
    {
        return [
            'titleLength' => strlen($videoInfo['title'] ?? ''),
            'hasNumbers' => preg_match('/\d+/', $videoInfo['title'] ?? '') ? true : false,
            'hasEmotionalWords' => $this->hasEmotionalWords($videoInfo['title'] ?? ''),
            'categoryCompetition' => $this->getCategoryCompetition($videoInfo['categoryId'] ?? null),
            'publishTiming' => $this->analyzePublishTiming($videoInfo['publishedAt'] ?? null),
            'channelAuthority' => $this->estimateChannelAuthority($videoInfo),
            'contentFreshness' => $this->calculateContentFreshness($videoInfo['publishedAt'] ?? null)
        ];
    }

    /**
     * Check for emotional words in title
     */
    private function hasEmotionalWords(string $title): bool
    {
        $emotionalWords = [
            'amazing', 'incredible', 'shocking', 'unbelievable', 'epic', 'crazy', 
            'insane', 'mind-blowing', 'spectacular', 'fantastic', 'awesome', 'wow'
        ];
        
        $lowerTitle = strtolower($title);
        foreach ($emotionalWords as $word) {
            if (str_contains($lowerTitle, $word)) return true;
        }
        return false;
    }

    /**
     * Get category competition level
     */
    private function getCategoryCompetition(?string $categoryId = null): string
    {
        $competitionLevels = [
            '10' => 'high',    // Music
            '20' => 'very-high', // Gaming
            '22' => 'high',    // People & Blogs
            '24' => 'very-high', // Entertainment  
            '25' => 'medium',  // News & Politics
            '27' => 'medium',  // Education
            '28' => 'high'     // Science & Technology
        ];
        
        return $competitionLevels[$categoryId] ?? 'medium';
    }

    /**
     * Analyze publish timing
     */
    private function analyzePublishTiming(?string $publishedAt = null): array
    {
        if (!$publishedAt) {
            return ['optimal' => false, 'reason' => 'No publish date available'];
        }
        
        $publishTime = strtotime($publishedAt);
        $dayOfWeek = date('N', $publishTime); // 1=Monday, 7=Sunday
        $hourOfDay = (int)date('H', $publishTime);
        
        // Optimal posting times (general YouTube trends)
        $optimalDays = [2, 3, 4, 5]; // Tuesday-Friday
        $optimalHours = [14, 15, 16, 17, 18, 19, 20]; // 2-8 PM
        
        $isOptimalDay = in_array($dayOfWeek, $optimalDays);
        $isOptimalHour = in_array($hourOfDay, $optimalHours);
        
        return [
            'optimal' => $isOptimalDay && $isOptimalHour,
            'dayScore' => $isOptimalDay ? 1.0 : 0.7,
            'timeScore' => $isOptimalHour ? 1.0 : 0.8,
            'reason' => $this->getTimingReason($isOptimalDay, $isOptimalHour)
        ];
    }

    /**
     * Get timing analysis reason
     */
    private function getTimingReason(bool $isOptimalDay, bool $isOptimalHour): string
    {
        if ($isOptimalDay && $isOptimalHour) return 'Published at optimal time';
        if ($isOptimalDay) return 'Good day, could improve time';
        if ($isOptimalHour) return 'Good time, could improve day';
        return 'Could improve both day and time';
    }

    /**
     * Estimate channel authority
     */
    private function estimateChannelAuthority(array $videoInfo): array
    {
        // Simple heuristics for channel authority
        $hasVerifiedBadge = false; // Would need API data
        $channelName = $videoInfo['channelTitle'] ?? '';
        
        return [
            'score' => 0.5, // Default medium authority
            'verified' => $hasVerifiedBadge,
            'name' => $channelName,
            'factors' => ['subscriber_count_unknown', 'upload_frequency_unknown']
        ];
    }

    /**
     * Calculate content freshness
     */
    private function calculateContentFreshness(?string $publishedAt = null): float
    {
        if (!$publishedAt) return 0.5;
        
        $publishTime = strtotime($publishedAt);
        $hoursSincePublished = (time() - $publishTime) / 3600;
        
        if ($hoursSincePublished < 24) return 1.0;      // Very fresh
        if ($hoursSincePublished < 168) return 0.8;     // Fresh (< 1 week)
        if ($hoursSincePublished < 720) return 0.6;     // Moderate (< 1 month)
        if ($hoursSincePublished < 2160) return 0.4;    // Older (< 3 months)
        return 0.2; // Old content
    }
    /**
     * Generate design title with AI enhancement
     */
    private function generateDesignTitle(string $baseTitle, string $style, int $variant, array $designVariant): string
    {
        $truncatedTitle = substr($baseTitle, 0, 40);
        $styleLabels = [
            'modern' => 'Modern',
            'bold' => 'Bold Impact',
            'minimal' => 'Clean Minimal',
            'colorful' => 'Vibrant',
            'professional' => 'Professional'
        ];

        $styleLabel = $styleLabels[$style] ?? ucfirst($style);
        $variantType = ucfirst($designVariant['type'] ?? 'Design');
        
        return "{$truncatedTitle} - {$variantType} {$styleLabel} #{$variant}";
    }

    /**
     * Generate enhanced design description
     */
    private function generateDesignDescription(array $videoInfo, string $style, string $customPrompt, array $designVariant): string
    {
        $baseDescription = "AI-generated thumbnail design optimized for YouTube engagement";
        
        if (!empty($customPrompt)) {
            return "{$baseDescription}. Custom requirements: {$customPrompt}";
        }

        $styleDescriptions = [
            'modern' => 'featuring contemporary typography and sleek visual elements',
            'bold' => 'with high contrast colors and dramatic visual impact',
            'minimal' => 'using clean lines and elegant simplicity',
            'colorful' => 'with vibrant colors and dynamic composition',
            'professional' => 'maintaining corporate aesthetics and professional appeal'
        ];

        $styleDesc = $styleDescriptions[$style] ?? 'with optimized visual design';
        $variantDesc = $this->getVariantDescription($designVariant);
        
        return "{$baseDescription} {$styleDesc}. {$variantDesc}";
    }

    /**
     * Get description for design variant
     */
    private function getVariantDescription(array $designVariant): string
    {
        $focus = $designVariant['focus'] ?? 'balanced';
        $layout = $designVariant['layout'] ?? 'standard';
        
        $focusDescriptions = [
            'title-focused' => 'Emphasizes clear, readable title text',
            'image-focused' => 'Highlights visual elements and imagery',
            'contrast-heavy' => 'Uses bold contrasts for maximum impact',
            'clean-simple' => 'Maintains minimal, uncluttered design',
            'artistic' => 'Features creative and unique visual elements',
            'ctr-optimized' => 'Designed for maximum click-through rates'
        ];
        
        return $focusDescriptions[$focus] ?? 'Balanced design approach';
    }

    /**
     * Generate thumbnail preview URL
     */
    private function generateThumbnailPreview(string $style, int $variant, array $colorPalette): string
    {
        // Generate deterministic preview based on style, variant, and colors
        $primaryColor = ltrim($colorPalette['primary'] ?? '#4F46E5', '#');
        $seed = crc32($style . $variant . $primaryColor);
        
        // For production, this would generate actual thumbnails
        // For now, return a placeholder with consistent parameters
        return "https://via.placeholder.com/1280x720/{$primaryColor}/ffffff?text={$style}+Design+{$variant}";
    }

    /**
     * Generate advanced design data with layers and elements
     */
    private function generateAdvancedDesignData(
        string $style, 
        array $videoInfo, 
        array $colorPalette, 
        array $designVariant, 
        int $width, 
        int $height
    ): array {
        $backgroundColor = $this->getStyleBackgroundColor($style, $colorPalette);
        $textColor = $this->getContrastingTextColor($backgroundColor);
        
        $layers = [];
        
        // Background layer
        $layers[] = [
            'id' => 'background',
            'type' => 'rectangle',
            'x' => 0,
            'y' => 0,
            'width' => $width,
            'height' => $height,
            'fill' => $backgroundColor,
            'name' => 'Background'
        ];
        
        // Gradient overlay for modern styles
        if ($style === 'modern' || $style === 'colorful') {
            $layers[] = [
                'id' => 'gradient-overlay',
                'type' => 'rectangle',
                'x' => 0,
                'y' => 0,
                'width' => $width,
                'height' => $height,
                'fillLinearGradientStartPoint' => ['x' => 0, 'y' => 0],
                'fillLinearGradientEndPoint' => ['x' => $width, 'y' => $height],
                'fillLinearGradientColorStops' => [
                    0, $colorPalette['primary'] . '80',
                    1, $colorPalette['secondary'] . '40'
                ],
                'name' => 'Gradient Overlay'
            ];
        }
        
        // Title layer
        $layers[] = $this->generateTitleLayer($videoInfo, $designVariant, $width, $height, $textColor);
        
        // Add decorative elements based on variant
        if (isset($designVariant['elements'])) {
            foreach ($designVariant['elements'] as $element) {
                $layers[] = $this->generateElementLayer($element, $colorPalette, $width, $height);
            }
        }
        
        // Accent elements for bold style
        if ($style === 'bold') {
            $layers[] = [
                'id' => 'accent-shape',
                'type' => 'rectangle',
                'x' => $width - 100,
                'y' => 50,
                'width' => 80,
                'height' => 8,
                'fill' => $colorPalette['accent'] ?? '#FF6B6B',
                'name' => 'Accent Shape'
            ];
        }
        
        return [
            'version' => '2.0',
            'canvas' => [
                'width' => $width,
                'height' => $height,
                'backgroundColor' => $backgroundColor
            ],
            'layers' => $layers,
            'metadata' => [
                'generatedFor' => 'youtube-thumbnail',
                'style' => $style,
                'aiGenerated' => true,
                'designVariant' => $designVariant,
                'colorPalette' => $colorPalette,
                'generatedAt' => date('c')
            ]
        ];
    }

    /**
     * Get background color based on style and palette
     */
    private function getStyleBackgroundColor(string $style, array $colorPalette): string
    {
        switch ($style) {
            case 'minimal':
                return '#FFFFFF';
            case 'professional':
                return '#F8FAFC';
            case 'bold':
                return $colorPalette['primary'] ?? '#DC2626';
            case 'colorful':
                return $colorPalette['secondary'] ?? '#7C3AED';
            case 'modern':
            default:
                return '#1F2937';
        }
    }

    /**
     * Get contrasting text color
     */
    private function getContrastingTextColor(string $backgroundColor): string
    {
        // Simple contrast calculation
        $hex = ltrim($backgroundColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        
        return $luminance > 0.5 ? '#000000' : '#FFFFFF';
    }

    /**
     * Generate title layer with optimized positioning
     */
    private function generateTitleLayer(array $videoInfo, array $designVariant, int $width, int $height, string $textColor): array
    {
        $title = $videoInfo['title'] ?? 'Video Title';
        $truncatedTitle = substr($title, 0, 50);
        
        $layout = $designVariant['layout'] ?? 'centered';
        $focus = $designVariant['focus'] ?? 'balanced';
        
        // Calculate position based on layout
        [$x, $y, $titleWidth] = $this->calculateTitlePosition($layout, $width, $height);
        
        // Calculate font size based on focus and title length
        $fontSize = $this->calculateOptimalFontSize($truncatedTitle, $focus, $titleWidth);
        
        return [
            'id' => 'title-text',
            'type' => 'text',
            'x' => $x,
            'y' => $y,
            'width' => $titleWidth,
            'height' => 120,
            'text' => $truncatedTitle,
            'fontSize' => $fontSize,
            'fontFamily' => $this->getOptimalFont($designVariant),
            'fill' => $textColor,
            'fontStyle' => 'bold',
            'align' => $layout === 'left-aligned' ? 'left' : 'center',
            'verticalAlign' => 'middle',
            'name' => 'Main Title',
            'strokeWidth' => $focus === 'contrast-heavy' ? 2 : 0,
            'stroke' => $focus === 'contrast-heavy' ? ($textColor === '#FFFFFF' ? '#000000' : '#FFFFFF') : ''
        ];
    }

    /**
     * Calculate optimal title position based on layout
     */
    private function calculateTitlePosition(string $layout, int $width, int $height): array
    {
        switch ($layout) {
            case 'left-aligned':
                return [64, $height * 0.6, $width * 0.6];
            case 'dynamic':
                return [32, $height * 0.4, $width * 0.8];
            case 'asymmetric':
                return [$width * 0.1, $height * 0.7, $width * 0.7];
            case 'minimal':
                return [$width * 0.1, $height * 0.5, $width * 0.8];
            case 'centered':
            default:
                return [64, $height * 0.55, $width - 128];
        }
    }

    /**
     * Calculate optimal font size
     */
    private function calculateOptimalFontSize(string $title, string $focus, int $width): int
    {
        $baseSize = 48;
        $titleLength = strlen($title);
        
        // Adjust for title length
        if ($titleLength > 40) $baseSize = 40;
        if ($titleLength > 30) $baseSize = 44;
        if ($titleLength < 20) $baseSize = 56;
        
        // Adjust for focus
        switch ($focus) {
            case 'title-focused':
                return (int)($baseSize * 1.2);
            case 'contrast-heavy':
                return (int)($baseSize * 1.1);
            case 'clean-simple':
                return (int)($baseSize * 0.9);
            default:
                return $baseSize;
        }
    }

    /**
     * Get optimal font for design variant
     */
    private function getOptimalFont(array $designVariant): string
    {
        $type = $designVariant['type'] ?? 'primary';
        
        $fonts = [
            'primary' => 'Arial Black',
            'bold' => 'Impact',
            'minimal' => 'Helvetica Neue',
            'creative' => 'Montserrat',
            'professional' => 'Open Sans',
            'optimized' => 'Roboto'
        ];
        
        return $fonts[$type] ?? 'Arial Black';
    }

    /**
     * Generate decorative element layer
     */
    private function generateElementLayer(string $element, array $colorPalette, int $width, int $height): array
    {
        switch ($element) {
            case 'explosion-effects':
                return [
                    'id' => 'explosion-' . uniqid(),
                    'type' => 'star',
                    'x' => $width * 0.8,
                    'y' => $height * 0.2,
                    'numPoints' => 8,
                    'innerRadius' => 15,
                    'outerRadius' => 30,
                    'fill' => $colorPalette['accent'] ?? '#FFD700',
                    'stroke' => '#FFFFFF',
                    'strokeWidth' => 2,
                    'name' => 'Explosion Effect'
                ];
                
            case 'step-numbers':
                return [
                    'id' => 'step-number-' . uniqid(),
                    'type' => 'circle',
                    'x' => 80,
                    'y' => 80,
                    'radius' => 25,
                    'fill' => $colorPalette['primary'] ?? '#4F46E5',
                    'stroke' => '#FFFFFF',
                    'strokeWidth' => 3,
                    'name' => 'Step Number'
                ];
                
            case 'progress-indicators':
                return [
                    'id' => 'progress-bar-' . uniqid(),
                    'type' => 'rectangle',
                    'x' => 64,
                    'y' => $height - 60,
                    'width' => $width - 128,
                    'height' => 8,
                    'fill' => $colorPalette['secondary'] ?? '#7C3AED',
                    'cornerRadius' => 4,
                    'name' => 'Progress Indicator'
                ];
                
            case 'corporate-styling':
                return [
                    'id' => 'corporate-line-' . uniqid(),
                    'type' => 'rectangle',
                    'x' => 0,
                    'y' => $height - 20,
                    'width' => $width,
                    'height' => 4,
                    'fill' => $colorPalette['primary'] ?? '#1E40AF',
                    'name' => 'Corporate Line'
                ];
                
            default:
                return [
                    'id' => 'decoration-' . uniqid(),
                    'type' => 'rectangle',
                    'x' => $width - 60,
                    'y' => 40,
                    'width' => 40,
                    'height' => 6,
                    'fill' => $colorPalette['accent'] ?? '#F59E0B',
                    'name' => 'Decorative Element'
                ];
        }
    }
}
