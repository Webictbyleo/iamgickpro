<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ReflectionClass;
use ReflectionParameter;
use App\Attribute\QueryParam;
use App\Attribute\RequestBody;

/**
 * Argument resolver for automatically deserializing and validating request DTOs
 */
class RequestDTOResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        
        if (!$type || !class_exists($type)) {
            return [];
        }
        
        // Check if the class has a RequestDTO suffix or implements a marker interface
        if (!str_ends_with($type, 'RequestDTO') && !str_ends_with($type, 'DTO')) {
            return [];
        }

        // Handle multipart/form-data requests generically
        if (str_starts_with($request->headers->get('Content-Type', ''), 'multipart/form-data')) {
            yield from $this->resolveMultipartFormData($request, $type);
            return;
        }

        // Handle GET requests with query parameters
        if ($request->getMethod() === 'GET') {
            yield from $this->resolveQueryParameters($request, $type);
            return;
        }

        // Check if we need to handle mixed query + body parameters
        $content = $request->getContent();
        $hasMixedParams = $this->hasMixedParameterTypes($type);
        
        if ($hasMixedParams) {
            yield from $this->resolveMixedParameters($request, $type, $content);
            return;
        }
        
        if (empty($content)) {
            // For non-GET methods without body content, try to resolve from query parameters
            yield from $this->resolveQueryParameters($request, $type);
            return;
        }

        try {
            // Enhanced deserialization with union type best-match resolution
            $dto = $this->deserializeWithBestMatch($content, $type, $request);
            
            // Validate the DTO
            $violations = $this->validator->validate($dto);
            
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
                }
                throw new BadRequestHttpException('Validation failed: ' . implode(', ', $errors));
            }
            
            yield $dto;
            
        } catch (\Symfony\Component\Serializer\Exception\NotEncodableValueException $e) {
            throw new BadRequestHttpException('Invalid JSON format');
        } catch (\Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException $e) {
            throw new BadRequestHttpException('Missing required fields: ' . $e->getMessage());
        } catch (\Symfony\Component\Serializer\Exception\NotNormalizableValueException $e) {
            throw new BadRequestHttpException('Invalid field type: ' . $e->getMessage());
        } catch (\Symfony\Component\Serializer\Exception\ExceptionInterface $e) {
            throw new BadRequestHttpException('Request validation failed: ' . $e->getMessage());
        } catch(\Exception $e){
            throw new BadRequestHttpException('An error occurred while processing the request: ' . $e->getMessage());
        }
    }

    private function resolveMultipartFormData(Request $request, string $type): iterable
    {
        try {
            $reflectionClass = new ReflectionClass($type);
            $constructor = $reflectionClass->getConstructor();
            
            if (!$constructor) {
                throw new BadRequestHttpException("DTO {$type} must have a constructor");
            }
            
            $parameters = $constructor->getParameters();
            $constructorArgs = [];
            
            foreach ($parameters as $parameter) {
                $paramName = $parameter->getName();
                $paramType = $parameter->getType();
                
                // Handle file uploads
                if ($paramType && $paramType->getName() === 'Symfony\Component\HttpFoundation\File\UploadedFile') {
                    $constructorArgs[$paramName] = $request->files->get($paramName);
                } else {
                    // Handle regular form fields
                    $constructorArgs[$paramName] = $request->request->get($paramName);
                }
            }
            
            // Create the DTO using named arguments
            $dto = $reflectionClass->newInstance(...$constructorArgs);
            
            // Validate the DTO
            $violations = $this->validator->validate($dto);
            
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
                }
                throw new BadRequestHttpException('Validation failed: ' . implode(', ', $errors));
            }
            
            yield $dto;
            
        } catch (\ReflectionException $e) {
            throw new BadRequestHttpException("Failed to resolve DTO {$type}: " . $e->getMessage());
        }
    }

    private function resolveQueryParameters(Request $request, string $type): iterable
    {
        try {
            $reflectionClass = new ReflectionClass($type);
            $constructor = $reflectionClass->getConstructor();
            
            if (!$constructor) {
                throw new BadRequestHttpException("DTO {$type} must have a constructor");
            }
            
            $parameters = $constructor->getParameters();
            $constructorArgs = [];
            
            foreach ($parameters as $parameter) {
                $paramName = $parameter->getName();
                $paramType = $parameter->getType();
                
                // Check if parameter has QueryParam attribute
                $queryParamAttributes = $parameter->getAttributes(QueryParam::class);
                $queryParam = null;
                if (!empty($queryParamAttributes)) {
                    $queryParam = $queryParamAttributes[0]->newInstance();
                }
                
                // Determine the query parameter name
                $queryName = $queryParam?->name ?? $paramName;
                
                // Get value from query parameters
                $value = $request->query->get($queryName);
                
                // Handle array parameters (e.g., tags[]=a&tags[]=b or tags=a,b)
                if ($value === null && $request->query->has($queryName)) {
                    // Check for array notation
                    $arrayValues = $request->query->all($queryName);
                    if (is_array($arrayValues) && !empty($arrayValues)) {
                        $value = $arrayValues;
                    }
                }
                
                // Handle type conversion and defaults
                if ($value === null) {
                    if ($queryParam?->required) {
                        throw new BadRequestHttpException("Required query parameter '{$queryName}' is missing");
                    }
                    
                    // Use default value if specified, otherwise use parameter default
                    if ($queryParam?->default !== null) {
                        $value = $queryParam->default;
                    } elseif ($parameter->isDefaultValueAvailable()) {
                        $value = $parameter->getDefaultValue();
                    } else {
                        $value = null;
                    }
                } else {
                    // Convert string values to appropriate types
                    $value = $this->convertQueryParameterValue($value, $paramType);
                }
                
                $constructorArgs[$paramName] = $value;
            }
            
            // Create the DTO using named arguments
            $dto = $reflectionClass->newInstance(...$constructorArgs);
            
            // Validate the DTO
            $violations = $this->validator->validate($dto);
            
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
                }
                throw new BadRequestHttpException('Validation failed: ' . implode(', ', $errors));
            }
            
            yield $dto;
            
        } catch (\ReflectionException $e) {
            throw new BadRequestHttpException("Failed to resolve DTO {$type}: " . $e->getMessage());
        }
    }

    private function convertQueryParameterValue(mixed $value, ?\ReflectionType $type): mixed
    {
        if ($type === null) {
            return $value;
        }
        
        // Handle union types (PHP 8.0+)
        if ($type instanceof \ReflectionUnionType) {
            // Try to convert to the first compatible type
            foreach ($type->getTypes() as $unionType) {
                if ($unionType instanceof \ReflectionNamedType) {
                    try {
                        return $this->convertSingleType($value, $unionType);
                    } catch (\Throwable) {
                        continue; // Try next type in union
                    }
                }
            }
            return $value; // Fallback if no conversion worked
        }
        
        // Handle named types
        if ($type instanceof \ReflectionNamedType) {
            return $this->convertSingleType($value, $type);
        }
        
        return $value;
    }
    
    private function convertSingleType(mixed $value, \ReflectionNamedType $type): mixed
    {
        $typeName = $type->getName();
        
        // Handle nullable types
        if ($type->allowsNull() && ($value === null || $value === '')) {
            return null;
        }
        
        // Handle array type (for query parameters like tags[]=a&tags[]=b)
        if ($typeName === 'array') {
            if (is_array($value)) {
                return $value;
            }
            // Convert single value to array
            return $value !== null ? [$value] : [];
        }
        
        // Convert based on type
        return match ($typeName) {
            'int' => $this->convertToInt($value),
            'float' => $this->convertToFloat($value),
            'bool' => $this->convertToBool($value),
            'string' => (string) $value,
            \DateTime::class, \DateTimeImmutable::class => $this->convertToDateTime($value, $typeName),
            default => $this->convertEnumOrCustomType($value, $typeName)
        };
    }
    
    private function convertToInt(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }
        throw new BadRequestHttpException("Invalid integer value: {$value}");
    }
    
    private function convertToFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        throw new BadRequestHttpException("Invalid float value: {$value}");
    }
    
    private function convertToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        // Handle common boolean representations
        $normalized = strtolower((string) $value);
        return match ($normalized) {
            'true', '1', 'yes', 'on' => true,
            'false', '0', 'no', 'off', '' => false,
            default => (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? 
                      throw new BadRequestHttpException("Invalid boolean value: {$value}")
        };
    }
    
    private function convertToDateTime(mixed $value, string $className): \DateTime|\DateTimeImmutable
    {
        if (empty($value)) {
            throw new BadRequestHttpException("Empty datetime value");
        }
        
        try {
            return $className === \DateTimeImmutable::class 
                ? new \DateTimeImmutable($value)
                : new \DateTime($value);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException("Invalid datetime format: {$value}");
        }
    }
    
    private function convertEnumOrCustomType(mixed $value, string $typeName): mixed
    {
        // Handle enums (PHP 8.1+)
        if (enum_exists($typeName)) {
            // Try to create enum from value
            if (method_exists($typeName, 'tryFrom')) {
                $enum = $typeName::tryFrom($value);
                if ($enum === null) {
                    throw new BadRequestHttpException("Invalid enum value '{$value}' for {$typeName}");
                }
                return $enum;
            }
        }
        
        // For other custom types, return as-is and let validation handle it
        return $value;
    }

    private function hasMixedParameterTypes(string $type): bool
    {
        try {
            $reflectionClass = new ReflectionClass($type);
            $constructor = $reflectionClass->getConstructor();
            
            if (!$constructor) {
                return false;
            }
            
            $hasQueryParams = false;
            $hasBodyParams = false;
            
            foreach ($constructor->getParameters() as $parameter) {
                $queryParamAttributes = $parameter->getAttributes(QueryParam::class);
                $bodyParamAttributes = $parameter->getAttributes(RequestBody::class);
                
                if (!empty($queryParamAttributes)) {
                    $hasQueryParams = true;
                } elseif (!empty($bodyParamAttributes)) {
                    $hasBodyParams = true;
                } else {
                    // If no attribute specified, assume it's a body param for non-GET methods
                    $hasBodyParams = true;
                }
            }
            
            return $hasQueryParams && $hasBodyParams;
        } catch (\ReflectionException) {
            return false;
        }
    }
    
    private function resolveMixedParameters(Request $request, string $type, string $content): iterable
    {
        try {
            $reflectionClass = new ReflectionClass($type);
            $constructor = $reflectionClass->getConstructor();
            
            if (!$constructor) {
                throw new BadRequestHttpException("DTO {$type} must have a constructor");
            }
            
            // Parse body content if available
            $bodyData = [];
            if (!empty($content)) {
                try {
                    $bodyData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new BadRequestHttpException('Invalid JSON format');
                }
            }
            
            $parameters = $constructor->getParameters();
            $constructorArgs = [];
            
            foreach ($parameters as $parameter) {
                $paramName = $parameter->getName();
                $paramType = $parameter->getType();
                
                // Check parameter source attributes
                $queryParamAttributes = $parameter->getAttributes(QueryParam::class);
                $bodyParamAttributes = $parameter->getAttributes(RequestBody::class);
                
                if (!empty($queryParamAttributes)) {
                    // Handle as query parameter
                    $queryParam = $queryParamAttributes[0]->newInstance();
                    $queryName = $queryParam->name ?? $paramName;
                    $value = $request->query->get($queryName);
                    
                    // Handle array parameters
                    if ($value === null && $request->query->has($queryName)) {
                        $arrayValues = $request->query->all($queryName);
                        if (is_array($arrayValues) && !empty($arrayValues)) {
                            $value = $arrayValues;
                        }
                    }
                    
                    if ($value === null) {
                        if ($queryParam->required) {
                            throw new BadRequestHttpException("Required query parameter '{$queryName}' is missing");
                        }
                        $value = $queryParam->default ?? ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
                    } else {
                        $value = $this->convertQueryParameterValue($value, $paramType);
                    }
                } else {
                    // Handle as body parameter (default)
                    $value = $bodyData[$paramName] ?? null;
                    
                    if ($value === null && $parameter->isDefaultValueAvailable()) {
                        $value = $parameter->getDefaultValue();
                    }
                }
                
                $constructorArgs[$paramName] = $value;
            }
            
            // Create the DTO using named arguments
            $dto = $reflectionClass->newInstance(...$constructorArgs);
            
            // Validate the DTO
            $violations = $this->validator->validate($dto);
            
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
                }
                throw new BadRequestHttpException('Validation failed: ' . implode(', ', $errors));
            }
            
            yield $dto;
            
        } catch (\ReflectionException $e) {
            throw new BadRequestHttpException("Failed to resolve DTO {$type}: " . $e->getMessage());
        }
    }

    /**
     * Deserialize with best-match union type resolution
     * Analyzes the incoming data to find the best fitting type for union parameters
     */
    private function deserializeWithBestMatch(string $content, string $type, Request $request): object
    {
        // Parse JSON content
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestHttpException('Invalid JSON format');
        }

        try {
            $reflectionClass = new ReflectionClass($type);
            $constructor = $reflectionClass->getConstructor();
            
            if (!$constructor) {
                throw new BadRequestHttpException("DTO {$type} must have a constructor");
            }

            $constructorArgs = [];
            
            foreach ($constructor->getParameters() as $parameter) {
                $paramName = $parameter->getName();
                $paramType = $parameter->getType();
                $value = $data[$paramName] ?? null;
                
                if ($value === null) {
                    // Handle null values
                    if ($parameter->isDefaultValueAvailable()) {
                        $constructorArgs[$paramName] = $parameter->getDefaultValue();
                    } elseif ($paramType?->allowsNull()) {
                        $constructorArgs[$paramName] = null;
                    } else {
                        $constructorArgs[$paramName] = null; // Let validation catch this
                    }
                } else {
                    // Resolve value with best-match for union types
                    $constructorArgs[$paramName] = $this->resolveBestMatchType($value, $paramType, $paramName);
                }
            }
            
            return $reflectionClass->newInstance(...$constructorArgs);
            
        } catch (\ReflectionException $e) {
            throw new BadRequestHttpException("Failed to deserialize {$type}: " . $e->getMessage());
        }
    }

    /**
     * Resolve the best matching type for a parameter value
     */
    private function resolveBestMatchType(mixed $value, ?\ReflectionType $type, string $paramName): mixed
    {
        if ($type === null) {
            return $value;
        }

        // Handle union types with best-match logic
        if ($type instanceof \ReflectionUnionType) {
            return $this->findBestMatchInUnion($value, $type, $paramName);
        }

        // Handle named types
        if ($type instanceof \ReflectionNamedType) {
            return $this->deserializeToNamedType($value, $type);
        }

        return $value;
    }

    /**
     * Find the best matching type in a union by scoring all types and choosing the best match
     */
    private function findBestMatchInUnion(mixed $value, \ReflectionUnionType $unionType, string $paramName): mixed
    {
        $types = $unionType->getTypes();
        
        // Handle null case first
        if ($value === null && $unionType->allowsNull()) {
            return null;
        }

        $candidates = [];
        
        // Score ALL types (both builtin and non-builtin) for unified comparison
        foreach ($types as $type) {
            if ($type instanceof \ReflectionNamedType) {
                $score = $type->isBuiltin() 
                    ? $this->scoreBuiltinTypeMatch($value, $type->getName())
                    : $this->scoreTypeMatch($value, $type);
                
                // Only consider types with positive scores
                if ($score > 0) {
                    $candidates[] = ['type' => $type, 'score' => $score];
                }
            }
        }

        // Sort by score (highest first) and try deserialization in order
        usort($candidates, fn($a, $b) => $b['score'] - $a['score']);

        $lastException = null;
        foreach ($candidates as $candidate) {
            try {
                return $this->deserializeToNamedType($value, $candidate['type']);
            } catch (\Throwable $e) {
                $lastException = $e;
                continue; // Try next candidate
            }
        }

        // If no type worked, throw the last exception with context
        throw new BadRequestHttpException(
            "Could not resolve union type for parameter '{$paramName}': " . 
            ($lastException ? $lastException->getMessage() : 'No matching type found')
        );
    }

    /**
     * Score how well a value matches a specific type based on its structure
     */
    private function scoreTypeMatch(mixed $value, \ReflectionNamedType $type): int
    {
        if (!is_array($value)) {
            return 0; // We only score array values for object types
        }

        $typeName = $type->getName();
        $score = 0;

        // Check for required constructor parameters first
        try {
            $reflectionClass = new ReflectionClass($typeName);
            $constructor = $reflectionClass->getConstructor();
            if ($constructor) {
                foreach ($constructor->getParameters() as $param) {
                    $paramName = $param->getName();
                    // If parameter is required (no default value) and not present in data, heavily penalize
                    if (!$param->isDefaultValueAvailable() && !$param->getType()?->allowsNull() && !isset($value[$paramName])) {
                        return -100; // This type is not viable
                    }
                }
            }
        } catch (\ReflectionException) {
            return 0;
        }

        // Special scoring for known layer property types
        switch ($typeName) {
            case 'App\DTO\ValueObject\TextLayerProperties':
                $textFields = ['text', 'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'textAlign', 'color', 'lineHeight', 'letterSpacing', 'textDecoration'];
                foreach ($textFields as $field) {
                    if (isset($value[$field])) $score += 10;
                }
                // Bonus for text-specific fields
                if (isset($value['text'])) $score += 50;
                if (isset($value['fontFamily'])) $score += 30;
                break;

            case 'App\DTO\ValueObject\ImageLayerProperties':
                // Critical: src is required for ImageLayerProperties
                if (!isset($value['src'])) {
                    return -100; // Cannot be an image layer without src
                }
                
                $imageFields = ['src', 'alt', 'objectFit', 'objectPosition', 'quality', 'brightness', 'contrast', 'saturation', 'blur'];
                foreach ($imageFields as $field) {
                    if (isset($value[$field])) $score += 10;
                }
                // Bonus for image-specific fields
                if (isset($value['src'])) $score += 50;
                if (isset($value['objectFit'])) $score += 30;
                break;

            case 'App\DTO\ValueObject\ShapeLayerProperties':
                $shapeFields = ['shapeType', 'fill', 'stroke', 'strokeWidth', 'strokeOpacity', 'strokeDashArray', 'strokeLineCap', 'strokeLineJoin', 'cornerRadius', 'sides', 'points', 'innerRadius', 'x1', 'y1', 'x2', 'y2', 'shadow', 'glow'];
                foreach ($shapeFields as $field) {
                    if (isset($value[$field])) $score += 10;
                }
                // Bonus for shape-specific fields
                if (isset($value['shapeType'])) $score += 50;
                if (isset($value['fill'])) $score += 30;
                break;
            case 'App\DTO\ValueObject\SvgLayerProperties':
                $svgFields = ['src', 'viewBox', 'preserveAspectRatio', 'fillColors', 'strokeColors', 'strokeWidths', 'originalWidth', 'originalHeight'];
                foreach ($svgFields as $field) {
                    if (isset($value[$field])) $score += 10;
                }
                // Bonus for SVG-specific fields
                if (isset($value['src'])) $score += 50;
                if (isset($value['viewBox'])) $score += 30;
                break;

            case 'App\DTO\ValueObject\Transform':
                $transformFields = ['x', 'y', 'width', 'height', 'rotation', 'scaleX', 'scaleY', 'skewX', 'skewY', 'opacity'];
                foreach ($transformFields as $field) {
                    if (isset($value[$field])) $score += 10;
                }
                break;

            default:
                // Generic scoring: try to match field names with constructor parameters
                try {
                    $reflectionClass = new ReflectionClass($typeName);
                    $constructor = $reflectionClass->getConstructor();
                    if ($constructor) {
                        foreach ($constructor->getParameters() as $param) {
                            if (isset($value[$param->getName()])) {
                                $score += 5;
                            }
                        }
                    }
                } catch (\ReflectionException) {
                    // Ignore reflection errors for scoring
                }
                break;
        }

        return $score;
    }

    /**
     * Score how well a value matches a builtin type
     */
    private function scoreBuiltinTypeMatch(mixed $value, string $typeName): int
    {
        return match ($typeName) {
            'int' => $this->scoreIntMatch($value),
            'float' => $this->scoreFloatMatch($value),
            'string' => $this->scoreStringMatch($value),
            'bool' => $this->scoreBoolMatch($value),
            'array' => $this->scoreArrayMatch($value),
            default => 0
        };
    }

    /**
     * Score how well a value matches the int type
     */
    private function scoreIntMatch(mixed $value): int
    {
        if (is_int($value)) {
            return 100; // Perfect match
        }
        if (is_numeric($value) && (string)(int)$value === (string)$value) {
            return 80; // Good conversion candidate
        }
        return 0; // Cannot convert
    }

    /**
     * Score how well a value matches the float type
     */
    private function scoreFloatMatch(mixed $value): int
    {
        if (is_float($value)) {
            return 100; // Perfect match
        }
        if (is_numeric($value)) {
            return 70; // Good conversion candidate
        }
        return 0; // Cannot convert
    }

    /**
     * Score how well a value matches the string type
     */
    private function scoreStringMatch(mixed $value): int
    {
        if (is_string($value)) {
            return 100; // Perfect match
        }
        if (is_scalar($value)) {
            return 50; // Can convert scalar to string
        }
        if (is_array($value)) {
            return 10; // Can convert array to string but it's usually not what we want
        }
        return 0; // Cannot convert objects to string reliably
    }

    /**
     * Score how well a value matches the bool type
     */
    private function scoreBoolMatch(mixed $value): int
    {
        if (is_bool($value)) {
            return 100; // Perfect match
        }
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'], true)) {
                return 80; // Good boolean string
            }
        }
        if (is_int($value) && ($value === 0 || $value === 1)) {
            return 70; // Integer 0/1 boolean
        }
        return 0; // Cannot reliably convert
    }

    /**
     * Score how well a value matches the array type
     */
    private function scoreArrayMatch(mixed $value): int
    {
        if (is_array($value)) {
            return 100; // Perfect match
        }
        return 0; // Cannot convert non-arrays to arrays reliably
    }

    /**
     * Deserialize value to a specific named type
     */
    private function deserializeToNamedType(mixed $value, \ReflectionNamedType $type): mixed
    {
        $typeName = $type->getName();
        
        // Handle null values
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        // Handle primitive types
        if ($type->isBuiltin()) {
            return $this->convertToBuiltinType($value, $typeName);
        }

        // Handle object types - serialize to JSON then deserialize to target type
        if (is_array($value)) {
            return $this->serializer->deserialize(json_encode($value), $typeName, 'json');
        }

        return $value;
    }

    /**
     * Convert value to builtin PHP types
     */
    private function convertToBuiltinType(mixed $value, string $typeName): mixed
    {
        return match ($typeName) {
            'int' => is_numeric($value) ? (int) $value : throw new BadRequestHttpException("Invalid integer: {$value}"),
            'float' => is_numeric($value) ? (float) $value : throw new BadRequestHttpException("Invalid float: {$value}"),
            'string' => (string) $value,
            'bool' => is_bool($value) ? $value : $this->convertToBool($value),
            'array' => is_array($value) ? $value : [$value],
            default => $value
        };
    }

    /**
     * Try to convert value to builtin PHP types without throwing exceptions
     * Returns null if conversion is not possible
     */
    private function tryConvertToBuiltinType(mixed $value, string $typeName): mixed
    {
        try {
            return match ($typeName) {
                'int' => is_numeric($value) ? (int) $value : null,
                'float' => is_numeric($value) ? (float) $value : null,
                'string' => (string) $value,
                'bool' => is_bool($value) ? $value : $this->tryConvertToBool($value),
                'array' => is_array($value) ? $value : null,
                default => null
            };
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Try to convert value to boolean without throwing exceptions
     */
    private function tryConvertToBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }
        
        if (is_int($value)) {
            return $value !== 0;
        }
        
        return null;
    }
}
