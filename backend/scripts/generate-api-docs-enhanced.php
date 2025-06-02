<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

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
        
        // Display current environment and server info
        $appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'dev';
        $currentServer = $this->getCurrentServer();
        $this->output->writeln(sprintf('<info>Environment: %s</info>', $appEnv));
        $this->output->writeln(sprintf('<info>Current API Server: %s (%s)</info>', $currentServer['url'], $currentServer['description']));
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
            'controllers' => [],
            'schemas' => []
        ];

        foreach ($controllers as $controller => $routes) {
            $documentation['controllers'][] = $this->generateControllerJsonData($controller, $routes);
        }

        // Generate schemas for all DTOs encountered
        $documentation['schemas'] = $this->generateJsonSchemas();

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
            'servers' => $this->getAllServersForOpenApi(),
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
        $currentServer = $this->getCurrentServer();
        $allServers = $this->getAllServersForOpenApi();
        
        // Add current server as primary baseUrl
        $collection['variable'][] = [
            'key' => 'baseUrl',
            'value' => $currentServer['url'],
            'description' => $currentServer['description'] ?? 'Current server URL'
        ];
        
        // Add all available servers as alternatives
        foreach ($allServers as $index => $server) {
            if ($server['url'] !== $currentServer['url']) {
                $varName = 'baseUrl' . ($index + 1);
                $collection['variable'][] = [
                    'key' => $varName,
                    'value' => $server['url'],
                    'description' => $server['description'] ?? 'Alternative server URL'
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
        $this->routes = $this->parseRoutesFromSymfonyRouter();
        
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
     * Parse routes using Symfony's debug:router command for accurate route information
     */
    private function parseRoutesFromSymfonyRouter(): array
    {
        $cacheKey = 'symfony_routes_' . md5(__DIR__ . '/../');
        
        if ($this->config['cache_enabled'] && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $this->output->writeln('<info>Loading routes from Symfony router...</info>');
        
        // Use Symfony's debug:router command to get accurate route information
        $routesJson = shell_exec('cd ' . __DIR__ . '/../ && php bin/console debug:router --format=json 2>/dev/null');
        
        if (!$routesJson) {
            $this->output->writeln('<error>Failed to execute debug:router command</error>');
            return [];
        }
        
        $allRoutes = json_decode($routesJson, true);
        
        if (!$allRoutes) {
            $this->output->writeln('<error>Failed to parse router JSON output</error>');
            return [];
        }
        
        $routes = [];
        
        foreach ($allRoutes as $name => $route) {
            // Filter for API routes
            if (str_starts_with($name, 'api_') && str_starts_with($route['path'], '/api/')) {
                $controller = $route['defaults']['_controller'] ?? null;
                
                if (!$controller) {
                    continue;
                }
                
                // Parse controller and action
                [$controllerClass, $action] = $this->parseControllerAction($controller);
                
                $routeData = [
                    'name' => $name,
                    'path' => $route['path'],
                    'methods' => $this->extractHttpMethods($route),
                    'controller' => $controllerClass,
                    'action' => $action,
                    'method_reflection' => $this->getMethodReflection($controllerClass, $action),
                    'security' => [],
                    'deprecated' => false,
                    'tags' => [],
                    'description' => ''
                ];
                
                // Enhance with reflection data if available
                if ($routeData['method_reflection']) {
                    $method = $routeData['method_reflection'];
                    $routeData['security'] = $this->extractSecurityInfo($method);
                    $routeData['deprecated'] = $this->isDeprecated($method);
                    $routeData['tags'] = $this->extractTags($method);
                    $routeData['description'] = $this->extractMethodSummary($method);
                }
                
                $routes[] = $routeData;
            }
        }
        
        if ($this->config['cache_enabled']) {
            $this->cache[$cacheKey] = $routes;
        }
        
        $this->output->writeln(sprintf('<info>Loaded %d API routes</info>', count($routes)));
        
        return $routes;
    }
    
    /**
     * Parse controller action string into class and method
     */
    private function parseControllerAction(string $controller): array
    {
        if (str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller, 2);
            return [$class, $method];
        }
        
        // Handle invokable controllers
        return [$controller, '__invoke'];
    }
    
    /**
     * Get method reflection safely
     */
    private function getMethodReflection(string $controllerClass, string $action): ?\ReflectionMethod
    {
        try {
            if (!class_exists($controllerClass)) {
                return null;
            }
            
            $reflection = new \ReflectionClass($controllerClass);
            if (!$reflection->hasMethod($action)) {
                return null;
            }
            
            return $reflection->getMethod($action);
        } catch (\Throwable $e) {
            $this->output->writeln(sprintf(
                '<warning>Failed to get method reflection for %s::%s: %s</warning>',
                $controllerClass,
                $action,
                $e->getMessage()
            ));
            return null;
        }
    }
    
    /**
     * Extract HTTP methods from route information
     */
    private function extractHttpMethods(array $route): array
    {
        if (isset($route['method']) && $route['method'] !== 'ANY') {
            return explode('|', $route['method']);
        }
        
        // Infer method from route name if not specified
        $path = $route['path'];
        if (str_contains($path, 'create') || str_contains($path, 'register') || str_contains($path, 'login')) {
            return ['POST'];
        } elseif (str_contains($path, 'update') || str_contains($path, 'change')) {
            return ['PUT', 'PATCH'];
        } elseif (str_contains($path, 'delete')) {
            return ['DELETE'];
        }
        
        return ['GET'];
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
    /**
     * Generate enhanced DTO documentation with better structure
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
            $output = $this->generateEnhancedClassDocumentation($reflection, $depth);
            $this->processedClasses[$className] = $output;
            
            unset($this->circularRefs[$className]);
            return $output;
        } catch (\Exception $e) {
            unset($this->circularRefs[$className]);
            return "Error processing class $className: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Generate enhanced class documentation as a TypeScript interface
     */
    private function generateEnhancedClassDocumentation(\ReflectionClass $reflection, int $depth = 0): string
    {
        $className = $reflection->getShortName();
        $output = "**{$className}**\n\n";
        
        // Class description
        $classDoc = $this->extractClassDocumentation($reflection);
        if ($classDoc) {
            $output .= "$classDoc\n\n";
        }
        
        // Properties with TypeScript interface format
        $properties = $this->extractClassProperties($reflection, $depth);
        
        if (!empty($properties)) {
            $output .= "```typescript\ninterface $className {\n";
            
            foreach ($properties as $property) {
                $output .= $this->generateTypeScriptPropertyDefinition($property, $depth);
            }
            
            $output .= "}\n```\n";
        } else {
            $output .= "*No properties documented*\n\n";
        }
        
        return $output;
    }

    /**
     * Generate a TypeScript property definition with comments for documentation
     */
    private function generateTypeScriptPropertyDefinition(array $property, int $depth): string
    {
        $name = $property['name'];
        $type = $this->convertToTypeScriptType($property['type'], $depth);
        $required = $property['required'] ?? !($property['nullable'] ?? false);
        
        $output = "";
        
        // Add JSDoc comment if there's a description
        if (!empty($property['description'])) {
            // Format description as JSDoc comment
            $lines = explode("\n", $this->sanitizeDescription($property['description']));
            $output .= "  /**\n";
            foreach ($lines as $line) {
                $output .= "   * " . trim($line) . "\n";
            }
            
            // Add default value if present
            if (!$required && isset($property['default'])) {
                $defaultValue = is_string($property['default']) 
                    ? "'{$property['default']}'" 
                    : var_export($property['default'], true);
                $output .= "   * @default {$defaultValue}\n";
            }
            
            // Add validation constraints as JSDoc
            if (!empty($property['validation'])) {
                foreach ($property['validation'] as $constraint) {
                    $output .= "   * @" . $this->formatValidationConstraintForJSDoc($constraint) . "\n";
                }
            }
            $output .= "   */\n";
        }
        
        // Check if this is a complex type that should be expanded inline
        $expandedType = $this->maybeExpandTypeInline($property['type'], $depth);
        if ($expandedType !== null) {
            $output .= "  " . $name . ($required ? "" : "?") . ": " . $expandedType . ";\n\n";
        } else {
            $output .= "  " . $name . ($required ? "" : "?") . ": " . $type . ";\n\n";
        }
        
        return $output;
    }
    
    /**
     * Format validation constraint for JSDoc comment
     */
    private function formatValidationConstraintForJSDoc(string $constraint): string
    {
        // Convert constraint to a JSDoc-friendly format
        $constraint = str_replace(['(', ')', ','], ['=', '', ' '], $constraint);
        return "validation " . $constraint;
    }
    
    /**
     * Convert PHP type to TypeScript type
     */
    private function convertToTypeScriptType(string $phpType, int $depth = 0): string
    {
        // Handle union types
        if (str_contains($phpType, '|')) {
            $types = explode('|', $phpType);
            $tsTypes = [];
            
            foreach ($types as $type) {
                $tsTypes[] = $this->convertSingleTypeToTypeScript(trim($type), $depth);
            }
            
            return implode(' | ', $tsTypes);
        }
        
        return $this->convertSingleTypeToTypeScript($phpType, $depth);
    }
    
    /**
     * Convert a single PHP type to TypeScript
     */
    private function convertSingleTypeToTypeScript(string $phpType, int $depth = 0): string
    {
        switch ($phpType) {
            case 'string':
                return 'string';
            case 'int':
            case 'integer':
            case 'float':
            case 'double':
                return 'number';
            case 'bool':
            case 'boolean':
                return 'boolean';
            case 'array':
                return 'any[]';
            case 'object':
                return 'Record<string, any>';
            case 'mixed':
                return 'any';
            case 'null':
                return 'null';
            default:
                // Handle complex array types like array<string, mixed>
                if (preg_match('/^array<([^,]+),\s*([^>]+)>$/', $phpType, $matches)) {
                    $keyType = $this->convertSingleTypeToTypeScript(trim($matches[1]), $depth);
                    $valueType = $this->convertSingleTypeToTypeScript(trim($matches[2]), $depth);
                    
                    // If key is string, use Record, otherwise use Map notation
                    if ($keyType === 'string') {
                        return "Record<{$keyType}, {$valueType}>";
                    } else {
                        return "Map<{$keyType}, {$valueType}>";
                    }
                }
                
                // Handle simple array types like array<Type>
                if (preg_match('/^array<([^>]+)>$/', $phpType, $matches)) {
                    $elementType = $this->convertSingleTypeToTypeScript(trim($matches[1]), $depth);
                    return "{$elementType}[]";
                }
                
                // Handle array types like string[], int[], MyClass[]
                if (str_ends_with($phpType, '[]')) {
                    $baseType = substr($phpType, 0, -2);
                    $convertedBase = $this->convertSingleTypeToTypeScript($baseType, $depth);
                    return $convertedBase . '[]';
                }
                
                // Handle class types with namespaces
                if (str_contains($phpType, '\\')) {
                    // Extract short name of the class
                    $parts = explode('\\', $phpType);
                    $shortName = end($parts);
                    
                    // Common known types that should have specific TypeScript representations
                    switch ($shortName) {
                        case 'DateTime':
                        case 'DateTimeInterface':
                            return 'string'; // ISO date string
                        case 'UuidInterface':
                        case 'Uuid':
                            return 'string'; // UUID string
                        default:
                            return $shortName;
                    }
                }
                
                // Handle known type aliases and common patterns
                $typeMapping = [
                    'DesignData' => 'DesignData',
                    'Transform' => 'Transform', 
                    'LayerProperties' => 'LayerProperties',
                    'TextLayerProperties' => 'TextLayerProperties',
                    'ImageLayerProperties' => 'ImageLayerProperties',
                    'ShapeLayerProperties' => 'ShapeLayerProperties',
                ];
                
                if (isset($typeMapping[$phpType])) {
                    return $typeMapping[$phpType];
                }
                
                // If it looks like a malformed array type, try to fix it
                if (str_starts_with($phpType, 'array<') && !str_ends_with($phpType, '>')) {
                    // Likely truncated, assume it's Record<string, any>
                    return 'Record<string, any>';
                }
                
                return $phpType;
        }
    }

    /**
     * Generate enhanced property documentation in table format (legacy format)
     */
    private function generateEnhancedPropertyDocumentation(array $property, int $depth = 0): string
    {
        $name = $property['name'];
        $type = $this->formatTypeForDisplay($property['type']);
        $required = $property['required'] ? 'Yes' : 'No';
        $description = $this->sanitizeDescription($property['description'] ?? '');
        
        return "| `{$name}` | {$type} | {$required} | {$description} |\n";
    }

    /**
     * Format type information for better display
     */
    private function formatTypeForDisplay(string $type): string
    {
        // Handle array types
        if (str_contains($type, 'array')) {
            return '`array`';
        }
        
        // Handle DTO types
        if (str_contains($type, 'DTO') || str_contains($type, 'App\\')) {
            $shortName = substr(strrchr($type, '\\'), 1) ?: $type;
            return "`{$shortName}`";
        }
        
        // Handle union types
        if (str_contains($type, '|')) {
            $types = array_map('trim', explode('|', $type));
            $formattedTypes = array_map(fn($t) => "`{$t}`", $types);
            return implode(' \\| ', $formattedTypes);
        }
        
        // Handle primitive types
        $primitiveTypes = ['string', 'int', 'integer', 'float', 'bool', 'boolean', 'array', 'object'];
        if (in_array(strtolower($type), $primitiveTypes)) {
            return "`{$type}`";
        }
        
        return "`{$type}`";
    }

    /**
     * Sanitize description for table display
     */
    private function sanitizeDescription(string $description): string
    {
        // Remove newlines and extra spaces
        $description = preg_replace('/\s+/', ' ', trim($description));
        
        // Escape pipe characters for table format
        $description = str_replace('|', '\\|', $description);
        
        // Limit length for readability
        if (strlen($description) > 100) {
            $description = substr($description, 0, 97) . '...';
        }
        
        return $description ?: 'No description available';
    }

    /**
     * Format validation information for display
     */
    private function formatValidationInfo(array $validation): string
    {
        if (empty($validation)) {
            return '';
        }
        
        $validationParts = [];
        
        foreach ($validation as $constraint) {
            if (str_contains($constraint, 'NotBlank')) {
                $validationParts[] = 'Required';
            } elseif (str_contains($constraint, 'Email')) {
                $validationParts[] = 'Email format';
            } elseif (str_contains($constraint, 'Length')) {
                if (preg_match('/min:\s*(\d+)/', $constraint, $matches)) {
                    $validationParts[] = "Min length: {$matches[1]}";
                }
                if (preg_match('/max:\s*(\d+)/', $constraint, $matches)) {
                    $validationParts[] = "Max length: {$matches[1]}";
                }
            } elseif (str_contains($constraint, 'Range')) {
                if (preg_match('/min:\s*(\d+)/', $constraint, $matches)) {
                    $validationParts[] = "Min: {$matches[1]}";
                }
                if (preg_match('/max:\s*(\d+)/', $constraint, $matches)) {
                    $validationParts[] = "Max: {$matches[1]}";
                }
            } elseif (str_contains($constraint, 'Choice')) {
                if (preg_match('/choices:\s*\[(.*?)\]/', $constraint, $matches)) {
                    $choices = explode(',', $matches[1]);
                    $choicesStr = implode(', ', array_map('trim', $choices));
                    $validationParts[] = "Choices: {$choicesStr}";
                }
            }
        }
        
        return !empty($validationParts) ? '*(' . implode(', ', $validationParts) . ')*' : '';
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
     * Generate complete fetch request example from DTO class with clear API endpoint path
     */
    private function generateRequestExample(string $className): string
    {
        if (!$this->config['show_request_examples'] || !class_exists($className)) {
            return '';
        }

        try {
            $reflection = new \ReflectionClass($className);
            $example = $this->generateDtoExample($reflection);
            $jsonData = json_encode($example, JSON_PRETTY_PRINT);
            
            // Get current route URL and method
            $routeUrl = $this->getCurrentRouteUrl();
            $routeMethod = $this->getCurrentRouteMethod();
            $requiresAuth = $this->currentRouteRequiresAuth();
            
            // Format the example data for readability
            $prettyJsonLines = explode("\n", $jsonData);
            $indentedJson = implode("\n    ", $prettyJsonLines);
            
            $baseUrl = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'example.com';
            $fullUrl = "https://{$baseUrl}{$routeUrl}";
            
            // Generate fetch example
            $fetchExample = "```javascript\n";
            $fetchExample .= "// Example API Request for " . $routeMethod . " " . $routeUrl . "\n";
            $fetchExample .= "async function example" . ucfirst(strtolower($routeMethod)) . "Request() {\n";
            $fetchExample .= "  const url = '" . $fullUrl . "';\n";
            $fetchExample .= "  const requestData = " . $indentedJson . ";\n\n";
            
            $fetchExample .= "  try {\n";
            $fetchExample .= "    const response = await fetch(url, {\n";
            $fetchExample .= "      method: '{$routeMethod}',\n";
            $fetchExample .= "      headers: {\n";
            $fetchExample .= "        'Content-Type': 'application/json',\n";
            
            if ($requiresAuth) {
                $fetchExample .= "        'Authorization': 'Bearer YOUR_JWT_TOKEN', // This endpoint requires authentication\n";
            }
            
            $fetchExample .= "      },\n";
            
            if ($routeMethod !== 'GET') {
                $fetchExample .= "      body: JSON.stringify(requestData)\n";
            }
            
            $fetchExample .= "    });\n\n";
            $fetchExample .= "    if (!response.ok) {\n";
            $fetchExample .= "      throw new Error('API error: ' + response.status);\n";
            $fetchExample .= "    }\n\n";
            $fetchExample .= "    const data = await response.json();\n";
            $fetchExample .= "    console.log('Success:', data);\n";
            $fetchExample .= "    return data;\n";
            $fetchExample .= "  } catch (error) {\n";
            $fetchExample .= "    console.error('Error:', error);\n";
            $fetchExample .= "    throw error;\n";
            $fetchExample .= "  }\n";
            $fetchExample .= "}\n";
            $fetchExample .= "```\n";
            
            return $fetchExample;
        } catch (\Exception $e) {
            return "Error generating example: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Check if the current route requires authentication
     */
    private function currentRouteRequiresAuth(): bool
    {
        if (!isset($this->currentRoute)) {
            return false;
        }
        
        // Check for security attributes like IsGranted
        if (!empty($this->currentRoute['security'])) {
            return true;
        }
        
        // Check controller method for security attributes
        if (isset($this->currentRoute['method_reflection'])) {
            $method = $this->currentRoute['method_reflection'];
            $attributes = $method->getAttributes();
            
            foreach ($attributes as $attribute) {
                $name = $attribute->getName();
                if (str_contains($name, 'IsGranted') || str_contains($name, 'Security')) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get the current route URL for examples
     */
    private function getCurrentRouteUrl(): string
    {
        if (isset($this->currentRoute) && !empty($this->currentRoute['path'])) {
            // Get the complete route path, ensuring it has the proper format
            $path = $this->currentRoute['path'];
            
            // Remove API prefix if it's already there to avoid duplication
            if (strpos($path, '/api') === 0) {
                return $path;
            } else {
                return '/api' . $path;
            }
        }
        
        return '/api/example/endpoint';
    }
    
    /**
     * Get the current route method for examples
     */
    private function getCurrentRouteMethod(): string
    {
        if (isset($this->currentRoute) && !empty($this->currentRoute['methods'])) {
            return strtoupper($this->currentRoute['methods'][0] ?? 'POST');
        }
        
        return 'POST';
    }

    /**
     * Generate example data structure for a DTO
     */
    private function generateDtoExample(\ReflectionClass $reflection): array
    {
        $example = [];
        
        // Handle promoted constructor parameters
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->isPromoted()) {
                    $example[$param->getName()] = $this->generatePropertyExample($param);
                }
            }
        }
        
        // Handle regular properties
        foreach ($reflection->getProperties() as $property) {
            if (!$this->isPromotedProperty($reflection, $property->getName())) {
                $example[$property->getName()] = $this->generateRegularPropertyExample($property);
            }
        }
        
        return $example;
    }

    /**
     * Generate example value for a promoted property parameter
     */
    private function generatePropertyExample(\ReflectionParameter $param): mixed
    {
        $type = $param->getType();
        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed';
        
        // Handle nullable types
        if ($type && $type->allowsNull() && $param->isOptional()) {
            return null;
        }
        
        return $this->generateExampleValue($typeName, $param->getName());
    }

    /**
     * Generate example value for a regular property
     */
    private function generateRegularPropertyExample(\ReflectionProperty $property): mixed
    {
        $type = $property->getType();
        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed';
        
        return $this->generateExampleValue($typeName, $property->getName());
    }

    /**
     * Generate example value based on type and property name
     */
    private function generateExampleValue(string $typeName, string $propertyName): mixed
    {
        // Handle DTO types
        if (str_contains($typeName, 'App\\DTO\\') && class_exists($typeName)) {
            try {
                $reflection = new \ReflectionClass($typeName);
                return $this->generateDtoExample($reflection);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        // Handle arrays
        if ($typeName === 'array') {
            // Generate array examples based on property name
            if (str_contains(strtolower($propertyName), 'id')) {
                return [1, 2, 3];
            } elseif (str_contains(strtolower($propertyName), 'tag')) {
                return ['tag1', 'tag2'];
            } elseif (str_contains(strtolower($propertyName), 'name')) {
                return ['Example Name 1', 'Example Name 2'];
            } else {
                return ['example_item'];
            }
        }
        
        // Handle primitive types with property name hints
        switch ($typeName) {
            case 'string':
                return $this->generateStringExample($propertyName);
            case 'int':
            case 'integer':
                return $this->generateIntExample($propertyName);
            case 'float':
                return 3.14;
            case 'bool':
            case 'boolean':
                return true;
            case \DateTimeInterface::class:
            case \DateTime::class:
                return date('c');
            default:
                return null;
        }
    }

    /**
     * Generate contextual string examples based on property name
     */
    private function generateStringExample(string $propertyName): string
    {
        $lowerName = strtolower($propertyName);
        
        if (str_contains($lowerName, 'email')) {
            return 'user@example.com';
        } elseif (str_contains($lowerName, 'name')) {
            return 'Example Name';
        } elseif (str_contains($lowerName, 'title')) {
            return 'Example Title';
        } elseif (str_contains($lowerName, 'description')) {
            return 'This is an example description.';
        } elseif (str_contains($lowerName, 'url')) {
            return 'https://example.com';
        } elseif (str_contains($lowerName, 'password')) {
            return 'secure_password123';
        } elseif (str_contains($lowerName, 'token')) {
            return 'example_token_12345';
        } elseif (str_contains($lowerName, 'category')) {
            return 'category_example';
        } elseif (str_contains($lowerName, 'type')) {
            return 'example_type';
        } elseif (str_contains($lowerName, 'status')) {
            return 'active';
        } elseif (str_contains($lowerName, 'id')) {
            return 'example_id_123';
        } else {
            return 'example_string';
        }
    }

    /**
     * Generate contextual integer examples based on property name
     */
    private function generateIntExample(string $propertyName): int
    {
        $lowerName = strtolower($propertyName);
        
        if (str_contains($lowerName, 'id')) {
            return 123;
        } elseif (str_contains($lowerName, 'count')) {
            return 10;
        } elseif (str_contains($lowerName, 'size')) {
            return 100;
        } elseif (str_contains($lowerName, 'width')) {
            return 800;
        } elseif (str_contains($lowerName, 'height')) {
            return 600;
        } elseif (str_contains($lowerName, 'age')) {
            return 25;
        } elseif (str_contains($lowerName, 'page')) {
            return 1;
        } elseif (str_contains($lowerName, 'limit')) {
            return 20;
        } else {
            return 42;
        }
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
        $currentServer = $this->getCurrentServer();
        $allServers = $this->getAllServersForOpenApi();
        
        $output .= "## Servers\n\n";
        $output .= "**Current Server (based on APP_ENV):** `" . $currentServer['url'] . "` - " . $currentServer['description'] . "\n\n";
        
        if (count($allServers) > 1) {
            $output .= "**All Available Servers:**\n\n";
            foreach ($allServers as $server) {
                $isCurrent = $server['url'] === $currentServer['url'] ? ' *(current)*' : '';
                $output .= "- **" . ($server['description'] ?? 'Server') . ":** `" . $server['url'] . "`" . $isCurrent . "\n";
            }
        }
        $output .= "\n";
        
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
    /**
     * Generate enhanced controller documentation with better organization
     */
    private function generateControllerDocumentation(string $controller, array $routes): string
    {
        $output = $this->generateControllerHeader($controller, $routes);
        $output .= $this->generateControllerDescription($routes);
        $output .= $this->generateControllerRoutes($routes);
        
        return $output;
    }

    /**
     * Generate controller header with route count
     */
    private function generateControllerHeader(string $controller, array $routes): string
    {
        $routeCount = count($routes);
        $routeWord = $routeCount === 1 ? 'route' : 'routes';
        
        return "## {$controller}\n\n*{$routeCount} {$routeWord}*\n\n";
    }

    /**
     * Generate controller description from class documentation
     */
    private function generateControllerDescription(array $routes): string
    {
        try {
            $reflection = new \ReflectionClass($routes[0]['controller']);
            $classDoc = $this->extractClassDocumentation($reflection);
            
            if ($classDoc) {
                return "{$classDoc}\n\n";
            }
        } catch (\Exception $e) {
            // Log error but continue
        }
        
        return '';
    }

    /**
     * Generate all routes for a controller with filtering
     */
    private function generateControllerRoutes(array $routes): string
    {
        $output = '';
        $routeNumber = 1;
        
        foreach ($routes as $route) {
            // Skip deprecated routes if configured
            if ($route['deprecated'] && !$this->config['show_deprecated']) {
                continue;
            }
            
            // Add route number for better navigation
            $output .= "<!-- Route {$routeNumber} -->\n";
            $output .= $this->generateRouteDocumentation($route);
            $routeNumber++;
        }
        
        return $output;
    }

    /**
     * Generate documentation for a single route with improved readability
     */
    /**
     * Property to store current route being processed for example generation
     */
    private $currentRoute = null;
    
    /**
     * Generate documentation for a single route
     */
    private function generateRouteDocumentation(array $route): string
    {
        // Set current route for example generation
        $this->currentRoute = $route;
        
        $method = $route['method_reflection'];
        $output = $this->generateRouteHeader($route);
        $output .= $this->generateRouteMetadata($route);
        $output .= $this->generateRouteDescription($method);
        $output .= $this->generateRouteParameters($method);
        $output .= $this->generateRequestBodySection($method);
        $output .= $this->generateResponseSection($method);
        $output .= $this->generateRouteFooter();
        
        return $output;
    }

    /**
     * Generate route header with method and path
     */
    private function generateRouteHeader(array $route): string
    {
        $methodsList = implode(', ', $route['methods']);
        $header = "### " . strtoupper($methodsList) . " " . $route['path'];
        
        if ($route['deprecated']) {
            $header .= " ⚠️ *Deprecated*";
        }
        
        return $header . "\n\n";
    }

    /**
     * Generate route metadata (security, tags)
     */
    private function generateRouteMetadata(array $route): string
    {
        $output = '';
        
        // Security annotations
        if (!empty($route['security']) && $this->config['include_security']) {
            $securityTypes = array_column($route['security'], 'type');
            $output .= "**Security:** " . implode(', ', $securityTypes) . "\n\n";
        }
        
        // Tags
        if (!empty($route['tags'])) {
            $output .= "**Tags:** " . implode(', ', $route['tags']) . "\n\n";
        }
        
        return $output;
    }

    /**
     * Generate route description from method documentation
     */
    private function generateRouteDescription(\ReflectionMethod $method): string
    {
        $methodDoc = $this->extractMethodDocumentation($method);
        return $methodDoc ? "$methodDoc\n\n" : '';
    }

    /**
     * Generate route parameters section
     */
    private function generateRouteParameters(\ReflectionMethod $method): string
    {
        $parameters = $this->extractMethodParameters($method);
        if (empty($parameters)) {
            return '';
        }
        
        $output = "#### Parameters\n\n";
        foreach ($parameters as $param) {
            $output .= $this->generateParameterDocumentation($param);
        }
        
        return $output . "\n";
    }

    /**
     * Generate request body section with improved DTO detection
     */
    private function generateRequestBodySection(\ReflectionMethod $method): string
    {
        $requestDto = $this->extractRequestDto($method);
        if (!$requestDto) {
            return '';
        }
        
        $output = "#### Request Body\n\n";
        $output .= $this->generateDtoDocumentation($requestDto);
        $output .= "\n";
        
        if ($this->config['show_request_examples']) {
            $output .= "**Example Request:**\n\n";
            $output .= $this->generateRequestExample($requestDto);
            $output .= "\n";
        }
        
        return $output;
    }

    /**
     * Generate response section with improved DTO detection - using JSON format
     */
    private function generateResponseSection(\ReflectionMethod $method): string
    {
        $responseDto = $this->extractResponseDto($method);
        if (!$responseDto) {
            return '';
        }
        
        $output = "#### Response\n\n";
        
        // For responses, we just show the JSON schema
        try {
            $reflection = new \ReflectionClass($responseDto);
            $example = $this->generateDtoExample($reflection);
            
            $output .= "**Response Schema:**\n\n";
            $output .= "```json\n" . json_encode($example, JSON_PRETTY_PRINT) . "\n```\n\n";
        } catch (\Exception $e) {
            $output .= "Error generating response schema: " . $e->getMessage() . "\n\n";
            // Fallback to TypeScript interface
            $output .= $this->generateDtoDocumentation($responseDto);
        }
        
        return $output;
    }

    /**
     * Generate route footer
     */
    private function generateRouteFooter(): string
    {
        return "---\n\n";
    }

    /**
     * Extract Request DTO from method parameters with improved detection
     */
    private function extractRequestDto(\ReflectionMethod $method): ?string
    {
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            if (!$type || !$type instanceof \ReflectionNamedType) {
                continue;
            }
            
            $typeName = $type->getName();
            
            // Enhanced DTO detection patterns - use actual type hints
            if ($this->isValidRequestDto($typeName)) {
                return $typeName;
            }
        }
        
        return null;
    }

    /**
     * Extract Response DTO from method return type and PHPDoc annotations
     */
    private function extractResponseDto(\ReflectionMethod $method): ?string
    {
        // First, try to extract from PHPDoc @return annotation
        $responseDto = $this->extractResponseDtoFromPhpDoc($method);
        if ($responseDto) {
            return $responseDto;
        }
        
        // Fallback to return type analysis
        $returnType = $method->getReturnType();
        if (!$returnType || !$returnType instanceof \ReflectionNamedType) {
            return null;
        }
        
        $typeName = $returnType->getName();
        
        // If it's JsonResponse, we already tried PHPDoc, so return null
        if ($typeName === 'Symfony\\Component\\HttpFoundation\\JsonResponse') {
            return null;
        }
        
        // Check if the return type itself is a DTO
        if ($this->isValidResponseDto($typeName)) {
            return $typeName;
        }
        
        return null;
    }

    /**
     * Extract Response DTO from PHPDoc @return annotation
     */
    private function extractResponseDtoFromPhpDoc(\ReflectionMethod $method): ?string
    {
        $docComment = $method->getDocComment();
        if (!$docComment) {
            return null;
        }
        
        // Use raw parsing instead of phpDocumentor library to avoid compatibility issues
        return $this->extractResponseDtoFromRawDocComment($method);
    }

    /**
     * Fallback method to extract Response DTO from raw doc comment
     */
    private function extractResponseDtoFromRawDocComment(\ReflectionMethod $method): ?string
    {
        $docComment = $method->getDocComment();
        if (!$docComment) {
            return null;
        }
        
        // Look for @return JsonResponse<SomeDTO> patterns using regex
        if (preg_match('/@return\s+JsonResponse<([^>]+)>/', $docComment, $matches)) {
            $dtoTypes = $matches[1];
            
            // Handle union types
            if (str_contains($dtoTypes, '|')) {
                $types = array_map('trim', explode('|', $dtoTypes));
                // Return the first non-error DTO
                foreach ($types as $type) {
                    if (!str_contains($type, 'Error') && $this->isValidResponseDto($type)) {
                        return $this->resolveFullClassName($type, $method);
                    }
                }
                // If no non-error DTO found, return the first valid one
                foreach ($types as $type) {
                    if ($this->isValidResponseDto($type)) {
                        return $this->resolveFullClassName($type, $method);
                    }
                }
            } else {
                // Single DTO type
                if ($this->isValidResponseDto($dtoTypes)) {
                    return $this->resolveFullClassName($dtoTypes, $method);
                }
            }
        }
        
        // Look for direct DTO return types
        if (preg_match('/@return\s+([A-Za-z_\\\\]+ResponseDTO[A-Za-z_]*)/', $docComment, $matches)) {
            $dtoType = $matches[1];
            if ($this->isValidResponseDto($dtoType)) {
                return $this->resolveFullClassName($dtoType, $method);
            }
        }
        
        return null;
    }

    /**
     * Resolve full class name from short name using method's use statements
     */
    private function resolveFullClassName(string $className, \ReflectionMethod $method): string
    {
        // If already a full class name, return as is
        if (str_contains($className, '\\')) {
            return $className;
        }
        
        // Try to resolve using the method's declaring class namespace
        $declaringClass = $method->getDeclaringClass();
        $namespace = $declaringClass->getNamespaceName();
        
        // Common DTO namespace patterns
        $possibleNamespaces = [
            $namespace . '\\DTO\\' . $className,
            $namespace . '\\DTO\\Response\\' . $className,
            'App\\DTO\\' . $className,
            'App\\DTO\\Response\\' . $className,
        ];
        
        foreach ($possibleNamespaces as $fullClassName) {
            if (class_exists($fullClassName)) {
                return $fullClassName;
            }
        }
        
        // If not found, return the original className
        return $className;
    }

    /**
     * Check if a class is a valid Request DTO
     */
    private function isValidRequestDto(string $className): bool
    {
        // Skip primitive types and built-in classes
        if (!str_contains($className, '\\')) {
            // Try to resolve as short class name
            $className = $this->resolveFullClassNameForValidation($className);
            if (!$className || !class_exists($className)) {
                return false;
            }
        } elseif (!class_exists($className)) {
            return false;
        }
        
        // Skip Symfony built-in classes
        if (str_starts_with($className, 'Symfony\\')) {
            return false;
        }
        
        $patterns = [
            '/.*RequestDTO$/i',
            '/.*Request$/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $className)) {
                return true;
            }
        }
        
        // Check if it's in a DTO namespace and has DTO-like structure
        return str_contains($className, 'DTO') && $this->isDtoClass($className);
    }

    /**
     * Get reflection method for a class and method name
     */
    private function getReflectionMethod(string $className, string $methodName): ?\ReflectionMethod
    {
        try {
            $reflectionClass = new \ReflectionClass($className);
            return $reflectionClass->getMethod($methodName);
        } catch (\ReflectionException $e) {
            return null;
        }
    }

    /**
     * Check if a class is a valid Response DTO
     */
    private function isValidResponseDto(string $className): bool
    {
        // Skip primitive types and built-in classes
        if (!str_contains($className, '\\')) {
            // Try to resolve as short class name
            $className = $this->resolveFullClassNameForValidation($className);
            if (!$className || !class_exists($className)) {
                return false;
            }
        } elseif (!class_exists($className)) {
            return false;
        }
        
        // Skip Symfony built-in classes
        if (str_starts_with($className, 'Symfony\\')) {
            return false;
        }
        
        $patterns = [
            '/.*ResponseDTO$/i',
            '/.*Response$/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $className)) {
                return true;
            }
        }
        
        // Check if it's in a DTO namespace
        return str_contains($className, 'DTO') && $this->isDtoClass($className);
    }

    /**
     * Helper method to resolve full class name for validation
     */
    private function resolveFullClassNameForValidation(string $className): ?string
    {
        // Common DTO namespace patterns to check
        $possibleNamespaces = [
            'App\\DTO\\' . $className,
            'App\\DTO\\Response\\' . $className,
            'App\\DTO\\Request\\' . $className,
        ];
        
        foreach ($possibleNamespaces as $fullClassName) {
            if (class_exists($fullClassName)) {
                return $fullClassName;
            }
        }
        
        return null;
    }

    /**
     * Check if a class is a DTO class by examining its structure
     */
    private function isDtoClass(string $className): bool
    {
        try {
            $reflection = new \ReflectionClass($className);
            
            // Check if it's in a DTO namespace
            if (str_contains($reflection->getNamespaceName(), 'DTO')) {
                return true;
            }
            
            // Check for readonly properties (common in DTOs)
            $hasReadonlyProperties = false;
            foreach ($reflection->getProperties() as $property) {
                if ($property->isReadOnly()) {
                    $hasReadonlyProperties = true;
                    break;
                }
            }
            
            // Check for promoted constructor parameters (common in modern DTOs)
            $hasPromotedParameters = false;
            $constructor = $reflection->getConstructor();
            if ($constructor) {
                foreach ($constructor->getParameters() as $param) {
                    if ($param->isPromoted()) {
                        $hasPromotedParameters = true;
                        break;
                    }
                }
            }
            
            return $hasReadonlyProperties || $hasPromotedParameters;
            
        } catch (\Exception $e) {
            return false;
        }
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
            $type = $param->getType();
            
            // Skip parameters that are classes/objects but not valid DTOs
            if ($type && $type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                
                // Skip primitive types (string, int, bool, float, array)
                $primitiveTypes = ['string', 'int', 'integer', 'bool', 'boolean', 'float', 'double', 'array', 'mixed'];
                if (in_array($typeName, $primitiveTypes, true)) {
                    $parameters[] = [
                        'name' => $param->getName(),
                        'type' => $this->getParameterTypeString($param),
                        'required' => !$param->isOptional(),
                        'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
                    ];
                    continue;
                }
                
                // Skip Request DTOs (handled as request body)
                if ($this->isValidRequestDto($typeName)) {
                    continue;
                }
                
                // Skip Response DTOs (not user input)
                if ($this->isValidResponseDto($typeName)) {
                    continue;
                }
                
                // Skip auto-resolved class/object parameters (services, repositories, etc.)
                if (class_exists($typeName)) {
                    // Skip Symfony framework classes
                    if (str_starts_with($typeName, 'Symfony\\') ||
                        str_starts_with($typeName, 'Doctrine\\') ||
                        str_starts_with($typeName, 'Psr\\')) {
                        continue;
                    }
                    
                    // Skip common service patterns
                    if (str_contains($typeName, 'Service') ||
                        str_contains($typeName, 'Repository') ||
                        str_contains($typeName, 'Manager') ||
                        str_contains($typeName, 'Handler') ||
                        str_contains($typeName, 'Factory') ||
                        str_contains($typeName, 'Helper') ||
                        str_contains($typeName, 'Validator') ||
                        str_contains($typeName, 'Normalizer') ||
                        str_contains($typeName, 'Serializer') ||
                        str_contains($typeName, 'EventDispatcher') ||
                        str_contains($typeName, 'Logger') ||
                        str_contains($typeName, 'Security') ||
                        str_contains($typeName, 'TokenStorage')) {
                        continue;
                    }
                    
                    // Skip entity classes (typically injected via ParamConverter)
                    if (str_contains($typeName, 'Entity\\') || 
                        str_contains($typeName, '\\Entity\\')) {
                        continue;
                    }
                    
                    // Skip any remaining class/object types that aren't DTOs
                    // Only allow classes that are explicitly DTOs or have DTO-like structure
                    if (!$this->isDtoClass($typeName)) {
                        continue;
                    }
                }
            }
            
            // Include the parameter in documentation
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
        
        // First, try to get type from reflection
        $reflectionType = null;
        if ($type instanceof \ReflectionNamedType) {
            $reflectionType = $type->getName();
        } elseif ($type instanceof \ReflectionUnionType) {
            $reflectionType = implode('|', array_map(fn($t) => $t->getName(), $type->getTypes()));
        }
        
        // Try to get more detailed type information from PHPDoc
        $phpDocType = $this->extractTypeFromParameterDoc($param);
        
        // If PHPDoc provides more specific type information, use it
        if (!empty($phpDocType) && $phpDocType !== 'mixed') {
            // If reflection type is array but PHPDoc gives us array details, use PHPDoc
            if ($reflectionType === 'array' && str_contains($phpDocType, '[]')) {
                return $phpDocType;
            }
            // If PHPDoc provides a more specific class type
            if (str_contains($phpDocType, '\\') && !str_contains($reflectionType ?? '', '\\')) {
                return $phpDocType;
            }
        }
        
        return $reflectionType ?? 'mixed';
    }
    
    /**
     * Extract type information from parameter documentation
     */
    private function extractTypeFromParameterDoc(\ReflectionParameter $param): string
    {
        $constructor = $param->getDeclaringFunction();
        if (!$constructor || !$constructor->getFileName()) {
            return '';
        }
        
        try {
            $file = $constructor->getFileName();
            $content = file_get_contents($file);
            $tokens = token_get_all($content);
            
            // Find the parameter in the constructor and get its PHPDoc type
            return $this->findParameterTypeInTokens($tokens, $param->getName());
        } catch (\Exception $e) {
            return '';
        }
    }
    
    /**
     * Find parameter type in token stream
     */
    private function findParameterTypeInTokens(array $tokens, string $paramName): string
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
                    // Extract type from the doc comment
                    $extractedType = $this->extractTypeFromDocComment($lastDocComment);
                    if (!empty($extractedType)) {
                        return $extractedType;
                    }
                    
                    // Also try to find @var annotations specifically for this parameter
                    $paramVarType = $this->extractVarAnnotationForParameter($lastDocComment, $paramName);
                    if (!empty($paramVarType)) {
                        return $paramVarType;
                    }
                }
            }
        }
        
        return '';
    }
    
    /**
     * Extract @var annotation for a specific parameter
     */
    private function extractVarAnnotationForParameter(string $docComment, string $paramName): string
    {
        // Look for @var annotations that might reference this parameter
        // First try to match the entire type including angle brackets
        if (preg_match('/@var\s+([^\s]+(?:<[^>]+>)?[^\s]*)(?:\s+\$' . preg_quote($paramName) . ')?/', $docComment, $matches)) {
            return trim($matches[1]);
        }
        
        // Fallback: look for @var followed by any type up to whitespace or $
        if (preg_match('/@var\s+([^\s\$]+)(?:\s+\$' . preg_quote($paramName) . ')?/', $docComment, $matches)) {
            $type = trim($matches[1]);
            
            // If we have angle brackets but they seem incomplete, try to extend
            if (str_contains($type, '<') && !str_contains($type, '>')) {
                               // Look for the complete type in the line
                $varPos = strpos($docComment, '@var');
                if ($varPos !== false) {
                    $line = substr($docComment, $varPos);
                    if (preg_match('/@var\s+([^$\s]*<[^>]*>[^$\s]*)/', $line, $extendedMatches)) {
                        return trim($extendedMatches[1]);
                    }
                }
            }
            
            return $type;
        }
        
        return '';
    }
    
    /**
     * Extract type from PHPDoc comment
     */
    private function extractTypeFromDocComment(string $docComment): string
    {
        if (empty($docComment)) {
            return '';
        }
        
        // Look for @var annotations first
        // First try to match the entire type including angle brackets
        if (preg_match('/@var\s+([^\s]+(?:<[^>]+>)?[^\s]*)/', $docComment, $matches)) {
            $varType = trim($matches[1]);
            
            // If it's a detailed array type like array<string, mixed>, return that
            if (str_contains($varType, 'array<')) {
                return $varType;
            }
            
            return $varType;
        }
        
        // Fallback: look for @var followed by any type up to whitespace
        if (preg_match('/@var\s+([^\s\$]+)/', $docComment, $matches)) {
            $type = trim($matches[1]);
            
            // If we have angle brackets but they seem incomplete, try to extend
            if (str_contains($type, '<') && !str_contains($type, '>')) {
                // Look for the complete type in the line
                $varPos = strpos($docComment, '@var');
                if ($varPos !== false) {
                    $line = substr($docComment, $varPos);
                    if (preg_match('/@var\s+([^$\s]*<[^>]*>[^$\s]*)/', $line, $extendedMatches)) {
                        return trim($extendedMatches[1]);
                    }
                }
            }
            
            return $type;
        }
        
        // Look for array type patterns in various formats
        $arrayPatterns = [
            '/array<([^>]+)>/',                           // array<Type>
            '/([A-Z][a-zA-Z0-9\\\\]*)\[\]/',             // Type[]
            '/array\s*\|\s*([A-Z][a-zA-Z0-9\\\\]*)\[\]/', // array|Type[]
            '/([A-Z][a-zA-Z0-9\\\\]*)\[\]\s*\|/',        // Type[]|other
        ];
        
        foreach ($arrayPatterns as $pattern) {
            if (preg_match($pattern, $docComment, $matches)) {
                if (isset($matches[1])) {
                    // For array<Type> format, return as Type[]
                    if (str_contains($pattern, 'array<')) {
                        return $matches[1] . '[]';
                    }
                    // For Type[] format, return as-is
                    return $matches[0];
                }
            }
        }
        
        // Look for union types with specific patterns
        if (preg_match('/([A-Z][a-zA-Z0-9\\\\]*(?:\|[A-Z][a-zA-Z0-9\\\\]*)+)/', $docComment, $matches)) {
            return $matches[1];
        }
        
        // Look for specific class names (DTOs, ValueObjects, etc.)
        $classPatterns = [
            '/App\\\\[A-Z][a-zA-Z0-9\\\\]*/',            // Full App namespaced classes
            '/[A-Z][a-zA-Z0-9]*(?:DTO|Data|ValueObject|Properties|Transform)/', // Common class suffixes
            '/(?:Text|Image|Shape)LayerProperties/',      // Specific layer properties
        ];
        
        foreach ($classPatterns as $pattern) {
            if (preg_match($pattern, $docComment, $matches)) {
                return $matches[0];
            }
        }
        
        // Look for primitive array types
        if (preg_match('/(string|int|float|bool|mixed)\[\]/', $docComment, $matches)) {
            return $matches[0];
        }
        
        return '';
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
        $reflectionType = $this->getParameterTypeString($param);
        $required = !$param->isOptional();
        
        // Extract PHPDoc using token parsing
        $description = $this->extractPromotedParamPhpDoc($reflection, $param);
        
        // Try to extract more specific type from PHPDoc
        $phpDocType = $this->extractTypeFromParameterDoc($param);
        
        // Use the more specific type
        $type = $reflectionType;
        if (!empty($phpDocType) && $phpDocType !== 'mixed') {
            // If PHPDoc provides array details but reflection shows just 'array'
            if ($reflectionType === 'array' && (str_contains($phpDocType, 'array<') || str_contains($phpDocType, '[]'))) {
                $type = $phpDocType;
            }
            // If PHPDoc provides a more specific class type
            elseif (str_contains($phpDocType, '\\') && !str_contains($reflectionType, '\\')) {
                $type = $phpDocType;
            }
            // If PHPDoc provides union type information
            elseif (str_contains($phpDocType, '|')) {
                $type = $phpDocType;
            }
        }
        
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
     * Fallback parsing for doc comments when phpDocumentor fails
     */
    private function parseDocCommentFallback(string $docComment): string
    {
        // Remove /** and */ markers
        $docComment = trim($docComment);
        $docComment = preg_replace('/^\/\*\*\s*/', '', $docComment);
        $docComment = preg_replace('/\s*\*\/$/', '', $docComment);
        
        // Split into lines and process
        $lines = explode("\n", $docComment);
        $description = [];
        
        foreach ($lines as $line) {
            // Remove leading * and whitespace
            $line = preg_replace('/^\s*\*\s?/', '', $line);
            $line = trim($line);
            
            // Skip @annotations but stop at them for description
            if (str_starts_with($line, '@')) {
                break;
            }
            
            if (!empty($line)) {
                $description[] = $line;
            }
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
        if ($this->config['include_examples']) {
            $example = $this->generateExampleFromValidation($property['validation'], $property['type']);
            if (!empty($example)) {
                $output .= "\n$indent  *Example: `$example`*";
            }
        }
        
        return $output;
    }

    /**
     * Check if a type should be expanded inline and return the expanded structure
     */
    private function maybeExpandTypeInline(string $phpType, int $depth): ?string
    {
        // Don't expand if we're too deep to prevent infinite recursion
        if ($depth >= $this->maxDepth) {
            return null;
        }
        
        // Handle array types with complex elements
        if (str_ends_with($phpType, '[]')) {
            $baseType = substr($phpType, 0, -2);
            $expandedElement = $this->maybeExpandTypeInline($baseType, $depth + 1);
            if ($expandedElement !== null) {
                return $expandedElement . '[]';
            }
            return null;
        }
        
        // Handle complex array types like array<string, mixed>
        if (preg_match('/^array<([^,]+),\s*([^>]+)>$/', $phpType, $matches)) {
            $keyType = $this->convertSingleTypeToTypeScript(trim($matches[1]), $depth);
            $valueType = $this->convertSingleTypeToTypeScript(trim($matches[2]), $depth);
            
            if ($keyType === 'string') {
                return "Record<{$keyType}, {$valueType}>";
            } else {
                return "Map<{$keyType}, {$valueType}>";
            }
        }
        
        // Handle union types - expand each type that can be expanded
        if (str_contains($phpType, '|')) {
            $types = explode('|', $phpType);
            $expandedTypes = [];
            $hasComplexExpansion = false;
            
            foreach ($types as $type) {
                $type = trim($type);
                
                // Skip null type in union expansion for cleaner output
                if ($type === 'null') {
                    continue;
                }
                
                $expanded = $this->maybeExpandTypeInline($type, $depth + 1);
                if ($expanded !== null && str_contains($expanded, '{')) {
                    // This is a complex object expansion
                    $expandedTypes[] = $expanded;
                    $hasComplexExpansion = true;
                } else {
                    // Simple type or couldn't expand
                    $simpleType = $this->convertSingleTypeToTypeScript($type, $depth);
                    $expandedTypes[] = $simpleType;
                }
            }
            
            // Only return expanded union if we have complex expansions
            if ($hasComplexExpansion && count($expandedTypes) > 1) {
                return implode(' | ', $expandedTypes);
            } elseif ($hasComplexExpansion && count($expandedTypes) == 1) {
                return $expandedTypes[0];
            }
            
            return null;
        }
        
        // Check if this is a complex class that should be expanded
        if (str_contains($phpType, '\\') && str_contains($phpType, 'App\\')) {
            try {
                if (class_exists($phpType)) {
                    $reflection = new \ReflectionClass($phpType);
                    return $this->generateInlineTypeStructure($reflection, $depth + 1);
                }
            } catch (\Exception $e) {
                // If we can't reflect the class, fall back to simple type
                return null;
            }
        }
        
        // Check for known complex types that should be expanded
        $knownComplexTypes = [
            'DesignData', 'Transform', 'LayerProperties', 
            'TextLayerProperties', 'ImageLayerProperties', 'ShapeLayerProperties',
            'LayerUpdate'
        ];
        
        if (in_array($phpType, $knownComplexTypes)) {
            $fullClassName = "App\\DTO\\ValueObject\\{$phpType}";
            try {
                if (class_exists($fullClassName)) {
                    $reflection = new \ReflectionClass($fullClassName);
                    return $this->generateInlineTypeStructure($reflection, $depth + 1);
                }
            } catch (\Exception $e) {
                // Ignore and continue
            }
        }
        
        return null;
    }
    
    /**
     * Generate inline type structure for complex objects
     */
    private function generateInlineTypeStructure(\ReflectionClass $reflection, int $depth): string
    {
        $properties = $this->extractClassProperties($reflection, $depth);
        
        if (empty($properties)) {
            return 'Record<string, any>';
        }
        
        $output = "{\n";
        foreach ($properties as $property) {
            $name = $property['name'];
            $type = $this->convertToTypeScriptType($property['type'], $depth);
            $required = $property['required'] ?? true;
            
            // Check for nested expansion
            $expandedType = $this->maybeExpandTypeInline($property['type'], $depth);
            $finalType = $expandedType ?? $type;
            
            $output .= "    " . $name . ($required ? "" : "?") . ": " . $finalType . ";\n";
        }
        $output .= "  }";
        
        return $output;
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
        foreach ($security as $securityItem) {
            // Handle different security data structures
            if (is_string($securityItem)) {
                // Simple string scheme name
                $formatted[] = [$securityItem => []];
            } elseif (is_array($securityItem) && isset($securityItem['type'])) {
                // Security item with type and args (from extractSecurityInfo)
                $schemeName = $this->mapSecurityTypeToSchemeName($securityItem['type']);
                if ($schemeName) {
                    $formatted[] = [$schemeName => []];
                }
            } elseif (is_array($securityItem)) {
                // Already formatted OpenAPI security requirement
                $formatted[] = $securityItem;
            }
        }
        
        return $formatted;
    }

    /**
     * Map security attribute type to OpenAPI security scheme name
     */
    private function mapSecurityTypeToSchemeName(string $type): ?string
    {
        return match (strtolower($type)) {
            'security', 'isgranted', 'role' => 'bearerAuth',
            'apikey' => 'apiKeyAuth',
            'basic' => 'basicAuth',
            'oauth' => 'oauth2',
            default => 'bearerAuth' // Default fallback
        };
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
        // Use the existing method reflection from route data
        $reflectionMethod = $route['method_reflection'] ?? null;
        if (!$reflectionMethod) {
            return null;
        }
        
        $requestDto = $this->extractRequestDto($reflectionMethod);
        if (!$requestDto) {
            return null;
        }
        
        // Ensure the DTO is processed for schema generation
        $this->generateDtoDocumentation($requestDto);
        
        // Generate component name for the DTO
        $componentName = $this->getSchemaName($requestDto);
        
        $requestBody = [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => "#/components/schemas/{$componentName}"
                    ]
                ]
            ]
        ];
        
        // Try to get description from DTO class docblock
        try {
            $dtoReflection = new \ReflectionClass($requestDto);
            $docComment = $dtoReflection->getDocComment();
            if ($docComment && preg_match('/@description\s+(.+)/', $docComment, $matches)) {
                $requestBody['description'] = trim($matches[1]);
            } else {
                $requestBody['description'] = "Request body for {$route['action']} {$route['path']}";
            }
        } catch (\ReflectionException $e) {
            $requestBody['description'] = "Request body for {$route['action']} {$route['path']}";
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

    /**
     * Extract controller description from class or routes
     */
    private function extractControllerDescription(string $controller, array $routes): string
    {
        try {
            if (class_exists($controller)) {
                $reflection = new \ReflectionClass($controller);
                $docComment = $reflection->getDocComment();
                if ($docComment) {
                    return $this->extractDescriptionFromDocComment($docComment);
                }
            }
        } catch (\Exception $e) {
            // Ignore reflection errors
        }

        // Fallback to controller name processing
        $parts = explode('\\', $controller);
        $className = end($parts);
        return str_replace('Controller', '', $className) . ' endpoints';
    }

    /**
     * Format route for JSON output
     */
    private function formatRouteForJson(array $route): array
    {
        return [
            'path' => $route['path'],
            'methods' => $route['methods'] ?? ['GET'],
            'name' => $route['name'] ?? '',
            'description' => $route['description'] ?? '',
            'summary' => $route['summary'] ?? '',
            'parameters' => $route['parameters'] ?? [],
            'request_body' => $this->formatJsonRequestBody($route),
            'responses' => $this->formatJsonResponses($route),
            'security' => $route['security'] ?? [],
            'deprecated' => $route['deprecated'] ?? false,
            'tags' => $route['tags'] ?? []
        ];
    }

    /**
     * Format request body for JSON output
     */
    private function formatJsonRequestBody(array $route): ?array
    {
        // Use the existing method reflection from route data
        $reflectionMethod = $route['method_reflection'] ?? null;
        if (!$reflectionMethod) {
            return null;
        }
        
        $requestDto = $this->extractRequestDto($reflectionMethod);
        if (!$requestDto) {
            return null;
        }

        // Ensure the DTO is processed for schema generation
        $this->generateDtoDocumentation($requestDto);

        $requestBody = [
            'required' => true,
            'content_type' => 'application/json',
            'schema' => [
                '$ref' => "#/components/schemas/" . $this->getSchemaName($requestDto)
            ]
        ];

        // Try to get description from DTO class docblock
        try {
            $dtoReflection = new \ReflectionClass($requestDto);
            $docComment = $dtoReflection->getDocComment();
            if ($docComment && preg_match('/@description\s+(.+)/', $docComment, $matches)) {
                $requestBody['description'] = trim($matches[1]);
            } else {
                $requestBody['description'] = "Request body for {$route['action']} {$route['path']}";
            }
        } catch (\ReflectionException $e) {
            $requestBody['description'] = "Request body for {$route['action']} {$route['path']}";
        }

        return $requestBody;
    }

    /**
     * Format responses for JSON output
     */
    private function formatJsonResponses(array $route): array
    {
        $responses = [];

        if (!empty($route['responses'])) {
            foreach ($route['responses'] as $code => $response) {
                $responses[$code] = [
                    'description' => $response['description'] ?? 'Success',
                    'content_type' => 'application/json',
                    'schema' => $this->generateJsonSchema($response)
                ];
            }
        } else {
            // Default success response
            $responses['200'] = [
                'description' => 'Success',
                'content_type' => 'application/json',
                'schema' => ['type' => 'object']
            ];
        }

        return $responses;
    }

    /**
     * Generate JSON schema reference for type info
     */
    private function generateJsonSchema(array $typeInfo): array
    {
        if (empty($typeInfo['type'])) {
            return ['type' => 'object'];
        }

        $type = $typeInfo['type'];
        
        // Handle primitive types
        if (in_array($type, ['string', 'integer', 'number', 'boolean', 'array'])) {
            $schema = ['type' => $type];

            if ($type === 'array' && !empty($typeInfo['items'])) {
                $schema['items'] = $this->generateJsonSchema($typeInfo['items']);
            }

            return $schema;
        }

        // Handle DTO types - create reference to schema
        if (str_contains($type, 'App\\DTO\\') && class_exists($type)) {
            $schemaName = $this->getSchemaName($type);
            return ['$ref' => '#/schemas/' . $schemaName];
        }

        // Handle union types
        if (str_contains($type, '|')) {
            $types = array_map('trim', explode('|', $type));
            $schemas = [];

            foreach ($types as $singleType) {
                if ($singleType !== 'null') {
                    $schemas[] = $this->generateJsonSchema(['type' => $singleType]);
                }
            }

            return ['oneOf' => $schemas];
        }

        return ['type' => 'object'];
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

    /**
     * Generate HTML documentation for a single route
     */
    private function generateRouteHtmlDocumentation(array $route): string
    {
        $methodsList = implode(', ', $route['methods']);
        $deprecated = $route['deprecated'] ? ' deprecated' : '';
        
        $html = "<div class='route-section{$deprecated}'>";
        
        // Route header
        $html .= "<div class='route-header'>";
        foreach ($route['methods'] as $method) {
            $html .= "<span class='method " . strtolower($method) . "'>" . htmlspecialchars($method) . "</span> ";
        }
        $html .= "<code>" . htmlspecialchars($route['path']) . "</code>";
        
        if ($route['deprecated']) {
            $html .= " <span class='deprecated-tag'>Deprecated</span>";
        }
        $html .= "</div>";
        
        // Description
        if (!empty($route['description'])) {
            $html .= "<p>" . htmlspecialchars($route['description']) . "</p>";
        }
        
        // Security
        if (!empty($route['security']) && $this->config['include_security']) {
            $html .= "<div class='security'><strong>Security:</strong> ";
            $securityTypes = array_map(function($sec) {
                return htmlspecialchars($sec['type']);
            }, $route['security']);
            $html .= implode(', ', $securityTypes);
            $html .= "</div>";
        }
        
        // Parameters
        if (!empty($route['parameters'])) {
            $html .= "<h4>Parameters</h4><ul>";
            foreach ($route['parameters'] as $param) {
                $html .= "<li><code>" . htmlspecialchars($param['name']) . "</code> ";
                $html .= "(" . htmlspecialchars($param['type']) . ")";
                if (!empty($param['description'])) {
                    $html .= " - " . htmlspecialchars($param['description']);
                }
                $html .= "</li>";
            }
            $html .= "</ul>";
        }
        
        // Request Body
        $requestDto = $this->extractRequestDto($route['method_reflection']);
        if ($requestDto) {
            $html .= "<h4>Request Body</h4>";
            $html .= "<pre><code class='language-typescript'>";
            $html .= htmlspecialchars($this->generateDtoDocumentation($requestDto));
            $html .= "</code></pre>";
            
            if ($this->config['show_request_examples']) {
                $html .= "<h5>Example Request</h5>";
                $html .= "<pre><code class='language-json'>";
                $html .= htmlspecialchars($this->generateRequestExample($requestDto));
                $html .= "</code></pre>";
            }
        }
        
        // Response
        $responseDto = $this->extractResponseDto($route['method_reflection']);
        if ($responseDto) {
            $html .= "<h4>Response</h4>";
            try {
                $reflection = new \ReflectionClass($responseDto);
                $example = $this->generateDtoExample($reflection);
                $html .= "<pre><code class='language-json'>";
                $html .= htmlspecialchars(json_encode($example, JSON_PRETTY_PRINT));
                $html .= "</code></pre>";
            } catch (\Exception $e) {
                $html .= "<pre><code class='language-typescript'>";
                $html .= htmlspecialchars($this->generateDtoDocumentation($responseDto));
                $html .= "</code></pre>";
            }
        }
        
        $html .= "</div>";
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
        $currentServer = $this->getCurrentServer();
        $allServers = $this->getAllServersForOpenApi();
        
        $html .= '<div class="servers"><h3>Available Servers</h3>';
        $html .= '<div><strong>Current Server (based on APP_ENV):</strong> <code>' . htmlspecialchars($currentServer['url']) . '</code> - ' . htmlspecialchars($currentServer['description']) . '</div>';
        
        if (count($allServers) > 1) {
            $html .= '<h4>All Servers:</h4><ul>';
            foreach ($allServers as $server) {
                $isCurrent = $server['url'] === $currentServer['url'] ? ' <em>(current)</em>' : '';
                $html .= '<li><strong>' . htmlspecialchars($server['description'] ?? 'Server') . ':</strong> <code>' . htmlspecialchars($server['url']) . '</code>' . $isCurrent . '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '</div>';
        
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
                $schemas[$schemaName] = $this->generateDtoOpenApiSchema($className);
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

    /**
     * Get the current server configuration based on APP_ENV environment variable
     */
    private function getCurrentServer(): array
    {
        $appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'dev';
        
        // Check if we have environment-based server configuration
        if (isset($this->config['servers']) && is_array($this->config['servers'])) {
            // Check if servers is an object (new format) with environment mapping
            if (isset($this->config['server_environment_mapping'])) {
                $environment = $this->config['server_environment_mapping'][$appEnv] ?? 'dev';
                
                if (isset($this->config['servers'][$environment])) {
                    return $this->config['servers'][$environment];
                }
            }
            
            // If servers is an array (old format), return first server
            if (isset($this->config['servers'][0])) {
                return $this->config['servers'][0];
            }
        }
        
        // Fallback to default development server
        return [
            'url' => 'http://localhost:8000',
            'description' => 'Development server (fallback)'
        ];
    }

    /**
     * Get all available servers in OpenAPI format
     */
    private function getAllServersForOpenApi(): array
    {
        if (isset($this->config['servers']) && is_array($this->config['servers'])) {
            // New format with environment-based servers
            if (isset($this->config['server_environment_mapping'])) {
                return array_values($this->config['servers']);
            }
            
            // Old format - return as is
            return $this->config['servers'];
        }
        
        // Fallback
        return [
            [
                'url' => 'http://localhost:8000',
                'description' => 'Development server'
            ]
        ];
    }

    /**
     * Generate JSON schemas for all DTOs encountered
     */
    private function generateJsonSchemas(): array
    {
        $schemas = [];
        foreach ($this->processedClasses as $className => $documentation) {
            if (str_contains($className, 'DTO')) {
                $schemaName = $this->getSchemaName($className);
                $schemas[$schemaName] = $this->generateDtoJsonSchema($className);
            }
        }
        return $schemas;
    }

    /**
     * Generate JSON schema for a DTO class
     */
    private function generateDtoJsonSchema(string $className): array
    {
        try {
            $reflection = new \ReflectionClass($className);
            $schema = [
                'name' => $this->getSchemaName($className),
                'type' => 'object',
                'properties' => [],
                'required' => []
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
                    'type' => $this->mapPhpTypeToJsonSchema($typeName),
                    'description' => $this->extractParamDescription($reflection, $param->getName())
                ];
                
                // Handle nullable types
                if ($paramType->allowsNull()) {
                    $property['nullable'] = true;
                }
                
                // Add to required array if not optional
                if (!$param->isOptional()) {
                    $schema['required'][] = $param->getName();
                }
                
                $schema['properties'][$param->getName()] = $property;
            }
            
            // Add class description if available
            $docComment = $reflection->getDocComment();
            if ($docComment) {
                $description = $this->extractDescriptionFromDocComment($docComment);
                if ($description) {
                    $schema['description'] = $description;
                }
            }
            
            return $schema;
        } catch (\Exception $e) {
            return [
                'name' => $this->getSchemaName($className),
                'type' => 'object',
                'error' => 'Failed to generate schema: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Map PHP types to JSON Schema types
     */
    private function mapPhpTypeToJsonSchema(string $phpType): string
    {
        return match ($phpType) {
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            'array' => 'array',
            default => 'string'
        };
    }

    /**
     * Extract parameter description from class documentation
     */
    private function extractParamDescription(\ReflectionClass $reflection, string $paramName): string
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return '';
        }
        
        // Look for @param annotations
        if (preg_match('/@param\s+[^\s]+\s+\$' . preg_quote($paramName) . '\s+(.+)$/m', $docComment, $matches)) {
            return trim($matches[1]);
        }
        
        return '';
    }

    /**
     * Extract description from doc comment
     */
    private function extractDescriptionFromDocComment(string $docComment): string
    {
        // Remove /** and */ and clean up
        $cleaned = preg_replace('/^\s*\/\*\*\s*|\s*\*\/\s*$/', '', $docComment);
        $cleaned = preg_replace('/^\s*\*\s?/m', '', $cleaned);
        
        // Get the first paragraph (before first @tag)
        $lines = explode("\n", $cleaned);
        $description = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '@')) {
                break;
            }
            $description[] = $line;
        }
        
        return implode(' ', $description);
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
