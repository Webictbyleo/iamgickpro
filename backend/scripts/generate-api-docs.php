<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Enhanced API Documentation Generator with PHPDoc parsing and recursive type collapsing
 * 
 * This script creates comprehensive API documentation by analyzing:
 * - Route definitions from Symfony
 * - DTO structures and validation constraints
 * - Controller method signatures
 * - PHPDoc comments for property descriptions
 * - Recursive type collapsing for nested DTOs
 * 
 * Usage:
 * - Basic: php generate-api-docs.php
 * - Buffer output: php generate-api-docs.php --buffer
 * - JSON format: php generate-api-docs.php --format=json
 * - Custom output: php generate-api-docs.php --output=/path/to/file.md
 */
class EnhancedApiDocGenerator
{
    private const DEFAULT_OUTPUT_FILE = __DIR__ . '/../../API_DOCUMENTATION.md';
    
    private array $requestSchemas = [];
    private array $responseSchemas = [];
    private array $routes = [];
    private array $routeToSchemaMapping = [];
    private array $collapsedTypes = []; // Cache for collapsed types
    
    // CLI options
    private bool $outputToBuffer = false;
    private string $outputFormat = 'markdown';
    private ?string $outputFile = null;
    
    public function __construct(array $options = [])
    {
        $this->outputToBuffer = $options['buffer'] ?? false;
        $this->outputFormat = $options['format'] ?? 'markdown';
        $this->outputFile = $options['output'] ?? self::DEFAULT_OUTPUT_FILE;
    }
    
    public function generate(): string|null
    {
        echo "🚀 Generating Enhanced API Documentation...\n";
        echo "📄 Output: " . ($this->outputToBuffer ? 'Buffer' : $this->outputFile) . "\n";
        echo "📋 Format: " . $this->outputFormat . "\n";
        
        $this->loadRoutes();
        $this->analyzeSchemas();
        $this->buildRouteMapping();
        $doc = $this->generateDocumentation();
        
        if ($this->outputToBuffer) {
            echo "✅ Enhanced API Documentation generated to buffer!\n";
            return $doc;
        } else {
            file_put_contents($this->outputFile, $doc);
            echo "✅ Enhanced API Documentation generated successfully!\n";
            echo "📄 Output: " . $this->outputFile . "\n";
            return null;
        }
    }
    
    private function loadRoutes(): void
    {
        echo "📋 Loading routes...\n";
        
        $routesJson = shell_exec('cd ' . __DIR__ . '/../ && php bin/console debug:router --format=json');
        $allRoutes = json_decode($routesJson, true);
        
        foreach ($allRoutes as $name => $route) {
            if (str_starts_with($name, 'api_') && str_starts_with($route['path'], '/api/')) {
                $this->routes[$name] = [
                    'name' => $name,
                    'path' => $route['path'],
                    'method' => $this->extractHttpMethods($route),
                    'controller' => $route['defaults']['_controller'] ?? null,
                ];
            }
        }
        
        echo sprintf("Found %d API routes\n", count($this->routes));
    }
    
    private function extractHttpMethods(array $route): array
    {
        if (isset($route['method']) && $route['method'] !== 'ANY') {
            return explode('|', $route['method']);
        }
        
        // Infer method from route name if not specified
        $name = $route['path'];
        if (str_contains($name, 'create') || str_contains($name, 'register') || str_contains($name, 'login')) {
            return ['POST'];
        } elseif (str_contains($name, 'update') || str_contains($name, 'change')) {
            return ['PUT', 'PATCH'];
        } elseif (str_contains($name, 'delete')) {
            return ['DELETE'];
        }
        
        return ['GET'];
    }
    
    private function analyzeSchemas(): void
    {
        echo "🔍 Analyzing schemas...\n";
        
        // Analyze request DTOs
        $requestFiles = glob(__DIR__ . '/../src/DTO/*RequestDTO.php');
        foreach ($requestFiles as $file) {
            $this->analyzeRequestDTO($file);
        }
        
        // Analyze response DTOs
        $responseFiles = glob(__DIR__ . '/../src/DTO/Response/*ResponseDTO.php');
        foreach ($responseFiles as $file) {
            $this->analyzeResponseDTO($file);
        }
        
        echo sprintf("Analyzed %d request and %d response schemas\n", 
            count($this->requestSchemas), count($this->responseSchemas));
    }
    
