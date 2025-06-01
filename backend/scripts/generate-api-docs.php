<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\Compound;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * Property information container
 */
class PropertyInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $required,
        public readonly string $description,
        public readonly ?string $phpDocType = null,
        public readonly array $validation = []
    ) {}
}

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
    /**
     * Extracts docblock descriptions for promoted constructor parameters by parsing the source file.
     * Returns an associative array: paramName => description
     */
    private function extractPromotedParamDocblocks(\ReflectionClass $class): array
    {
        $constructor = $class->getConstructor();
        if (!$constructor) return [];
        $file = $constructor->getFileName();
        $startLine = $constructor->getStartLine();
        $endLine = $constructor->getEndLine();
        if (!$file || !is_readable($file)) return [];
        $lines = file($file);
        $constructorLines = array_slice($lines, $startLine-1, $endLine-$startLine+1);
        $joined = implode("\n", $constructorLines);
        // Match all docblock+parameter pairs
        $pattern = '/(\/\*\*.*?\*\/)[^\S\r\n]*[\r\n]+[^\S\r\n]*((?:public|protected|private)?\s*readonly\s*\??[\\\w\[\]]+\s*\$([a-zA-Z0-9_]+))/s';
        preg_match_all($pattern, $joined, $matches, PREG_SET_ORDER);
        $result = [];
        foreach ($matches as $m) {
            $doc = $m[1];
            $paramName = $m[3];
            
            // Store the full docblock for later @var extraction
            $this->promotedParamFullDocblocks[$paramName] = $doc;
            
            // Extract main description (lines before any @ annotation)
            $descriptionLines = [];
            // $doc is $m[1], the full docblock string "/** ... */"
            $docBlockContent = preg_split('/\r?\n/', $doc);
            
            // Iterate from the line after /** to the line before */
            // (Indices 0 and count-1 are /** and */ respectively if they are on their own lines)
            $startIndex = 0;
            if (isset($docBlockContent[0]) && trim($docBlockContent[0]) === '/**') {
                $startIndex = 1;
            }
            $endIndex = count($docBlockContent);
            if (isset($docBlockContent[$endIndex - 1]) && trim($docBlockContent[$endIndex - 1]) === '*/') {
                $endIndex = $endIndex - 1;
            }

            for ($i = $startIndex; $i < $endIndex; $i++) {
                $line = $docBlockContent[$i];
                // Remove leading space and asterisk, then trim
                $cleanedLine = trim(preg_replace('/^\s*\*\s?/', '', $line));

                if (strpos($cleanedLine, '@') === 0) { // Found a tag
                    break;
                }
                $descriptionLines[] = $cleanedLine;
            }

            if (!empty($descriptionLines)) {
                $paragraphs = [];
                $currentParagraph = '';
                foreach ($descriptionLines as $line) {
                    if ($line === '') { // Empty line signifies a paragraph break
                        if (trim($currentParagraph) !== '') {
                            $paragraphs[] = trim($currentParagraph);
                        }
                        $currentParagraph = '';
                    } else {
                        if ($currentParagraph !== '') {
                            $currentParagraph .= ' '; // Add space between lines within a paragraph
                        }
                        $currentParagraph .= $line;
                    }
                }
                if (trim($currentParagraph) !== '') { // Add the last paragraph
                    $paragraphs[] = trim($currentParagraph);
                }
                
                $finalDescription = implode("\n\n", $paragraphs); // Join paragraphs with Markdown paragraph separator
                if (!empty($finalDescription)) {
                    $result[$paramName] = $finalDescription;
                }
            }
        }
        return $result;
    }

    /**
     * Central method to extract comprehensive property information
     * Handles both promoted constructor properties and regular properties
     */
    private function extractPropertyInfo(\ReflectionClass $class, string $propertyName, ?\ReflectionParameter $parameter = null, ?\ReflectionProperty $property = null): PropertyInfo
    {
        // Determine if this is a promoted property or regular property
        $isPromoted = $parameter !== null && $this->isPromotedProperty($class, $parameter);
        
        // Extract type information
        $type = $this->extractTypeInfo($parameter, $property);
        $phpDocType = $this->extractPhpDocType($class, $propertyName, $parameter, $property);
        $finalType = $phpDocType ?: $type ?: 'any';
        
        // Extract description
        $description = $this->extractPropertyDescription($class, $propertyName, $parameter, $property);
        
        // Determine if required
        $required = $this->isPropertyRequired($parameter, $property);
        
        // Extract validation (if available)
        $validation = $this->extractValidationForPropertyReflection($class, $propertyName);
        
        return new PropertyInfo(
            $propertyName,
            $finalType,
            $required,
            $description,
            $phpDocType,
            $validation
        );
    }

    /**
     * Extract type information from parameter or property reflection
     */
    private function extractTypeInfo(?\ReflectionParameter $parameter = null, ?\ReflectionProperty $property = null): string
    {
        $reflectionType = null;
        
        if ($parameter) {
            $reflectionType = $parameter->getType();
        } elseif ($property) {
            $reflectionType = $property->getType();
        }
        
        if (!$reflectionType) {
            return 'any';
        }
        
        if ($reflectionType instanceof \ReflectionUnionType) {
            $types = [];
            foreach ($reflectionType->getTypes() as $unionType) {
                $typeName = $unionType->getName();
                if (str_ends_with($typeName, 'DTO') && class_exists($typeName)) {
                    $types[] = $typeName;
                } elseif ($this->isValueObjectClass($typeName)) {
                    $types[] = $typeName;
                } else {
                    $types[] = $this->convertPhpTypeToTypeScript($typeName);
                }
            }
            return implode(' | ', $types);
        }
        
        $typeName = $reflectionType->getName();
        
        // For DTO types, return fully qualified name as-is
        if (str_ends_with($typeName, 'DTO') && class_exists($typeName)) {
            return $typeName;
        }
        
        // Check if it's a value object
        if ($this->isValueObjectClass($typeName)) {
            return $typeName;
        }
        
        return $this->convertPhpTypeToTypeScript($typeName);
    }

    /**
     * Extract PHPDoc type information with support for arrays and complex types
     */
    /**
     * Extract PHPDoc type using professional docblock parser
     */
    private function extractPhpDocType(\ReflectionClass $class, string $propertyName, ?\ReflectionParameter $parameter = null, ?\ReflectionProperty $property = null): ?string
    {
        try {
            // Create context for the class (for proper type resolution)
            $context = $this->contextFactory->createFromReflector($class);
            
            // First try parameter-specific docblock (for promoted properties)
            if ($parameter) {
                $docComment = $this->getParameterDocComment($class, $parameter);
                if ($docComment) {
                    $docBlock = $this->docBlockFactory->create($docComment, $context);
                    
                    // Look for @var tags
                    $varTags = $docBlock->getTagsByName('var');
                    if (!empty($varTags)) {
                        /** @var Var_ $varTag */
                        $varTag = $varTags[0];
                        $type = $varTag->getType();
                        if ($type) {
                            return $this->convertPhpDocTypeToString($type);
                        }
                    }
                    
                    // Look for @param tags in constructor
                    $constructorDoc = $parameter->getDeclaringFunction()->getDocComment();
                    if ($constructorDoc) {
                        $constructorDocBlock = $this->docBlockFactory->create($constructorDoc, $context);
                        $paramTags = $constructorDocBlock->getTagsByName('param');
                        
                        foreach ($paramTags as $paramTag) {
                            /** @var Param $paramTag */
                            if ($paramTag->getVariableName() === $propertyName) {
                                $type = $paramTag->getType();
                                if ($type) {
                                    return $this->convertPhpDocTypeToString($type);
                                }
                            }
                        }
                    }
                }
            }
            
            // Try property-specific docblock
            if ($property) {
                $docComment = $property->getDocComment();
                if ($docComment) {
                    $docBlock = $this->docBlockFactory->create($docComment, $context);
                    $varTags = $docBlock->getTagsByName('var');
                    if (!empty($varTags)) {
                        /** @var Var_ $varTag */
                        $varTag = $varTags[0];
                        $type = $varTag->getType();
                        if ($type) {
                            return $this->convertPhpDocTypeToString($type);
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            // Fallback to manual parsing if professional parser fails
            echo "Warning: DocBlock parsing failed for {$propertyName}: " . $e->getMessage() . "\n";
        }
        
        return null;
    }
    
    /**
     * Convert PHPDoc type object to string representation
     */
    private function convertPhpDocTypeToString($type): string
    {
        if ($type instanceof Array_) {
            $valueType = $type->getValueType();
            $elementTypeString = $this->convertPhpDocTypeToString($valueType);
            
            // Check if element type is a value object class
            if ($this->isValueObjectClass($elementTypeString)) {
                return $elementTypeString . '[]';
            }
            
            return $this->convertPhpTypeToTypeScript($elementTypeString) . '[]';
        }
        
        if ($type instanceof Nullable) {
            $actualType = $type->getActualType();
            return $this->convertPhpDocTypeToString($actualType);
        }
        
        if ($type instanceof Object_) {
            $fqsen = $type->getFqsen();
            if ($fqsen) {
                $className = (string) $fqsen;
                // Remove leading backslash if present
                $className = ltrim($className, '\\');
                
                // Check if it's a value object class
                if ($this->isValueObjectClass($className)) {
                    return $className;
                }
                
                return $this->convertPhpTypeToTypeScript($className);
            }
        }
        
        if ($type instanceof Compound) {
            // Handle union types like Tag[]|null
            $types = [];
            foreach ($type as $subType) {
                $typeString = $this->convertPhpDocTypeToString($subType);
                if ($typeString !== 'null') { // Skip null from nullable unions
                    $types[] = $typeString;
                }
            }
            
            if (count($types) === 1) {
                return $types[0];
            }
            
            return implode(' | ', $types);
        }
        
        // Default: convert type name to string
        $typeString = (string) $type;
        
        // Check if it's a value object class
        if ($this->isValueObjectClass($typeString)) {
            return $typeString;
        }
        
        return $this->convertPhpTypeToTypeScript($typeString);
    }
    
    /**
     * Get docblock comment for a specific parameter (for promoted properties)
     */
    private function getParameterDocComment(\ReflectionClass $class, \ReflectionParameter $parameter): ?string
    {
        $constructor = $parameter->getDeclaringFunction();
        $file = $constructor->getFileName();
        $paramName = $parameter->getName();
        
        if (!$file || !is_readable($file)) {
            return null;
        }
        
        $lines = file($file);
        $startLine = $constructor->getStartLine();
        $endLine = $constructor->getEndLine();
        
        // Get the content around the constructor, with more context
        $contextStart = max(0, $startLine - 10);
        $contextEnd = min(count($lines), $endLine + 5);
        $contextLines = array_slice($lines, $contextStart, $contextEnd - $contextStart);
        $content = implode('', $contextLines);
        
        // Look for a more specific pattern: docblock followed directly by parameter declaration
        // This pattern looks for /** ... */ followed by optional whitespace/attributes, then the parameter
        $pattern = '/\/\*\*(?:[^*]|\*(?!\/))*\*\/\s*(?:#\[[^\]]*\]\s*)*public\s+[^$]*\$' . preg_quote($paramName, '/') . '/s';
        
        if (preg_match($pattern, $content, $matches)) {
            // Extract just the docblock part
            if (preg_match('/(\/\*\*(?:[^*]|\*(?!\/))*\*\/)/', $matches[0], $docMatches)) {
                return $docMatches[1];
            }
        }
        
        return null;
    }

    /**
     * Extract comprehensive property description using professional docblock parser
     */
    private function extractPropertyDescription(\ReflectionClass $class, string $propertyName, ?\ReflectionParameter $parameter = null, ?\ReflectionProperty $property = null): string
    {
        try {
            $context = $this->contextFactory->createFromReflector($class);
            
            // First try parameter-specific docblock (most detailed for promoted properties)
            if ($parameter) {
                $docComment = $this->getParameterDocComment($class, $parameter);
                if ($docComment) {
                    $docBlock = $this->docBlockFactory->create($docComment, $context);
                    $summary = $docBlock->getSummary();
                    $description = $docBlock->getDescription();
                    
                    if ($summary || $description) {
                        $fullDesc = trim($summary . "\n\n" . $description);
                        if ($fullDesc) {
                            return $this->cleanDescription($fullDesc);
                        }
                    }
                }
            }
            
            // Try property-specific docblock
            if ($property) {
                $docComment = $property->getDocComment();
                if ($docComment) {
                    $docBlock = $this->docBlockFactory->create($docComment, $context);
                    $summary = $docBlock->getSummary();
                    $description = $docBlock->getDescription();
                    
                    if ($summary || $description) {
                        $fullDesc = trim($summary . "\n\n" . $description);
                        if ($fullDesc) {
                            return $this->cleanDescription($fullDesc);
                        }
                    }
                }
            }
            
            // Try constructor parameter docblock
            if ($parameter) {
                $constructorDoc = $parameter->getDeclaringFunction()->getDocComment();
                if ($constructorDoc) {
                    $docBlock = $this->docBlockFactory->create($constructorDoc, $context);
                    $paramTags = $docBlock->getTagsByName('param');
                    
                    foreach ($paramTags as $paramTag) {
                        /** @var Param $paramTag */
                        if ($paramTag->getVariableName() === $propertyName) {
                            $description = $paramTag->getDescription();
                            if ($description) {
                                return (string) $description;
                            }
                        }
                    }
                }
            }
            
            // Try class-level @property annotations
            $classDoc = $class->getDocComment();
            if ($classDoc) {
                $docBlock = $this->docBlockFactory->create($classDoc, $context);
                $propertyTags = $docBlock->getTagsByName('property');
                
                foreach ($propertyTags as $propertyTag) {
                    // Property tags have a different structure, check the tag content
                    $tagContent = (string) $propertyTag;
                    if (strpos($tagContent, '$' . $propertyName) !== false) {
                        // Extract description after the property name
                        $pattern = '/\$' . preg_quote($propertyName, '/') . '\s+(.+)/';
                        if (preg_match($pattern, $tagContent, $matches)) {
                            return trim($matches[1]);
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            // Fallback to manual parsing if professional parser fails
            echo "Warning: DocBlock description parsing failed for {$propertyName}: " . $e->getMessage() . "\n";
        }
        
        // Fallback to manual parsing for promoted properties
        $promotedDocblocks = $this->extractPromotedParamDocblocks($class);
        if (isset($promotedDocblocks[$propertyName])) {
            return $promotedDocblocks[$propertyName];
        }
        
        // Final fallback to generated description
        return $this->generatePropertyDescription($propertyName, $class->getName());
    }

    /**
     * Clean and format description text
     */
    private function cleanDescription(string $description): string
    {
        // Split by lines and clean each line
        $lines = explode("\n", $description);
        $cleanLines = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Skip empty lines, annotation lines, and PHP code lines
            if (empty($trimmed) || 
                str_starts_with($trimmed, '#[') || 
                str_starts_with($trimmed, 'public ') ||
                str_starts_with($trimmed, 'private ') ||
                str_starts_with($trimmed, 'protected ') ||
                str_contains($trimmed, '::class') ||
                str_contains($trimmed, 'readonly') ||
                str_contains($trimmed, '$') || // Skip lines with PHP variables
                preg_match('/\b(string|int|bool|array|object|float)\b/', $trimmed) || // Skip type declarations
                str_contains($trimmed, '<?php') ||
                str_contains($trimmed, '?>')) {
                continue;
            }
            
            // Clean up common docblock artifacts
            $trimmed = preg_replace('/^\* ?/', '', $trimmed); // Remove leading asterisks
            $trimmed = preg_replace('/^\/ ?\*+ ?/', '', $trimmed); // Remove /** patterns
            $trimmed = preg_replace('/ ?\*+ ?\/$/', '', $trimmed); // Remove */ patterns
            $trimmed = trim($trimmed);
            
            if (!empty($trimmed)) {
                $cleanLines[] = $trimmed;
            }
        }
        
        // Join lines and clean up extra whitespace
        $result = implode(' ', $cleanLines);
        $result = preg_replace('/\s+/', ' ', $result); // Replace multiple spaces with single space
        $result = trim($result);
        
        // Remove common PHP code patterns that might have slipped through
        $result = preg_replace('/\bpublic readonly\b.*?$/', '', $result);
        $result = preg_replace('/\$\w+/', '', $result); // Remove variable references
        $result = preg_replace('/\b[A-Z][a-zA-Z]*DTO\b/', '', $result); // Remove DTO class names
        
        // Remove duplicate sentences/phrases (common when docblock content gets duplicated)
        $sentences = preg_split('/[.!?]+/', $result);
        $uniqueSentences = [];
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (!empty($sentence) && !in_array($sentence, $uniqueSentences)) {
                $uniqueSentences[] = $sentence;
            }
        }
        $result = implode('. ', $uniqueSentences);
        if (!empty($result) && !str_ends_with($result, '.')) {
            $result .= '.';
        }
        
        // Truncate if too long (for inline comments)
        if (strlen($result) > 200) {
            $result = substr($result, 0, 197) . '...';
        }
        
        return $result;
    }

    /**
     * Parse description from a DocBlock comment
     */
    private function parseDocBlockDescription(string $docComment): ?string
    {
        $lines = preg_split('/\r?\n/', $docComment);
        $descriptionLines = [];
        $inDescription = false;
        
        foreach ($lines as $line) {
            $cleanedLine = trim(preg_replace('/^\s*\*\s?/', '', $line));
            
            if (strpos($cleanedLine, '@') === 0) {
                break; // Stop at first annotation
            }
            
            if ($cleanedLine === '' && !$inDescription) {
                continue; // Skip empty lines before description starts
            }
            
            if ($cleanedLine !== '' && !$inDescription) {
                $inDescription = true;
            }
            
            if ($inDescription) {
                $descriptionLines[] = $cleanedLine;
            }
        }
        
        if (empty($descriptionLines)) {
            return null;
        }
        
        // Format description with proper paragraph breaks
        $paragraphs = [];
        $currentParagraph = '';
        
        foreach ($descriptionLines as $line) {
            if ($line === '') {
                if (trim($currentParagraph) !== '') {
                    $paragraphs[] = trim($currentParagraph);
                }
                $currentParagraph = '';
            } else {
                if ($currentParagraph !== '') {
                    $currentParagraph .= ' ';
                }
                $currentParagraph .= $line;
            }
        }
        
        if (trim($currentParagraph) !== '') {
            $paragraphs[] = trim($currentParagraph);
        }
        
        return implode("\n\n", $paragraphs);
    }

    /**
     * Determine if a property is required
     */
    private function isPropertyRequired(?\ReflectionParameter $parameter = null, ?\ReflectionProperty $property = null): bool
    {
        if ($parameter) {
            return !$parameter->isOptional() && !$parameter->allowsNull();
        }
        
        if ($property) {
            $type = $property->getType();
            return $type && !$type->allowsNull();
        }
        
        return true; // Default to required
    }

    private const DEFAULT_OUTPUT_FILE = __DIR__ . '/../../API_DOCUMENTATION.md';
    
    private array $requestSchemas = [];
    private array $responseSchemas = [];
    private array $routes = [];
    private array $routeToSchemaMapping = [];
    private array $collapsedTypes = []; // Cache for collapsed types
    private array $promotedParamFullDocblocks = []; // Cache for full docblocks of promoted parameters
    
    // Professional docblock and property analysis tools
    private DocBlockFactory $docBlockFactory;
    private ContextFactory $contextFactory;
    private PropertyInfoExtractor $propertyInfo;
    
    // CLI options
    private bool $outputToBuffer = false;
    private string $outputFormat = 'markdown';
    private ?string $outputFile = null;
    
    public function __construct(array $options = [])
    {
        $this->outputToBuffer = $options['buffer'] ?? false;
        $this->outputFormat = $options['format'] ?? 'markdown';
        $this->outputFile = $options['output'] ?? self::DEFAULT_OUTPUT_FILE;
        
        // Initialize professional docblock parsing tools
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
        $this->propertyInfo = new PropertyInfoExtractor(
            [new ReflectionExtractor()]
        );
    }
    
    public function generate(): string|null
    {
        echo "🚀 Generating Enhanced API Documentation with Professional Docblock Parsing...\n";
        echo "📄 Output: " . ($this->outputToBuffer ? 'Buffer' : $this->outputFile) . "\n";
        echo "📋 Format: " . $this->outputFormat . "\n";
        
        try {
            $this->loadRoutes();
            $this->analyzeSchemas();
            $this->buildRouteMapping();
            $doc = $this->generateDocumentation();
        } catch (\Exception $e) {
            echo "❌ Error during generation: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            throw $e;
        }
        
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
     * Extract properties from DTO including inherited properties using the centralized approach
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
                        $propertyInfo = $this->extractPropertyInfo($currentClass, $paramName, $param, null);
                        $collapsedType = $this->collapseType($propertyInfo->type);
                        
                        $properties[] = [
                            'name' => $propertyInfo->name,
                            'type' => $collapsedType,
                            'required' => $propertyInfo->required,
                            'description' => $propertyInfo->description,
                            'validation' => $propertyInfo->validation,
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
                $propertyInfo = $this->extractPropertyInfo($currentClass, $propName, null, $property);
                $collapsedType = $this->collapseType($propertyInfo->type);
                
                $properties[] = [
                    'name' => $propertyInfo->name,
                    'type' => $collapsedType,
                    'required' => $propertyInfo->required,
                    'description' => $propertyInfo->description,
                    'validation' => $propertyInfo->validation,
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
    /**
     * Check if a constructor parameter is a promoted property
     */
    /**
     * Check if a type is a value object class
     */
    private function isValueObjectClass(string $type): bool
    {
        // Check if it's one of our known value object types
        $valueObjectTypes = [
            'Tag', 'Transform', 'LayerProperties', 'UserSettings', 
            'ProjectSettings', 'MediaMetadata', 'LayerUpdate', 'DesignData',
            'TextLayerProperties', 'ImageLayerProperties', 'ShapeLayerProperties'
        ];
        
        if (in_array($type, $valueObjectTypes)) {
            return true;
        }
        
        // Check if it's a fully qualified value object class
        if (str_starts_with($type, 'App\\DTO\\ValueObject\\')) {
            return class_exists($type);
        }
        
        // Check if it exists in the ValueObject namespace
        $fqn = 'App\\DTO\\ValueObject\\' . $type;
        return class_exists($fqn);
    }
    
    /**
     * Extract property description from PHPDoc comments
     */
    /**
     * Recursively collapse DTO and Value Object types to inline their properties
     * Handles arrays, nullable types, and prevents infinite recursion
     */
    private function collapseType(string $type, array $visited = []): string|array
    {
        // Handle array types
        if (str_ends_with($type, '[]')) {
            $elementType = substr($type, 0, -2);
            $collapsedElement = $this->collapseType($elementType, $visited);
            
            // For arrays, always preserve the array syntax but use the collapsed element type
            if (is_array($collapsedElement)) {
                // If the element type was collapsed to an object structure, 
                // we want to represent it as an array of that structure
                $shortName = $this->extractClassNameFromType($elementType);
                return $shortName . '[]';
            } else {
                return $collapsedElement . '[]';
            }
        }
        
        // Handle union types
        if (str_contains($type, '|')) {
            $types = array_map('trim', explode('|', $type));
            $collapsedTypes = [];
            $hasValueObjects = false;
            
            foreach ($types as $unionType) {
                if ($unionType === 'null') {
                    continue; // Skip null in union types for now
                }
                
                // Check if this is a value object that should be expanded, not collapsed
                if ($this->isValueObjectClass($unionType)) {
                    $hasValueObjects = true;
                    $collapsedTypes[] = $unionType; // Keep the type name for expansion later
                } else {
                    $collapsed = $this->collapseType($unionType, $visited);
                    $collapsedTypes[] = is_array($collapsed) ? $collapsed : $unionType;
                }
            }
            
            // If all types in the union are value objects, preserve as union for expansion
            if ($hasValueObjects && count($collapsedTypes) > 1) {
                return implode(' | ', $collapsedTypes);
            }
            
            return count($collapsedTypes) === 1 ? $collapsedTypes[0] : $collapsedTypes;
        }
        
        // Check if it's a collapsible type (DTO or value object)
        if (!$this->isCollapsibleType($type)) {
            return $type;
        }
        
        // Prevent infinite recursion
        if (in_array($type, $visited)) {
            return $type; // Return type name to break recursion
        }
        
        // Check cache first
        if (isset($this->collapsedTypes[$type])) {
            return $this->collapsedTypes[$type];
        }
        
        $visited[] = $type;
        
        // Try to find and analyze the class
        $className = $this->extractClassNameFromType($type);
        $fqn = $this->resolveClassFullyQualifiedName($className);
        
        if (!$fqn || !class_exists($fqn)) {
            return $type; // Return original type if can't resolve
        }
        
        try {
            $reflection = new \ReflectionClass($fqn);
            
            // Handle abstract classes specially
            if ($reflection->isAbstract()) {
                // For abstract classes, return a generic object type or union of implementations
                if ($className === 'LayerProperties') {
                    // Return union type indicating it could be any layer properties type
                    return 'TextLayerProperties | ImageLayerProperties | ShapeLayerProperties';
                }
                // For other abstract classes, return a generic object
                return 'object';
            }
            
            $properties = $this->extractDtoPropertiesForCollapse($reflection, $visited);
            
            // Convert to inline object structure
            $collapsedStructure = [];
            foreach ($properties as $prop) {
                $collapsedStructure[$prop['name']] = [
                    'type' => $prop['type'],
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
     * Check if a type can be collapsed (DTO or Value Object)
     */
    private function isCollapsibleType(string $type): bool
    {
        return str_ends_with($type, 'DTO') || $this->isValueObjectClass($type);
    }

    /**
     * Extract properties specifically for type collapsing (to avoid infinite loops)
     */
    private function extractDtoPropertiesForCollapse(\ReflectionClass $reflection, array $visited = []): array
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
                    // Skip if already processed
                    if (in_array($paramName, $processedProperties)) {
                        continue;
                    }
                    // Check if it's a promoted property
                    $isPromoted = $this->isPromotedProperty($currentClass, $param);
                    if ($isPromoted) {
                        $processedProperties[] = $paramName;
                        $propertyInfo = $this->extractPropertyInfo($currentClass, $paramName, $param, null);
                        
                        // Recursively collapse the type, but only if not already visited
                        $collapsedType = $this->collapseType($propertyInfo->type, $visited);
                        
                        $properties[] = [
                            'name' => $propertyInfo->name,
                            'type' => $collapsedType,
                            'required' => $propertyInfo->required,
                            'description' => $propertyInfo->description,
                        ];
                    }
                }
            }
            
            // Get regular properties (non-promoted)
            foreach ($currentClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                $propName = $property->getName();
                
                // Skip if already processed or promoted
                if (in_array($propName, $processedProperties) || $property->isPromoted()) {
                    continue;
                }
                
                $processedProperties[] = $propName;
                $propertyInfo = $this->extractPropertyInfo($currentClass, $propName, null, $property);
                
                // Recursively collapse the type
                $collapsedType = $this->collapseType($propertyInfo->type, $visited);
                
                $properties[] = [
                    'name' => $propertyInfo->name,
                    'type' => $collapsedType,
                    'required' => $propertyInfo->required,
                    'description' => $propertyInfo->description,
                ];
            }
            
            // Move up the inheritance chain
            $currentClass = $currentClass->getParentClass();
        }
        
        return $properties;
    }

    /**
     * Resolve fully qualified class name for both DTOs and Value Objects
     */
    private function resolveClassFullyQualifiedName(string $className): ?string
    {
        // If already fully qualified, check if it exists
        if (str_contains($className, '\\') && class_exists($className)) {
            return $className;
        }
        
        // Handle value objects
        if ($this->isValueObjectClass($className)) {
            return $this->resolveValueObjectClassName($className);
        }
        
        // Try different DTO namespaces
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
            // Check for value object types
            default => $this->isValueObjectClass($phpType) ? $phpType :
                (str_ends_with($phpType, 'DTO') ? $phpType : 'any')
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
            // Add safety check for missing keys
            if (!isset($prop['required']) || !isset($prop['description']) || !isset($prop['type'])) {
                echo "WARNING: Property missing required keys in {$name}:\n";
                echo "  Property: " . print_r($prop, true) . "\n";
                // Use defaults for missing values
                $prop['required'] = $prop['required'] ?? false;
                $prop['description'] = $prop['description'] ?? 'No description';
                $prop['type'] = $prop['type'] ?? 'any';
            }
            
            $optional = $prop['required'] ? '' : '?';
            $comment = $prop['description'] ? "  // {$prop['description']}" : '';
            
            // Handle collapsed types
            if (is_array($prop['type'])) {
                $definition .= "  {$prop['name']}{$optional}: {\n";
                $definition .= $this->formatCollapsedType($prop['type'], '    ');
                $definition .= "  };{$comment}\n";
            } else {
                if ($prop['type'] === null) {
                    echo "WARNING: Property {$prop['name']} in {$name} has null type, using 'any'\n";
                    $expandedType = 'any';
                } else {
                    $expandedType = $this->expandValueObjectType($prop['type']);
                }
                $definition .= "  {$prop['name']}{$optional}: {$expandedType};{$comment}\n";
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
            // Add safety checks for array keys
            if (!is_array($propData)) {
                continue;
            }
            
            $required = $propData['required'] ?? false;
            $description = $propData['description'] ?? '';
            $type = $propData['type'] ?? 'any';
            
            $optional = $required ? '' : '?';
            $comment = $description ? "  // {$description}" : '';
            
            if (is_array($type)) {
                // Nested collapsed type
                $formatted .= "{$indent}{$propName}{$optional}: {\n";
                $formatted .= $this->formatCollapsedType($type, $indent . '  ');
                $formatted .= "{$indent}};{$comment}\n";
            } else {
                $typeDefinition = $this->expandValueObjectType($type);
                $formatted .= "{$indent}{$propName}{$optional}: {$typeDefinition};{$comment}\n";
            }
        }
        
        return $formatted;
    }

    /**
     * Expand value object types to their TypeScript interface definitions
     */
    private function expandValueObjectType(?string $type): string
    {
        // Handle null or empty types
        if (empty($type)) {
            return 'any';
        }
        
        // Debug output for union types
        if (str_contains($type, '|')) {
            // Handle union types like TextLayerProperties|ImageLayerProperties|ShapeLayerProperties
            $unionTypes = array_map('trim', explode('|', $type));
            $expandedTypes = [];
            
            foreach ($unionTypes as $unionType) {
                if ($unionType === 'null') {
                    continue; // Skip null in union types for TypeScript
                }
                $expandedTypes[] = $this->expandValueObjectType($unionType);
            }
            
            if (count($expandedTypes) === 1) {
                return $expandedTypes[0];
            }
            
            return implode(' | ', $expandedTypes);
        }
        
        // Handle array types like Tag[]
        if (preg_match('/^(.+)\[\]$/', $type, $matches)) {
            $elementType = $matches[1];
            $expandedElementType = $this->expandValueObjectType($elementType);
            return $expandedElementType . '[]';
        }
        
        // Check if it's a value object
        if ($this->isValueObjectClass($type)) {
            return $this->generateValueObjectInterface($type);
        }
        
        return $type;
    }

    /**
     * Generate TypeScript interface for a value object
     */
    private function generateValueObjectInterface(string $valueObjectType): string
    {
        // Try to resolve the full class name
        $className = $this->resolveValueObjectClassName($valueObjectType);
        if (!$className || !class_exists($className)) {
            return $valueObjectType; // Return original if can't resolve
        }
        
        try {
            $reflection = new \ReflectionClass($className);
            $properties = $this->extractDtoPropertiesWithInheritance($reflection);
            
            if (empty($properties)) {
                return $valueObjectType;
            }
            
            // Generate inline interface
            $interface = "{\n";
            foreach ($properties as $prop) {
                $optional = $prop['required'] ? '' : '?';
                
                // Handle case where prop type might be an array (collapsed type)
                if (is_array($prop['type'])) {
                    $propType = "{\n" . $this->formatCollapsedType($prop['type'], '    ') . "  }";
                } else {
                    $propType = $this->expandValueObjectType($prop['type']);
                }
                
                $comment = $prop['description'] ? " // {$prop['description']}" : '';
                $interface .= "    {$prop['name']}{$optional}: {$propType};{$comment}\n";
            }
            $interface .= "  }";
            
            return $interface;
        } catch (\Exception $e) {
            return $valueObjectType; // Return original if analysis fails
        }
    }

    /**
     * Resolve the full class name for a value object
     */
    private function resolveValueObjectClassName(string $type): ?string
    {
        // If already fully qualified, return as-is
        if (str_starts_with($type, 'App\\DTO\\ValueObject\\') && class_exists($type)) {
            return $type;
        }
        
        // Try to resolve short name
        $possibleClasses = [
            'App\\DTO\\ValueObject\\' . $type,
            $type
        ];
        
        foreach ($possibleClasses as $className) {
            if (class_exists($className)) {
                return $className;
            }
        }
        
        return null;
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
