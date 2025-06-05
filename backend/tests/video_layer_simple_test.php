<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/test_base.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\VideoLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;

/**
 * Simplified test suite for VideoLayerRenderer
 */
class VideoLayerTestSimple extends TestBase
{
    private VideoLayerRenderer $renderer;
    private SvgDocumentBuilder $builder;
    private array $testResults = [];
    private array $gallery = [];

    public function __construct()
    {
        // Initialize test components
        $transformBuilder = new SvgTransformBuilder();
        $this->renderer = new VideoLayerRenderer($transformBuilder);
        $this->builder = new SvgDocumentBuilder();
    }

    public function runAllTests(): void
    {
        $this->output("=== Video Layer Renderer Test Suite (Simplified) ===\n");

        // Basic functionality tests only
        $this->testBasicVideoRendering();
        $this->testVideoDimensions();
        $this->testEmptyVideoLayer();

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
        
        $this->recordTest('Basic Video', $result['success'], $result['error']);
        $this->addToGallery('Basic Video', $result['svg'], 'Standard video layer');
    }

    private function testVideoDimensions(): void
    {
        $this->output("Testing video dimensions...\n");

        $dimensions = [
            [100, 100],
            [200, 150],
            [300, 200]
        ];

        foreach ($dimensions as [$width, $height]) {
            $layer = $this->createVideoLayer($width, $height);
            $result = $this->renderLayer($layer);

            $this->assertTrue($result['success'], "Video rendering {$width}x{$height} should succeed");
            $this->recordTest("Video {$width}x{$height}", $result['success'], $result['error']);
            $this->addToGallery("Video {$width}x{$height}", $result['svg'], "Video player {$width}x{$height}");
        }
    }

    private function testEmptyVideoLayer(): void
    {
        $this->output("Testing empty video layer...\n");

        $layer = $this->createVideoLayer(0, 0);
        $result = $this->renderLayer($layer);

        $this->assertTrue($result['success'], 'Empty video layer should render');
        $this->recordTest('Empty Video', $result['success'], $result['error']);
        $this->addToGallery('Default Video', $result['svg'], 'Video with default dimensions');
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
                // Position the content - only replace the first <g tag
                $positioned = preg_replace('/^<g/', "<g transform=\"translate(250, $y)\"", $content, 1);
                $svg .= $positioned . "\n";
            }
            
            $y += 120;
        }
        
        $svg .= "</svg>";
        
        $outputDir = __DIR__ . '/output';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        $filename = $outputDir . '/video_layer_gallery_simple.svg';
        file_put_contents($filename, $svg);
        $this->output("Gallery saved to: $filename\n");
    }

    private function createVideoLayer(int $width = 100, int $height = 100, array $properties = []): Layer
    {
        $layer = new Layer();
        $layer->setType('video');
        $layer->setWidth($width);
        $layer->setHeight($height);
        $layer->setX(0);
        $layer->setY(0);
        $layer->setZIndex(1);
        $layer->setVisible(true);
        $layer->setOpacity(1.0);
        
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
                $svg .= $element->ownerDocument->saveXML($element);
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
    $test = new VideoLayerTestSimple();
    $test->runAllTests();
}
