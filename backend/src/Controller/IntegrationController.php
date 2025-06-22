<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Request\SaveIntegrationRequestDTO;
use App\DTO\Request\TestIntegrationRequestDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\Entity\User;
use App\Service\IntegrationService;
use App\Service\ResponseDTOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Integration Controller
 * 
 * Manages third-party API integrations including saving credentials,
 * testing connections, and retrieving integration settings.
 */
#[Route('/api/integrations', name: 'api_integrations_')]
#[IsGranted('ROLE_USER')]
class IntegrationController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly IntegrationService $integrationService,
        private readonly ValidatorInterface $validator,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    /**
     * Get all user integrations
     * 
     * Returns a list of all configured integrations for the authenticated user,
     * including configuration status and settings.
     * 
     * @return JsonResponse Integration list or error response
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $integrations = $this->integrationService->getUserIntegrations($user);
            
            return new JsonResponse([
                'success' => true,
                'data' => $integrations
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve integrations'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Save integration credentials
     * 
     * Stores encrypted API credentials for a third-party service.
     * Supports OpenAI, Remove.bg, Unsplash, Pexels, and Replicate integrations.
     * 
     * @param SaveIntegrationRequestDTO $dto Integration credentials and settings
     * @return JsonResponse Success confirmation or error response
     */
    #[Route('', name: 'save', methods: ['POST'])]
    public function save(#[MapRequestPayload] SaveIntegrationRequestDTO $dto): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $this->integrationService->saveCredentials(
                $user,
                $dto->serviceName,
                $dto->credentials,
                $dto->settings ?? []
            );
            
            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('Integration saved successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to save integration'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Test integration credentials
     * 
     * Tests API credentials by making a simple API call to verify connectivity
     * and authentication. Can test provided credentials or stored credentials.
     * 
     * @param TestIntegrationRequestDTO $dto Service name and optional credentials
     * @return JsonResponse Test result with success status and message
     */
    #[Route('/test', name: 'test', methods: ['POST'])]
    public function test(#[MapRequestPayload] TestIntegrationRequestDTO $dto): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $result = $this->integrationService->testCredentials(
                $user,
                $dto->serviceName,
                $dto->credentials
            );
            
            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'] ?? $result['error'] ?? 'Test completed',
                'data' => $result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to test integration'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get integration settings
     * 
     * Retrieves settings for a specific integration without exposing
     * sensitive credential information.
     * 
     * @param string $serviceName Service identifier
     * @return JsonResponse Integration settings or error response
     */
    #[Route('/{serviceName}', name: 'get', methods: ['GET'])]
    public function get(string $serviceName): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $settings = $this->integrationService->getSettings($user, $serviceName);
            $hasCredentials = $this->integrationService->getCredentials($user, $serviceName) !== null;
            
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'service' => $serviceName,
                    'isConfigured' => $hasCredentials,
                    'settings' => $settings
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve integration'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Remove integration
     * 
     * Deletes stored credentials and settings for a third-party service.
     * This action cannot be undone.
     * 
     * @param string $serviceName Service identifier
     * @return JsonResponse Success confirmation or error response
     */
    #[Route('/{serviceName}', name: 'remove', methods: ['DELETE'])]
    public function remove(string $serviceName): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $removed = $this->integrationService->removeIntegration($user, $serviceName);
            
            if (!$removed) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Integration not found'),
                    Response::HTTP_NOT_FOUND
                );
            }
            
            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('Integration removed successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to remove integration'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