    private function analyzeRequestDTO(string $file): void
    {
        $className = basename($file, '.php');
        $fullClassName = 'App\\DTO\\' . $className;
        
        if (!class_exists($fullClassName)) {
            return;
        }
        
        try {
            $reflection = new \ReflectionClass($fullClassName);
            $properties = $this->extractDtoPropertiesWithInheritance($reflection);
            
            // Extract validation constraints for each property
            foreach ($properties as &$property) {
                $validation = $this->extractValidationForPropertyReflection($reflection, $property['name']);
                $property['validation'] = $validation;
                
                // Update description if validation provides better context
                $validationDescription = $this->generateValidationBasedDescription($property['name'], $validation);
                if ($validationDescription && !$property['description']) {
                    $property['description'] = $validationDescription;
                }
            }
            
            $this->requestSchemas[$className] = [
                'name' => $className,
                'properties' => $properties,
                'description' => $this->extractClassDescriptionFromReflection($reflection),
            ];
            
        } catch (\ReflectionException $e) {
            // Fallback to old regex-based parsing if reflection fails
            $this->analyzeRequestDTOLegacy($file);
        }
    }
    
    private function analyzeRequestDTOLegacy(string $file): void
    {
        $content = file_get_contents($file);
        $className = basename($file, '.php');
        
        // Extract properties and validation
        preg_match_all('/public readonly (\??\w+(?:\|\w+)*) \$(\w+)(?:\s*=\s*([^;]+))?;/', $content, $matches, PREG_SET_ORDER);
        
        $properties = [];
        foreach ($matches as $match) {
            $type = $this->normalizeType($match[1]);
            $name = $match[2];
            $hasDefault = isset($match[3]);
            $isOptional = str_contains($match[1], '?') || $hasDefault;
            
            // Extract validation constraints
            $validation = $this->extractValidationForProperty($content, $name);
            
            $properties[] = [
                'name' => $name,
                'type' => $type,
                'required' => !$isOptional,
                'validation' => $validation,
                'description' => $this->generateValidationBasedDescription($name, $validation),
            ];
        }
        
        $this->requestSchemas[$className] = [
            'name' => $className,
            'properties' => $properties,
            'description' => $this->extractClassDescription($content),
        ];
    }
    
    private function analyzeResponseDTO(string $file): void
    {
        $className = basename($file, '.php');
        $fqn = "App\\DTO\\Response\\{$className}";
        
        if (!class_exists($fqn)) {
            return;
        }
        
        $reflection = new \ReflectionClass($fqn);
        $properties = $this->extractDtoPropertiesWithInheritance($reflection);
        
        $this->responseSchemas[$className] = [
            'name' => $className,
            'properties' => $properties,
            'description' => $this->extractClassDescriptionFromReflection($reflection),
        ];
    }
    
    /**
     * Extract properties from DTO including inherited properties
     */
    private function extractDtoPropertiesWithInheritance(\ReflectionClass $reflection): array
    {
        $properties = [];
        $processedProperties = [];
        
        // Start with current class and walk up the inheritance chain
        $currentClass = $reflection;
        while ($currentClass !== false) {
            // Get constructor parameters (for property promotion)
            $constructor = $currentClass->getConstructor();
            if ($constructor) {
                foreach ($constructor->getParameters() as $param) {
                    $paramName = $param->getName();
                    
                    // Skip if already processed (child class overrides parent)
                    if (in_array($paramName, $processedProperties)) {
                        continue;
                    }
                    
                    // Check if it's a promoted property
                    $isPromoted = $this->isPromotedProperty($currentClass, $param);
                    
                    if ($isPromoted) {
                        $processedProperties[] = $paramName;
                        
                        $type = $this->getParameterType($param);
                        $collapsedType = $this->collapseType($type);
                        $properties[] = [
                            'name' => $paramName,
                            'type' => $collapsedType,
                            'required' => !$param->isOptional() && !$param->allowsNull(),
                            'description' => $this->extractPropertyDescriptionFromPhpDoc($currentClass, $paramName) 
                                ?: $this->generatePropertyDescription($paramName, $currentClass->getName()),
                        ];
                    }
                }
            }
            
            // Get regular properties (non-promoted)
            foreach ($currentClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                $propName = $property->getName();
                
                // Skip if already processed
                if (in_array($propName, $processedProperties)) {
                    continue;
                }
                
                // Skip promoted properties (already handled above)
                if ($property->isPromoted()) {
                    continue;
                }
                
                $processedProperties[] = $propName;
                
                $type = $this->getPropertyType($property);
                $collapsedType = $this->collapseType($type);
                $properties[] = [
                    'name' => $propName,
                    'type' => $collapsedType,
                    'required' => !$property->getType()?->allowsNull() ?? true,
                    'description' => $this->extractPropertyDescriptionFromPhpDoc($currentClass, $propName)
                        ?: $this->generatePropertyDescription($propName, $currentClass->getName()),
                ];
            }
            
            // Move up the inheritance chain
            $currentClass = $currentClass->getParentClass();
        }
        
        return $properties;
    }
    
