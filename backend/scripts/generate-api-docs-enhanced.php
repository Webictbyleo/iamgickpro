<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Types\ContextFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Enhanced API Documentation Generator for Symfony 7 + PHP 8.4
 * 
 * Features:
 * - Configurable output formats (Markdown, JSON, HTML)
 * - Performance optimizations with caching
 * - Enhanced error handling and logging
 * - Progress tracking for large APIs
 * - Customizable templates and themes
 * - Integration with OpenAPI/Swagger specs
 * - Support for nested DTOs and circular references
 * - Advanced validation constraint documentation
 * - Security annotations parsing
 * - Example generation from validation rules
 */
class EnhancedApiDocGenerator
{
    private DocBlockFactory $docBlockFactory;
    private ContextFactory $contextFactory;
    private array $processedClasses = [];
    private array $routes = [];
    private array $config;
    private OutputInterface $output;
    private array $cache = [];
    private array $circularRefs = [];
    private int $maxDepth;
    
    // Configuration constants
    private const DEFAULT_CONFIG = [
        'output_format' => 'markdown', // markdown, json, html, openapi
        'max_depth' => 5,
        'include_examples' => true,
        'include_validation' => true,
        'include_security' => true,
        'group_by_tags' => false,
        'show_deprecated' => true,
        'cache_enabled' => true,
        'template_dir' => null,
        'custom_css' => null,
        'exclude_controllers' => [],
        'include_only_controllers' => [],
        'exclude_routes' => [],
        'sort_routes' => 'path', // path, method, name
        'show_request_examples' => true,
        'show_response_examples' => true,
        'generate_postman_collection' => false,
        'openapi_version' => '3.0.3'
    ];

    public function __construct(array $config = [], ?OutputInterface $output = null)
    {
        // Load config file if it exists
        $configFile = __DIR__ . '/../config/api-docs-config.json';
        if (file_exists($configFile)) {
            $fileConfig = json_decode(file_get_contents($configFile), true);
            if ($fileConfig) {
                $config = array_merge($fileConfig, $config); // CLI config overrides file config
            }
        }
        
        $this->config = array_merge(self::DEFAULT_CONFIG, $config);
        $this->output = $output ?? new ConsoleOutput();
        $this->maxDepth = $this->config['max_depth'];
        
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
        
        $this->output->writeln('<info>Enhanced API Documentation Generator initialized</info>');
        if (file_exists($configFile)) {
            $this->output->writeln('<info>Configuration loaded from: ' . $configFile . '</info>');
        }
    }

