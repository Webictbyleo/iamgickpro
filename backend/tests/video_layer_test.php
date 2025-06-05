<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/test_base.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\VideoLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use DOMDocument;
use DOMXPath;

/**
 * Comprehensive test suite for VideoLayerRenderer
 * Tests video placeholder rendering, play button icons, labels, and edge cases
 */
class VideoLayerTest extends TestBase
{
    private VideoLayerRenderer $renderer;
    private SvgDocumentBuilder $builder;
    private array $testResults = [];
    private array $gallery = [];

    public function __construct()
    {
        parent::__construct();
        $this->renderer = new VideoLayerRenderer();
        $this->builder = new SvgDocumentBuilder();
    }

    public function runAllTests(): void
    {
        $this->output("=== Video Layer Renderer Test Suite ===\n");

        // Basic functionality tests
        $this->testBasicVideoRendering();
        $this->testVideoDimensions();
        $this->testEmptyVideoLayer();

        // Video placeholder tests
        $this->testVideoPlaceholder();
        $this->testPlaceholderStyling();

        // Play button tests
        $this->testPlayButton();
        $this->testPlayButtonScaling();
        $this->testPlayButtonPositioning();

        // Video label tests
        $this->testVideoLabel();
        $this->testLabelStyling();
        $this->testLabelPositioning();

        // Video properties tests
        $this->testVideoProperties();
        $this->testVideoSource();
        $this->testVideoDuration();
        $this->testMultipleProperties();

        // Edge cases and validation
        $this->testZeroDimensions();
        $this->testNegativeDimensions();
        $this->testInvalidProperties();

        // Performance tests
        $this->testPerformance();
        $this->testComplexVideoLayers();

        // Generate visual gallery
        $this->generateVideoGallery();

        $this->printTestSummary();
    }

    private function testBasicVideoRendering(): void
    {
        $this->output("Testing basic video rendering...\n");

        $layer = $this->createVideoLayer();
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Basic video rendering should succeed');
        $this->assertContains('<g', $result['svg'], 'Should contain group element');
        $this->assertContains('<rect', $result['svg'], 'Should contain rectangle placeholder');
        $this->assertContains('<circle', $result['svg'], 'Should contain play button circle');
        $this->assertContains('<text', $result['svg'], 'Should contain video label');

        $this->recordTest('Basic Video', $result['success'], $result['error']);
        $this->addToGallery('Basic Video', $result['svg'], 'Standard video layer');
    }

    private function testVideoDimensions(): void
    {
        $this->output("Testing video dimensions...\n");

        $dimensions = [
            [100, 100],
            [200, 150],
            [300, 200],
            [400, 300],
            [160, 90],   // 16:9 aspect ratio
            [400, 225]   // 16:9 aspect ratio
        ];

        foreach ($dimensions as [$width, $height]) {
            $layer = $this->createVideoLayer($width, $height);
            $result = $this->renderLayer($layer);

            $this->assertTrue($result['success'], "Video with dimensions {$width}x{$height} should render");
            $this->assertContains("width=\"$width\"", $result['svg'], "Should have width $width");
            $this->assertContains("height=\"$height\"", $result['svg'], "Should have height $height");

            $this->recordTest("Dimensions: {$width}x{$height}", $result['success'], $result['error']);
            $this->addToGallery("Video {$width}x{$height}", $result['svg'], "Video player {$width}x{$height}");
        }
    }

    private function testEmptyVideoLayer(): void
    {
        $this->output("Testing empty video layer...\n");

        $layer = new Layer();
        $layer->setId(rand(1000, 9999));
        $layer->setType('video');
        // No width/height set - should use defaults

        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Empty video layer should render with defaults');
        $this->assertContains('width="100"', $result['svg'], 'Should use default width');
        $this->assertContains('height="100"', $result['svg'], 'Should use default height');

        $this->recordTest('Empty Video Layer', $result['success'], $result['error']);
        $this->addToGallery('Default Video', $result['svg'], 'Video with default dimensions');
    }

