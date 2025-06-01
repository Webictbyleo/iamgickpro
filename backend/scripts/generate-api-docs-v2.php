<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Types\ContextFactory;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Modern API Documentation Generator for Symfony 7 + PHP 8.4
 * 
 * Handles:
 * - Multi-line validation constraints in promoted constructor parameters
 * - Proper PHPDoc extraction using reflection and token parsing
 * - Union types with automatic expansion
 * - Clean separation between class and property documentation
 */
class ModernApiDocGenerator
{
    private DocBlockFactory $docBlockFactory;
    private ContextFactory $contextFactory;
    private array $processedClasses = [];
    private array $routes = [];

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
    }

    /**
     * Generate comprehensive API documentation
     */
    public function generate(): string
    {
        $this->loadRoutes();
        
        $output = "# API Documentation\n\n";
        $output .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "## Table of Contents\n\n";
        
        // Group routes by controller
        $controllers = $this->groupRoutesByController();
        
        foreach ($controllers as $controller => $routes) {
            $output .= "- [$controller](#" . $this->generateAnchor($controller) . ")\n";
        }
        
        $output .= "\n---\n\n";
        
        // Generate documentation for each controller
        foreach ($controllers as $controller => $routes) {
            $output .= $this->generateControllerDocumentation($controller, $routes);
        }
        
        return $output;
    }

    /**
     * Load routes from Symfony configuration
     */
    private function loadRoutes(): void
    {
        $routesFile = __DIR__ . '/../config/routes.yaml';
        if (!file_exists($routesFile)) {
            throw new \RuntimeException('Routes file not found');
        }

        // Parse routes.yaml or use route collection
        $this->routes = $this->parseRoutesFromDirectory();
    }

    /**
     * Parse routes by scanning controller directory
     */
    private function parseRoutesFromDirectory(): array
    {
        $routes = [];
        $controllerDir = __DIR__ . '/../src/Controller';
        
        if (!is_dir($controllerDir)) {
            return $routes;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($controllerDir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $routes = array_merge($routes, $this->extractRoutesFromController($file->getPathname()));
            }
        }

        return $routes;
    }

    /**
     * Extract routes from controller file using reflection
     */
    private function extractRoutesFromController(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $routes = [];

        // Extract namespace and class name
        if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch) &&
            preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            
            $className = $nsMatch[1] . '\\' . $classMatch[1];
            
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                $routes = array_merge($routes, $this->extractRoutesFromReflection($reflection));
            }
        }

        return $routes;
    }

    /**
     * Extract routes from controller reflection
     */
    private function extractRoutesFromReflection(\ReflectionClass $reflection): array
    {
        $routes = [];
        
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
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
                        'method_reflection' => $method
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
     * Group routes by controller class
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
        
        return $grouped;
    }

    /**
     * Get display name for controller
     */
    private function getControllerDisplayName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Generate documentation for a controller
     */
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
            $output .= $this->generateRouteDocumentation($route);
        }
        
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
     * Generate documentation for a single route
     */
    private function generateRouteDocumentation(array $route): string
    {
        $method = $route['method_reflection'];
        $output = "### " . strtoupper(implode(', ', $route['methods'])) . " " . $route['path'] . "\n\n";
        
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
     * Generate DTO documentation using modern reflection
     */
    private function generateDtoDocumentation(string $className): string
    {
        if (isset($this->processedClasses[$className])) {
            return $this->processedClasses[$className];
        }
        
        try {
            $reflection = new \ReflectionClass($className);
            $output = $this->generateClassDocumentation($reflection);
            $this->processedClasses[$className] = $output;
            return $output;
        } catch (\Exception $e) {
            return "Error processing class $className: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Generate class documentation using modern PHP reflection
     */
    private function generateClassDocumentation(\ReflectionClass $reflection): string
    {
        $output = "**{$reflection->getShortName()}**\n\n";
        
        // Class description
        $classDoc = $this->extractClassDocumentation($reflection);
        if ($classDoc) {
            $output .= "$classDoc\n\n";
        }
        
        // Properties
        $properties = $this->extractClassProperties($reflection);
        
        if (!empty($properties)) {
            $output .= "Properties:\n\n";
            foreach ($properties as $property) {
                $output .= $this->generatePropertyDocumentation($property);
            }
        }
        
        return $output;
    }

    /**
     * Extract class properties using modern reflection
     */
    private function extractClassProperties(\ReflectionClass $reflection): array
    {
        $properties = [];
        
        // Handle promoted constructor parameters
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->isPromoted()) {
                    $properties[] = $this->extractPromotedPropertyInfo($reflection, $param);
                }
            }
        }
        
        // Handle regular properties
        foreach ($reflection->getProperties() as $property) {
            if (!$this->isPromotedProperty($reflection, $property->getName())) {
                $properties[] = $this->extractRegularPropertyInfo($property);
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
    private function extractPromotedPropertyInfo(\ReflectionClass $reflection, \ReflectionParameter $param): array
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
            'validation' => $validation
        ];
    }

    /**
     * Extract regular property information
     */
    private function extractRegularPropertyInfo(\ReflectionProperty $property): array
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
            'validation' => []
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
        
        if (empty($formattedArgs)) {
            return $name;
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
     * Generate property documentation
     */
    private function generatePropertyDocumentation(array $property): string
    {
        $output = "- **{$property['name']}** ({$property['type']})";
        
        if (!$property['required']) {
            $output .= " *optional*";
        }
        
        if (!empty($property['validation'])) {
            $output .= " *" . implode(', ', $property['validation']) . "*";
        }
        
        if (!empty($property['description'])) {
            $output .= "\n  " . str_replace("\n", "\n  ", $property['description']);
        }
        
        // Add union type expansion if applicable
        if ($this->shouldExpandType($property['type'])) {
            $expanded = $this->expandUnionType($property['type']);
            if (!empty($expanded)) {
                $output .= "\n\n  **Union Type Details:**\n\n";
                $output .= "  " . str_replace("\n", "\n  ", $expanded);
            }
        }
        
        $output .= "\n\n";
        
        return $output;
    }

    /**
     * Generate anchor from text
     */
    private function generateAnchor(string $text): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $text));
    }

    /**
     * Check if a type is a union type that should be expanded
     */
    private function shouldExpandType(string $type): bool
    {
        return str_contains($type, '|') && str_contains($type, 'App\\DTO\\');
    }

    /**
     * Expand union types into their component types
     */
    private function expandUnionType(string $type): string
    {
        if (!$this->shouldExpandType($type)) {
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
                $expanded[] = $this->generateClassDocumentation(new \ReflectionClass($singleType));
            }
        }
        
        return implode("\n", $expanded);
    }
}

// Command-line interface
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['buffer', 'format:', 'output:']);
    
    $generator = new ModernApiDocGenerator();
    $documentation = $generator->generate();
    
    if (isset($options['output'])) {
        file_put_contents($options['output'], $documentation);
        echo "Documentation written to: {$options['output']}\n";
    } elseif (isset($options['buffer'])) {
        echo $documentation;
    } else {
        // Write to default location
        $outputFile = __DIR__ . '/../../API_DOCUMENTATION.md';
        file_put_contents($outputFile, $documentation);
        echo "Documentation generated: $outputFile\n";
    }
}
