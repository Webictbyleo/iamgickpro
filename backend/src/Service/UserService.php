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
        $planName = $this->planService->getUserPlanName($user);
        $subscription = $this->planService->getUserSubscription($user);
        
        return [
            'plan' => $planName,
            'isActive' => $user->isActive(),
            'subscription' => $subscription ? [
                'status' => $subscription->getStatus(),
                'start_date' => $subscription->getStartDate()->format('Y-m-d H:i:s'),
                'end_date' => $subscription->getEndDate()?->format('Y-m-d H:i:s'),
                'is_active' => $subscription->isActive()
            ] : null,
            'usage' => [
                'projects' => $user->getProjects()->count(),
                'mediaFiles' => $user->getMediaFiles()->count(),
                'exportJobs' => $user->getExportJobs()->count(),
                'storageUsed' => $this->calculateStorageUsage($user),
            ],
            'limits' => $this->planService->getPlanLimits($planName),
            'features' => $this->planService->getPlanFeatures($planName),
            'planInfo' => [
                'name' => $this->planService->getPlanDisplayName($planName),
                'description' => $this->planService->getPlanDescription($planName),
                'pricing' => $this->planService->getPlanPricing($planName),
            ],
            'constraints' => $this->constraintService->getUserLimitsSummary($user)
        ];
    }

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
        
        // Assign default plan to new user
        try {
            $this->assignDefaultPlanToUser($user);
            $this->logger->info('Default plan assigned to new user', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to assign default plan to new user', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);
            // Don't throw exception here - user registration should succeed even if plan assignment fails
        }
        
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

    public function authenticateUser(string $email, string $password): ?User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }
        return $user;
    }

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
}