    private function testVideoPlaceholder(): void
    {
        $this->output("Testing video placeholder...\n");

        $layer = $this->createVideoLayer(300, 200);
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Video placeholder should render');
        
        // Check for placeholder rectangle
        $this->assertContains('<rect', $result['svg'], 'Should contain placeholder rectangle');
        $this->assertContains('fill="#1f2937"', $result['svg'], 'Should have dark background');
        $this->assertContains('stroke="#374151"', $result['svg'], 'Should have border');
        $this->assertContains('rx="8"', $result['svg'], 'Should have rounded corners');

        $this->recordTest('Video Placeholder', $result['success'], $result['error']);
        $this->addToGallery('Video Placeholder', $result['svg'], 'Video placeholder styling');
    }

    private function testPlaceholderStyling(): void
    {
        $this->output("Testing placeholder styling...\n");

        $layer = $this->createVideoLayer(250, 180);
        $result = $this->renderLayer($layer);

        // Parse DOM to verify styling
        $dom = new DOMDocument();
        $dom->loadXML($result['svg']);
        $xpath = new DOMXPath($dom);
        
        $rects = $xpath->query('//rect');
        $this->assertTrue($rects->length > 0, 'Should have rectangle elements');
        
        $placeholderRect = $rects->item(0);
        $this->assertNotNull($placeholderRect, 'Should have placeholder rectangle');
        $this->assertEquals('#1f2937', $placeholderRect->getAttribute('fill'), 'Should have correct fill color');
        $this->assertEquals('2', $placeholderRect->getAttribute('stroke-width'), 'Should have correct stroke width');

        $this->recordTest('Placeholder Styling', $result['success'], $result['error']);
    }

    private function testPlayButton(): void
    {
        $this->output("Testing play button...\n");

        $layer = $this->createVideoLayer(200, 150);
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Play button should render');
        
        // Check for play button circle
        $this->assertContains('<circle', $result['svg'], 'Should contain play button circle');
        $this->assertContains('fill="#ffffff"', $result['svg'], 'Should have white fill');
        $this->assertContains('fill-opacity="0.9"', $result['svg'], 'Should have opacity');

        // Parse DOM to verify positioning
        $dom = new DOMDocument();
        $dom->loadXML($result['svg']);
        $xpath = new DOMXPath($dom);
        
        $circles = $xpath->query('//circle');
        $this->assertTrue($circles->length > 0, 'Should have circle elements');
        
        $playButton = $circles->item(0);
        $this->assertNotNull($playButton, 'Should have play button circle');
        
        // Should be centered
        $cx = (float)$playButton->getAttribute('cx');
        $cy = (float)$playButton->getAttribute('cy');
        $this->assertEquals(100.0, $cx, 'Play button should be horizontally centered');
        $this->assertEquals(75.0, $cy, 'Play button should be vertically centered');

        $this->recordTest('Play Button', $result['success'], $result['error']);
        $this->addToGallery('Play Button', $result['svg'], 'Video with play button');
    }

    private function testPlayButtonScaling(): void
    {
        $this->output("Testing play button scaling...\n");

        $sizes = [
            [100, 100],
            [200, 150],
            [400, 300],
            [50, 50]
        ];

        foreach ($sizes as [$width, $height]) {
            $layer = $this->createVideoLayer($width, $height);
            $result = $this->renderLayer($layer);

            $this->assertTrue($result['success'], "Play button scaling for {$width}x{$height} should work");

            // Parse DOM to check button size
            $dom = new DOMDocument();
            $dom->loadXML($result['svg']);
            $xpath = new DOMXPath($dom);
            
            $circles = $xpath->query('//circle');
            if ($circles->length > 0) {
                $playButton = $circles->item(0);
                $radius = (float)$playButton->getAttribute('r');
                $expectedRadius = min($width, $height) * 0.2 / 2;
                
                $this->assertEqualsWithDelta($expectedRadius, $radius, 0.1, 
                    "Play button should scale with layer size");
            }

            $this->recordTest("Play Button {$width}x{$height}", $result['success'], $result['error']);
        }
    }

