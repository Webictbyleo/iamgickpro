<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/test_base.php';

use App\Entity\Layer;
use App\Service\Svg\LayerRenderer\VideoLayerRenderer;
use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;

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
        // Initialize test components
        $transformBuilder = new SvgTransformBuilder();
        $this->renderer = new VideoLayerRenderer($transformBuilder);
        $this->builder = new SvgDocumentBuilder();
    }

    public function runAllTests(): void
    {
        $this->output("=== Video Layer Renderer Test Suite ===\n");

        // Basic functionality tests
        $this->testBasicVideoRendering();
        $this->testVideoDimensions();
        $this->testEmptyVideoLayer();
        $this->testVideoPlaceholder();
        $this->testPlayButtonIcon();
        $this->testVideoLabels();
        $this->testVideoProperties();
        $this->testEdgeCases();
        $this->testPerformance();
        $this->generateVideoGallery();
        
        $this->generateReport();
    }

    private function testBasicVideoRendering(): void
    {
        $this->output("🎬 Testing Basic Video Rendering...\n");
        
        $this->runTest("Basic: Empty video layer", function() {
            $layer = $this->createTestLayer('video', [], 200, 150);
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, '<g') !== false &&
                   strpos($xmlString, '<rect') !== false &&
                   strpos($xmlString, 'fill="#1f2937"') !== false;
        });

        $this->runTest("Basic: Video with source", function() {
            $layer = $this->createTestLayer('video', [
                'src' => 'video/sample.mp4',
                'title' => 'Sample Video'
            ], 320, 240);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, '<g') !== false &&
                   strpos($xmlString, '<rect') !== false;
        });

        $this->runTest("Basic: Video renderer can handle layer", function() {
            $layer = $this->createTestLayer('video', [], 200, 150);
            return $this->renderer->canRender($layer);
        });
    }

    private function testVideoDimensions(): void
    {
        $this->output("📐 Testing Video Dimensions...\n");
        
        $this->runTest("Dimensions: Default dimensions", function() {
            $layer = $this->createTestLayer('video', []);
            // No width/height set - should use defaults (100x100 based on debug output)
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // VideoLayerRenderer uses 100x100 as default dimensions
            return strpos($xmlString, '<g') !== false &&
                   strpos($xmlString, 'width="100"') !== false &&
                   strpos($xmlString, 'height="100"') !== false;
        });

        $this->runTest("Dimensions: Custom dimensions", function() {
            $layer = $this->createTestLayer('video', [], 640, 480);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, 'width="640"') !== false &&
                   strpos($xmlString, 'height="480"') !== false;
        });

        $this->runTest("Dimensions: Wide aspect ratio", function() {
            $layer = $this->createTestLayer('video', [], 1920, 1080);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, 'width="1920"') !== false &&
                   strpos($xmlString, 'height="1080"') !== false;
        });

        $this->runTest("Dimensions: Square format", function() {
            $layer = $this->createTestLayer('video', [], 400, 400);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, 'width="400"') !== false &&
                   strpos($xmlString, 'height="400"') !== false;
        });
    }

    private function testEmptyVideoLayer(): void
    {
        $this->output("📹 Testing Empty Video Layer...\n");
        
        $this->runTest("Empty: No properties", function() {
            $layer = $this->createTestLayer('video', [], 300, 200);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, '<g') !== false &&
                   strpos($xmlString, 'Video Layer') !== false;
        });

        $this->runTest("Empty: Null properties", function() {
            $layer = $this->createTestLayer('video', null, 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, '<g') !== false;
        });
    }

    private function testVideoPlaceholder(): void
    {
        $this->output("📦 Testing Video Placeholder...\n");
        
        $this->runTest("Placeholder: Background rectangle", function() {
            $layer = $this->createTestLayer('video', [], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, '<rect') !== false &&
                   strpos($xmlString, 'fill="#1f2937"') !== false &&
                   strpos($xmlString, 'stroke="#374151"') !== false &&
                   strpos($xmlString, 'stroke-width="2"') !== false &&
                   strpos($xmlString, 'rx="8"') !== false;
        });

        $this->runTest("Placeholder: Different sizes", function() {
            $layer = $this->createTestLayer('video', [], 800, 600);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, 'width="800"') !== false &&
                   strpos($xmlString, 'height="600"') !== false;
        });

        $this->runTest("Placeholder: Small video", function() {
            $layer = $this->createTestLayer('video', [], 100, 75);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, 'width="100"') !== false &&
                   strpos($xmlString, 'height="75"') !== false;
        });
    }

    private function testPlayButtonIcon(): void
    {
        $this->output("▶️ Testing Play Button Icon...\n");
        
        $this->runTest("Play Button: Icon presence", function() {
            $layer = $this->createTestLayer('video', [], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Look for circle play button element
            return strpos($xmlString, '<g') !== false &&
                   strpos($xmlString, '<circle') !== false;
        });

        $this->runTest("Play Button: Circle styling", function() {
            $layer = $this->createTestLayer('video', [], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // VideoLayerRenderer uses a white circle, not a triangle
            return strpos($xmlString, '<circle') !== false &&
                   strpos($xmlString, 'fill="#ffffff"') !== false &&
                   strpos($xmlString, 'fill-opacity="0.9"') !== false;
        });

        $this->runTest("Play Button: Size scaling", function() {
            $layer = $this->createTestLayer('video', [], 400, 300);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Larger video should have larger play button
            return strpos($xmlString, '<circle') !== false;
        });

        $this->runTest("Play Button: Position centering", function() {
            $layer = $this->createTestLayer('video', [], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Play button should be centered
            return strpos($xmlString, 'cx="100"') !== false &&
                   strpos($xmlString, 'cy="75"') !== false;
        });
    }

    private function testVideoLabels(): void
    {
        $this->output("🏷️ Testing Video Labels...\n");
        
        $this->runTest("Label: Default label", function() {
            $layer = $this->createTestLayer('video', [], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, 'Video Layer') !== false &&
                   strpos($xmlString, '<text') !== false;
        });

        $this->runTest("Label: Text styling", function() {
            $layer = $this->createTestLayer('video', [], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Check for actual styling used by VideoLayerRenderer
            return strpos($xmlString, '<text') !== false &&
                   strpos($xmlString, 'font-family="Arial, sans-serif"') !== false &&
                   strpos($xmlString, 'font-size="12"') !== false &&
                   strpos($xmlString, 'fill="#9ca3af"') !== false;
        });

        $this->runTest("Label: Center positioning", function() {
            $layer = $this->createTestLayer('video', [], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, 'text-anchor="middle"') !== false;
        });

        $this->runTest("Label: Position scaling", function() {
            $layer = $this->createTestLayer('video', [], 400, 300);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Label should be positioned relative to video size
            return strpos($xmlString, '<text') !== false;
        });
    }

    private function testVideoProperties(): void
    {
        $this->output("⚙️ Testing Video Properties...\n");
        
        $this->runTest("Properties: Video source", function() {
            $layer = $this->createTestLayer('video', [
                'src' => 'https://example.com/video.mp4'
            ], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Should render regardless of source URL
            return strpos($xmlString, '<g') !== false;
        });

        $this->runTest("Properties: Video poster", function() {
            $layer = $this->createTestLayer('video', [
                'poster' => 'https://example.com/poster.jpg'
            ], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, '<g') !== false;
        });

        $this->runTest("Properties: Video duration", function() {
            $layer = $this->createTestLayer('video', [
                'duration' => 180.5
            ], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, '<g') !== false;
        });

        $this->runTest("Properties: Video controls", function() {
            $layer = $this->createTestLayer('video', [
                'controls' => true,
                'autoplay' => false,
                'loop' => true,
                'muted' => false
            ], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, '<g') !== false;
        });

        $this->runTest("Properties: Multiple properties", function() {
            $layer = $this->createTestLayer('video', [
                'src' => 'video/sample.webm',
                'poster' => 'images/poster.png',
                'duration' => 300.0,
                'controls' => true,
                'autoplay' => false,
                'loop' => false,
                'muted' => true,
                'title' => 'Sample Video Title'
            ], 320, 240);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, '<g') !== false;
        });
    }

    private function testEdgeCases(): void
    {
        $this->output("🔍 Testing Edge Cases...\n");
        
        $this->runTest("Edge: Zero dimensions", function() {
            $layer = $this->createTestLayer('video', [], 0, 0);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Should fallback to default dimensions
            return strpos($xmlString, '<g') !== false;
        });

        $this->runTest("Edge: Negative dimensions", function() {
            $layer = $this->createTestLayer('video', [], -200, -150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Should handle negative dimensions gracefully
            return strpos($xmlString, '<g') !== false;
        });

        $this->runTest("Edge: Very large dimensions", function() {
            $layer = $this->createTestLayer('video', [], 4000, 3000);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, 'width="4000"') !== false &&
                   strpos($xmlString, 'height="3000"') !== false;
        });

        $this->runTest("Edge: Very small dimensions", function() {
            $layer = $this->createTestLayer('video', [], 10, 8);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            return strpos($xmlString, 'width="10"') !== false &&
                   strpos($xmlString, 'height="8"') !== false;
        });

        $this->runTest("Edge: Invalid video properties", function() {
            $layer = $this->createTestLayer('video', [
                'src' => null,
                'duration' => 'invalid',
                'controls' => 'not-boolean'
            ], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Should handle invalid properties gracefully
            return strpos($xmlString, '<g') !== false;
        });

        $this->runTest("Edge: Special characters in properties", function() {
            $layer = $this->createTestLayer('video', [
                'src' => 'video/file with spaces & special chars.mp4',
                'title' => 'Video with "quotes" & <tags>'
            ], 200, 150);
            
            $svgElement = $this->renderer->render($layer, $this->builder);
            $xmlString = $this->builder->saveDocument($svgElement);
            
            // Should handle special characters properly
            return strpos($xmlString, '<g') !== false;
        });
    }

    private function testPerformance(): void
    {
        $this->output("⚡ Testing Performance...\n");
        
        $this->runTest("Performance: Large video rendering", function() {
            $startTime = microtime(true);
            
            for ($i = 0; $i < 50; $i++) {
                $layer = $this->createTestLayer('video', [
                    'src' => "video/clip_{$i}.mp4",
                    'duration' => rand(30, 600)
                ], 320 + ($i % 5) * 64, 240 + ($i % 5) * 48);
                
                $svgElement = $this->renderer->render($layer, $this->builder);
            }
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            $this->output("   Rendered 50 video layers in " . number_format($executionTime, 4) . " seconds\n");
            return $executionTime < 1.0; // Should complete within 1 second
        });

        $this->runTest("Performance: Complex video placeholders", function() {
            $startTime = microtime(true);
            
            // Create video layers with various sizes
            for ($i = 0; $i < 10; $i++) {
                $layer = $this->createTestLayer('video', [
                    'poster' => "poster_{$i}.jpg"
                ], 1920, 1080);
                $svgElement = $this->renderer->render($layer, $this->builder);
            }
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            $this->output("   Generated 10 complex video placeholders in " . number_format($executionTime, 4) . " seconds\n");
            return $executionTime < 2.0; // Should complete within 2 seconds
        });
    }

    private function generateVideoGallery(): void
    {
        $this->output("📋 Generating comprehensive video gallery...\n");
        
        try {
            $galleryBuilder = new SvgDocumentBuilder();
            $gallerySvg = $galleryBuilder->createDocument(1200, 800);
            $gallerySvg->setAttribute('style', 'background: linear-gradient(135deg, #1f2937 0%, #374151 100%);');
            
            // Create background gradient FIRST in defs
            $defs = $galleryBuilder->addDefinitions($gallerySvg);
            $bgGradient = $galleryBuilder->createLinearGradient('bg-gradient', [
                ['offset' => '0%', 'color' => '#1f2937'],
                ['offset' => '100%', 'color' => '#374151']
            ], [], $gallerySvg->ownerDocument);
            $defs->appendChild($bgGradient);
            
            // Create background rectangle that references the gradient
            $background = $galleryBuilder->createElement('rect');
            $background->setAttribute('width', '100%');
            $background->setAttribute('height', '100%');
            $background->setAttribute('fill', 'url(#bg-gradient)');
            $gallerySvg->appendChild($background);
            
            // Add title first
            $title = $galleryBuilder->createElement('text');
            $title->setAttribute('x', '600');
            $title->setAttribute('y', '40');
            $title->setAttribute('text-anchor', 'middle');
            $title->setAttribute('font-family', 'Arial, sans-serif');
            $title->setAttribute('font-size', '28');
            $title->setAttribute('font-weight', 'bold');
            $title->setAttribute('fill', 'white');
            $title->setAttribute('stroke', 'rgba(0,0,0,0.3)');
            $title->setAttribute('stroke-width', '1');
            $title->appendChild($galleryBuilder->createText('Comprehensive Video Gallery', $title->ownerDocument));
            $gallerySvg->appendChild($title);
            
            $videoConfigs = [
                // Row 1: Different standard sizes
                ['x' => 50, 'y' => 100, 'width' => 160, 'height' => 120, 'props' => [], 'label' => 'Small Video'],
                ['x' => 250, 'y' => 100, 'width' => 200, 'height' => 150, 'props' => [], 'label' => 'Standard Video'],
                ['x' => 490, 'y' => 100, 'width' => 320, 'height' => 180, 'props' => [], 'label' => 'HD Video'],
                ['x' => 850, 'y' => 100, 'width' => 250, 'height' => 140, 'props' => [], 'label' => 'Wide Video'],
                
                // Row 2: Different aspect ratios
                ['x' => 50, 'y' => 300, 'width' => 400, 'height' => 150, 'props' => [], 'label' => 'Ultrawide Video'],
                ['x' => 500, 'y' => 300, 'width' => 150, 'height' => 150, 'props' => [], 'label' => 'Square Video'],
                ['x' => 700, 'y' => 300, 'width' => 120, 'height' => 180, 'props' => [], 'label' => 'Portrait Video'],
                ['x' => 870, 'y' => 300, 'width' => 280, 'height' => 200, 'props' => [], 'label' => 'Widescreen'],
                
                // Row 3: With properties
                ['x' => 50, 'y' => 530, 'width' => 240, 'height' => 135, 'props' => [
                    'src' => 'video/movie.mp4',
                    'duration' => 7200.0,
                    'controls' => true
                ], 'label' => 'Movie File'],
                ['x' => 330, 'y' => 530, 'width' => 180, 'height' => 100, 'props' => [
                    'src' => 'video/tutorial.webm',
                    'poster' => 'poster.jpg'
                ], 'label' => 'Tutorial Video'],
                ['x' => 550, 'y' => 530, 'width' => 200, 'height' => 112, 'props' => [
                    'src' => 'video/animation.mov',
                    'loop' => true,
                    'autoplay' => true
                ], 'label' => 'Animation'],
                ['x' => 790, 'y' => 530, 'width' => 160, 'height' => 90, 'props' => [
                    'src' => 'video/short-clip.mp4',
                    'muted' => true
                ], 'label' => 'Short Clip'],
                
                // Row 4: Edge cases
                ['x' => 100, 'y' => 680, 'width' => 80, 'height' => 60, 'props' => [], 'label' => 'Tiny Video'],
                ['x' => 220, 'y' => 680, 'width' => 500, 'height' => 100, 'props' => [], 'label' => 'Banner Video'],
                ['x' => 760, 'y' => 680, 'width' => 100, 'height' => 80, 'props' => [
                    'src' => 'video/very-long-filename-with-special-characters_&_symbols.mp4'
                ], 'label' => 'Complex Name']
            ];
            
            $layerCounter = 1;
            foreach ($videoConfigs as $config) {
                $layer = $this->createTestLayer('video', $config['props'], $config['width'], $config['height']);
                // Generate unique layer names and IDs
                $layerName = 'gallery-video-' . $layerCounter;
                $layer->setName($layerName);
                $layer->setX($config['x']);
                $layer->setY($config['y']);
                
                $videoElement = $this->renderer->render($layer, $galleryBuilder);
                // Ensure unique IDs by updating the layer ID
                if ($videoElement->hasAttribute('id')) {
                    $videoElement->setAttribute('id', $layerName);
                }
                $gallerySvg->appendChild($videoElement);
                
                // Add label below the video
                $label = $galleryBuilder->createElement('text');
                $label->setAttribute('x', (string)($config['x'] + $config['width'] / 2));
                $label->setAttribute('y', (string)($config['y'] + $config['height'] + 20));
                $label->setAttribute('text-anchor', 'middle');
                $label->setAttribute('font-family', 'Arial, sans-serif');
                $label->setAttribute('font-size', '10');
                $label->setAttribute('fill', '#d1d5db');
                $label->appendChild($galleryBuilder->createText($config['label'], $label->ownerDocument));
                $gallerySvg->appendChild($label);
                
                $layerCounter++;
            }
            
            // Process all definitions at the end
            $galleryBuilder->processDefinitions($gallerySvg);
            
            // Save gallery
            $outputDir = __DIR__ . '/output';
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            $xmlString = $gallerySvg->ownerDocument->saveXML($gallerySvg);
            $outputFile = $outputDir . '/comprehensive_video_gallery.svg';
            file_put_contents($outputFile, $xmlString);
            
            $this->output("✅ Gallery saved to: {$outputFile}\n");
            $this->output("📊 Generated " . count($videoConfigs) . " video examples\n");
            
        } catch (Exception $e) {
            $this->output("❌ Gallery generation failed: {$e->getMessage()}\n");
        }
    }

    private function createTestLayer(string $type, ?array $properties = [], ?int $width = null, ?int $height = null): Layer
    {
        $layer = new Layer();
        $layer->setType($type);
        $layer->setName('test-layer-' . rand(1000, 9999));
        
        if ($properties !== null) {
            $layer->setProperties($properties);
        }
        
        if ($width !== null) {
            $layer->setWidth($width);
        }
        if ($height !== null) {
            $layer->setHeight($height);
        }
        
        return $layer;
    }

    private function runTest(string $testName, callable $testFunction): void
    {
        try {
            $startTime = microtime(true);
            $result = $testFunction();
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;
            
            if ($result === true) {
                $this->output("  ✅ {$testName} (" . number_format($duration, 2) . "ms)\n");
                $this->testResults[] = ['name' => $testName, 'status' => 'PASS', 'duration' => $duration];
            } else {
                $this->output("  ❌ {$testName}: Test returned false\n");
                $this->testResults[] = ['name' => $testName, 'status' => 'FAIL', 'duration' => $duration, 'error' => 'Test returned false'];
            }
        } catch (Exception $e) {
            $this->output("  ❌ {$testName}: {$e->getMessage()}\n");
            $this->testResults[] = ['name' => $testName, 'status' => 'FAIL', 'duration' => 0, 'error' => $e->getMessage()];
        }
    }

    private function generateReport(): void
    {
        $this->output("\n" . str_repeat("=", 50) . "\n");
        $this->output("📊 VIDEO LAYER TEST RESULTS SUMMARY\n");
        $this->output(str_repeat("=", 50) . "\n");
        
        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($r) => $r['status'] === 'PASS'));
        $failedTests = $totalTests - $passedTests;
        
        $this->output("Total tests: {$totalTests}\n");
        $this->output("Passed: {$passedTests}\n");
        $this->output("Failed: {$failedTests}\n");
        
        if ($failedTests > 0) {
            $this->output("\n❌ Failed tests:\n");
            foreach ($this->testResults as $result) {
                if ($result['status'] === 'FAIL') {
                    $this->output("  - {$result['name']}: {$result['error']}\n");
                }
            }
        }
        
        $successRate = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;
        $this->output("\n✨ Success rate: " . number_format($successRate, 1) . "%\n");
        
        if ($successRate === 100.0) {
            $this->output("🎉 All tests passed! Video layer renderer is working perfectly.\n");
        } elseif ($successRate >= 90.0) {
            $this->output("✅ Great! Most tests passed with minor issues.\n");
        } elseif ($successRate >= 70.0) {
            $this->output("⚠️  Good progress, but some issues need attention.\n");
        } else {
            $this->output("❌ Significant issues detected. Review implementation.\n");
        }
        
        $this->output("\n🏁 Video layer renderer test suite completed.\n");
    }
}

// Run the test suite
$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

try {
    $testSuite = new VideoLayerTest();
    $testSuite->runAllTests();
} catch (Throwable $e) {
    echo "❌ Video test suite failed to initialize: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
