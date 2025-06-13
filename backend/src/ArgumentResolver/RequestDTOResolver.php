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
            // Deserialize JSON to DTO
            $dto = $this->serializer->deserialize($content, $type, 'json');
            
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
}