    private function testPlayButtonPositioning(): void
    {
        $this->output("Testing play button positioning...\n");

        $layer = $this->createVideoLayer(300, 200);
        $result = $this->renderLayer($layer);

        // Parse DOM to verify positioning
        $dom = new DOMDocument();
        $dom->loadXML($result['svg']);
        $xpath = new DOMXPath($dom);
        
        $circles = $xpath->query('//circle');
        if ($circles->length > 0) {
            $playButton = $circles->item(0);
            $cx = (float)$playButton->getAttribute('cx');
            $cy = (float)$playButton->getAttribute('cy');
            
            // Should be at center of video
            $this->assertEquals(150.0, $cx, 'Play button should be horizontally centered');
            $this->assertEquals(100.0, $cy, 'Play button should be vertically centered');
        }

        $this->recordTest('Play Button Position', $result['success'], $result['error']);
    }

    private function testVideoLabel(): void
    {
        $this->output("Testing video label...\n");

        $layer = $this->createVideoLayer(250, 180);
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Video label should render');
        
        // Check for label text
        $this->assertContains('<text', $result['svg'], 'Should contain label text');
        $this->assertContains('Video Layer', $result['svg'], 'Should contain label content');
        $this->assertContains('text-anchor="middle"', $result['svg'], 'Should be center-aligned');

        $this->recordTest('Video Label', $result['success'], $result['error']);
        $this->addToGallery('Video Label', $result['svg'], 'Video with label');
    }

    private function testLabelStyling(): void
    {
        $this->output("Testing label styling...\n");

        $layer = $this->createVideoLayer(200, 150);
        $result = $this->renderLayer($layer);

        // Parse DOM to verify label styling
        $dom = new DOMDocument();
        $dom->loadXML($result['svg']);
        $xpath = new DOMXPath($dom);
        
        $texts = $xpath->query('//text');
        $this->assertTrue($texts->length > 0, 'Should have text elements');
        
        $label = $texts->item(0);
        $this->assertNotNull($label, 'Should have label text');
        $this->assertEquals('Arial, sans-serif', $label->getAttribute('font-family'), 'Should have correct font');
        $this->assertEquals('12', $label->getAttribute('font-size'), 'Should have correct font size');
        $this->assertEquals('#9ca3af', $label->getAttribute('fill'), 'Should have correct color');

        $this->recordTest('Label Styling', $result['success'], $result['error']);
    }

    private function testLabelPositioning(): void
    {
        $this->output("Testing label positioning...\n");

        $layer = $this->createVideoLayer(300, 200);
        $result = $this->renderLayer($layer);

        // Parse DOM to verify positioning
        $dom = new DOMDocument();
        $dom->loadXML($result['svg']);
        $xpath = new DOMXPath($dom);
        
        $texts = $xpath->query('//text');
        if ($texts->length > 0) {
            $label = $texts->item(0);
            $x = (float)$label->getAttribute('x');
            $y = (float)$label->getAttribute('y');
            
            // Should be centered horizontally and near bottom
            $this->assertEquals(150.0, $x, 'Label should be horizontally centered');
            $this->assertEquals(190.0, $y, 'Label should be near bottom');
        }

        $this->recordTest('Label Position', $result['success'], $result['error']);
    }

    private function testVideoProperties(): void
    {
        $this->output("Testing video properties...\n");

        $layer = $this->createVideoLayer(200, 150, [
            'source' => 'video.mp4',
            'duration' => 120,
            'autoplay' => true
        ]);
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Video with properties should render');
        $this->recordTest('Video Properties', $result['success'], $result['error']);
        $this->addToGallery('Video with Props', $result['svg'], 'Video with source and duration');
    }

    private function testVideoSource(): void
    {
        $this->output("Testing video source...\n");

        $sources = [
            'video.mp4',
            'https://example.com/video.mp4',
            'path/to/video.webm',
            ''
        ];

        foreach ($sources as $source) {
            $layer = $this->createVideoLayer(200, 150, ['source' => $source]);
            $result = $this->renderLayer($layer);

            $this->assertTrue($result['success'], "Video with source '$source' should render");
            $this->recordTest("Source: " . ($source ?: 'empty'), $result['success'], $result['error']);
        }
    }

    private function testVideoDuration(): void
    {
        $this->output("Testing video duration...\n");

        $durations = [0, 30, 120, 3600, -1];

        foreach ($durations as $duration) {
            $layer = $this->createVideoLayer(200, 150, ['duration' => $duration]);
            $result = $this->renderLayer($layer);

            $this->assertTrue($result['success'], "Video with duration '$duration' should render");
            $this->recordTest("Duration: {$duration}s", $result['success'], $result['error']);
        }
    }

