<?php

declare(strict_types=1);

namespace App\Controller\Trait;

use App\DTO\Response\AuthResponseDTO;
use App\DTO\Response\DesignResponseDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\ExportJobResponseDTO;
use App\DTO\Response\LayerResponseDTO;
use App\DTO\Response\MediaResponseDTO;
use App\DTO\Response\PaginatedResponseDTO;
use App\DTO\Response\PluginResponseDTO;
use App\DTO\Response\ProjectResponseDTO;
use App\DTO\Response\SearchResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\DTO\Response\TemplateResponseDTO;
use App\DTO\Response\UserProfileResponseDTO;
use App\DTO\Response\UserResponseDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait for typed JSON responses using response DTOs
 */
trait TypedResponseTrait
{
    /**
     * Create a typed JSON response with AuthResponseDTO
     */
    protected function authResponse(AuthResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with UserResponseDTO
     */
    protected function userResponse(UserResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with UserProfileResponseDTO
     */
    protected function userProfileResponse(UserProfileResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with DesignResponseDTO
     */
    protected function designResponse(DesignResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with ProjectResponseDTO
     */
    protected function projectResponse(ProjectResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with MediaResponseDTO
     */
    protected function mediaResponse(MediaResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with TemplateResponseDTO
     */
    protected function templateResponse(TemplateResponseDTO $responseDTO, int $httpStatus = Response::HTTP_OK): JsonResponse
    {
        return $this->json($responseDTO->toArray(), $httpStatus);
    }

    /**
     * Create a typed JSON response with LayerResponseDTO
     */
    protected function layerResponse(LayerResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto, $status);
    }

    /**
     * Create a typed JSON response with ExportJobResponseDTO
     */
    protected function exportJobResponse(ExportJobResponseDTO $responseDTO, int $httpStatus = Response::HTTP_OK): JsonResponse
    {
        return $this->json($responseDTO->toArray(), $httpStatus);
    }

    /**
     * Create a typed JSON response with SearchResponseDTO
     */
    protected function searchResponse(SearchResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with PluginResponseDTO
     */
    protected function pluginResponse(PluginResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with SuccessResponseDTO
     */
    protected function successResponse(SuccessResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with ErrorResponseDTO
     */
    protected function errorResponse(ErrorResponseDTO $dto, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }

    /**
     * Create a typed JSON response with PaginatedResponseDTO
     */
    protected function paginatedResponse(PaginatedResponseDTO $dto, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($dto->toArray(), $status);
    }
}
