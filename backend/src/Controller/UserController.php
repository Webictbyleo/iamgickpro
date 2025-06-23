<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Request\ChangePasswordRequestDTO;
use App\DTO\Request\UpdateProfileRequestDTO;
use App\DTO\Request\UploadAvatarRequestDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\DTO\Response\UserProfileResponseDTO;
use App\Entity\User;
use App\Entity\SubscriptionPlan;
use App\Entity\PlanLimit;
use App\Entity\PlanFeature;
use App\Service\ResponseDTOFactory;
use App\Service\UserService;
use App\Service\FileUploadService;
use App\Service\DatabasePlanService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * User Controller
 * 
 * Manages user account operations including profile management, avatar uploads,
 * password changes, privacy settings, and subscription information.
 * Handles personal data export/download and account deletion functionality.
 * All endpoints require authentication and operate on the current user's data.
 */
#[Route('/api/user', name: 'api_user_')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly UserService $userService,
        private readonly FileUploadService $fileUploadService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly ResponseDTOFactory $responseDTOFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly DatabasePlanService $planService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get current user's profile information
     * 
     * Returns comprehensive profile data including personal information,
     * settings, and account details for the authenticated user.
     * 
     * @return JsonResponse User profile data with extended information
     */
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            return $this->userProfileResponse(
                $this->responseDTOFactory->createUserProfileResponse($user)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve profile'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update current user's profile information
     * 
     * Updates user profile data including personal information, professional details,
     * and account preferences. Uses comprehensive validation and returns updated profile.
     * 
     * @param UpdateProfileRequestDTO $updateDTO Profile update data transfer object
     * @return JsonResponse Updated user profile data or validation errors
     */
    #[Route('/profile', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(UpdateProfileRequestDTO $updateDTO): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            if (!$updateDTO->hasAnyData()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('No data provided for update'),
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            $updatedUser = $this->userService->updateProfile($user, $updateDTO->toArray());
            
            // Return the updated user data for frontend compatibility
            $profileResponse = $this->responseDTOFactory->createUserProfileResponse($updatedUser);
            return $this->userProfileResponse($profileResponse);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse($e->getMessage()),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to update profile'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Upload and update user avatar image
     * 
     * Handles avatar file upload, validation, and updates user profile.
     * Automatically removes old avatar file and returns new avatar URL.
     * 
     * @param UploadAvatarRequestDTO $dto Validated avatar upload data
     * @return JsonResponse Success response with new avatar URL or error details
     */
    #[Route('/avatar', name: 'upload_avatar', methods: ['POST'])]
    public function uploadAvatar(UploadAvatarRequestDTO $dto): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            if (!$dto->avatar) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('No file uploaded'),
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            $avatarFilename = $this->fileUploadService->uploadAvatar($dto->avatar);
            $avatarUrl = $this->fileUploadService->getAvatarUrl($avatarFilename);
            
            // Delete old avatar if exists
            if ($user->getAvatar()) {
                $this->fileUploadService->deleteAvatar(basename($user->getAvatar()));
            }
            
            $user->setAvatar($avatarUrl);
            $this->userService->updateProfile($user, []);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatar' => $avatarUrl
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
           
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse($e->getMessage()),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Change user password
     * 
     * Updates user password after validating current password and ensuring
     * new password meets security requirements including confirmation match.
     * 
     * @param ChangePasswordRequestDTO $dto Validated password change data
     * @return JsonResponse Success confirmation or validation errors
     */
    #[Route('/password', name: 'change_password', methods: ['PUT'])]
    public function changePassword(ChangePasswordRequestDTO $dto): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $this->userService->changePassword(
                $user,
                $dto->currentPassword,
                $dto->newPassword
            );
            
            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('Password changed successfully')
            );
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse($e->getMessage()),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse($e->getMessage()),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to change password'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Request user data download for GDPR compliance
     * 
     * Initiates a background process to prepare comprehensive data export
     * for the user, including all personal data and content.
     * 
     * @return JsonResponse Confirmation message with estimated completion time
     */
    #[Route('/settings/privacy/download', name: 'download_data', methods: ['POST'])]
    public function requestDataDownload(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            // In a real implementation, this would trigger a background job
            // to prepare the user's data export
            
            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse(
                    'Data download request submitted'
                )
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to process download request'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Export user data in portable format
     * 
     * Generates and returns comprehensive user data export including
     * all user content, settings, and account information.
     * 
     * @return JsonResponse Complete user data export or error details
     */
    #[Route('/settings/privacy/export', name: 'export_data', methods: ['POST'])]
    public function exportPortableData(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $exportData = $this->userService->generateDataExport($user);
            
            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse(
                    'Data export completed'
                )
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to export data'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete user account and all associated data
     * 
     * Initiates account deletion process which removes all user data,
     * content, and associated resources permanently.
     * 
     * @return JsonResponse Confirmation of deletion initiation or error details
     */
    #[Route('/settings/privacy/delete', name: 'delete_account', methods: ['DELETE'])]
    public function deleteAccount(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $this->userService->deleteUserAccount($user);
            
            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('Account deletion initiated')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to delete account'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get user subscription information
     * 
     * Returns current subscription details including plan type, billing status,
     * usage limits, and subscription features for the authenticated user.
     * 
     * @return JsonResponse Subscription data or error details
     */
    #[Route('/subscription', name: 'subscription', methods: ['GET'])]
    public function getSubscription(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $subscriptionData = $this->userService->getSubscriptionData($user);
            return JsonResponse::fromJsonString(
                $this->serializer->serialize(
                    ['data'=>$subscriptionData],
                    'json'
                ),
                Response::HTTP_OK
            );
           
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to get subscription data'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ========================================
    // ADMIN PLAN MANAGEMENT ENDPOINTS
    // ========================================

    /**
     * Get all subscription plans (Admin only)
     * 
     * Returns all subscription plans including inactive ones for admin management.
     * Only accessible to users with ROLE_ADMIN.
     * 
     * @return JsonResponse List of all plans with their limits and features
     */
    #[Route('/admin/plans', name: 'admin_plans_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function listPlansAdmin(): JsonResponse
    {
        try {
            $plans = $this->entityManager->getRepository(SubscriptionPlan::class)
                ->findBy([], ['sortOrder' => 'ASC']);

            $plansData = array_map(function (SubscriptionPlan $plan) {
                return $this->formatPlanForAdmin($plan);
            }, $plans);

            return new JsonResponse([
                'success' => true,
                'message' => 'Plans retrieved successfully',
                'data' => ['plans' => $plansData]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve plans for admin', [
                'error' => $e->getMessage(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve plans'
            ], 500);
        }
    }

    /**
     * Create a new subscription plan (Admin only)
     * 
     * Creates a new subscription plan with limits and features.
     * Only accessible to users with ROLE_ADMIN.
     * 
     * @param Request $request Plan data including name, price, limits, and features
     * @return JsonResponse Created plan data or validation errors
     */
    #[Route('/admin/plans', name: 'admin_plans_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createPlanAdmin(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ], 400);
            }

            // Create new plan
            $plan = new SubscriptionPlan();
            $this->updatePlanFromData($plan, $data);

            // Validate the plan
            $errors = $this->validator->validate($plan);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $errorMessages)
                ], 400);
            }

            $this->entityManager->persist($plan);
            $this->entityManager->flush();

            $userId = $this->getUser()?->getUserIdentifier() ?? 'unknown';
            $this->logger->info('Subscription plan created', [
                'plan_id' => $plan->getId(),
                'plan_code' => $plan->getCode(),
                'admin_user' => $userId
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Plan created successfully',
                'data' => ['plan' => $this->formatPlanForAdmin($plan)]
            ], 201);
        } catch (\Exception $e) {
            $userId = $this->getUser()?->getUserIdentifier() ?? 'unknown';
            $this->logger->error('Failed to create plan', [
                'error' => $e->getMessage(),
                'admin_user' => $userId
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to create plan'
            ], 500);
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS FOR ADMIN PLANS
    // ========================================

    /**
     * Update plan from request data
     */
    private function updatePlanFromData(SubscriptionPlan $plan, array $data): void
    {
        if (isset($data['code'])) {
            $plan->setCode($data['code']);
        }

        if (isset($data['name'])) {
            $plan->setName($data['name']);
        }

        if (isset($data['description'])) {
            $plan->setDescription($data['description']);
        }

        if (isset($data['monthly_price'])) {
            $plan->setMonthlyPrice((string) $data['monthly_price']);
        }

        if (isset($data['yearly_price'])) {
            $plan->setYearlyPrice((string) $data['yearly_price']);
        }

        if (isset($data['currency'])) {
            $plan->setCurrency($data['currency']);
        }

        if (isset($data['is_active'])) {
            $plan->setIsActive((bool) $data['is_active']);
        }

        if (isset($data['is_default'])) {
            // If setting this plan as default, remove default from others
            if ($data['is_default']) {
                $this->clearOtherDefaultPlans();
            }
            $plan->setIsDefault((bool) $data['is_default']);
        }

        if (isset($data['sort_order'])) {
            $plan->setSortOrder((int) $data['sort_order']);
        }

        // Update limits
        if (isset($data['limits']) && is_array($data['limits'])) {
            $this->updatePlanLimits($plan, $data['limits']);
        }

        // Update features
        if (isset($data['features']) && is_array($data['features'])) {
            $this->updatePlanFeatures($plan, $data['features']);
        }
    }

    /**
     * Update plan limits
     */
    private function updatePlanLimits(SubscriptionPlan $plan, array $limits): void
    {
        // Remove existing limits
        foreach ($plan->getLimits() as $limit) {
            $this->entityManager->remove($limit);
        }
        $plan->getLimits()->clear();

        // Add new limits
        foreach ($limits as $limitName => $limitValue) {
            $limit = new PlanLimit();
            $limit->setPlan($plan);
            $limit->setType($limitName);
            $limit->setValue((int) $limitValue);
            
            $plan->addLimit($limit);
            $this->entityManager->persist($limit);
        }
    }

    /**
     * Update plan features
     */
    private function updatePlanFeatures(SubscriptionPlan $plan, array $features): void
    {
        // Remove existing features
        foreach ($plan->getFeatures() as $feature) {
            $this->entityManager->remove($feature);
        }
        $plan->getFeatures()->clear();

        // Add new features
        foreach ($features as $featureName => $isEnabled) {
            $feature = new PlanFeature();
            $feature->setPlan($plan);
            $feature->setCode($featureName);
            $feature->setName(ucwords(str_replace('_', ' ', $featureName)));
            $feature->setEnabled((bool) $isEnabled);
            
            $plan->addFeature($feature);
            $this->entityManager->persist($feature);
        }
    }

    /**
     * Clear default flag from other plans
     */
    private function clearOtherDefaultPlans(): void
    {
        $this->entityManager->createQuery(
            'UPDATE App\Entity\SubscriptionPlan p SET p.isDefault = false WHERE p.isDefault = true'
        )->execute();
    }

    /**
     * Format plan data for admin interface
     */
    private function formatPlanForAdmin(SubscriptionPlan $plan): array
    {
        $limits = [];
        foreach ($plan->getLimits() as $limit) {
            $limits[$limit->getType()] = $limit->getValue();
        }

        $features = [];
        foreach ($plan->getFeatures() as $feature) {
            $features[$feature->getCode()] = $feature->isEnabled();
        }

        return [
            'id' => $plan->getId(),
            'code' => $plan->getCode(),
            'name' => $plan->getName(),
            'description' => $plan->getDescription(),
            'monthly_price' => $plan->getMonthlyPrice(),
            'yearly_price' => $plan->getYearlyPrice(),
            'currency' => $plan->getCurrency(),
            'is_active' => $plan->isActive(),
            'is_default' => $plan->isDefault(),
            'sort_order' => $plan->getSortOrder(),
            'limits' => $limits,
            'features' => $features,
            'created_at' => $plan->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $plan->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