    /**
     * Generate comprehensive API documentation
     */
    public function generate(): string
    {
        $startTime = microtime(true);
        
        try {
            $this->output->writeln('<info>Loading routes...</info>');
            $this->loadRoutes();
            
            $this->output->writeln(sprintf('<info>Found %d routes</info>', count($this->routes)));
            
            switch ($this->config['output_format']) {
                case 'json':
                    $result = $this->generateJsonDocumentation();
                    break;
                case 'html':
                    $result = $this->generateHtmlDocumentation();
                    break;
                case 'openapi':
                    $result = $this->generateOpenApiSpecification();
                    break;
                default:
                    $result = $this->generateMarkdownDocumentation();
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            $this->output->writeln(sprintf('<info>Documentation generated in %s seconds</info>', $duration));
            
            if ($this->config['generate_postman_collection']) {
                $this->generatePostmanCollection();
            }
            
            return $result;
            
        } catch (\Throwable $e) {
            $this->output->writeln(sprintf('<error>Error generating documentation: %s</error>', $e->getMessage()));
            $this->output->writeln(sprintf('<error>File: %s:%d</error>', $e->getFile(), $e->getLine()));
            throw $e;
        }
    }

    /**
     * Generate Markdown documentation
     */
    private function generateMarkdownDocumentation(): string
    {
        $output = $this->generateMarkdownHeader();
        $controllers = $this->groupAndSortRoutes();
        
        // Table of contents
        $output .= $this->generateTableOfContents($controllers);
        $output .= "\n---\n\n";
        
        // Progress bar for large APIs
        $progress = null;
        if (count($controllers) > 5) {
            $progress = new ProgressBar($this->output, count($controllers));
            $progress->setFormat('Processing controllers: %current%/%max% [%bar%] %percent:3s%%');
            $progress->start();
        }
        
        // Generate documentation for each controller
        foreach ($controllers as $controller => $routes) {
            $output .= $this->generateControllerDocumentation($controller, $routes);
            $progress?->advance();
        }
        
        $progress?->finish();
        $this->output->writeln('');
        
        return $output;
    }

    /**
     * Generate JSON documentation
     */
    private function generateJsonDocumentation(): string
    {
        $controllers = $this->groupAndSortRoutes();
        $documentation = [
            'metadata' => [
                'generator' => 'Enhanced API Documentation Generator',
                'version' => '2.0.0',
                'generated_at' => date('c'),
                'symfony_version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
                'php_version' => PHP_VERSION
            ],
            'controllers' => []
        ];

        foreach ($controllers as $controller => $routes) {
            $documentation['controllers'][] = $this->generateControllerJsonData($controller, $routes);
        }

        return json_encode($documentation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate HTML documentation
     */
    private function generateHtmlDocumentation(): string
    {
        $controllers = $this->groupAndSortRoutes();
        
        $html = $this->generateHtmlHeader();
        $html .= $this->generateHtmlNavigation($controllers);
        $html .= '<div class="content">';
        
        foreach ($controllers as $controller => $routes) {
            $html .= $this->generateControllerHtmlDocumentation($controller, $routes);
        }
        
        $html .= '</div>';
        $html .= $this->generateHtmlFooter();
        
        return $html;
    }

    /**
     * Generate OpenAPI specification
     */
    private function generateOpenApiSpecification(): string
    {
        $controllers = $this->groupAndSortRoutes();
        $customSettings = $this->config['custom_settings'] ?? [];
        
        $openapi = [
            'openapi' => $this->config['openapi_version'],
            'info' => [
                'title' => $customSettings['api_title'] ?? 'API Documentation',
                'version' => $customSettings['api_version'] ?? '1.0.0',
                'description' => $customSettings['api_description'] ?? 'Generated API documentation'
            ],
            'servers' => $this->config['servers'] ?? [
                ['url' => '/api', 'description' => 'Development server']
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => $this->config['security_schemes'] ?? $this->generateSecuritySchemes()
            ]
        ];

        // Add contact information if available
        if (isset($customSettings['contact'])) {
            $openapi['info']['contact'] = $customSettings['contact'];
        }

        // Add license information if available
        if (isset($customSettings['license'])) {
            $openapi['info']['license'] = $customSettings['license'];
        }

        foreach ($controllers as $controller => $routes) {
            foreach ($routes as $route) {
                $pathKey = $this->normalizeOpenApiPath($route['path']);
                foreach ($route['methods'] as $method) {
                    $operation = $this->generateOpenApiOperation($route, $method);
                    
                    // Add security requirements if configured
                    if (!empty($this->config['security_schemes'])) {
                        $operation['security'] = [];
                        foreach (array_keys($this->config['security_schemes']) as $schemeName) {
                            $operation['security'][] = [$schemeName => []];
                        }
                    }
                    
                    $openapi['paths'][$pathKey][strtolower($method)] = $operation;
                }
            }
        }

        // Generate component schemas
        $openapi['components']['schemas'] = $this->generateOpenApiSchemas();

        return json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate Postman collection
     */
    private function generatePostmanCollection(): void
    {
        $controllers = $this->groupAndSortRoutes();
        $customSettings = $this->config['custom_settings'] ?? [];
        
        $collection = [
            'info' => [
                'name' => $customSettings['api_title'] ?? 'API Collection',
                'description' => $customSettings['api_description'] ?? 'Generated from API documentation',
                'version' => $customSettings['api_version'] ?? '1.0.0',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'item' => [],
            'variable' => []
        ];

        // Add server variables
        if (isset($this->config['servers'])) {
            foreach ($this->config['servers'] as $index => $server) {
                $varName = $index === 0 ? 'baseUrl' : 'baseUrl' . ($index + 1);
                $collection['variable'][] = [
                    'key' => $varName,
                    'value' => $server['url'],
                    'description' => $server['description'] ?? 'Server URL'
                ];
            }
        }

        // Add authentication variables
        if (isset($this->config['security_schemes'])) {
            foreach ($this->config['security_schemes'] as $schemeName => $scheme) {
                if ($scheme['type'] === 'http' && $scheme['scheme'] === 'bearer') {
                    $collection['variable'][] = [
                        'key' => $schemeName . 'Token',
                        'value' => 'your-token-here',
                        'description' => 'Bearer token for ' . $schemeName
                    ];
                }
            }
        }

        foreach ($controllers as $controller => $routes) {
            $folder = [
                'name' => $controller,
                'item' => []
            ];

            foreach ($routes as $route) {
                foreach ($route['methods'] as $method) {
                    $folder['item'][] = $this->generatePostmanRequest($route, $method);
                }
            }

            $collection['item'][] = $folder;
        }

        $filename = dirname(__DIR__, 2) . '/API_Collection.postman_collection.json';
        file_put_contents($filename, json_encode($collection, JSON_PRETTY_PRINT));
        $this->output->writeln(sprintf('<info>Postman collection saved: %s</info>', $filename));
    }

    /**
     * Load routes with enhanced error handling
     */
    private function loadRoutes(): void
    {
        $this->routes = $this->parseRoutesFromDirectory();
        
        // Apply filters
        if (!empty($this->config['exclude_controllers'])) {
            $this->routes = array_filter($this->routes, fn($route) => 
                !in_array($this->getControllerDisplayName($route['controller']), $this->config['exclude_controllers'])
            );
        }
        
        if (!empty($this->config['include_only_controllers'])) {
            $this->routes = array_filter($this->routes, fn($route) => 
                in_array($this->getControllerDisplayName($route['controller']), $this->config['include_only_controllers'])
            );
        }
        
        if (!empty($this->config['exclude_routes'])) {
            $this->routes = array_filter($this->routes, fn($route) => 
                !in_array($route['path'], $this->config['exclude_routes'])
            );
        }
    }

    /**
     * Parse routes with better error handling and caching
     */
    private function parseRoutesFromDirectory(): array
    {
        $cacheKey = 'routes_' . md5(__DIR__ . '/../src/Controller');
        
        if ($this->config['cache_enabled'] && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $routes = [];
        $controllerDir = __DIR__ . '/../src/Controller';
        
        if (!is_dir($controllerDir)) {
            $this->output->writeln('<warning>Controller directory not found</warning>');
            return $routes;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($controllerDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                try {
                    $fileRoutes = $this->extractRoutesFromController($file->getPathname());
                    $routes = array_merge($routes, $fileRoutes);
                } catch (\Throwable $e) {
                    $this->output->writeln(sprintf(
                        '<warning>Failed to parse controller %s: %s</warning>',
                        $file->getFilename(),
                        $e->getMessage()
                    ));
                }
            }
        }

        if ($this->config['cache_enabled']) {
            $this->cache[$cacheKey] = $routes;
        }

        return $routes;
    }

    /**
     * Extract routes with enhanced reflection and security analysis
     */
    private function extractRoutesFromController(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $routes = [];

        // Extract namespace and class name
        if (!preg_match('/namespace\s+([^;]+);/', $content, $nsMatch) ||
            !preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return $routes;
        }

        $className = $nsMatch[1] . '\\' . $classMatch[1];

        if (!class_exists($className)) {
            return $routes;
        }

        try {
            $reflection = new \ReflectionClass($className);
            $routes = $this->extractRoutesFromReflection($reflection);
        } catch (\Throwable $e) {
            $this->output->writeln(sprintf(
                '<warning>Failed to reflect class %s: %s</warning>',
                $className,
                $e->getMessage()
            ));
        }

        return $routes;
    }

    /**
     * Extract routes with security and deprecation analysis
     */
    private function extractRoutesFromReflection(\ReflectionClass $reflection): array
    {
        $routes = [];
        
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip inherited methods unless they have route attributes
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                $hasRouteAttribute = false;
                foreach ($method->getAttributes() as $attr) {
                    if (str_contains($attr->getName(), 'Route')) {
                        $hasRouteAttribute = true;
                        break;
                    }
                }
                if (!$hasRouteAttribute) {
                    continue;
                }
            }

            $attributes = $method->getAttributes();
            
            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();
                
                if (str_contains($attributeName, 'Route')) {
                    $args = $attribute->getArguments();
                    
                    $route = [
                        'path' => $args[0] ?? '',
                        'methods' => $args['methods'] ?? ['GET'],
                        'controller' => $reflection->getName(),
                        'action' => $method->getName(),
                        'method_reflection' => $method,
                        'security' => $this->extractSecurityInfo($method),
                        'deprecated' => $this->isDeprecated($method),
                        'tags' => $this->extractTags($method),
                        'description' => $this->extractMethodSummary($method)
                    ];
                    
                    if (isset($args['name'])) {
                        $route['name'] = $args['name'];
                    }
                    
                    $routes[] = $route;
                }
            }
        }
        
        return $routes;
    }

    /**
     * Extract security information from method
     */
    private function extractSecurityInfo(\ReflectionMethod $method): array
    {
        $security = [];
        
        foreach ($method->getAttributes() as $attribute) {
            $name = $attribute->getName();
            if (str_contains($name, 'Security') || str_contains($name, 'IsGranted')) {
                $security[] = [
                    'type' => basename(str_replace('\\', '/', $name)),
                    'args' => $attribute->getArguments()
                ];
            }
        }
        
        return $security;
    }

    /**
     * Check if method is deprecated
     */
    private function isDeprecated(\ReflectionMethod $method): bool
    {
        $docComment = $method->getDocComment();
        if ($docComment && str_contains($docComment, '@deprecated')) {
            return true;
        }
        
        foreach ($method->getAttributes() as $attribute) {
            if (str_contains($attribute->getName(), 'Deprecated')) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extract tags from method documentation
     */
    private function extractTags(\ReflectionMethod $method): array
    {
        $tags = [];
        $docComment = $method->getDocComment();
        
        if ($docComment && preg_match_all('/@tag\s+([^\s\*]+)/', $docComment, $matches)) {
            $tags = $matches[1];
        }
        
        return $tags;
    }

    /**
     * Extract method summary
     */
    private function extractMethodSummary(\ReflectionMethod $method): string
    {
        $docComment = $method->getDocComment();
        
        if (!$docComment) {
            return '';
        }
        
        try {
            $docBlock = $this->docBlockFactory->create($docComment);
            return $docBlock->getSummary();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Group and sort routes based on configuration
     */
    private function groupAndSortRoutes(): array
    {
        if ($this->config['group_by_tags']) {
            return $this->groupRoutesByTags();
        }
        
        return $this->groupRoutesByController();
    }

    /**
     * Group routes by tags
     */
    private function groupRoutesByTags(): array
    {
        $grouped = [];
        
        foreach ($this->routes as $route) {
            $tags = $route['tags'] ?? ['Untagged'];
            
            foreach ($tags as $tag) {
                if (!isset($grouped[$tag])) {
                    $grouped[$tag] = [];
                }
                $grouped[$tag][] = $route;
            }
        }
        
        return $this->sortRoutesInGroups($grouped);
    }

    /**
     * Group routes by controller
     */
    private function groupRoutesByController(): array
    {
        $grouped = [];
        
        foreach ($this->routes as $route) {
            $controller = $route['controller'];
            $controllerName = $this->getControllerDisplayName($controller);
            
            if (!isset($grouped[$controllerName])) {
                $grouped[$controllerName] = [];
            }
            
            $grouped[$controllerName][] = $route;
        }
        
        return $this->sortRoutesInGroups($grouped);
    }

    /**
     * Sort routes within groups
     */
    private function sortRoutesInGroups(array $grouped): array
    {
        foreach ($grouped as &$routes) {
            usort($routes, function($a, $b) {
                switch ($this->config['sort_routes']) {
                    case 'method':
                        return strcmp($a['methods'][0] ?? '', $b['methods'][0] ?? '');
                    case 'name':
                        return strcmp($a['name'] ?? '', $b['name'] ?? '');
                    default: // path
                        return strcmp($a['path'], $b['path']);
                }
            });
        }
        
        return $grouped;
    }

    /**
     * Generate enhanced DTO documentation with circular reference detection
     */
    private function generateDtoDocumentation(string $className, int $depth = 0): string
    {
        // Prevent infinite recursion
        if ($depth > $this->maxDepth) {
            return "**$className** *(max depth reached)*\n";
        }
        
        if (isset($this->circularRefs[$className])) {
            return "**$className** *(circular reference detected)*\n";
        }
        
        if (isset($this->processedClasses[$className])) {
            return $this->processedClasses[$className];
        }
        
        $this->circularRefs[$className] = true;
        
        try {
            $reflection = new \ReflectionClass($className);
            $output = $this->generateClassDocumentation($reflection, $depth);
            $this->processedClasses[$className] = $output;
            
            unset($this->circularRefs[$className]);
            return $output;
        } catch (\Exception $e) {
            unset($this->circularRefs[$className]);
            return "Error processing class $className: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Generate example data from validation constraints
     */
    private function generateExampleFromValidation(array $validation, string $type): string
    {
        if (!$this->config['include_examples']) {
            return '';
        }
        
        $example = '';
        
        switch ($type) {
            case 'string':
                $example = '"example_string"';
                break;
            case 'int':
            case 'integer':
                $example = '42';
                break;
            case 'float':
                $example = '3.14';
                break;
            case 'bool':
            case 'boolean':
                $example = 'true';
                break;
            case 'array':
                $example = '[]';
                break;
            default:
                $example = 'null';
        }
        
        // Override with validation-specific examples
        foreach ($validation as $constraint) {
            if (str_contains($constraint, 'Choice')) {
                if (preg_match('/choices:\s*\[(.*?)\]/', $constraint, $matches)) {
                    $choices = explode(',', $matches[1]);
                    $example = trim($choices[0] ?? $example, '\'" ');
                }
            } elseif (str_contains($constraint, 'Range')) {
                if (preg_match('/min:\s*(\d+)/', $constraint, $matches)) {
                    $min = (int)$matches[1];
                    $example = (string)($min + 1);
                }
            }
        }
        
        return $example;
    }

    /**
     * Generate markdown header with metadata
     */
    private function generateMarkdownHeader(): string
    {
        $customSettings = $this->config['custom_settings'] ?? [];
        $title = $customSettings['api_title'] ?? 'API Documentation';
        $version = $customSettings['api_version'] ?? '1.0.0';
        $description = $customSettings['api_description'] ?? '';
        
        $output = "# $title\n\n";
        
        if ($description) {
            $output .= "$description\n\n";
        }
        
        $output .= "**Version:** $version\n\n";
        $output .= "**Generated on:** " . date('Y-m-d H:i:s') . "\n";
        $output .= "**Generator:** Enhanced API Documentation Generator v2.0\n";
        $output .= "**Symfony Version:** " . \Symfony\Component\HttpKernel\Kernel::VERSION . "\n";
        $output .= "**PHP Version:** " . PHP_VERSION . "\n\n";
        
        // Add contact and license information if available
        if (isset($customSettings['contact'])) {
            $contact = $customSettings['contact'];
            $output .= "**Contact:** ";
            if (isset($contact['name'])) {
                $output .= $contact['name'];
                if (isset($contact['email'])) {
                    $output .= " ([" . $contact['email'] . "](mailto:" . $contact['email'] . "))";
                }
            } elseif (isset($contact['email'])) {
                $output .= "[" . $contact['email'] . "](mailto:" . $contact['email'] . ")";
            }
            $output .= "\n\n";
        }
        
        if (isset($customSettings['license'])) {
            $license = $customSettings['license'];
            $output .= "**License:** ";
            if (isset($license['url'])) {
                $output .= "[" . ($license['name'] ?? 'License') . "](" . $license['url'] . ")";
            } else {
                $output .= $license['name'] ?? 'Licensed';
            }
            $output .= "\n\n";
        }
        
        // Add servers information
        if (isset($this->config['servers'])) {
            $output .= "## Servers\n\n";
            foreach ($this->config['servers'] as $server) {
                $output .= "- **" . ($server['description'] ?? 'Server') . ":** `" . $server['url'] . "`\n";
            }
            $output .= "\n";
        }
        
        // Add authentication information
        if (isset($this->config['security_schemes'])) {
            $output .= "## Authentication\n\n";
            foreach ($this->config['security_schemes'] as $schemeName => $scheme) {
                $output .= "### " . ucfirst($schemeName) . "\n\n";
                $output .= "- **Type:** " . ucfirst($scheme['type']) . "\n";
                if (isset($scheme['scheme'])) {
                    $output .= "- **Scheme:** " . ucfirst($scheme['scheme']) . "\n";
                }
                if (isset($scheme['bearerFormat'])) {
                    $output .= "- **Bearer Format:** " . $scheme['bearerFormat'] . "\n";
                }
                $output .= "\n";
            }
        }
        
        if (!$this->config['show_deprecated']) {
            $output .= "> **Note:** Deprecated endpoints are hidden in this documentation.\n\n";
        }
        
        return $output;
    }

    /**
     * Generate table of contents with enhanced formatting
     */
    private function generateTableOfContents(array $controllers): string
    {
        $output = "## Table of Contents\n\n";
        
        foreach ($controllers as $controller => $routes) {
            $routeCount = count($routes);
            $deprecatedCount = count(array_filter($routes, fn($r) => $r['deprecated']));
            
            $output .= "- [$controller](#" . $this->generateAnchor($controller) . ")";
            $output .= " *($routeCount routes";
            
            if ($deprecatedCount > 0 && $this->config['show_deprecated']) {
                $output .= ", $deprecatedCount deprecated";
            }
            
            $output .= ")*\n";
        }
        
        return $output;
    }

    /**
     * Get display name for controller with namespace handling
     */
    private function getControllerDisplayName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Generate anchor from text with better normalization
     */
    private function generateAnchor(string $text): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($text, '-')));
    }

    // Placeholder methods for different output formats
    private function generateControllerDocumentation(string $controller, array $routes): string
    {
        $output = "## $controller\n\n";
        
        // Add controller description if available
        $reflection = new \ReflectionClass($routes[0]['controller']);
        $classDoc = $this->extractClassDocumentation($reflection);
        
        if ($classDoc) {
            $output .= "$classDoc\n\n";
        }
        
        foreach ($routes as $route) {
            // Skip deprecated routes if configured
            if ($route['deprecated'] && !$this->config['show_deprecated']) {
                continue;
            }
            
            $output .= $this->generateRouteDocumentation($route);
        }
        
        return $output;
    }

    /**
     * Generate documentation for a single route
     */
    private function generateRouteDocumentation(array $route): string
    {
        $method = $route['method_reflection'];
        $methodsList = implode(', ', $route['methods']);
        $output = "### " . strtoupper($methodsList) . " " . $route['path'];
        
        if ($route['deprecated']) {
            $output .= " ⚠️ *Deprecated*";
        }
        
        $output .= "\n\n";
        
        // Security annotations
        if (!empty($route['security']) && $this->config['include_security']) {
            $output .= "**Security:** ";
            $securityInfo = [];
            foreach ($route['security'] as $security) {
                $securityInfo[] = $security['type'];
            }
            $output .= implode(', ', $securityInfo) . "\n\n";
        }
        
        // Tags
        if (!empty($route['tags'])) {
            $output .= "**Tags:** " . implode(', ', $route['tags']) . "\n\n";
        }
        
        // Method description
        $methodDoc = $this->extractMethodDocumentation($method);
        if ($methodDoc) {
            $output .= "$methodDoc\n\n";
        }
        
        // Parameters
        $parameters = $this->extractMethodParameters($method);
        if (!empty($parameters)) {
            $output .= "#### Parameters\n\n";
            foreach ($parameters as $param) {
                $output .= $this->generateParameterDocumentation($param);
            }
            $output .= "\n";
        }
        
        // Request body (for POST/PUT/PATCH)
        $requestBody = $this->extractRequestBodyInfo($method);
        if ($requestBody) {
            $output .= "#### Request Body\n\n";
            $output .= $this->generateDtoDocumentation($requestBody);
            $output .= "\n";
        }
        
        // Response
        $response = $this->extractResponseInfo($method);
        if ($response) {
            $output .= "#### Response\n\n";
            $output .= $this->generateDtoDocumentation($response);
            $output .= "\n";
        }
        
        $output .= "---\n\n";
        
        return $output;
    }

    /**
     * Extract class documentation
     */
    private function extractClassDocumentation(\ReflectionClass $reflection): string
    {
        $docComment = $reflection->getDocComment();
        
        if (!$docComment) {
            return '';
        }
        
        try {
            $docBlock = $this->docBlockFactory->create($docComment);
            return $docBlock->getSummary() . "\n\n" . (string)$docBlock->getDescription();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract method documentation
     */
    private function extractMethodDocumentation(\ReflectionMethod $method): string
    {
        $docComment = $method->getDocComment();
        
        if (!$docComment) {
            return '';
        }
        
        try {
            $docBlock = $this->docBlockFactory->create($docComment);
            return $docBlock->getSummary() . "\n\n" . (string)$docBlock->getDescription();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract method parameters
     */
    private function extractMethodParameters(\ReflectionMethod $method): array
    {
        $parameters = [];
        
        foreach ($method->getParameters() as $param) {
            // Skip request/response objects and services
            $type = $param->getType();
            if ($type && $type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                if (str_contains($typeName, 'Request') && str_contains($typeName, 'DTO')) {
                    continue; // This will be handled as request body
                }
                if (str_contains($typeName, 'Service') || 
                    str_contains($typeName, 'Repository') ||
                    str_contains($typeName, 'Manager')) {
                    continue; // Skip injected services
                }
            }
            
            $parameters[] = [
                'name' => $param->getName(),
                'type' => $this->getParameterTypeString($param),
                'required' => !$param->isOptional(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            ];
        }
        
        return $parameters;
    }

    /**
     * Get parameter type string
     */
    private function getParameterTypeString(\ReflectionParameter $param): string
    {
        $type = $param->getType();
        
        if (!$type) {
            return 'mixed';
        }
        
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }
        
        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(fn($t) => $t->getName(), $type->getTypes()));
        }
        
        return 'mixed';
    }

    /**
     * Extract request body information
     */
    private function extractRequestBodyInfo(\ReflectionMethod $method): ?string
    {
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            if ($type && $type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                if (str_contains($typeName, 'DTO') && str_contains($typeName, 'Request')) {
                    return $typeName;
                }
            }
        }
        
        return null;
    }

    /**
     * Extract response information
     */
    private function extractResponseInfo(\ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();
        
        if (!$returnType) {
            return null;
        }
        
        if ($returnType instanceof \ReflectionNamedType) {
            $typeName = $returnType->getName();
            if (str_contains($typeName, 'Response') || str_contains($typeName, 'DTO')) {
                return $typeName;
            }
        }
        
        return null;
    }

    /**
     * Generate parameter documentation
     */
    private function generateParameterDocumentation(array $param): string
    {
        $output = "- **{$param['name']}** ({$param['type']})";
        
        if (!$param['required']) {
            $output .= " *optional*";
            if ($param['default'] !== null) {
                $defaultValue = is_string($param['default']) ? "'{$param['default']}'" : var_export($param['default'], true);
                $output .= " *default: $defaultValue*";
            }
        }
        
        $output .= "\n";
        
        return $output;
    }

    /**
     * Generate class documentation using modern PHP reflection
     */
    private function generateClassDocumentation(\ReflectionClass $reflection, int $depth = 0): string
    {
        $output = "**{$reflection->getShortName()}**\n\n";
        
        // Class description
        $classDoc = $this->extractClassDocumentation($reflection);
        if ($classDoc) {
            $output .= "$classDoc\n\n";
        }
        
        // Properties
        $properties = $this->extractClassProperties($reflection, $depth);
        
        if (!empty($properties)) {
            $output .= "Properties:\n\n";
            foreach ($properties as $property) {
                $output .= $this->generatePropertyDocumentation($property, $depth);
            }
        }
        
        return $output;
    }

    /**
     * Extract class properties using modern reflection
     */
    private function extractClassProperties(\ReflectionClass $reflection, int $depth = 0): array
    {
        $properties = [];
        
        // Handle promoted constructor parameters
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->isPromoted()) {
                    $properties[] = $this->extractPromotedPropertyInfo($reflection, $param, $depth);
                }
            }
        }
        
        // Handle regular properties
        foreach ($reflection->getProperties() as $property) {
            if (!$this->isPromotedProperty($reflection, $property->getName())) {
                $properties[] = $this->extractRegularPropertyInfo($property, $depth);
            }
        }
        
        return $properties;
    }

    /**
     * Check if a property is promoted
     */
    private function isPromotedProperty(\ReflectionClass $reflection, string $propertyName): bool
    {
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return false;
        }
        
        foreach ($constructor->getParameters() as $param) {
            if ($param->isPromoted() && $param->getName() === $propertyName) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extract promoted property information using token parsing
     */
    private function extractPromotedPropertyInfo(\ReflectionClass $reflection, \ReflectionParameter $param, int $depth = 0): array
    {
        $name = $param->getName();
        $type = $this->getParameterTypeString($param);
        $required = !$param->isOptional();
        
        // Extract PHPDoc using token parsing instead of regex
        $description = $this->extractPromotedParamPhpDoc($reflection, $param);
        
        // Extract validation constraints
        $validation = $this->extractParameterValidation($param);
        
        return [
            'name' => $name,
            'type' => $type,
            'required' => $required,
            'description' => $description,
            'validation' => $validation,
            'depth' => $depth
        ];
    }

    /**
     * Extract regular property information
     */
    private function extractRegularPropertyInfo(\ReflectionProperty $property, int $depth = 0): array
    {
        $name = $property->getName();
        $type = $this->getPropertyTypeString($property);
        $required = !$property->hasDefaultValue();
        
        // Extract PHPDoc
        $description = $this->extractPropertyPhpDoc($property);
        
        return [
            'name' => $name,
            'type' => $type,
            'required' => $required,
            'description' => $description,
            'validation' => [],
            'depth' => $depth
        ];
    }

    /**
     * Extract PHPDoc for promoted parameter using token parsing
     */
    private function extractPromotedParamPhpDoc(\ReflectionClass $reflection, \ReflectionParameter $param): string
    {
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return '';
        }
        
        $file = $constructor->getFileName();
        if (!$file) {
            return '';
        }
        
        $content = file_get_contents($file);
        $tokens = token_get_all($content);
        
        // Find the parameter position and extract preceding doc comment
        return $this->findParameterDocComment($tokens, $param->getName());
    }

    /**
     * Find parameter doc comment using token parsing
     */
    private function findParameterDocComment(array $tokens, string $paramName): string
    {
        $tokenCount = count($tokens);
        $lastDocComment = '';
        
        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];
            
            // Store the last doc comment we encounter
            if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                $lastDocComment = $token[1];
                continue;
            }
            
            // Look for variable that matches our parameter name
            if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$' . $paramName) {
                // Check if this is in a constructor parameter context
                if ($this->isInConstructorContext($tokens, $i)) {
                    return $this->parseDocComment($lastDocComment);
                }
            }
        }
        
        return '';
    }