    /**
     * Check if a constructor parameter is a promoted property
     */
    private function isPromotedProperty(\ReflectionClass $class, \ReflectionParameter $param): bool
    {
        try {
            $property = $class->getProperty($param->getName());
            return $property->isPromoted();
        } catch (\ReflectionException $e) {
            return false;
        }
    }
    
    /**
     * Get type information from parameter
     */
    private function getParameterType(\ReflectionParameter $param): string
    {
        $type = $param->getType();
        
        if (!$type) {
            return 'any';
        }
        
        if ($type instanceof \ReflectionUnionType) {
            $types = [];
            foreach ($type->getTypes() as $unionType) {
                $typeName = $unionType->getName();
                // For DTO types, return fully qualified name
                if (str_ends_with($typeName, 'DTO') && class_exists($typeName)) {
                    $types[] = $typeName;
                } else {
                    $types[] = $this->convertPhpTypeToTypeScript($typeName);
                }
            }
            return implode(' | ', $types);
        }
        
        $typeName = $type->getName();
        // For DTO types, return fully qualified name as-is
        if (str_ends_with($typeName, 'DTO') && class_exists($typeName)) {
            return $typeName;
        }
        
        return $this->convertPhpTypeToTypeScript($typeName);
    }
    
    /**
     * Get type information from property
     */
    private function getPropertyType(\ReflectionProperty $property): string
    {
        $type = $property->getType();
        
        if (!$type) {
            return 'any';
        }
        
        if ($type instanceof \ReflectionUnionType) {
            $types = [];
            foreach ($type->getTypes() as $unionType) {
                $typeName = $unionType->getName();
                // For DTO types, return fully qualified name
                if (str_ends_with($typeName, 'DTO') && class_exists($typeName)) {
                    $types[] = $typeName;
                } else {
                    $types[] = $this->convertPhpTypeToTypeScript($typeName);
                }
            }
            return implode(' | ', $types);
        }
        
        $typeName = $type->getName();
        // For DTO types, return fully qualified name as-is
        if (str_ends_with($typeName, 'DTO') && class_exists($typeName)) {
            return $typeName;
        }
        
        return $this->convertPhpTypeToTypeScript($typeName);
    }
    
