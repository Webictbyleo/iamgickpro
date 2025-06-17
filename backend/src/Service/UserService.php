<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

readonly class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        private EmailService $emailService,
        private string $avatarUploadDirectory
    ) {
    }

    public function updateProfile(User $user, array $profileData): User
    {
        // Update basic profile fields
        if (isset($profileData['firstName'])) {
            $user->setFirstName($profileData['firstName']);
        }
        
        if (isset($profileData['lastName'])) {
            $user->setLastName($profileData['lastName']);
        }
        
        if (isset($profileData['username'])) {
            $user->setUsername($profileData['username']);
        }
        
        if (isset($profileData['email'])) {
            $user->setEmail($profileData['email']);
        }
        
        if (isset($profileData['jobTitle'])) {
            $user->setJobTitle($profileData['jobTitle']);
        }
        
        if (isset($profileData['company'])) {
            $user->setCompany($profileData['company']);
        }
        
        if (isset($profileData['website'])) {
            $user->setWebsite($profileData['website']);
        }
        
        if (isset($profileData['portfolio'])) {
            $user->setPortfolio($profileData['portfolio']);
        }
        
        if (isset($profileData['bio'])) {
            $user->setBio($profileData['bio']);
        }
        
        if (isset($profileData['socialLinks'])) {
            $user->setSocialLinks($profileData['socialLinks']);
        }
        
        if (isset($profileData['timezone'])) {
            $user->setTimezone($profileData['timezone']);
        }
        
        if (isset($profileData['language'])) {
            $user->setLanguage($profileData['language']);
        }

        // Validate the updated user
        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            throw new \InvalidArgumentException((string) $violations);
        }

        $this->entityManager->flush();
        
        $this->logger->info('User profile updated', [
            'user_id' => $user->getId(),
            'updated_fields' => array_keys($profileData)
        ]);

        return $user;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new AccessDeniedException('Current password is incorrect');
        }

        // Validate new password strength
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }

        // Hash and set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();
        
        $this->logger->info('User password changed', [
            'user_id' => $user->getId()
        ]);
    }

    public function generateDataExport(User $user): array
    {
        $userData = [
            'profile' => [
                'id' => $user->getId(),
                'uuid' => $user->getUuid(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'username' => $user->getUsername(),
                'jobTitle' => $user->getJobTitle(),
                'company' => $user->getCompany(),
                'website' => $user->getWebsite(),
                'portfolio' => $user->getPortfolio(),
                'bio' => $user->getBio(),
                'socialLinks' => $user->getSocialLinks(),
                'timezone' => $user->getTimezone(),
                'language' => $user->getLanguage(),
                'createdAt' => $user->getCreatedAt()->format('c'),
                'updatedAt' => $user->getUpdatedAt()?->format('c'),
            ],
            'projects' => [],
            'mediaFiles' => [],
            'exportJobs' => []
        ];

        // Add projects data
        foreach ($user->getProjects() as $project) {
            $userData['projects'][] = [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'createdAt' => $project->getCreatedAt()->format('c'),
                'updatedAt' => $project->getUpdatedAt()?->format('c'),
            ];
        }

        // Add media files data
        foreach ($user->getMediaFiles() as $media) {
            // Extract filename from URL path
            $url = $media->getUrl();
            $filename = $url ? basename(parse_url($url, PHP_URL_PATH)) : '';
            
            // Get original name from metadata if available, otherwise use name
            $metadata = $media->getMetadata();
            $originalName = $metadata['original_filename'] ?? $media->getName();
            
            $userData['mediaFiles'][] = [
                'id' => $media->getId(),
                'filename' => $filename,
                'originalName' => $originalName,
                'mimeType' => $media->getMimeType(),
                'size' => $media->getSize(),
                'createdAt' => $media->getCreatedAt()->format('c'),
            ];
        }

        // Add export jobs data
        foreach ($user->getExportJobs() as $exportJob) {
            $userData['exportJobs'][] = [
                'id' => $exportJob->getId(),
                'status' => $exportJob->getStatus(),
                'format' => $exportJob->getFormat(),
                'createdAt' => $exportJob->getCreatedAt()->format('c'),
                'completedAt' => $exportJob->getCompletedAt()?->format('c'),
            ];
        }

        $this->logger->info('User data export generated', [
            'user_id' => $user->getId(),
            'projects_count' => count($userData['projects']),
            'media_files_count' => count($userData['mediaFiles']),
            'export_jobs_count' => count($userData['exportJobs'])
        ]);

        return $userData;
    }

    public function deleteUserAccount(User $user): void
    {
        $userId = $user->getId();
        $userEmail = $user->getEmail();
        
        // Log the deletion attempt
        $this->logger->warning('User account deletion initiated', [
            'user_id' => $userId,
            'email' => $userEmail
        ]);

        // Send account deletion confirmation email
        try {
            $this->emailService->sendAccountDeletionConfirmation($user);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send account deletion confirmation email', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        // Remove the user (cascade delete should handle related entities)
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        
        $this->logger->warning('User account deleted', [
            'user_id' => $userId,
            'email' => $userEmail
        ]);
    }

    public function getSubscriptionData(User $user): array
    {
        $plan = $user->getPlan();
        $limits = $this->getPlanLimits($plan);
        $features = $this->getPlanFeatures($plan);
        
        return [
            'plan' => $plan,
            'isActive' => $user->isActive(),
            'usage' => [
                'projects' => $user->getProjects()->count(),
                'mediaFiles' => $user->getMediaFiles()->count(),
                'exportJobs' => $user->getExportJobs()->count(),
                'storageUsed' => $this->calculateStorageUsage($user),
            ],
            'limits' => $limits,
            'features' => $features,
            'planInfo' => [
                'name' => $this->getPlanDisplayName($plan),
                'description' => $this->getPlanDescription($plan),
                'price' => $this->getPlanPricing($plan),
            ]
        ];
    }

    private function getPlanLimits(string $plan): array
    {
        return match ($plan) {
            'free' => [
                'projects' => 5,
                'storage' => 100 * 1024 * 1024, // 100MB
                'exports' => 10,
                'collaborators' => 1,
                'templates' => 10,
            ],
            'pro' => [
                'projects' => 100,
                'storage' => 10 * 1024 * 1024 * 1024, // 10GB
                'exports' => 500,
                'collaborators' => 5,
                'templates' => -1, // unlimited
            ],
            'business' => [
                'projects' => -1, // unlimited
                'storage' => 100 * 1024 * 1024 * 1024, // 100GB
                'exports' => -1, // unlimited
                'collaborators' => -1, // unlimited
                'templates' => -1, // unlimited
            ],
            default => [
                'projects' => 5,
                'storage' => 100 * 1024 * 1024,
                'exports' => 10,
                'collaborators' => 1,
                'templates' => 10,
            ],
        };
    }

    private function getPlanFeatures(string $plan): array
    {
        return match ($plan) {
            'free' => [
                'basic_templates' => true,
                'basic_export' => true,
                'cloud_storage' => true,
                'premium_templates' => false,
                'advanced_export' => false,
                'collaboration' => false,
                'api_access' => false,
                'priority_support' => false,
                'custom_branding' => false,
                'team_management' => false,
            ],
            'pro' => [
                'basic_templates' => true,
                'basic_export' => true,
                'cloud_storage' => true,
                'premium_templates' => true,
                'advanced_export' => true,
                'collaboration' => true,
                'api_access' => true,
                'priority_support' => true,
                'custom_branding' => false,
                'team_management' => false,
            ],
            'business' => [
                'basic_templates' => true,
                'basic_export' => true,
                'cloud_storage' => true,
                'premium_templates' => true,
                'advanced_export' => true,
                'collaboration' => true,
                'api_access' => true,
                'priority_support' => true,
                'custom_branding' => true,
                'team_management' => true,
            ],
            default => [
                'basic_templates' => true,
                'basic_export' => true,
                'cloud_storage' => true,
                'premium_templates' => false,
                'advanced_export' => false,
                'collaboration' => false,
                'api_access' => false,
                'priority_support' => false,
                'custom_branding' => false,
                'team_management' => false,
            ],
        };
    }

    private function calculateStorageUsage(User $user): int
    {
        $totalSize = 0;
        foreach ($user->getMediaFiles() as $media) {
            $totalSize += $media->getSize();
        }
        return $totalSize;
    }

    private function getPlanDisplayName(string $plan): string
    {
        return match ($plan) {
            'free' => 'Free',
            'pro' => 'Pro',
            'business' => 'Business',
            default => 'Free',
        };
    }

    private function getPlanDescription(string $plan): string
    {
        return match ($plan) {
            'free' => 'Perfect for personal projects and getting started',
            'pro' => 'Best for professionals and growing businesses',
            'business' => 'Advanced features for teams and enterprises',
            default => 'Basic plan with essential features',
        };
    }

    private function getPlanPricing(string $plan): array
    {
        return match ($plan) {
            'free' => [
                'monthly' => 0,
                'yearly' => 0,
                'currency' => 'USD',
            ],
            'pro' => [
                'monthly' => 19,
                'yearly' => 190, // ~16.67/month
                'currency' => 'USD',
            ],
            'business' => [
                'monthly' => 49,
                'yearly' => 490, // ~40.83/month
                'currency' => 'USD',
            ],
            default => [
                'monthly' => 0,
                'yearly' => 0,
                'currency' => 'USD',
            ],
        };
    }
}