    /**
     * Check if we're in a constructor parameter context
     */
    private function isInConstructorContext(array $tokens, int $position): bool
    {
        // Look backwards to see if we're in a constructor
        $parenCount = 0;
        
        for ($i = $position - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            
            if ($token === ')') {
                $parenCount++;
            } elseif ($token === '(') {
                $parenCount--;
                if ($parenCount < 0) {
                    // Found opening paren, check if preceded by __construct
                    return $this->isPrecededByConstruct($tokens, $i);
                }
            }
        }
        
        return false;
    }

    /**
     * Check if position is preceded by __construct
     */
    private function isPrecededByConstruct(array $tokens, int $position): bool
    {
        for ($i = $position - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            
            if (is_array($token) && $token[0] === T_STRING && $token[1] === '__construct') {
                return true;
            }
            
            // Stop looking if we hit another function or class
            if (is_array($token) && ($token[0] === T_FUNCTION || $token[0] === T_CLASS)) {
                break;
            }
        }
        
        return false;
    }

    /**
     * Parse doc comment to extract description and @var type
     */
    private function parseDocComment(string $docComment): string
    {
        if (empty($docComment)) {
            return '';
        }
        
        try {
            $docBlock = $this->docBlockFactory->create($docComment);
            
            $description = trim($docBlock->getSummary());
            if ($docBlock->getDescription()) {
                $description .= "\n\n" . trim((string)$docBlock->getDescription());
            }
            
            return $description;
        } catch (\Exception $e) {
            // Fallback to simple parsing
            return $this->parseDocCommentFallback($docComment);
        }
    }