    /**
     * Extract property description from PHPDoc comments
     */
    private function extractPropertyDescriptionFromPhpDoc(\ReflectionClass $class, string $propertyName): ?string
    {
        // Try to get from property docblock
        try {
            $property = $class->getProperty($propertyName);
            $docComment = $property->getDocComment();
            if ($docComment && preg_match('/@var\s+\S+\s+(.+)/', $docComment, $matches)) {
                return trim($matches[1]);
            }
        } catch (\ReflectionException $e) {
            // Property might not exist as a regular property (could be promoted)
        }
        
        // Try to get from constructor parameter docblock
        $constructor = $class->getConstructor();
        if ($constructor) {
            $docComment = $constructor->getDocComment();
            if ($docComment) {
                // Look for @param annotations
                $pattern = '/@param\s+\S+\s+\$' . preg_quote($propertyName, '/') . '\s+(.+)/';
                if (preg_match($pattern, $docComment, $matches)) {
                    return trim($matches[1]);
                }
            }
        }
        
        // Try to get from class docblock for specific property mentions
        $classDoc = $class->getDocComment();
        if ($classDoc) {
            $pattern = '/\*\s+@property\s+\S+\s+\$' . preg_quote($propertyName, '/') . '\s+(.+)/';
            if (preg_match($pattern, $classDoc, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return null;
    }
    
    /**
     * Collapse DTO types recursively to inline their properties
     */
    private function collapseType(string $type): string|array
    {
        // Check if it's a DTO type that should be collapsed
        if (!str_ends_with($type, 'DTO')) {
            return $type;
        }
        
        // Check cache first
        if (isset($this->collapsedTypes[$type])) {
            return $this->collapsedTypes[$type];
        }
        
        // Try to find and analyze the DTO class
        $className = $this->extractClassNameFromType($type);
        $fqn = $this->resolveFullyQualifiedName($className);
        
        if (!$fqn || !class_exists($fqn)) {
            return $type; // Return original type if can't resolve
        }
        
        try {
            $reflection = new \ReflectionClass($fqn);
            $properties = $this->extractDtoPropertiesWithInheritance($reflection);
            
            // Convert to inline object structure
            $collapsedStructure = [];
            foreach ($properties as $prop) {
                $propType = $prop['type'];
                
                // Recursively collapse nested DTOs (prevent infinite recursion)
                if (str_ends_with($propType, 'DTO') && $propType !== $type) {
                    $propType = $this->collapseType($propType);
                }
                
                $collapsedStructure[$prop['name']] = [
                    'type' => $propType,
                    'required' => $prop['required'],
                    'description' => $prop['description']
                ];
            }
            
            // Cache the result
            $this->collapsedTypes[$type] = $collapsedStructure;
            return $collapsedStructure;
            
        } catch (\ReflectionException $e) {
            return $type; // Return original type if reflection fails
        }
    }
    
    /**
     * Extract class name from type string
     */
    private function extractClassNameFromType(string $type): string
    {
        // Handle union types
        if (str_contains($type, '|')) {
            $parts = explode('|', $type);
            foreach ($parts as $part) {
                $part = trim($part);
                if (str_ends_with($part, 'DTO')) {
                    return $part;
                }
            }
        }
        
        return $type;
    }
    
    /**
     * Resolve fully qualified name for DTO classes
     */
    private function resolveFullyQualifiedName(string $className): ?string
    {
        // If already fully qualified, check if it exists
        if (str_contains($className, '\\') && class_exists($className)) {
            return $className;
        }
        
        // Try different namespaces
        $namespaces = [
            'App\\DTO\\Response\\',
            'App\\DTO\\',
            'App\\DTO\\Request\\',
        ];
        
        foreach ($namespaces as $namespace) {
            $fqn = $namespace . $className;
            if (class_exists($fqn)) {
                return $fqn;
            }
        }
        
        return null;
    }
    
    /**
     * Convert PHP type to TypeScript type
     */
    private function convertPhpTypeToTypeScript(string $phpType): string
    {
        return match($phpType) {
            'int' => 'number',
            'float' => 'number',
            'bool' => 'boolean',
            'string' => 'string',
            'array' => 'any[]',
            'object' => 'object',
            'mixed' => 'any',
            'null' => 'null',
            'DateTime', 'DateTimeImmutable', 'DateTimeInterface' => 'string',
            default => str_ends_with($phpType, 'DTO') ? $phpType : 'any'
        };
    }
    
    /**
     * Extract class description from reflection
     */
    private function extractClassDescriptionFromReflection(\ReflectionClass $reflection): string
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return '';
        }
        
        // Extract description from doc comment
        preg_match('/\/\*\*\s*\n\s*\*\s*([^\n]+)/', $docComment, $matches);
        return $matches[1] ?? '';
    }
    
    /**
     * Generate property description based on name and context
     */
    private function generatePropertyDescription(string $propertyName, string $className): string
    {
        $descriptions = [
            'success' => 'Indicates if the request was successful',
            'message' => 'Human-readable message describing the result',
            'timestamp' => 'ISO 8601 timestamp of when the response was generated',
            'token' => 'JWT authentication token',
            'user' => 'User information object',
            'id' => 'Unique identifier',
            'name' => 'Display name',
            'email' => 'Email address',
            'data' => 'Response data payload',
            'items' => 'Array of items',
            'total' => 'Total count of items',
            'page' => 'Current page number',
            'limit' => 'Items per page',
            'totalPages' => 'Total number of pages',
            'design' => 'Design object data',
            'designs' => 'Array of design objects',
            'project' => 'Project object data',
            'projects' => 'Array of project objects',
            'layer' => 'Layer object data',
            'layers' => 'Array of layer objects',
            'media' => 'Media object data',
            'template' => 'Template object data',
            'templates' => 'Array of template objects',
            'job' => 'Export job object data',
            'jobs' => 'Array of export job objects',
            'plugin' => 'Plugin object data',
            'plugins' => 'Array of plugin objects',
            'status' => 'Current status',
            'progress' => 'Progress percentage (0-100)',
            'error' => 'Error message if any',
            'errors' => 'Array of validation errors',
            'code' => 'Error code identifier',
            'details' => 'Additional error details',
        ];
        
        return $descriptions[$propertyName] ?? ucfirst($propertyName) . ' value';
    }
    
    /**
     * Extract validation constraints using reflection (for constructor property promotion)
     */
    private function extractValidationForPropertyReflection(\ReflectionClass $class, string $propertyName): array
    {
        $validation = [];
        
        try {
            // Check constructor parameters for promoted properties
            $constructor = $class->getConstructor();
            if ($constructor) {
                foreach ($constructor->getParameters() as $param) {
                    if ($param->getName() === $propertyName) {
                        $attributes = $param->getAttributes();
                        foreach ($attributes as $attribute) {
                            $attributeName = $attribute->getName();
                            if (str_starts_with($attributeName, 'Symfony\\Component\\Validator\\Constraints\\')) {
                                $constraint = basename($attributeName);
                                $args = $attribute->getArguments();
                                
                                $validation[] = [
                                    'constraint' => $constraint,
                                    'params' => $args,
                                ];
                            }
                        }
                        break;
                    }
                }
            }
            
            // Also check property attributes if it's a regular property
            if ($class->hasProperty($propertyName)) {
                $property = $class->getProperty($propertyName);
                $attributes = $property->getAttributes();
                foreach ($attributes as $attribute) {
                    $attributeName = $attribute->getName();
                    if (str_starts_with($attributeName, 'Symfony\\Component\\Validator\\Constraints\\')) {
                        $constraint = basename($attributeName);
                        $args = $attribute->getArguments();
                        
                        $validation[] = [
                            'constraint' => $constraint,
                            'params' => $args,
                        ];
                    }
                }
            }
            
        } catch (\ReflectionException $e) {
            // If reflection fails, return empty validation
        }
        
        return $validation;
    }
    
    private function extractValidationForProperty(string $content, string $propertyName): array
    {
        $validation = [];
        
        // Extract validation attributes before the property
        $pattern = '/(?:#\[Assert\\\\(\w+)(?:\([^)]*\))?\]\s*)*public readonly[^$]*\$' . $propertyName . '/s';
        
        if (preg_match($pattern, $content, $match)) {
            $attributeText = $match[0];
            
            // Extract individual validation constraints
            preg_match_all('/#\[Assert\\\\(\w+)(?:\(([^)]*)\))?\]/', $attributeText, $attrMatches, PREG_SET_ORDER);
            
            foreach ($attrMatches as $attrMatch) {
                $constraint = $attrMatch[1];
                $params = $attrMatch[2] ?? '';
                
                $validation[] = [
                    'constraint' => $constraint,
                    'params' => $this->parseConstraintParams($params),
                ];
            }
        }
        
        return $validation;
    }
    
    private function parseConstraintParams(string $params): array
    {
        $parsed = [];
        
        if (empty($params)) return $parsed;
        
        // Extract key-value pairs
        preg_match_all('/(\w+):\s*([^,]+)/', $params, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $key = $match[1];
            $value = trim($match[2], ' \'"');
            $parsed[$key] = $value;
        }
        
        return $parsed;
    }
    
    private function buildRouteMapping(): void
    {
        echo "🔗 Building route to schema mapping...\n";
        
        foreach ($this->routes as $routeName => $route) {
            $this->routeToSchemaMapping[$routeName] = [
                'request' => $this->inferRequestSchema($routeName, $route),
                'response' => $this->inferResponseSchema($routeName, $route),
            ];
        }
    }
    
    private function inferRequestSchema(string $routeName, array $route): ?string
    {
        $parts = explode('_', $routeName);
        $resource = $parts[1] ?? '';
        $action = $parts[2] ?? '';
        
        // Specific mappings
        $mappings = [
            'api_auth_register' => 'RegisterRequestDTO',
            'api_auth_login' => 'LoginRequestDTO',
            'api_auth_change_password' => 'ChangePasswordRequestDTO',
            'api_auth_update_profile' => 'UpdateProfileRequestDTO',
        ];
        
        if (isset($mappings[$routeName])) {
            return $mappings[$routeName];
        }
        
        // Generic patterns
        if ($action === 'create') {
            return 'Create' . ucfirst($resource) . 'RequestDTO';
        }
        
        if ($action === 'update') {
            return 'Update' . ucfirst($resource) . 'RequestDTO';
        }
        
        if ($action === 'search') {
            return 'Search' . ucfirst($resource) . 'RequestDTO';
        }
        
        if ($action === 'duplicate') {
            return 'Duplicate' . ucfirst($resource) . 'RequestDTO';
        }
        
        return null;
    }
    
    private function inferResponseSchema(string $routeName, array $route): string
    {
        $parts = explode('_', $routeName);
        $resource = $parts[1] ?? '';
        $action = $parts[2] ?? '';
        
        // Auth endpoints
        if ($resource === 'auth') {
            if (in_array($action, ['register', 'login', 'me'])) {
                return 'AuthResponseDTO';
            }
            return 'SuccessResponseDTO';
        }
        
        // CRUD patterns
        if (in_array($action, ['create', 'show', 'update'])) {
            return ucfirst($resource) . 'ResponseDTO';
        }
        
        if (in_array($action, ['index', 'list', 'search'])) {
            if ($resource === 'templates' && $action === 'search') {
                return 'TemplateSearchResponseDTO';
            }
            return 'PaginatedResponseDTO';
        }
        
        if ($action === 'delete') {
            return 'SuccessResponseDTO';
        }
        
        // Default
        return 'SuccessResponseDTO';
    }
    
    private function generateDocumentation(): string
    {
        echo "📝 Generating documentation...\n";
        
        if ($this->outputFormat === 'json') {
            return $this->generateJsonDocumentation();
        } else {
            return $this->generateMarkdownDocumentation();
        }
    }
    
    private function generateMarkdownDocumentation(): string
    {
        $doc = $this->generateHeader();
        $doc .= $this->generateSchemaSection();
        $doc .= $this->generateEndpointsSection();
        $doc .= $this->generateFooter();
        
        return $doc;
    }
    
    private function generateJsonDocumentation(): string
    {
        $data = [
            'meta' => [
                'title' => 'IamgickPro Design Platform API',
                'version' => '1.0',
                'baseUrl' => 'http://localhost:8000/api',
                'generatedAt' => date('c'),
            ],
            'schemas' => [
                'requests' => $this->requestSchemas,
                'responses' => $this->responseSchemas,
            ],
            'routes' => $this->routes,
        ];
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    private function generateHeader(): string
    {
        $date = date('Y-m-d H:i:s');
        
        return <<<MD
# API Documentation - IamgickPro Design Platform

## Overview

This documentation provides comprehensive information about the IamgickPro Design Platform API endpoints, including routes, HTTP methods, request/response schemas, and authentication requirements.

**Base URL**: `http://localhost:8000/api`  
**Authentication**: JWT Bearer Token (where required)  
**Content-Type**: `application/json`

*Generated on: {$date}*

---

## Table of Contents

1. [Schema Definitions](#schema-definitions)
2. [Authentication](#authentication)
3. [Designs](#designs)
4. [Projects](#projects)
5. [Layers](#layers)
6. [Media](#media)
7. [Export Jobs](#export-jobs)
8. [Templates](#templates)
9. [Plugins](#plugins)
10. [User Management](#user-management)
11. [Error Handling](#error-handling)

---

MD;
    }
    
    private function generateSchemaSection(): string
    {
        $section = "## Schema Definitions\n\n";
        $section .= "The following TypeScript interfaces define the structure of requests and responses used throughout the API.\n\n";
        
        // Request schemas
        if (!empty($this->requestSchemas)) {
            $section .= "### Request Schemas\n\n";
            foreach ($this->requestSchemas as $schema) {
                $section .= $this->generateSchemaDefinition($schema);
            }
        }
        
        // Response schemas
        if (!empty($this->responseSchemas)) {
            $section .= "### Response Schemas\n\n";
            foreach ($this->responseSchemas as $schema) {
                $section .= $this->generateSchemaDefinition($schema);
            }
        }
        
        return $section;
    }
    
    private function generateSchemaDefinition(array $schema): string
    {
        $name = $schema['name'];
        $description = $schema['description'];
        
        $definition = "#### {$name}\n\n";
        
        if ($description) {
            $definition .= "> {$description}\n\n";
        }
        
        $definition .= "```typescript\n";
        $definition .= "interface {$name} {\n";
        
        foreach ($schema['properties'] as $prop) {
            $optional = $prop['required'] ? '' : '?';
            $comment = $prop['description'] ? "  // {$prop['description']}" : '';
            
            // Handle collapsed types
            if (is_array($prop['type'])) {
                $definition .= "  {$prop['name']}{$optional}: {\n";
                $definition .= $this->formatCollapsedType($prop['type'], '    ');
                $definition .= "  };{$comment}\n";
            } else {
                $definition .= "  {$prop['name']}{$optional}: {$prop['type']};{$comment}\n";
            }
        }
        
        $definition .= "}\n```\n\n";
        
        return $definition;
    }
    
    /**
     * Format collapsed type structure for TypeScript interface
     */
    private function formatCollapsedType(array $collapsedType, string $indent = ''): string
    {
        $formatted = '';
        
        foreach ($collapsedType as $propName => $propData) {
            $optional = $propData['required'] ? '' : '?';
            $comment = $propData['description'] ? "  // {$propData['description']}" : '';
            
            if (is_array($propData['type'])) {
                // Nested collapsed type
                $formatted .= "{$indent}{$propName}{$optional}: {\n";
                $formatted .= $this->formatCollapsedType($propData['type'], $indent . '  ');
                $formatted .= "{$indent}};{$comment}\n";
            } else {
                $formatted .= "{$indent}{$propName}{$optional}: {$propData['type']};{$comment}\n";
            }
        }
        
        return $formatted;
    }
    
    private function generateEndpointsSection(): string
    {
        $section = "";
        
        // Group routes by section
        $grouped = $this->groupRoutes();
        
        foreach ($grouped as $sectionName => $routes) {
            $section .= "## " . ucfirst($sectionName) . "\n\n";
            
            foreach ($routes as $route) {
                $section .= $this->generateEndpointDoc($route);
            }
        }
        
        return $section;
    }
    
    private function groupRoutes(): array
    {
        $grouped = [];
        
        foreach ($this->routes as $routeName => $route) {
            $parts = explode('_', $routeName);
            $section = $parts[1] ?? 'other';
            $grouped[$section][] = ['name' => $routeName, 'route' => $route];
        }
        
        return $grouped;
    }
    
    private function generateEndpointDoc(array $routeData): string
    {
        $routeName = $routeData['name'];
        $route = $routeData['route'];
        $mapping = $this->routeToSchemaMapping[$routeName];
        
        $methods = implode(', ', $route['method']);
        $title = $this->generateEndpointTitle($routeName);
        
        $doc = "### {$title}\n\n";
        $doc .= "- **Route**: `{$methods} {$route['path']}`\n";
        $doc .= "- **Authentication**: " . $this->getAuthRequirement($routeName) . "\n";
        $doc .= "- **Description**: " . $this->getEndpointDescription($routeName) . "\n\n";
        
        // Request schema
        if ($mapping['request']) {
            $doc .= "#### Request Schema\n\n";
            $doc .= "See [{$mapping['request']}](#{$this->getAnchor($mapping['request'])})\n\n";
        }
        
        // Response schema
        $doc .= "#### Response Schema\n\n";
        $doc .= "See [{$mapping['response']}](#{$this->getAnchor($mapping['response'])})\n\n";
        
        $doc .= "---\n\n";
        
        return $doc;
    }
    
    private function generateEndpointTitle(string $routeName): string
    {
        $parts = explode('_', $routeName);
        array_shift($parts); // Remove 'api'
        
        return ucwords(implode(' ', $parts));
    }
    
    private function getAuthRequirement(string $routeName): string
    {
        $publicRoutes = ['api_auth_register', 'api_auth_login'];
        return in_array($routeName, $publicRoutes) ? 'None required' : 'Required (JWT Bearer Token)';
    }
    
    private function getEndpointDescription(string $routeName): string
    {
        $descriptions = [
            'api_auth_register' => 'Register a new user account',
            'api_auth_login' => 'Authenticate user and receive JWT token',
            'api_auth_me' => 'Get current authenticated user information',
            'api_auth_logout' => 'Logout user and invalidate token',
            'api_auth_update_profile' => 'Update user profile information',
            'api_auth_change_password' => 'Change user password',
        ];
        
        if (isset($descriptions[$routeName])) {
            return $descriptions[$routeName];
        }
        
        // Generate from route structure
        $parts = explode('_', $routeName);
        $resource = $parts[1] ?? '';
        $action = $parts[2] ?? '';
        
        $actionMap = [
            'index' => 'Get paginated list of',
            'list' => 'Get paginated list of',
            'create' => 'Create a new',
            'show' => 'Get a specific',
            'update' => 'Update an existing',
            'delete' => 'Delete a',
            'search' => 'Search',
            'duplicate' => 'Duplicate a',
        ];
        
        $actionText = $actionMap[$action] ?? $action;
        return "{$actionText} {$resource}";
    }
    
    private function getAnchor(string $schemaName): string
    {
        return strtolower($schemaName);
    }
    
    private function generateFooter(): string
    {
        return <<<MD

## Error Handling

All API endpoints return consistent error responses when something goes wrong.

### Error Response Schema

See [ErrorResponseDTO](#errorresponsedto)

### Common HTTP Status Codes

- **200**: Success
- **201**: Created
- **400**: Bad Request (validation errors)
- **401**: Unauthorized (invalid or missing token)
- **403**: Forbidden (insufficient permissions)
- **404**: Not Found
- **422**: Unprocessable Entity (validation failed)
- **429**: Too Many Requests (rate limited)
- **500**: Internal Server Error

---

## Development Notes

### TypeScript Integration

All schemas are provided as TypeScript interfaces for easy integration with frontend applications.

### Authentication

Include the JWT token in the Authorization header:

```typescript
headers: {
  'Authorization': `Bearer \${token}`,
  'Content-Type': 'application/json'
}
```

---

*This documentation was auto-generated from the backend DTOs and routes. To regenerate, run: `php scripts/generate-api-docs.php`*

MD;
    }
    
    // Helper methods
    
    private function normalizeType(string $type): string
    {
        $typeMap = [
            'int' => 'number',
            'float' => 'number',
            'bool' => 'boolean',
            'string' => 'string',
            'array' => 'any[]',
            'object' => 'object',
        ];
        
        // Handle nullable types
        if (str_starts_with($type, '?')) {
            $baseType = substr($type, 1);
            $mappedType = $typeMap[$baseType] ?? $baseType;
            return $mappedType . ' | null';
        }
        
        return $typeMap[$type] ?? $type;
    }
    
    private function inferTypeFromValue(string $value): string
    {
        if (str_starts_with($value, '$this->')) {
            $property = substr($value, 6);
            if (in_array($property, ['success', 'isPublic', 'visible', 'locked'])) {
                return 'boolean';
            }
            if (in_array($property, ['id', 'width', 'height', 'page', 'limit', 'total'])) {
                return 'number';
            }
            if (str_contains($property, 'At') || $property === 'timestamp') {
                return 'string';  // ISO date string
            }
            return 'string';
        }
        
        if ($value === 'true' || $value === 'false') return 'boolean';
        if (is_numeric($value)) return 'number';
        if (str_contains($value, '->toArray()')) return 'object';
        if (str_contains($value, 'array_map')) return 'any[]';
        
        return 'string';
    }
    
    private function extractClassDescription(string $content): string
    {
        if (preg_match('/\/\*\*\s*\n\s*\*\s*([^@\n]+)/', $content, $match)) {
            return trim($match[1]);
        }
        return '';
    }
    
    private function generateValidationBasedDescription(string $name, array $validation): string
    {
        $baseDescriptions = [
            'email' => 'Email address',
            'password' => 'User password',
            'name' => 'Display name',
            'title' => 'Title',
            'description' => 'Description',
            'type' => 'Type identifier',
            'status' => 'Status value',
            'id' => 'Unique identifier',
            'width' => 'Width in pixels',
            'height' => 'Height in pixels',
            'x' => 'X coordinate',
            'y' => 'Y coordinate',
            'rotation' => 'Rotation angle in degrees',
            'opacity' => 'Opacity value (0-1)',
            'visible' => 'Visibility flag',
            'locked' => 'Lock status',
            'zIndex' => 'Z-index for layering',
            'content' => 'Content data',
            'settings' => 'Configuration settings',
            'metadata' => 'Additional metadata',
        ];
        
        $description = $baseDescriptions[$name] ?? ucfirst($name);
        
        // Add validation constraints to description
        $constraints = [];
        foreach ($validation as $rule) {
            $constraint = $rule['constraint'];
            $params = $rule['params'];
            
            switch ($constraint) {
                case 'NotBlank':
                    $constraints[] = 'required';
                    break;
                case 'Email':
                    $constraints[] = 'valid email format';
                    break;
                case 'Length':
                    if (isset($params['min']) && isset($params['max'])) {
                        $constraints[] = "{$params['min']}-{$params['max']} characters";
                    } elseif (isset($params['min'])) {
                        $constraints[] = "min {$params['min']} characters";
                    } elseif (isset($params['max'])) {
                        $constraints[] = "max {$params['max']} characters";
                    }
                    break;
                case 'Range':
                    if (isset($params['min']) && isset($params['max'])) {
                        $constraints[] = "range {$params['min']}-{$params['max']}";
                    }
                    break;
            }
        }
        
        if (!empty($constraints)) {
            $description .= ' (' . implode(', ', $constraints) . ')';
        }
        
        return $description;
    }
    
    private function generateResponsePropertyDescription(string $name): string
    {
        $descriptions = [
            'success' => 'Indicates if the request was successful',
            'message' => 'Human-readable message',
            'timestamp' => 'ISO 8601 timestamp',
            'token' => 'JWT authentication token',
            'data' => 'Response data',
            'pagination' => 'Pagination information',
            'id' => 'Unique identifier',
            'email' => 'User email address',
            'name' => 'Name',
            'createdAt' => 'Creation timestamp',
            'updatedAt' => 'Last update timestamp',
        ];
        
        return $descriptions[$name] ?? '';
    }
}

// CLI argument parsing
function parseCliArgs(array $argv): array
{
    $options = [];
    
    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        
        if ($arg === '--buffer') {
            $options['buffer'] = true;
        } elseif (str_starts_with($arg, '--format=')) {
            $options['format'] = substr($arg, 9);
        } elseif (str_starts_with($arg, '--output=')) {
            $options['output'] = substr($arg, 9);
        } elseif ($arg === '--help' || $arg === '-h') {
            echo "Enhanced API Documentation Generator\n\n";
            echo "Usage: php generate-api-docs.php [options]\n\n";
            echo "Options:\n";
            echo "  --buffer              Output to buffer instead of file\n";
            echo "  --format=FORMAT       Output format (markdown, json)\n";
            echo "  --output=FILE         Output file path\n";
            echo "  --help, -h            Show this help message\n\n";
            echo "Examples:\n";
            echo "  php generate-api-docs.php\n";
            echo "  php generate-api-docs.php --buffer\n";
            echo "  php generate-api-docs.php --format=json --output=api.json\n";
            exit(0);
        }
    }
    
    return $options;
}

// Run the generator
$options = parseCliArgs($argv);
$generator = new EnhancedApiDocGenerator($options);
$result = $generator->generate();

// If buffering, output the result
if ($options['buffer'] ?? false) {
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "GENERATED DOCUMENTATION:\n";
    echo str_repeat('=', 50) . "\n";
    echo $result;
}
