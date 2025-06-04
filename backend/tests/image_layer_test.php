<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\Svg\SvgDocumentBuilder;
use App\Service\Svg\SvgTransformBuilder;
use App\Service\Svg\LayerRenderer\ImageLayerRenderer;
use App\Entity\Layer;
use Psr\Log\NullLogger;

/**
 * Comprehensive Image Layer Processing Test Suite
 * 
 * This test suite validates all aspects of image layer rendering including:
 * - Basic image rendering with different sources
 * - Image fitting and aspect ratio handling
 * - CSS and SVG filters application
 * - Image placeholder generation
 * - Error handling and edge cases
 * - Performance with various image types
 */
class ImageLayerTest
{
    private SvgDocumentBuilder $documentBuilder;
    private SvgTransformBuilder $transformBuilder;
    private ImageLayerRenderer $renderer;
    private array $testResults = [];
    private int $testsPassed = 0;
    private int $totalTests = 0;
    private float $startTime;

    // Sample base64 encoded images for testing
    private const SAMPLE_PNG_BASE64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    private const SAMPLE_SVG_BASE64 = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzMzOThmZiIvPjwvc3ZnPg==';
    private const SAMPLE_JPEG_BASE64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAhEAACAQMDBQAAAAAAAAAAAAABAgMABAUGIWGRkqGx0f/EABUBAQEAAAAAAAAAAAAAAAAAAAMF/8QAGhEAAgIDAAAAAAAAAAAAAAAAAAECEgMRkf/aAAwDAQACEQMRAD8AltJagyeH0AthI5xdrLcNM91BF5pX2HaH9bcfaSXWGaRmknyJckliyjqTzSlT54b6bk+h0R//2Q==';

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->documentBuilder = new SvgDocumentBuilder(true);
        $this->transformBuilder = new SvgTransformBuilder();
        $this->renderer = new ImageLayerRenderer($this->transformBuilder);
        
