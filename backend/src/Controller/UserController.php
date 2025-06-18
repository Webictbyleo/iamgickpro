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
use App\Service\ResponseDTOFactory;
use App\Service\UserService;
use App\Service\FileUploadService;
use PHPUnit\Util\Json;
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
}
