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

        $content = $request->getContent();
        
        if (empty($content)) {
            throw new BadRequestHttpException('Request body is empty');
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
}
