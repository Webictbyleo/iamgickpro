<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Uid\Uuid;

/**
 * Service for authentication and user management
 */
class AuthService
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900; // 15 minutes in seconds
    private const TOKEN_EXPIRY = 3600; // 1 hour in seconds

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Register a new user
     */
    public function register(
        string $email,
        string $password,
        string $firstName,
        string $lastName
    ): User {
        // Check if user already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            throw new \InvalidArgumentException('User with this email already exists');
        }

        $user = new User();
        $user->setEmail($email)
             ->setFirstName($firstName)
             ->setLastName($lastName)
             ->setRoles(['ROLE_USER']);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Generate email verification token
        $verificationToken = $this->generateToken();
        $user->setEmailVerificationToken($verificationToken)
             ->setEmailVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Authenticate user with email and password
     */
    public function authenticate(string $email, string $password): User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            throw new UserNotFoundException('Invalid credentials');
        }

        // Check if account is locked
        if ($this->isAccountLocked($user)) {
            $lockUntil = $user->getLockedUntil();
            $remainingTime = $lockUntil ? $lockUntil->getTimestamp() - time() : 0;
            throw new \RuntimeException(sprintf('Account locked. Try again in %d minutes.', ceil($remainingTime / 60)));
        }

        // Verify password
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            $this->handleFailedLogin($user);
            throw new UserNotFoundException('Invalid credentials');
        }

        // Reset failed attempts on successful login
        $this->resetFailedLoginAttempts($user);

        return $user;
    }

    /**
     * Change user password
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new \InvalidArgumentException('Current password is incorrect');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(string $email): User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            throw new UserNotFoundException('User not found');
        }

        $resetToken = $this->generateToken();
        $user->setPasswordResetToken($resetToken)
             ->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): User
    {
        $user = $this->userRepository->findOneBy(['passwordResetToken' => $token]);
        
        if (!$user) {
            throw new \InvalidArgumentException('Invalid reset token');
        }

        if ($user->getPasswordResetTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('Reset token has expired');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword)
             ->setPasswordResetToken(null)
             ->setPasswordResetTokenExpiresAt(null);

        // Reset failed login attempts
        $this->resetFailedLoginAttempts($user);

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token): User
    {
        $user = $this->userRepository->findOneBy(['emailVerificationToken' => $token]);
        
        if (!$user) {
            throw new \InvalidArgumentException('Invalid verification token');
        }

        if ($user->getEmailVerificationTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('Verification token has expired');
        }

        $user->setEmailVerified(true)
             ->setEmailVerificationToken(null)
             ->setEmailVerificationTokenExpiresAt(null);

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Resend email verification
     */
    public function resendEmailVerification(User $user): void
    {
        if ($user->isEmailVerified()) {
            throw new \InvalidArgumentException('Email is already verified');
        }

        $verificationToken = $this->generateToken();
        $user->setEmailVerificationToken($verificationToken)
             ->setEmailVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->entityManager->flush();
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        if (isset($data['avatar'])) {
            $user->setAvatar($data['avatar']);
        }

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Check if account is locked
     */
    private function isAccountLocked(User $user): bool
    {
        $lockedUntil = $user->getLockedUntil();
        
        if (!$lockedUntil) {
            return false;
        }

        if ($lockedUntil <= new \DateTimeImmutable()) {
            // Lock period expired, unlock account
            $user->setLockedUntil(null)
                 ->setFailedLoginAttempts(0);
            $this->entityManager->flush();
            return false;
        }

        return true;
    }

    /**
     * Handle failed login attempt
     */
    private function handleFailedLogin(User $user): void
    {
        $attempts = $user->getFailedLoginAttempts() + 1;
        $user->setFailedLoginAttempts($attempts);

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $lockUntil = new \DateTimeImmutable('+' . self::LOCKOUT_DURATION . ' seconds');
            $user->setLockedUntil($lockUntil);
        }

        $this->entityManager->flush();
    }

    /**
     * Reset failed login attempts
     */
    private function resetFailedLoginAttempts(User $user): void
    {
        $user->setFailedLoginAttempts(0)
             ->setLockedUntil(null);

        $this->entityManager->flush();
    }

    /**
     * Generate secure token
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }

    /**
     * Check if user exists
     */
    public function userExists(string $email): bool
    {
        return $this->userRepository->findOneBy(['email' => $email]) !== null;
    }

    /**
     * Activate user account
     */
    public function activateUser(User $user): void
    {
        $user->setIsActive(true);
        $this->entityManager->flush();
    }

    /**
     * Deactivate user account
     */
    public function deactivateUser(User $user): void
    {
        $user->setIsActive(false);
        $this->entityManager->flush();
    }

    /**
     * Grant role to user
     */
    public function grantRole(User $user, string $role): void
    {
        $roles = $user->getRoles();
        
        if (!in_array($role, $roles)) {
            $roles[] = $role;
            $user->setRoles($roles);
            $this->entityManager->flush();
        }
    }

    /**
     * Revoke role from user
     */
    public function revokeRole(User $user, string $role): void
    {
        $roles = $user->getRoles();
        $key = array_search($role, $roles);
        
        if ($key !== false) {
            unset($roles[$key]);
            $user->setRoles(array_values($roles));
            $this->entityManager->flush();
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'full_name' => $user->getFirstName() . ' ' . $user->getLastName(),
            'email_verified' => $user->isEmailVerified(),
            'active' => $user->isActive(),
            'roles' => $user->getRoles(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
            'failed_login_attempts' => $user->getFailedLoginAttempts(),
            'locked_until' => $user->getLockedUntil(),
        ];
    }
}