    private function testMultipleProperties(): void
    {
        $this->output("Testing multiple properties...\n");

        $layer = $this->createVideoLayer(300, 200, [
            'source' => 'https://example.com/video.mp4',
            'duration' => 300,
            'autoplay' => false,
            'loop' => true,
            'muted' => true,
            'controls' => true
        ]);
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Video with multiple properties should render');
        $this->recordTest('Multiple Properties', $result['success'], $result['error']);
        $this->addToGallery('Full Props Video', $result['svg'], 'Video with all properties');
    }

    private function testZeroDimensions(): void
    {
        $this->output("Testing zero dimensions...\n");

        $layer = $this->createVideoLayer(0, 0);
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Video with zero dimensions should render');
        $this->recordTest('Zero Dimensions', $result['success'], $result['error']);
    }

    private function testNegativeDimensions(): void
    {
        $this->output("Testing negative dimensions...\n");

        $layer = $this->createVideoLayer(-100, -50);
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Video with negative dimensions should render');
        $this->recordTest('Negative Dimensions', $result['success'], $result['error']);
    }

    private function testInvalidProperties(): void
    {
        $this->output("Testing invalid properties...\n");

        $layer = $this->createVideoLayer(200, 150, [
            'invalidProp' => 'invalid',
            'duration' => 'not-a-number',
            'autoplay' => 'not-boolean'
        ]);
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Video with invalid properties should render gracefully');
        $this->recordTest('Invalid Properties', $result['success'], $result['error']);
    }