    /**
     * Fallback doc comment parsing
     */
    private function parseDocCommentFallback(string $docComment): string
    {
        $lines = explode("\n", $docComment);
        $description = [];
        
        foreach ($lines as $line) {
            $line = trim($line, " \t/*");
            
            if (empty($line) || str_starts_with($line, '@')) {
                break;
            }
            
            $description[] = $line;
        }
        
        return implode(' ', $description);
    }

    /**
     * Extract property PHPDoc
     */
    private function extractPropertyPhpDoc(\ReflectionProperty $property): string
    {
        $docComment = $property->getDocComment();
        
        if (!$docComment) {
            return '';
        }
        
        return $this->parseDocComment($docComment);
    }

    /**
     * Get property type string
     */
    private function getPropertyTypeString(\ReflectionProperty $property): string
    {
        $type = $property->getType();
        
        if (!$type) {
            return 'mixed';
        }
        
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }
        
        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(fn($t) => $t->getName(), $type->getTypes()));
        }
        
        return 'mixed';
    }

    /**
     * Extract parameter validation constraints
     */
    private function extractParameterValidation(\ReflectionParameter $param): array
    {
        $validation = [];
        $attributes = $param->getAttributes();
        
        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            if (str_contains($name, 'Constraints\\') || str_contains($name, 'Assert\\')) {
                $validation[] = $this->formatValidationConstraint($attribute);
            }
        }
        
        return $validation;
    }

    /**
     * Format validation constraint
     */
    private function formatValidationConstraint(\ReflectionAttribute $attribute): string
    {
        $name = basename(str_replace('\\', '/', $attribute->getName()));
        $args = $attribute->getArguments();
        
        if (empty($args)) {
            return $name;
        }
        
        $formattedArgs = [];
        foreach ($args as $key => $value) {
            if (is_string($key)) {
                $formattedArgs[] = "$key: " . $this->formatValue($value);
            } else {
                $formattedArgs[] = $this->formatValue($value);
            }
        }
        
        return $name . '(' . implode(', ', $formattedArgs) . ')';
    }

    /**
     * Format validation value
     */
    private function formatValue($value): string
    {
        if (is_string($value)) {
            return "'$value'";
        }
        
        if (is_array($value)) {
            $formatted = array_map([$this, 'formatValue'], $value);
            return '[' . implode(', ', $formatted) . ']';
        }
        
        if (is_object($value)) {
            // Handle constraint objects
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }
            return get_class($value);
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_null($value)) {
            return 'null';
        }
        
        return (string)$value;
    }

    /**
     * Generate property documentation with enhanced formatting
     */
    private function generatePropertyDocumentation(array $property, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);
        $output = $indent . "- **{$property['name']}** ({$property['type']})";
        
        if (!$property['required']) {
            $output .= " *optional*";
        }
        
        if (!empty($property['validation']) && $this->config['include_validation']) {
            $output .= " *" . implode(', ', $property['validation']) . "*";
        }
        
        if (!empty($property['description'])) {
            $description = str_replace("\n", "\n$indent  ", $property['description']);
            $output .= "\n$indent  " . $description;
        }
        
        // Add union type expansion if applicable
        if ($this->shouldExpandType($property['type']) && $depth < $this->maxDepth) {
            $expanded = $this->expandUnionType($property['type'], $depth + 1);
            if (!empty($expanded)) {
                $output .= "\n\n$indent  **Union Type Details:**\n\n";
                $expandedIndented = str_replace("\n", "\n$indent  ", $expanded);
                $output .= "$indent  " . $expandedIndented;
            }
        }
        
        // Generate examples if configured
        if ($this->config['include_examples']) {
            $example = $this->generateExampleFromValidation($property['validation'], $property['type']);
            if (!empty($example)) {
                $output .= "\n$indent  *Example: `$example`*";
            }
        }
        
        $output .= "\n\n";
        
        return $output;
    }

    /**
     * Expand union types into their component types with depth control
     */
    private function expandUnionType(string $type, int $depth = 0): string
    {
        if (!$this->shouldExpandType($type) || $depth > $this->maxDepth) {
            return '';
        }
        
        $types = explode('|', $type);
        $expanded = [];
        
        foreach ($types as $singleType) {
            $singleType = trim($singleType);
            
            // Skip null and primitive types
            if ($singleType === 'null' || !str_contains($singleType, 'App\\DTO\\')) {
                continue;
            }
            
            if (class_exists($singleType)) {
                $expanded[] = $this->generateClassDocumentation(new \ReflectionClass($singleType), $depth);
            }
        }
        
        return implode("\n", $expanded);
    }

    /**
     * Determine if a type should be expanded in documentation
     */
    private function shouldExpandType(string $type): bool
    {
        // Expand union types that contain DTOs
        if (str_contains($type, '|') && str_contains($type, 'App\\DTO\\')) {
            return true;
        }
        
        // Expand complex DTO types
        if (str_contains($type, 'App\\DTO\\') && !str_contains($type, '[]')) {
            return true;
        }
        
        return false;
    }

    /**
     * Generate operation ID for OpenAPI
     */
    private function generateOperationId(array $route): string
    {
        $method = strtolower($route['methods'][0]);
        $path = str_replace(['/', '{', '}'], ['_', '', ''], $route['path']);
        $path = trim($path, '_');
        
        return $method . ucfirst($path);
    }

    /**
     * Format security requirements for OpenAPI
     */
    private function formatSecurityForOpenApi(array $security): array
    {
        if (empty($security)) {
            return [];
        }
        
        $formatted = [];
        foreach ($security as $scheme) {
            $formatted[] = [$scheme => []];
        }
        
        return $formatted;
    }

    /**
     * Extract OpenAPI parameters from route
     */
    private function extractOpenApiParameters(array $route): array
    {
        $parameters = [];
        
        // Extract path parameters
        if (preg_match_all('/\{(\w+)\}/', $route['path'], $matches)) {
            foreach ($matches[1] as $param) {
                $parameters[] = [
                    'name' => $param,
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'string']
                ];
            }
        }
        
        // Add query parameters if documented
        if (!empty($route['parameters'])) {
            foreach ($route['parameters'] as $param) {
                if ($param['in'] === 'query') {
                    $parameters[] = [
                        'name' => $param['name'],
                        'in' => 'query',
                        'required' => $param['required'] ?? false,
                        'description' => $param['description'] ?? '',
                        'schema' => $param['schema'] ?? ['type' => 'string']
                    ];
                }
            }
        }
        
        return $parameters;
    }

    /**
     * Extract OpenAPI request body from route
     */
    private function extractOpenApiRequestBody(array $route): ?array
    {
        if (empty($route['request_body'])) {
            return null;
        }
        
        $requestBody = [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => $this->generateOpenApiSchema($route['request_body'])
                ]
            ]
        ];
        
        if (!empty($route['request_body']['description'])) {
            $requestBody['description'] = $route['request_body']['description'];
        }
        
        return $requestBody;
    }

    /**
     * Extract OpenAPI responses from route
     */
    private function extractOpenApiResponses(array $route): array
    {
        $responses = [];
        
        if (!empty($route['responses'])) {
            foreach ($route['responses'] as $code => $response) {
                $responses[$code] = [
                    'description' => $response['description'] ?? 'Success',
                    'content' => [
                        'application/json' => [
                            'schema' => $this->generateOpenApiSchema($response)
                        ]
                    ]
                ];
            }
        } else {
            // Default success response
            $responses['200'] = [
                'description' => 'Success',
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'object']
                    ]
                ]
            ];
        }
        
        // Add common error responses
        $responses['400'] = [
            'description' => 'Bad Request',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'error' => ['type' => 'string'],
                            'message' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ];
        
        return $responses;
    }

    /**
     * Generate OpenAPI schema from type information
     */
    private function generateOpenApiSchema(array $typeInfo): array
    {
        if (empty($typeInfo['type'])) {
            return ['type' => 'object'];
        }
        
        $type = $typeInfo['type'];
        
        // Handle primitive types
        if (in_array($type, ['string', 'integer', 'number', 'boolean', 'array'])) {
            $schema = ['type' => $type];
            
            if ($type === 'array' && !empty($typeInfo['items'])) {
                $schema['items'] = $this->generateOpenApiSchema($typeInfo['items']);
            }
            
            return $schema;
        }
        
        // Handle DTO types
        if (str_contains($type, 'App\\DTO\\') && class_exists($type)) {
            return $this->generateDtoOpenApiSchema($type);
        }
        
        // Handle union types
        if (str_contains($type, '|')) {
            $types = array_map('trim', explode('|', $type));
            $schemas = [];
            
            foreach ($types as $singleType) {
                if ($singleType !== 'null') {
                    $schemas[] = $this->generateOpenApiSchema(['type' => $singleType]);
                }
            }
            
            return ['oneOf' => $schemas];
        }
        
        return ['type' => 'object'];
    }

    /**
     * Generate OpenAPI schema for DTO classes
     */
    private function generateDtoOpenApiSchema(string $className): array
    {
        try {
            $reflection = new \ReflectionClass($className);
            $schema = [
                'type' => 'object',
                'properties' => []
            ];
            
            $constructor = $reflection->getConstructor();
            if (!$constructor) {
                return $schema;
            }
            
            foreach ($constructor->getParameters() as $param) {
                $paramType = $param->getType();
                if (!$paramType) {
                    continue;
                }
                
                $typeName = $paramType instanceof \ReflectionNamedType ? $paramType->getName() : 'mixed';
                $property = [
                    'type' => $this->mapPhpTypeToOpenApi($typeName)
                ];
                
                if (!$param->isOptional()) {
                    $schema['required'][] = $param->getName();
                }
                
                $schema['properties'][$param->getName()] = $property;
            }
            
            return $schema;
        } catch (\Exception $e) {
            return ['type' => 'object'];
        }
    }

    /**
     * Map PHP types to OpenAPI types
     */
    private function mapPhpTypeToOpenApi(string $phpType): string
    {
        return match ($phpType) {
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            'array' => 'array',
            default => 'string'
        };
    }

    private function generateControllerJsonData(string $controller, array $routes): array
    {
        return [
            'name' => $controller,
            'description' => $this->extractControllerDescription($controller, $routes),
            'routes' => array_map(fn($route) => $this->formatRouteForJson($route), $routes)
        ];
    }

    private function generateControllerHtmlDocumentation(string $controller, array $routes): string
    {
        $html = "<section id='" . $this->generateAnchor($controller) . "'>";
        $html .= "<h2>$controller</h2>";
        
        foreach ($routes as $route) {
            if ($route['deprecated'] && !$this->config['show_deprecated']) {
                continue;
            }
            
            $html .= $this->generateRouteHtmlDocumentation($route);
        }
        
        $html .= "</section>";
        return $html;
    }

    private function generateHtmlHeader(): string
    {
        $customSettings = $this->config['custom_settings'] ?? [];
        $title = $customSettings['api_title'] ?? 'API Documentation';
        $description = $customSettings['api_description'] ?? '';
        $version = $customSettings['api_version'] ?? '1.0.0';
        
        $customCss = $this->config['custom_css'] ?? '';
        
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <meta name="description" content="' . htmlspecialchars($description) . '">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        .header { background: #f8f9fa; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .header h1 { margin: 0 0 10px 0; color: #333; }
        .header .version { color: #666; font-size: 14px; }
        .header .description { margin: 10px 0; color: #555; }
        .servers, .auth-info { background: #e9ecef; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .servers h3, .auth-info h3 { margin-top: 0; }
        .content { max-width: 1200px; margin: 0 auto; }
        nav { position: fixed; left: 0; top: 0; width: 250px; height: 100vh; background: #f8f9fa; padding: 20px; overflow-y: auto; border-right: 1px solid #dee2e6; }
        .content { margin-left: 290px; }
        code { background: #f1f3f4; padding: 2px 4px; border-radius: 3px; font-family: "SFMono-Regular", Consolas, monospace; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #e9ecef; }
        .deprecated { opacity: 0.6; text-decoration: line-through; }
        .method { display: inline-block; padding: 3px 8px; border-radius: 3px; color: white; font-weight: bold; font-size: 11px; text-transform: uppercase; }
        .method.get { background: #28a745; }
        .method.post { background: #007bff; }
        .method.put { background: #ffc107; color: black; }
        .method.patch { background: #17a2b8; }
        .method.delete { background: #dc3545; }
        .route-section { margin: 20px 0; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
        .route-header { margin-bottom: 15px; }
        .contact-info, .license-info { margin: 10px 0; }
        .contact-info a, .license-info a { color: #007bff; text-decoration: none; }
        .contact-info a:hover, .license-info a:hover { text-decoration: underline; }
        ' . $customCss . '
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($title) . '</h1>
        <div class="version">Version: ' . htmlspecialchars($version) . '</div>';
        
        if ($description) {
            $html = '<div class="description">' . htmlspecialchars($description) . '</div>';
        }
        
        // Add contact and license info
        if (isset($customSettings['contact'])) {
            $contact = $customSettings['contact'];
            $html .= '<div class="contact-info"><strong>Contact:</strong> ';
            if (isset($contact['name'])) {
                $html .= htmlspecialchars($contact['name']);
                if (isset($contact['email'])) {
                    $html .= ' (<a href="mailto:' . htmlspecialchars($contact['email']) . '">' . htmlspecialchars($contact['email']) . '</a>)';
                }
            } elseif (isset($contact['email'])) {
                $html .= '<a href="mailto:' . htmlspecialchars($contact['email']) . '">' . htmlspecialchars($contact['email']) . '</a>';
            }
            $html .= '</div>';
        }
        
        if (isset($customSettings['license'])) {
            $license = $customSettings['license'];
            $html .= '<div class="license-info"><strong>License:</strong> ';
            if (isset($license['url'])) {
                $html .= '<a href="' . htmlspecialchars($license['url']) . '">' . htmlspecialchars($license['name'] ?? 'License') . '</a>';
            } else {
                $html .= htmlspecialchars($license['name'] ?? 'Licensed');
            }
            $html .= '</div>';
        }
        
        // Add servers info
        if (isset($this->config['servers'])) {
            $html .= '<div class="servers"><h3>Available Servers</h3><ul>';
            foreach ($this->config['servers'] as $server) {
                $html .= '<li><strong>' . htmlspecialchars($server['description'] ?? 'Server') . ':</strong> <code>' . htmlspecialchars($server['url']) . '</code></li>';
            }
            $html .= '</ul></div>';
        }
        
        // Add authentication info
        if (isset($this->config['security_schemes'])) {
            $html .= '<div class="auth-info"><h3>Authentication</h3>';
            foreach ($this->config['security_schemes'] as $schemeName => $scheme) {
                $html .= '<div><strong>' . htmlspecialchars(ucfirst($schemeName)) . ':</strong> ';
                $html .= htmlspecialchars(ucfirst($scheme['type']));
                if (isset($scheme['scheme'])) {
                    $html .= ' (' . htmlspecialchars($scheme['scheme']) . ')';
                }
                if (isset($scheme['bearerFormat'])) {
                    $html .= ' - Format: ' . htmlspecialchars($scheme['bearerFormat']);
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    private function generateHtmlFooter(): string
    {
        return '</body></html>';
    }

    private function generateHtmlNavigation(array $controllers): string
    {
        $nav = '<nav><h3>Controllers</h3><ul>';
        foreach ($controllers as $controller => $routes) {
            $nav .= '<li><a href="#' . $this->generateAnchor($controller) . '">' . $controller . '</a></li>';
        }
        $nav .= '</ul></nav>';
        return $nav;
    }

    private function generateSecuritySchemes(): array
    {
        return [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT'
            ]
        ];
    }

    private function generateOpenApiOperation(array $route, string $method): array
    {
        return [
            'summary' => $route['description'] ?: ucfirst($method) . ' ' . $route['path'],
            'operationId' => $this->generateOperationId($route, $method),
            'tags' => $route['tags'] ?: [$this->getControllerDisplayName($route['controller'])],
            'deprecated' => $route['deprecated'],
            'security' => $this->formatSecurityForOpenApi($route['security']),
            'parameters' => $this->extractOpenApiParameters($route),
            'requestBody' => $this->extractOpenApiRequestBody($route),
            'responses' => $this->extractOpenApiResponses($route)
        ];
    }

    private function generateOpenApiSchemas(): array
    {
        // Return schemas for all DTOs encountered
        $schemas = [];
        foreach ($this->processedClasses as $className => $documentation) {
            if (str_contains($className, 'DTO')) {
                $schemaName = $this->getSchemaName($className);
                $schemas[$schemaName] = $this->generateOpenApiSchema($className);
            }
        }
        return $schemas;
    }

    private function getSchemaName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    private function generatePostmanRequest(array $route, string $method): array
    {
        return [
            'name' => $route['description'] ?: ucfirst($method) . ' ' . $route['path'],
            'request' => [
                'method' => strtoupper($method),
                'header' => [
                    ['key' => 'Content-Type', 'value' => 'application/json'],
                    ['key' => 'Authorization', 'value' => 'Bearer {{token}}']
                ],
                'url' => [
                    'raw' => '{{base_url}}' . $route['path'],
                    'host' => ['{{base_url}}'],
                    'path' => explode('/', trim($route['path'], '/'))
                ]
            ]
        ];
    }

    private function normalizeOpenApiPath(string $path): string
    {
        // Convert Symfony route patterns to OpenAPI format
        return preg_replace('/\{([^}]+)\}/', '{$1}', $path);
    }

    // Helper methods for enhanced functionality
    private function extractControllerDescription(string $controller, array $routes): string
    {
        if (empty($routes)) return '';
        
        try {
            $reflection = new \ReflectionClass($routes[0]['controller']);
            return $this->extractClassDocumentation($reflection);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function formatRouteForJson(array $route): array
    {
        return [
            'path' => $route['path'],
            'methods' => $route['methods'],
            'action' => $route['action'],
            'description' => $route['description'],
            'deprecated' => $route['deprecated'],
            'security' => $route['security'],
            'tags' => $route['tags']
        ];
    }

    private function generateRouteHtmlDocumentation(array $route): string
    {
        $methods = array_map('strtolower', $route['methods']);
        $methodTags = implode(' ', array_map(fn($m) => "<span class='method $m'>$m</span>", $methods));
        
        $html = "<div class='route" . ($route['deprecated'] ? ' deprecated' : '') . "'>";
        $html .= "<h3>$methodTags {$route['path']}</h3>";
        $html .= "<p>{$route['description']}</p>";
        $html .= "</div>";
        
        return $html;
    }
}

/**
 * Main function to handle CLI execution
 */
function main(): void
{
    global $argc, $argv;
    
    $options = getopt('', [
        'format:', 'output:', 'config:', 'no-cache', 'include-examples', 
        'exclude-deprecated', 'max-depth:', 'help'
    ]);
    
    if (isset($options['help'])) {
        echo "Enhanced API Documentation Generator\n";
        echo "Usage: php generate-api-docs-enhanced.php [options]\n\n";
        echo "Options:\n";
        echo "  --format=FORMAT     Output format: markdown, json, html, openapi (default: markdown)\n";
        echo "  --output=FILE       Output file path\n";
        echo "  --config=FILE       Configuration file path\n";
        echo "  --no-cache          Disable caching\n";
        echo "  --include-examples  Include request/response examples\n";
        echo "  --exclude-deprecated Hide deprecated endpoints\n";
        echo "  --max-depth=N       Maximum recursion depth for DTOs (default: 5)\n";
        echo "  --help              Show this help message\n";
        exit(0);
    }
    
    $config = [];
    
    if (isset($options['format'])) {
        $config['output_format'] = $options['format'];
    }
    
    if (isset($options['no-cache'])) {
        $config['cache_enabled'] = false;
    }
    
    if (isset($options['include-examples'])) {
        $config['include_examples'] = true;
    }
    
    if (isset($options['exclude-deprecated'])) {
        $config['show_deprecated'] = false;
    }
    
    if (isset($options['max-depth'])) {
        $config['max_depth'] = (int)$options['max-depth'];
    }
    
    // Load config file if specified
    if (isset($options['config']) && file_exists($options['config'])) {
        $fileConfig = json_decode(file_get_contents($options['config']), true);
        if ($fileConfig) {
            $config = array_merge($config, $fileConfig);
        }
    }
    
    $generator = new EnhancedApiDocGenerator($config);
    $documentation = $generator->generate();
    
    if (isset($options['output'])) {
        file_put_contents($options['output'], $documentation);
        echo "Documentation written to: {$options['output']}\n";
    } else {
        // Write to default location based on format
        $extension = match ($config['output_format'] ?? 'markdown') {
            'json' => 'json',
            'html' => 'html',
            'openapi' => 'yaml',
            default => 'md'
        };
        
        $outputFile = __DIR__ . "/../../API_DOCUMENTATION.$extension";
        file_put_contents($outputFile, $documentation);
        echo "Documentation generated: $outputFile\n";
    }
}

// Enhanced command-line interface
if (php_sapi_name() === 'cli') {
    $options = getopt('', [
        'format:', 'output:', 'config:', 'cache', 'no-cache', 'progress',
        'include-examples', 'exclude-deprecated', 'max-depth:', 'help'
    ]);
    
    if (isset($options['help'])) {
        echo "Enhanced API Documentation Generator\n\n";
        echo "Usage: php generate-api-docs-enhanced.php [options]\n\n";
        echo "Options:\n";
        echo "  --format=FORMAT     Output format (markdown, json, html, openapi)\n";
        echo "  --output=FILE       Output file path\n";
        echo "  --config=FILE       Configuration file path\n";
        echo "  --cache             Enable caching (default)\n";
        echo "  --no-cache          Disable caching\n";
        echo "  --progress          Show progress bars\n";
        echo "  --include-examples  Include request/response examples\n";
        echo "  --exclude-deprecated Hide deprecated endpoints\n";
        echo "  --max-depth=N       Maximum recursion depth for DTOs (default: 5)\n";
        echo "  --help              Show this help message\n";
        exit(0);
    }
    
    $config = [];
    
    if (isset($options['format'])) {
        $config['output_format'] = $options['format'];
    }
    
    if (isset($options['no-cache'])) {
        $config['cache_enabled'] = false;
    }
    
    if (isset($options['include-examples'])) {
        $config['include_examples'] = true;
    }
    
    if (isset($options['exclude-deprecated'])) {
        $config['show_deprecated'] = false;
    }
    
    if (isset($options['max-depth'])) {
        $config['max_depth'] = (int)$options['max-depth'];
    }
    
    // Load config file if specified
    if (isset($options['config']) && file_exists($options['config'])) {
        $fileConfig = json_decode(file_get_contents($options['config']), true);
        if ($fileConfig) {
            $config = array_merge($config, $fileConfig);
        }
    }
    
    $generator = new EnhancedApiDocGenerator($config);
    $documentation = $generator->generate();
    
    if (isset($options['output'])) {
        file_put_contents($options['output'], $documentation);
        echo "Documentation written to: {$options['output']}\n";
    } else {
        // Write to default location based on format
        $extension = match ($config['output_format'] ?? 'markdown') {
            'json' => 'json',
            'html' => 'html',
            'openapi' => 'yaml',
            default => 'md'
        };
        
        $outputFile = __DIR__ . "/../../API_DOCUMENTATION.$extension";
        file_put_contents($outputFile, $documentation);
        echo "Documentation generated: $outputFile\n";
    }
}

// Main execution
if ($argc > 0 && basename($argv[0]) === basename(__FILE__)) {
    try {
        main();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Use --help for usage information.\n";
        exit(1);
    }
}
