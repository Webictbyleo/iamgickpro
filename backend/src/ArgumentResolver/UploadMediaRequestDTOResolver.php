<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\DTO\Request\UploadMediaRequestDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Argument resolver for handling multipart/form-data requests for UploadMediaRequestDTO.
 * 
 * This resolver extracts file uploads and form data from multipart requests
 * and creates the appropriate DTO instance.
 */
class UploadMediaRequestDTOResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Only handle UploadMediaRequestDTO
        if ($argument->getType() !== UploadMediaRequestDTO::class) {
            return [];
        }

        // Only handle multipart/form-data requests
        if (!str_starts_with($request->headers->get('Content-Type', ''), 'multipart/form-data')) {
            return [];
        }

        // Extract file and name from the request
        $file = $request->files->get('file');
        $name = $request->request->get('name');

        // Create and return the DTO
        yield new UploadMediaRequestDTO(
            file: $file,
            name: $name
        );
    }
}