    private function testPerformance(): void
    {
        $this->output("Testing performance with multiple video layers...\n");

        $startTime = microtime(true);
        $successCount = 0;

        for ($i = 0; $i < 50; $i++) {
            $width = 200 + ($i % 100);
            $height = 150 + ($i % 50);
            $layer = $this->createVideoLayer($width, $height, [
                'source' => "video_$i.mp4",
                'duration' => 60 + ($i * 10)
            ]);
            $result = $this->renderLayer($layer);
            if ($result['success']) {
                $successCount++;
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->output("Rendered 50 video layers in " . number_format($duration, 3) . "s\n");
        $this->assertTrue($duration < 2.0, 'Should render 50 layers in under 2 seconds');
        $this->recordTest('Performance (50 layers)', $duration < 2.0, "Duration: " . number_format($duration, 3) . "s, Success: $successCount/50");
    }

    private function testComplexVideoLayers(): void
    {
        $this->output("Testing complex video layers...\n");

        $complexConfigs = [
            [400, 300, ['source' => 'hd_video.mp4', 'duration' => 7200]],
            [800, 450, ['source' => 'movie.mp4', 'duration' => 10800]],
            [160, 90, ['source' => 'mobile.mp4', 'duration' => 30]],
            [1920, 1080, ['source' => '4k_video.mp4', 'duration' => 3600]]
        ];

        foreach ($complexConfigs as [$width, $height, $props]) {
            $layer = $this->createVideoLayer($width, $height, $props);
            $result = $this->renderLayer($layer);

            $this->assertTrue($result['success'], "Complex video {$width}x{$height} should render");
            $this->recordTest("Complex {$width}x{$height}", $result['success'], $result['error']);
            $this->addToGallery("Complex {$width}x{$height}", $result['svg'], "Complex video layer");
        }
    }

    private function generateVideoGallery(): void
    {
        $this->output("Generating video gallery...\n");

        $galleryWidth = 1200;
        $galleryHeight = count($this->gallery) * 120 + 100;
        
        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"$galleryWidth\" height=\"$galleryHeight\" viewBox=\"0 0 $galleryWidth $galleryHeight\">\n";
        
        // Add gradient background
        $svg .= "<defs>\n";
        $svg .= "<linearGradient id=\"videoGalleryBg\" x1=\"0%\" y1=\"0%\" x2=\"100%\" y2=\"100%\">\n";
        $svg .= "<stop offset=\"0%\" stop-color=\"#111827\"/>\n";
        $svg .= "<stop offset=\"100%\" stop-color=\"#374151\"/>\n";
        $svg .= "</linearGradient>\n";
        $svg .= "</defs>\n";
        
        $svg .= "<rect width=\"100%\" height=\"100%\" fill=\"url(#videoGalleryBg)\"/>\n";
        
        // Add title
        $svg .= "<text x=\"600\" y=\"30\" text-anchor=\"middle\" font-family=\"Arial, sans-serif\" font-size=\"24\" font-weight=\"bold\" fill=\"#f9fafb\">Video Layer Gallery</text>\n";
        
        $y = 80;
        foreach ($this->gallery as $item) {
            // Add label
            $svg .= "<text x=\"20\" y=\"" . ($y + 15) . "\" font-family=\"Arial, sans-serif\" font-size=\"14\" font-weight=\"bold\" fill=\"#f3f4f6\">{$item['title']}</text>\n";
            
            // Add description
            $svg .= "<text x=\"20\" y=\"" . ($y + 35) . "\" font-family=\"Arial, sans-serif\" font-size=\"12\" fill=\"#d1d5db\">{$item['description']}</text>\n";
            
            // Add the video content (extract from SVG)
            $content = $this->extractGroupFromSvg($item['svg']);
            if ($content) {
                // Position the content
                $positioned = str_replace(['<g', '<g '], ["<g transform=\"translate(250, $y)\" ", "<g transform=\"translate(250, $y)\" "], $content);
                $svg .= $positioned . "\n";
            }
            
            $y += 120;
        }
        
        $svg .= "</svg>";
        
        $outputDir = __DIR__ . '/output';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        $filename = $outputDir . '/video_layer_gallery.svg';
        file_put_contents($filename, $svg);
        $this->output("Gallery saved to: $filename\n");
    }

    private function createVideoLayer(int $width = 100, int $height = 100, array $properties = []): Layer
    {
        $layer = new Layer();
        $layer->setId(rand(1000, 9999));
        $layer->setType('video');
        $layer->setWidth($width);
        $layer->setHeight($height);
        
        if (!empty($properties)) {
            $layer->setProperties($properties);
        }

        return $layer;
    }

    private function renderLayer(Layer $layer): array
    {
        try {
            $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$layer->getWidth()}\" height=\"{$layer->getHeight()}\">";
            
            if ($this->renderer->canRender($layer)) {
                $element = $this->renderer->render($layer, $this->builder);
                $svg .= $this->builder->getDocument()->saveXML($element);
            }
            
            $svg .= "</svg>";
            
            return ['success' => true, 'svg' => $svg, 'error' => null];
        } catch (Exception $e) {
            return ['success' => false, 'svg' => '', 'error' => $e->getMessage()];
        }
    }

    private function recordTest(string $testName, bool $success, ?string $error): void
    {
        $this->testResults[] = [
            'name' => $testName,
            'success' => $success,
            'error' => $error
        ];
    }

    private function addToGallery(string $title, string $svg, string $description): void
    {
        $this->gallery[] = [
            'title' => $title,
            'svg' => $svg,
            'description' => $description
        ];
    }

    private function extractGroupFromSvg(string $svg): ?string
    {
        $pattern = "/<g[^>]*>.*?<\/g>/s";
        if (preg_match($pattern, $svg, $matches)) {
            return $matches[0];
        }
        return null;
    }

    private function printTestSummary(): void
    {
        $total = count($this->testResults);
        $passed = array_filter($this->testResults, fn($test) => $test['success']);
        $passCount = count($passed);
        $failCount = $total - $passCount;

        $this->output("\n=== Video Layer Test Summary ===\n");
        $this->output("Total tests: $total\n");
        $this->output("Passed: $passCount\n");
        $this->output("Failed: $failCount\n");
        $this->output("Success rate: " . round(($passCount / $total) * 100, 2) . "%\n");

        if ($failCount > 0) {
            $this->output("\nFailed tests:\n");
            foreach ($this->testResults as $test) {
                if (!$test['success']) {
                    $this->output("- {$test['name']}: {$test['error']}\n");
                }
            }
        }

        $this->output("\nGallery items generated: " . count($this->gallery) . "\n");
        $this->output("=================================\n");
    }
}

// Run the tests
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $test = new VideoLayerTest();
    $test->runAllTests();
}
