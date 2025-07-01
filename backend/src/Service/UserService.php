<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserSubscription;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use App\DTO\RegisterRequestDTO;

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
        private string $avatarUploadDirectory,
        private DatabasePlanService $planService,
        private SubscriptionConstraintService $constraintService
    ) {
    }

    public function updateProfile(User $user, array $profileData): User
    {
        // Sanitize and validate input data
        $profileData = $this->sanitizeProfileData($profileData);
        
        // Update basic profile fields
        if (isset($profileData['firstName'])) {
            $user->setFirstName($profileData['firstName']);
        }
        
        if (isset($profileData['lastName'])) {
            $user->setLastName($profileData['lastName']);
        }
        
        if (isset($profileData['username'])) {
            // Check if username is unique (excluding current user)
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['username' => $profileData['username']]);
            
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                throw new \InvalidArgumentException('Username is already taken');
            }
            
            $user->setUsername($profileData['username']);
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

    /**
     * Sanitize profile data to prevent XSS and other security issues
     */
    private function sanitizeProfileData(array $profileData): array
    {
        $sanitized = [];
        
        foreach ($profileData as $key => $value) {
            if ($value === null) {
                $sanitized[$key] = null;
                continue;
            }
            
            switch ($key) {
                case 'firstName':
                case 'lastName':
                case 'username':
                case 'jobTitle':
                case 'company':
                case 'timezone':
                case 'language':
                    // Basic string sanitization - trim and remove dangerous characters
                    $sanitized[$key] = trim(strip_tags((string) $value));
                    break;
                    
                case 'website':
                case 'portfolio':
                    // URL sanitization
                    $url = trim(strip_tags((string) $value));
                    if (!empty($url) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                        $url = 'https://' . $url;
                    }
                    $sanitized[$key] = filter_var($url, FILTER_SANITIZE_URL) ?: null;
                    break;
                    
                case 'bio':
                    // Allow basic formatting but strip dangerous tags
                    $sanitized[$key] = trim(strip_tags((string) $value, '<p><br><strong><em><ul><ol><li>'));
                    break;
                    
                case 'socialLinks':
                    // Sanitize social links array (platform=url format)
                    if (is_array($value)) {
                        $sanitizedLinks = [];
                        foreach ($value as $platform => $url) {
                            $cleanPlatform = trim(strip_tags((string) $platform));
                            $cleanUrl = trim(strip_tags((string) $url));
                            
                            // Skip empty URLs - they are optional
                            if (empty($cleanUrl)) {
                                continue;
                            }
                            
                            // Ensure URL has protocol if not empty
                            if (!str_starts_with($cleanUrl, 'http://') && !str_starts_with($cleanUrl, 'https://')) {
                                $cleanUrl = 'https://' . $cleanUrl;
                            }
                            
                            // Only add if platform name is valid and URL is valid
                            if (preg_match('/^[a-zA-Z0-9_-]+$/', $cleanPlatform) && filter_var($cleanUrl, FILTER_VALIDATE_URL)) {
                                $sanitizedLinks[$cleanPlatform] = $cleanUrl;
                            }
                        }
                        $sanitized[$key] = $sanitizedLinks;
                    } else {
                        $sanitized[$key] = [];
                    }
                    break;
                    
                default:
                    // For any unknown fields, just sanitize as string
                    $sanitized[$key] = trim(strip_tags((string) $value));
                    break;
            }
        }
        
        return $sanitized;
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

    /**
     * Generate comprehensive data export for a user
     */
    public function generateComprehensiveDataExport(User $user): array
    {
        $this->logger->info('Starting comprehensive data export', [
            'user_id' => $user->getId()
        ]);
        
        $exportData = [
            'user_profile' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'bio' => $user->getBio(),
                'portfolio' => $user->getPortfolio(),
                'social_links' => $user->getSocialLinks(),
                'is_verified' => $user->isVerified(),
                'email_verified' => $user->getEmailVerified(),
                'is_active' => $user->getIsActive(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'last_login_at' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
                'avatar' => $user->getAvatar(),
                'plan' => $user->getPlan(),
                'settings' => $user->getSettings(),
                'roles' => $user->getRoles()
            ],
            'subscription_data' => [],
            'designs' => [],
            'templates' => [],
            'media_files' => [],
            'audit_logs' => [],
            'export_metadata' => [
                'generated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'format_version' => '1.0',
                'privacy_policy_version' => '1.0'
            ]
        ];

        // Get subscription data
        $subscriptions = $user->getSubscriptions();
        foreach ($subscriptions as $subscription) {
            $exportData['subscription_data'][] = [
                'id' => $subscription->getId(),
                'plan_code' => $subscription->getPlan()?->getCode(),
                'plan_name' => $subscription->getPlan()?->getName(),
                'status' => $subscription->getStatus(),
                'start_date' => $subscription->getStartDate()?->format('Y-m-d H:i:s'),
                'end_date' => $subscription->getEndDate()?->format('Y-m-d H:i:s'),
                'created_at' => $subscription->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $subscription->getUpdatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        // Get user designs (if Design entity exists)
        try {
            $designRepository = $this->entityManager->getRepository('App\Entity\Design');
            if ($designRepository) {
                $designs = $designRepository->findBy(['user' => $user]);
                foreach ($designs as $design) {
                    $exportData['designs'][] = [
                        'id' => $design->getId(),
                        'title' => $design->getTitle(),
                        'name' => $design->getName(),
                        'description' => $design->getDescription(),
                        'data' => $design->getData(),
                        'width' => $design->getWidth(),
                        'height' => $design->getHeight(),
                        'canvas_width' => $design->getCanvasWidth(),
                        'canvas_height' => $design->getCanvasHeight(),
                        'background' => $design->getBackground(),
                        'created_at' => $design->getCreatedAt()?->format('Y-m-d H:i:s'),
                        'updated_at' => $design->getUpdatedAt()?->format('Y-m-d H:i:s')
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Could not export designs', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }

        // Get user templates (if Template entity exists)
        try {
            $templateRepository = $this->entityManager->getRepository('App\Entity\Template');
            if ($templateRepository) {
                $templates = $templateRepository->findBy(['user' => $user]);
                foreach ($templates as $template) {
                    $exportData['templates'][] = [
                        'id' => $template->getId(),
                        'uuid' => $template->getUuid()?->__toString(),
                        'name' => $template->getName(),
                        'description' => $template->getDescription(),
                        'category' => $template->getCategory(),
                        'tags' => $template->getTags(),
                        'width' => $template->getWidth(),
                        'height' => $template->getHeight(),
                        'canvas_settings' => $template->getCanvasSettings(),
                        'layers' => $template->getLayers(),
                        'thumbnail_url' => $template->getThumbnailUrl(),
                        'created_at' => $template->getCreatedAt()?->format('Y-m-d H:i:s'),
                        'updated_at' => $template->getUpdatedAt()?->format('Y-m-d H:i:s')
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Could not export templates', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }

        // Get user media files (if MediaFile entity exists)
        try {
            $mediaRepository = $this->entityManager->getRepository('App\Entity\MediaFile');
            if ($mediaRepository) {
                $mediaFiles = $mediaRepository->findBy(['user' => $user]);
                foreach ($mediaFiles as $media) {
                    $exportData['media_files'][] = [
                        'id' => $media->getId(),
                        'filename' => $media->getFilename(),
                        'original_name' => $media->getOriginalName(),
                        'mime_type' => $media->getMimeType(),
                        'size' => $media->getSize(),
                        'path' => $media->getPath(),
                        'created_at' => $media->getCreatedAt()?->format('Y-m-d H:i:s')
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Could not export media files', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }

        // Get audit logs (if AuditLog entity exists)
        try {
            $auditRepository = $this->entityManager->getRepository('App\Entity\AuditLog');
            if ($auditRepository) {
                $auditLogs = $auditRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 100); // Last 100 entries
                foreach ($auditLogs as $log) {
                    $exportData['audit_logs'][] = [
                        'id' => $log->getId(),
                        'action' => $log->getAction(),
                        'ip_address' => $log->getIpAddress(),
                        'user_agent' => $log->getUserAgent(),
                        'metadata' => $log->getMetadata(),
                        'created_at' => $log->getCreatedAt()?->format('Y-m-d H:i:s')
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Could not export audit logs', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }

        $this->logger->info('Comprehensive data export completed', [
            'user_id' => $user->getId(),
            'export_size' => strlen(json_encode($exportData))
        ]);

        return $exportData;
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

    /**
     * Perform account deletion (soft or hard delete)
     */
    public function performAccountDeletion(User $user, bool $hardDelete = false): void
    {
        $userId = $user->getId();
        $userEmail = $user->getEmail();
        
        $this->logger->info('Starting account deletion process', [
            'user_id' => $userId,
            'user_email' => $userEmail,
            'hard_delete' => $hardDelete
        ]);

        if ($hardDelete) {
            // Hard delete: Remove all user data permanently
            $this->performHardAccountDeletion($user);
        } else {
            // Soft delete: Anonymize user data but keep records for compliance
            $this->performSoftAccountDeletion($user);
        }

        $this->logger->info('Account deletion completed', [
            'user_id' => $userId,
            'user_email' => $userEmail,
            'hard_delete' => $hardDelete
        ]);
    }

    /**
     * Perform hard account deletion - permanently remove all user data
     */
    private function performHardAccountDeletion(User $user): void
    {
        $userId = $user->getId();
        
        // Delete related entities in correct order to avoid foreign key constraints
        
        // Delete user designs
        try {
            $designRepository = $this->entityManager->getRepository('App\Entity\Design');
            if ($designRepository) {
                $designs = $designRepository->findBy(['user' => $user]);
                foreach ($designs as $design) {
                    $this->entityManager->remove($design);
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error deleting user designs', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        // Delete user templates
        try {
            $templateRepository = $this->entityManager->getRepository('App\Entity\Template');
            if ($templateRepository) {
                $templates = $templateRepository->findBy(['user' => $user]);
                foreach ($templates as $template) {
                    $this->entityManager->remove($template);
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error deleting user templates', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        // Delete user media files
        try {
            $mediaRepository = $this->entityManager->getRepository('App\Entity\MediaFile');
            if ($mediaRepository) {
                $mediaFiles = $mediaRepository->findBy(['user' => $user]);
                foreach ($mediaFiles as $media) {
                    // Delete physical file if it exists
                    $filePath = $media->getPath();
                    if ($filePath && file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $this->entityManager->remove($media);
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error deleting user media files', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        // Delete user subscriptions
        $subscriptions = $user->getSubscriptions();
        foreach ($subscriptions as $subscription) {
            $this->entityManager->remove($subscription);
        }

        // Delete audit logs
        try {
            $auditRepository = $this->entityManager->getRepository('App\Entity\AuditLog');
            if ($auditRepository) {
                $auditLogs = $auditRepository->findBy(['user' => $user]);
                foreach ($auditLogs as $log) {
                    $this->entityManager->remove($log);
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error deleting audit logs', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        // Delete user avatar file if it exists
        if ($user->getAvatar()) {
            $avatarPath = $this->avatarUploadDirectory . '/' . $user->getAvatar();
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
        }

        // Finally, delete the user entity
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * Perform soft account deletion - anonymize user data but keep records
     */
    private function performSoftAccountDeletion(User $user): void
    {
        $userId = $user->getId();
        $anonymousId = 'deleted_user_' . $userId . '_' . time();
        
        // Anonymize user data
        $user->setEmail($anonymousId . '@deleted.local');
        $user->setUsername($anonymousId);
        $user->setFirstName('Deleted');
        $user->setLastName('User');
        $user->setBio(null);
        $user->setPortfolio(null);
        $user->setSocialLinks([]);
        $user->setPassword(''); // Clear password
        $user->setRoles(['ROLE_DELETED']);
        $user->setIsVerified(false);
        $user->setEmailVerified(false);
        $user->setPasswordResetToken(null);
        $user->setPasswordResetTokenExpiresAt(null);
        $user->setEmailVerificationToken(null);
        
        // Delete avatar file if it exists
        if ($user->getAvatar()) {
            $avatarPath = $this->avatarUploadDirectory . '/' . $user->getAvatar();
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
            $user->setAvatar(null);
        }
        
        // Mark user as deleted with timestamp using the touch method
        // The touch() method will update the updatedAt automatically
        
        // Anonymize user designs
        try {
            $designRepository = $this->entityManager->getRepository('App\Entity\Design');
            if ($designRepository) {
                $designs = $designRepository->findBy(['user' => $user]);
                foreach ($designs as $design) {
                    $design->setTitle('Deleted Design');
                    $design->setName('Deleted Design');
                    $design->setDescription('Design from deleted user account');
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error anonymizing user designs', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        // Cancel all active subscriptions
        $subscriptions = $user->getSubscriptions();
        foreach ($subscriptions as $subscription) {
            if ($subscription->getStatus() === 'active') {
                $subscription->setStatus('cancelled');
                $subscription->setEndDate(new \DateTime());
            }
        }

        $this->entityManager->flush();
    }

    private function sanitizeInput(string $input): string
    {
        $sanitized = strip_tags(trim($input));
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $sanitized;
    }

    private function calculateStorageUsage(User $user): int
    {
        $totalSize = 0;
        foreach ($user->getMediaFiles() as $media) {
            $totalSize += $media->getSize();
        }
        return $totalSize;
    }

    /**
     * Assign the default subscription plan to a user
     * 
     * @param User $user The user to assign the plan to
     * @throws \Exception If no default plan is found or assignment fails
     */
    private function assignDefaultPlanToUser(User $user): void
    {
        // Get the default plan entity
        $defaultPlan = $this->planService->getDefaultPlanEntity();
        
        if (!$defaultPlan) {
            throw new \RuntimeException('No default subscription plan found');
        }
        
        // Create user subscription
        $userSubscription = new UserSubscription();
        $userSubscription->setUser($user);
        $userSubscription->setSubscriptionPlan($defaultPlan);
        $userSubscription->setStatus('active');
        $userSubscription->setBillingPeriod('monthly');
        $userSubscription->setStartDate(new \DateTimeImmutable());
        // No end date for free plan
        $userSubscription->setEndDate(null);
        
        // Validate the subscription
        $errors = $this->validator->validate($userSubscription);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException('Subscription validation failed: ' . implode(', ', $errorMessages));
        }
        
        $this->entityManager->persist($userSubscription);
        $this->entityManager->flush();
        
        $this->logger->info('Default plan assigned successfully', [
            'user_id' => $user->getId(),
            'plan_id' => $defaultPlan->getId(),
            'plan_code' => $defaultPlan->getCode()
        ]);
    }

    /**
     * Get comprehensive subscription data for a user
     * Returns data structure that matches frontend UserSubscription interface
     */
    public function getSubscriptionData(User $user): array
    {
        $subscriptions = $user->getSubscriptions();
        $activeSubscription = null;
        
        // Find active subscription
        foreach ($subscriptions as $subscription) {
            if ($subscription->getStatus() === 'active') {
                $activeSubscription = $subscription;
                break;
            }
        }
        
        $isActive = $activeSubscription !== null;
        $userPlan = $user->getPlan() ?? 'free';
        
        // Initialize default values
        $planInfo = [
            'name' => 'Free Plan',
            'description' => 'Basic plan with essential features',
            'price' => [
                'monthly' => 0,
                'yearly' => 0,
                'currency' => 'USD'
            ]
        ];
        
        $limits = [
            'storage' => 1073741824, // 1GB for free plan
            'projects' => 5,
            'exports' => 10,
            'media_files' => 50
        ];
        
        $features = [
            'basic_templates' => true,
            'cloud_storage' => true,
            'standard_exports' => true,
            'premium_templates' => false,
            'priority_support' => false,
            'team_collaboration' => false,
            'advanced_exports' => false,
            'custom_branding' => false
        ];
        
        // If user has active subscription, get plan details
        if ($activeSubscription) {
            $plan = $activeSubscription->getPlan();
            
            if ($plan) {
                $planInfo = [
                    'name' => $plan->getName(),
                    'description' => $plan->getDescription(),
                    'price' => [
                        'monthly' => (float) $plan->getMonthlyPrice(),
                        'yearly' => (float) $plan->getYearlyPrice(),
                        'currency' => $plan->getCurrency() ?? 'USD'
                    ]
                ];
                
                // Get plan limits
                $planLimits = [];
                foreach ($plan->getLimits() as $limit) {
                    $planLimits[$limit->getType()] = $limit->getValue();
                }
                if (!empty($planLimits)) {
                    $limits = array_merge($limits, $planLimits);
                }
                
                // Get plan features
                $planFeatures = [];
                foreach ($plan->getFeatures() as $feature) {
                    $planFeatures[$feature->getCode()] = $feature->isEnabled();
                }
                if (!empty($planFeatures)) {
                    $features = array_merge($features, $planFeatures);
                }
            }
        }
        
        // Get current usage statistics
        $projects = $user->getProjects();
        $mediaFiles = $user->getMediaFiles();
        
        $usage = [
            'projects' => count($projects),
            'mediaFiles' => count($mediaFiles),
            'storageUsed' => $this->calculateStorageUsed($user),
            'exportJobs' => 0 // TODO: Implement export job tracking
        ];
        
        // Return structure matching frontend UserSubscription interface
        return [
            'plan' => $userPlan,
            'isActive' => $isActive,
            'usage' => $usage,
            'limits' => $limits,
            'features' => $features,
            'planInfo' => $planInfo
        ];
    }

    /**
     * Calculate storage used by user
     */
    private function calculateStorageUsed(User $user): int
    {
        $totalSize = 0;
        
        foreach ($user->getMediaFiles() as $mediaFile) {
            $totalSize += $mediaFile->getSize() ?? 0;
        }
        
        return $totalSize;
    }

    /**
     * Register a new user account
     * 
     * Creates a new user with the provided information and returns the user entity.
     * Generates email verification token and sends welcome/verification emails.
     * 
     * @param RegisterRequestDTO $dto User registration data
     * @return User The created user entity
     * @throws \InvalidArgumentException If validation fails or user already exists
     */
    public function registerUser(RegisterRequestDTO $dto): User
    {
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            throw new \InvalidArgumentException('User with this email already exists');
        }
        if ($dto->username && $this->userRepository->findOneBy(['username' => $dto->username])) {
            throw new \InvalidArgumentException('Username is already taken');
        }
        
        $user = new User();
        $user->setEmail($dto->email);
        $user->setFirstName($this->sanitizeInput($dto->firstName));
        $user->setLastName($this->sanitizeInput($dto->lastName));
        if ($dto->username) {
            $user->setUsername(trim($dto->username));
        }
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setEmailVerified(false);
        
        // Generate email verification token
        $verificationToken = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($verificationToken);
        $user->setEmailVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
        
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException(implode(", ", $errorMessages));
        }
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Send welcome email
        try {
            $this->emailService->sendWelcome($user);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to send welcome email during registration', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
        
        // Send email verification
        try {
            $this->emailService->sendEmailVerification($user, $verificationToken);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to send verification email during registration', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
        
        return $user;
    }

    /**
     * Authenticate user with email and password
     * 
     * Validates user credentials and returns the user entity if authentication succeeds.
     * 
     * @param string $email User's email address
     * @param string $password User's password
     * @return User|null The authenticated user or null if authentication fails
     */
    public function authenticateUser(string $email, string $password): ?User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }
        return $user;
    }

    /**
     * Request password reset
     * 
     * Generates a password reset token and sends reset email.
     * Does not reveal if the email exists for security reasons.
     * 
     * @param string $email User's email address
     */
    public function requestPasswordReset(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            // Do not reveal user existence
            return;
        }
        
        $token = bin2hex(random_bytes(32));
        $user->setPasswordResetToken($token);
        $user->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        
        $this->entityManager->flush();
        
        $this->emailService->sendPasswordResetEmail($user, $token);
    }

    /**
     * Reset password using token
     * 
     * Validates the reset token and updates the user's password.
     * 
     * @param string $token Password reset token
     * @param string $newPassword New password
     * @throws \InvalidArgumentException If token is invalid/expired or password is weak
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $user = $this->userRepository->findOneBy(['passwordResetToken' => $token]);
        if (!$user) {
            throw new \InvalidArgumentException('Invalid or expired reset token');
        }
        
        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if (!$expiresAt || $expiresAt < new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('Reset token expired');
        }
        
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setPasswordResetToken(null);
        $user->setPasswordResetTokenExpiresAt(null);
        
        $this->entityManager->flush();
        
        $this->logger->info('User password reset', ['user_id' => $user->getId()]);
    }

    /**
     * Verify email using token
     * 
     * Validates the email verification token and marks the email as verified.
     * 
     * @param string $token Email verification token
     * @return bool True if verification succeeds, false otherwise
     */
    public function verifyEmail(string $token): bool
    {
        $user = $this->userRepository->findOneBy(['emailVerificationToken' => $token]);
        if (!$user) {
            return false;
        }
        
        if (!$user->isEmailVerificationTokenValid()) {
            return false;
        }
        
        $user->setEmailVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);
        
        $this->entityManager->flush();
        
        $this->logger->info('Email verified successfully', [
            'user_id' => $user->getId()
        ]);
        
        return true;
    }

    /**
     * Resend email verification
     * 
     * Generates a new verification token and sends verification email.
     * Does not reveal if the email exists for security reasons.
     * 
     * @param string $email User's email address
     * @throws \InvalidArgumentException If email is already verified
     */
    public function resendEmailVerification(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            // Don't reveal user existence
            return;
        }
        
        if ($user->getEmailVerified()) {
            throw new \InvalidArgumentException('Email is already verified');
        }
        
        // Generate new verification token
        $verificationToken = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($verificationToken);
        $user->setEmailVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
        
        $this->entityManager->flush();
        
        // Send verification email
        $this->emailService->sendEmailVerification($user, $verificationToken);
        
        $this->logger->info('Email verification resent', [
            'user_id' => $user->getId()
        ]);
    }
}