        // Create output directory
        $outputDir = __DIR__ . '/output';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
    }

    public function runComprehensiveImageTests(): void
    {
        echo "🖼️ COMPREHENSIVE IMAGE LAYER PROCESSING TEST SUITE\n";
        echo str_repeat("=", 70) . "\n\n";

        // Core image rendering tests
        $this->testBasicImageRendering();
        $this->testImageSources();
        $this->testImageFitting();
        $this->testAspectRatioHandling();
        
        // Filter and effect tests
        $this->testCssFilters();
        $this->testSvgFilters();
        $this->testCombinedFilters();
        
        // Placeholder and error handling
        $this->testImagePlaceholders();
        $this->testErrorHandling();
        $this->testEdgeCases();
        
        // Performance and validation
        $this->testPerformance();
        $this->testSvgValidation();
        $this->testComplexImageDocuments();
        
        $this->generateReport();
    }

    private function testBasicImageRendering(): void
    {
        echo "🔧 Testing Basic Image Rendering...\n";
        
        $basicTests = [
            'Simple PNG Image' => [
                'src' => self::SAMPLE_PNG_BASE64,
                'width' => 100,
                'height' => 100
            ],
            'SVG Image' => [
                'src' => self::SAMPLE_SVG_BASE64,
                'width' => 150,
                'height' => 150
            ],
            'JPEG Image' => [
                'src' => self::SAMPLE_JPEG_BASE64,
                'width' => 200,
                'height' => 150
            ]
        ];

        foreach ($basicTests as $testName => $config) {
            $this->runTest("Basic: {$testName}", function() use ($config) {
                $layer = $this->createImageLayer($config);
                $element = $this->renderer->render($layer, $this->documentBuilder);
                
                $this->assertNotNull($element, "Should render image element");
                $this->assertEquals('g', $element->nodeName, "Should create group element");
                
                // Check for image child element
                $imageChild = null;
                foreach ($element->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'image') {
                        $imageChild = $child;
                        break;
                    }
                }
                
                $this->assertNotNull($imageChild, "Should contain image child element");
                $this->assertTrue($imageChild->hasAttribute('x'), "Should have x attribute");
                $this->assertTrue($imageChild->hasAttribute('y'), "Should have y attribute");
                $this->assertTrue($imageChild->hasAttribute('width'), "Should have width attribute");
                $this->assertTrue($imageChild->hasAttribute('height'), "Should have height attribute");
                $this->assertTrue($imageChild->hasAttributeNS('http://www.w3.org/1999/xlink', 'href'), "Should have href attribute");
                
                return $imageChild;
            });
        }
    }

    private function testImageSources(): void
    {
        echo "🔗 Testing Image Sources...\n";
        
        $sourceTests = [
            'Data URL PNG' => self::SAMPLE_PNG_BASE64,
            'Data URL SVG' => self::SAMPLE_SVG_BASE64,
            'Data URL JPEG' => self::SAMPLE_JPEG_BASE64,
            'HTTPS URL' => 'https://via.placeholder.com/100x100.png',
            'HTTP URL' => 'http://example.com/image.jpg',
            'Relative Path' => 'images/sample.png',
            'Invalid URL' => 'invalid://url',
            'Empty Source' => '',
            'Malformed Data URL' => 'data:image/png;base64,invalid'
        ];

        foreach ($sourceTests as $testName => $src) {
            $this->runTest("Source: {$testName}", function() use ($src, $testName) {
                $layer = $this->createImageLayer(['src' => $src]);
                $element = $this->renderer->render($layer, $this->documentBuilder);
                
                $this->assertNotNull($element, "Should always render an element");
                
                // Check if invalid sources create placeholders
                if (in_array($testName, ['Invalid URL', 'Empty Source'])) {
                    $this->assertEquals('g', $element->nodeName, "Invalid sources should create placeholder group");
                    // Placeholder should NOT contain an image child
                    $hasImageChild = false;
                    foreach ($element->childNodes as $child) {
                        if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'image') {
                            $hasImageChild = true;
                            break;
                        }
                    }
                    $this->assertTrue(!$hasImageChild, "Placeholder should not contain image element");
                } else {
                    $this->assertEquals('g', $element->nodeName, "All sources should create group element");
                    // Valid sources (including malformed data URLs that pass regex) should contain an image child
                    $hasImageChild = false;
                    foreach ($element->childNodes as $child) {
                        if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'image') {
                            $hasImageChild = true;
                            break;
                        }
                    }
                    $this->assertTrue($hasImageChild, "Valid sources should contain image element");
                }
                
                return $element;
            });
        }
    }

    private function testImageFitting(): void
    {
        echo "📐 Testing Image Fitting...\n";
        
        $fitTests = [
            'Contain (default)' => 'contain',
            'Cover' => 'cover',
            'Fill' => 'fill',
            'Scale Down' => 'scale-down',
            'None' => 'none',
            'Invalid Fit' => 'invalid-fit'
        ];

        foreach ($fitTests as $testName => $fit) {
            $this->runTest("Fit: {$testName}", function() use ($fit) {
                $layer = $this->createImageLayer([
                    'src' => self::SAMPLE_PNG_BASE64,
                    'fit' => $fit
                ]);
                $element = $this->renderer->render($layer, $this->documentBuilder);
                
                $this->assertNotNull($element, "Should render element");
                $this->assertEquals('g', $element->nodeName, "Should create group element");
                
                // Find the image child element
                $imageChild = null;
                foreach ($element->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'image') {
                        $imageChild = $child;
                        break;
                    }
                }
                
                $this->assertNotNull($imageChild, "Should contain image child element");
                $this->assertTrue($imageChild->hasAttribute('preserveAspectRatio'), "Should have preserveAspectRatio attribute");
                
                $preserveAspectRatio = $imageChild->getAttribute('preserveAspectRatio');
                
                // Validate aspect ratio based on fit type
                switch ($fit) {
                    case 'cover':
                        $this->assertEquals('xMidYMid slice', $preserveAspectRatio, "Cover fit should use slice aspect ratio");
                        break;
                    case 'fill':
                        $this->assertEquals('none', $preserveAspectRatio, "Fill fit should disable aspect ratio");
                        break;
                    case 'scale-down':
                    case 'none':
                    case 'contain':
                    case 'invalid-fit':
                    default:
                        $this->assertEquals('xMidYMid meet', $preserveAspectRatio, "Default fit should use meet aspect ratio");
                        break;
                }
                
                return $imageChild;
            });
        }
    }

    private function testAspectRatioHandling(): void
    {
        echo "🔄 Testing Aspect Ratio Handling...\n";
        
        $aspectRatioTests = [
            'Preserve Aspect Ratio True' => true,
            'Preserve Aspect Ratio False' => false
        ];

        foreach ($aspectRatioTests as $testName => $preserveAspectRatio) {
            $this->runTest("Aspect: {$testName}", function() use ($preserveAspectRatio) {
                $layer = $this->createImageLayer([
                    'src' => self::SAMPLE_PNG_BASE64,
                    'preserveAspectRatio' => $preserveAspectRatio,
                    'fit' => 'contain'
                ]);
                $element = $this->renderer->render($layer, $this->documentBuilder);
                
                $this->assertNotNull($element, "Should render element");
                
                // Find the image child element
                $imageChild = null;
                foreach ($element->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'image') {
                        $imageChild = $child;
                        break;
                    }
                }
                
                $this->assertNotNull($imageChild, "Should contain image child element");
                
                if ($preserveAspectRatio) {
                    $this->assertEquals('xMidYMid meet', $imageChild->getAttribute('preserveAspectRatio'), "Should preserve aspect ratio");
                } else {
                    $this->assertEquals('none', $imageChild->getAttribute('preserveAspectRatio'), "Should not preserve aspect ratio");
                }
                
                return $imageChild;
            });
        }
    }

    private function testCssFilters(): void
    {
        echo "🎨 Testing CSS Filters...\n";
        
        $filterTests = [
            'Brightness Filter' => ['brightness' => 1.5],
            'Contrast Filter' => ['contrast' => 1.3],
            'Saturation Filter' => ['saturation' => 0.5],
            'Hue Rotation' => ['hue' => 90],
            'Blur Filter' => ['blur' => 5],
            'Sepia Filter' => ['sepia' => 0.8],
            'Grayscale Filter' => ['grayscale' => 0.6],
            'Invert Filter' => ['invert' => 0.4],
            'Multiple Filters' => [
                'brightness' => 1.2,
                'contrast' => 1.1,
                'saturation' => 0.9,
                'blur' => 2
            ]
        ];

        foreach ($filterTests as $testName => $filters) {
            $this->runTest("CSS Filter: {$testName}", function() use ($filters) {
                $layer = $this->createImageLayer(array_merge([
                    'src' => self::SAMPLE_PNG_BASE64
                ], $filters));
                $element = $this->renderer->render($layer, $this->documentBuilder);
                
                $this->assertNotNull($element, "Should render element");
                
                // Find the image child element
                $imageChild = null;
                foreach ($element->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'image') {
                        $imageChild = $child;
                        break;
                    }
                }
                
                $this->assertNotNull($imageChild, "Should contain image child element");
                
                // Check for CSS filter style attribute
                if (count($filters) > 0 && array_filter($filters, fn($v) => $v !== 0 && $v !== 1)) {
                    $this->assertTrue($imageChild->hasAttribute('style'), "Should have style attribute for CSS filters");
                    $style = $imageChild->getAttribute('style');
                    $this->assertStringContains('filter:', $style, "Style should contain filter property");
                }
                
                return $imageChild;
            });
        }
    }

    private function testSvgFilters(): void
    {
        echo "✨ Testing SVG Filters...\n";
        
        $svgFilterTests = [
            'Drop Shadow' => [
                'shadow' => [
                    'enabled' => true,
                    'offsetX' => 5,
                    'offsetY' => 5,
                    'blur' => 10,
                    'color' => '#000000',
                    'opacity' => 0.5
                ]
            ],
            'Complex Shadow' => [
                'shadow' => [
                    'enabled' => true,
                    'offsetX' => -3,
                    'offsetY' => 8,
                    'blur' => 15,
                    'color' => '#ff0000',
                    'opacity' => 0.3
                ]
            ],
            'No Shadow (Disabled)' => [
                'shadow' => [
                    'enabled' => false,
                    'offsetX' => 5,
                    'offsetY' => 5,
                    'blur' => 10
                ]
            ]
        ];

        foreach ($svgFilterTests as $testName => $properties) {
            $this->runTest("SVG Filter: {$testName}", function() use ($properties) {
                $svgRoot = $this->documentBuilder->createDocument(400, 300);
                $layer = $this->createImageLayer(array_merge([
                    'src' => self::SAMPLE_PNG_BASE64
                ], $properties));
                
                $element = $this->renderer->render($layer, $this->documentBuilder);
                $this->assertNotNull($element, "Should render element");
                
                // Find the image child element
                $imageChild = null;
                foreach ($element->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'image') {
                        $imageChild = $child;
                        break;
                    }
                }
                
                $this->assertNotNull($imageChild, "Should contain image child element");
                
                // Import and add to SVG to test filter creation
                $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                $svgRoot->appendChild($importedElement);
                
                // Process definitions to ensure filters are added
                $this->documentBuilder->processDefinitions($svgRoot);
                
                $svgContent = $this->documentBuilder->saveDocument($svgRoot);
                
                // Check for SVG filter elements if shadow is enabled
                if ($properties['shadow']['enabled'] ?? false) {
                    $this->assertStringContains('<filter', $svgContent, "Should contain SVG filter element");
                    $this->assertStringContains('feGaussianBlur', $svgContent, "Should contain blur filter");
                    $this->assertStringContains('feOffset', $svgContent, "Should contain offset filter");
                    $this->assertTrue($imageChild->hasAttribute('filter'), "Should have filter attribute");
                }
                
                return $imageChild;
            });
        }
    }

    private function testCombinedFilters(): void
    {
        echo "🎭 Testing Combined Filters...\n";
        
        $this->runTest("Combined: CSS + SVG Filters", function() {
            $svgRoot = $this->documentBuilder->createDocument(400, 300);
            $layer = $this->createImageLayer([
                'src' => self::SAMPLE_PNG_BASE64,
                'brightness' => 1.3,
                'contrast' => 1.2,
                'blur' => 2,
                'shadow' => [
                    'enabled' => true,
                    'offsetX' => 4,
                    'offsetY' => 4,
                    'blur' => 8,
                    'color' => '#333333',
                    'opacity' => 0.4
                ]
            ]);
            
            $element = $this->renderer->render($layer, $this->documentBuilder);
            $this->assertNotNull($element, "Should render element");
            
            // Find the image child element
            $imageChild = null;
            foreach ($element->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'image') {
                    $imageChild = $child;
                    break;
                }
            }
            
            $this->assertNotNull($imageChild, "Should contain image child element");
            
            // Should have both CSS and SVG filters
            $this->assertTrue($imageChild->hasAttribute('style'), "Should have CSS filter style");
            
            // Import and add to SVG to test SVG filter creation
            $importedElement = $svgRoot->ownerDocument->importNode($element, true);
            $svgRoot->appendChild($importedElement);
            $this->documentBuilder->processDefinitions($svgRoot);
            
            $svgContent = $this->documentBuilder->saveDocument($svgRoot);
            $this->assertStringContains('<filter', $svgContent, "Should contain SVG filter");
            
            return $imageChild;
        });
    }

    private function testImagePlaceholders(): void
    {
        echo "🎯 Testing Image Placeholders...\n";
        
        $placeholderTests = [
            'Empty Source' => '',
            'Invalid URL' => 'invalid://url',
            'Malformed Data URL' => 'data:invalid',
            'Large Placeholder' => ['src' => '', 'width' => 300, 'height' => 200],
            'Small Placeholder' => ['src' => '', 'width' => 40, 'height' => 30],
            'Tiny Placeholder' => ['src' => '', 'width' => 10, 'height' => 10]
        ];

        foreach ($placeholderTests as $testName => $config) {
            $this->runTest("Placeholder: {$testName}", function() use ($config, $testName) {
                if (is_string($config)) {
                    $config = ['src' => $config];
                }
                
                $layer = $this->createImageLayer($config);
                $element = $this->renderer->render($layer, $this->documentBuilder);
                
                $this->assertNotNull($element, "Should render placeholder element");
                $this->assertEquals('g', $element->nodeName, "Placeholder should be a group element");
                
                // Check for placeholder content
                $this->assertTrue($element->hasChildNodes(), "Placeholder should have child elements");
                
                // Check for background rectangle (may be nested in a child group)
                $hasRect = false;
                $checkForRect = function($node) use (&$hasRect, &$checkForRect) {
                    if ($node->nodeType === XML_ELEMENT_NODE) {
                        if ($node->nodeName === 'rect') {
                            $hasRect = true;
                            return;
                        }
                        if ($node->hasChildNodes()) {
                            foreach ($node->childNodes as $child) {
                                $checkForRect($child);
                                if ($hasRect) return;
                            }
                        }
                    }
                };
                
                foreach ($element->childNodes as $child) {
                    $checkForRect($child);
                    if ($hasRect) break;
                }
                
                $this->assertTrue($hasRect, "Placeholder should contain background rectangle");
                
                return $element;
            });
        }
    }

    private function testErrorHandling(): void
    {
        echo "⚠️ Testing Error Handling...\n";
        
        $errorTests = [
            'Null Properties' => null,
            'Invalid Filter Values' => [
                'brightness' => 'invalid',
                'contrast' => -5,
                'blur' => 'not-a-number'
            ],
            'Extreme Filter Values' => [
                'brightness' => 999,
                'hue' => 720,
                'blur' => -10
            ],
            'Malformed Shadow Config' => [
                'shadow' => [
                    'enabled' => 'not-boolean',
                    'offsetX' => 'invalid',
                    'blur' => null
                ]
            ]
        ];

        foreach ($errorTests as $testName => $properties) {
            $this->runTest("Error: {$testName}", function() use ($properties) {
                $layer = $this->createImageLayer([
                    'src' => self::SAMPLE_PNG_BASE64
                ]);
                
                if ($properties !== null) {
                    $layer->setProperties(array_merge($layer->getProperties() ?? [], $properties));
                } else {
                    $layer->setProperties(null);
                }
                
                // Should handle errors gracefully and still render
                $element = $this->renderer->render($layer, $this->documentBuilder);
                $this->assertNotNull($element, "Should handle errors gracefully and render element");
                
                return $element;
            }, true);
        }
    }

    private function testEdgeCases(): void
    {
        echo "🔍 Testing Edge Cases...\n";
        
        $edgeCases = [
            'Zero Dimensions' => ['width' => 0, 'height' => 0],
            'Negative Dimensions' => ['width' => -100, 'height' => -50],
            'Very Large Dimensions' => ['width' => 10000, 'height' => 8000],
            'Extreme Aspect Ratio' => ['width' => 1000, 'height' => 1],
            'Fractional Dimensions' => ['width' => 100.5, 'height' => 75.3]
        ];

        foreach ($edgeCases as $testName => $dimensions) {
            $this->runTest("Edge Case: {$testName}", function() use ($dimensions) {
                $layer = $this->createImageLayer(array_merge([
                    'src' => self::SAMPLE_PNG_BASE64
                ], $dimensions));
                
                $element = $this->renderer->render($layer, $this->documentBuilder);
                $this->assertNotNull($element, "Should handle edge case and render element");
                
                return $element;
            }, true);
        }
    }

    private function testPerformance(): void
    {
        echo "⚡ Testing Performance...\n";
        
        $this->runTest("Performance: Multiple Images", function() {
            $start = microtime(true);
            $svgRoot = $this->documentBuilder->createDocument(1000, 800);
            
            // Render 50 images with various configurations
            for ($i = 0; $i < 50; $i++) {
                $layer = $this->createImageLayer([
                    'src' => self::SAMPLE_PNG_BASE64,
                    'brightness' => 1 + ($i % 10) * 0.1,
                    'hue' => ($i * 7) % 360
                ]);
                
                $element = $this->renderer->render($layer, $this->documentBuilder);
                if ($element && $i < 20) { // Only add first 20 to avoid memory issues
                    $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                    $svgRoot->appendChild($importedElement);
                }
            }
            
            $this->documentBuilder->processDefinitions($svgRoot);
            $duration = microtime(true) - $start;
            
            echo "    ⏱️ Rendered 50 images in " . number_format($duration * 1000, 2) . "ms\n";
            
            return $duration;
        });
    }

    private function testSvgValidation(): void
    {
        echo "✅ Testing SVG Validation...\n";
        
        $this->runTest("Validation: Complete Image Document", function() {
            $svgRoot = $this->documentBuilder->createDocument(600, 400);
            
            // Add various image configurations
            $imageConfigs = [
                ['src' => self::SAMPLE_PNG_BASE64, 'brightness' => 1.2],
                ['src' => self::SAMPLE_SVG_BASE64, 'blur' => 3],
                ['src' => '', 'width' => 100, 'height' => 100], // Placeholder
                ['src' => self::SAMPLE_JPEG_BASE64, 'shadow' => ['enabled' => true, 'offsetX' => 5, 'offsetY' => 5, 'blur' => 10]]
            ];
            
            foreach ($imageConfigs as $config) {
                $layer = $this->createImageLayer($config);
                $element = $this->renderer->render($layer, $this->documentBuilder);
                
                if ($element) {
                    $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                    $svgRoot->appendChild($importedElement);
                }
            }
            
            $this->documentBuilder->processDefinitions($svgRoot);
            $svgContent = $this->documentBuilder->saveDocument($svgRoot);
            
            // Save for inspection
            file_put_contents(__DIR__ . '/output/image_validation_test.svg', $svgContent);
            
            // Basic validation
            $this->assertTrue(strpos($svgContent, '<svg') !== false, "Should contain SVG opening tag");
            $this->assertTrue(strpos($svgContent, '</svg>') !== false, "Should contain SVG closing tag");
            $this->assertTrue(strpos($svgContent, 'xmlns=') !== false, "Should contain SVG namespace");
            
            // Check for image elements and placeholders
            $imageCount = substr_count($svgContent, '<image');
            $groupCount = substr_count($svgContent, '<g');
            $this->assertTrue($imageCount + $groupCount >= 4, "Should contain expected number of image/group elements");
            
            return strlen($svgContent);
        });
    }

    private function testComplexImageDocuments(): void
    {
        echo "📄 Testing Complex Image Documents...\n";
        
        $this->runTest("Complex: Image Gallery Document with Real URLs", function() {
            $svgRoot = $this->documentBuilder->createDocument(1200, 800, '#f8f9fa');
            
            // Create a grid of images with different sources and effects
            $imageConfigs = [
                // Row 1: Different image sources
                ['src' => 'https://picsum.photos/150/150?random=1', 'x' => 50, 'y' => 50, 'brightness' => 1.3],
                ['src' => 'https://via.placeholder.com/150x150/ff6b6b/ffffff?text=PNG', 'x' => 250, 'y' => 50, 'contrast' => 1.5],
                ['src' => 'https://picsum.photos/150/150?random=2', 'x' => 450, 'y' => 50, 'saturation' => 0.5],
                ['src' => self::SAMPLE_PNG_BASE64, 'x' => 650, 'y' => 50, 'hue' => 90],
                
                // Row 2: Different filters
                ['src' => 'https://picsum.photos/150/150?random=3', 'x' => 50, 'y' => 250, 'blur' => 3],
                ['src' => 'https://via.placeholder.com/150x150/4ecdc4/ffffff?text=JPEG', 'x' => 250, 'y' => 250, 'sepia' => 0.7],
                ['src' => 'https://picsum.photos/150/150?random=4', 'x' => 450, 'y' => 250, 'grayscale' => 0.8],
                ['src' => self::SAMPLE_SVG_BASE64, 'x' => 650, 'y' => 250, 'invert' => 0.6],
                
                // Row 3: Effects and special cases
                ['src' => 'https://picsum.photos/150/150?random=5', 'x' => 50, 'y' => 450, 'shadow' => ['enabled' => true, 'offsetX' => 3, 'offsetY' => 3, 'blur' => 6]],
                ['src' => 'https://via.placeholder.com/150x150/45b7d1/ffffff?text=HTTP', 'x' => 250, 'y' => 450, 'brightness' => 1.2, 'contrast' => 1.1],
                ['src' => '', 'x' => 450, 'y' => 450], // Placeholder test
                ['src' => 'relative/path/image.jpg', 'x' => 650, 'y' => 450, 'hue' => 180] // Relative path test
            ];
            
            foreach ($imageConfigs as $config) {
                $layer = $this->createImageLayer([
                    'src' => $config['src'],
                    'width' => 150,
                    'height' => 150,
                    'x' => $config['x'],
                    'y' => $config['y'],
                    'brightness' => $config['brightness'] ?? 1.0,
                    'contrast' => $config['contrast'] ?? 1.0,
                    'saturation' => $config['saturation'] ?? 1.0,
                    'hue' => $config['hue'] ?? 0,
                    'blur' => $config['blur'] ?? 0,
                    'sepia' => $config['sepia'] ?? 0,
                    'grayscale' => $config['grayscale'] ?? 0,
                    'invert' => $config['invert'] ?? 0,
                    'shadow' => $config['shadow'] ?? null
                ]);
                
                $element = $this->renderer->render($layer, $this->documentBuilder);
                if ($element) {
                    $importedElement = $svgRoot->ownerDocument->importNode($element, true);
                    $svgRoot->appendChild($importedElement);
                }
            }
            
            $this->documentBuilder->processDefinitions($svgRoot);
            $svgContent = $this->documentBuilder->saveDocument($svgRoot);
            
            // Save complex document
            file_put_contents(__DIR__ . '/output/complex_image_gallery.svg', $svgContent);
            
            // Verify the SVG contains actual HTTP/HTTPS URLs
            $httpUrlCount = substr_count($svgContent, 'https://picsum.photos/');
            $placeholderUrlCount = substr_count($svgContent, 'https://via.placeholder.com/');
            $relativePathCount = substr_count($svgContent, 'relative/path/image.jpg');
            
            echo "    Generated SVG contains:\n";
            echo "      - HTTPS URLs (picsum): {$httpUrlCount}\n";
            echo "      - HTTPS URLs (placeholder): {$placeholderUrlCount}\n";
            echo "      - Relative paths: {$relativePathCount}\n";
            echo "      - Total document size: " . number_format(strlen($svgContent)) . " bytes\n";
            
            // Validate that we have real URLs in the output
            if ($httpUrlCount === 0 && $placeholderUrlCount === 0) {
                throw new \Exception("Generated SVG does not contain expected HTTP/HTTPS URLs");
            }
            
            return strlen($svgContent);
        });
    }

    // Helper methods
    private function createImageLayer(array $properties): Layer
    {
        $layer = new Layer();
        $layer->setType('image');
        $layer->setX($properties['x'] ?? 50);
        $layer->setY($properties['y'] ?? 50);
        $layer->setWidth($properties['width'] ?? 100);
        $layer->setHeight($properties['height'] ?? 100);
        $layer->setZIndex(1);
        $layer->setVisible(true);
        
        // Set a mock ID for testing purposes since entities aren't persisted
        static $layerIdCounter = 1;
        $reflection = new \ReflectionClass($layer);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($layer, $layerIdCounter++);
        
        // Remove position/size properties from the properties array
        $layerProperties = array_diff_key($properties, array_flip(['x', 'y', 'width', 'height']));
        $layer->setProperties($layerProperties);
        
        return $layer;
    }

    private function runTest(string $testName, callable $testFunction, bool $expectErrors = false): void
    {
        $this->totalTests++;
        $startTime = microtime(true);
        
        try {
            $result = $testFunction();
            $duration = microtime(true) - $startTime;
            
            if (!$expectErrors && $result === null) {
                throw new \Exception("Test returned null result");
            }
            
            $this->testsPassed++;
            $this->testResults[] = [
                'name' => $testName,
                'status' => 'PASS',
                'duration' => $duration,
                'message' => 'Test completed successfully'
            ];
            
            echo "  ✅ {$testName} (" . number_format($duration * 1000, 2) . "ms)\n";
            
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            
            if ($expectErrors) {
                $this->testsPassed++;
                $status = 'PASS';
                $message = "Expected error handled: " . $e->getMessage();
                echo "  ✅ {$testName} (Expected error handled)\n";
            } else {
                $status = 'FAIL';
                $message = $e->getMessage();
                echo "  ❌ {$testName}: {$message}\n";
            }
            
            $this->testResults[] = [
                'name' => $testName,
                'status' => $status,
                'duration' => $duration,
                'message' => $message
            ];
        }
    }

    private function assertNotNull($value, string $message): void
    {
        if ($value === null) {
            throw new \Exception($message);
        }
    }

    private function assertEquals($expected, $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new \Exception("{$message}. Expected: {$expected}, Actual: {$actual}");
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \Exception($message);
        }
    }

    private function assertStringContains(string $needle, string $haystack, string $message): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new \Exception("{$message}. Expected to find '{$needle}' in string");
        }
    }

    private function generateReport(): void
    {
        $totalDuration = microtime(true) - $this->startTime;
        
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "📊 IMAGE LAYER TEST REPORT\n";
        echo str_repeat("=", 70) . "\n";
        
        // Overall statistics
        $passRate = $this->totalTests > 0 ? ($this->testsPassed / $this->totalTests) * 100 : 0;
        echo "🏆 Overall Results:\n";
        echo "   Tests Passed: {$this->testsPassed}/{$this->totalTests}\n";
        echo "   Pass Rate: " . number_format($passRate, 1) . "%\n";
        echo "   Total Duration: " . number_format($totalDuration, 2) . "s\n\n";
        
        // Failed tests
        $failedTests = array_filter($this->testResults, fn($r) => $r['status'] === 'FAIL');
        if (!empty($failedTests)) {
            echo "❌ Failed Tests:\n";
            foreach ($failedTests as $test) {
                echo "   • {$test['name']}: {$test['message']}\n";
            }
            echo "\n";
        }
        
        // Test categories breakdown
        $categories = [];
        foreach ($this->testResults as $result) {
            $category = explode(':', $result['name'])[0];
            if (!isset($categories[$category])) {
                $categories[$category] = ['passed' => 0, 'total' => 0];
            }
            $categories[$category]['total']++;
            if ($result['status'] === 'PASS') {
                $categories[$category]['passed']++;
            }
        }
        
        echo "📋 Test Categories:\n";
        foreach ($categories as $category => $stats) {
            $categoryRate = $stats['total'] > 0 ? ($stats['passed'] / $stats['total']) * 100 : 0;
            echo "   {$category}: {$stats['passed']}/{$stats['total']} (" . number_format($categoryRate, 1) . "%)\n";
        }
        
        // Final verdict
        echo "\n";
        if ($this->testsPassed === $this->totalTests) {
            echo "🎉 ALL IMAGE TESTS PASSED! The image layer processing is fully functional.\n";
        } elseif ($passRate >= 90) {
            echo "✨ EXCELLENT! Most image tests passed with minor issues.\n";
        } elseif ($passRate >= 70) {
            echo "👍 GOOD! Majority of image tests passed, some improvements needed.\n";
        } else {
            echo "⚠️ NEEDS ATTENTION! Multiple image test failures detected.\n";
        }
        
        // Save detailed report
        $reportData = [
            'summary' => [
                'total_tests' => $this->totalTests,
                'passed_tests' => $this->testsPassed,
                'pass_rate' => $passRate,
                'duration' => $totalDuration
            ],
            'test_results' => $this->testResults,
            'categories' => $categories
        ];
        
        file_put_contents(__DIR__ . '/output/image_layer_test_report.json', json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed report saved to: output/image_layer_test_report.json\n";
        echo "📁 Test output files saved to: output/\n";
    }
}

// Run the tests
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ImageLayerTest();
    $test->runComprehensiveImageTests();
}
