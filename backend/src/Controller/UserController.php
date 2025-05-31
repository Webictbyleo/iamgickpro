<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use App\Service\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/user', name: 'api_user_')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly FileUploadService $fileUploadService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->json([
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'jobTitle' => $user->getJobTitle(),
            'company' => $user->getCompany(),
            'website' => $user->getWebsite(),
            'portfolio' => $user->getPortfolio(),
            'bio' => $user->getBio(),
            'avatar' => $user->getAvatar(),
            'socialLinks' => $user->getSocialLinks(),
            'timezone' => $user->getTimezone(),
            'language' => $user->getLanguage(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'updatedAt' => $user->getUpdatedAt()?->format('c'),
        ]);
    }

    #[Route('/profile', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!is_array($data)) {
                return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
            }
            
            $updatedUser = $this->userService->updateProfile($user, $data);
            
            return $this->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => json_decode($this->serializer->serialize($updatedUser, 'json', ['groups' => ['user:read']]), true)
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to update profile'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/avatar', name: 'upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $file = $request->files->get('avatar');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $avatarFilename = $this->fileUploadService->uploadAvatar($file);
            $avatarUrl = $this->fileUploadService->getAvatarUrl($avatarFilename);
            
            // Delete old avatar if exists
            if ($user->getAvatar()) {
                $this->fileUploadService->deleteAvatar(basename($user->getAvatar()));
            }
            
            $user->setAvatar($avatarUrl);
            $this->userService->updateProfile($user, []);
            
            return $this->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'avatar' => $avatarUrl
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/password', name: 'change_password', methods: ['PUT'])]
    public function changePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
            return $this->json(['error' => 'Current password and new password are required'], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $this->userService->changePassword($user, $data['currentPassword'], $data['newPassword']);
            return $this->json(['success' => true, 'message' => 'Password changed successfully']);
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to change password'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/settings/privacy/download', name: 'download_data', methods: ['POST'])]
    public function requestDataDownload(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // In a real implementation, this would trigger a background job
        // to prepare the user's data export
        
        return $this->json([
            'message' => 'Data download request submitted',
            'estimatedTime' => '24 hours'
        ]);
    }

    #[Route('/settings/privacy/export', name: 'export_data', methods: ['POST'])]
    public function exportPortableData(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        try {
            $exportData = $this->userService->generateDataExport($user);
            
            return $this->json([
                'success' => true,
                'message' => 'Data export completed',
                'data' => $exportData
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to export data'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/settings/privacy/delete', name: 'delete_account', methods: ['DELETE'])]
    public function deleteAccount(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        try {
            $this->userService->deleteUserAccount($user);
            return $this->json(['message' => 'Account deletion initiated']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete account'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/subscription', name: 'subscription', methods: ['GET'])]
    public function getSubscription(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        try {
            $subscriptionData = $this->userService->getSubscriptionData($user);
            return $this->json($subscriptionData);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to get subscription data'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
