<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'error' => 'Invalid JSON data'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Check if user already exists
            if ($this->userRepository->findOneBy(['email' => $data['email'] ?? ''])) {
                return $this->json([
                    'error' => 'User with this email already exists'
                ], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($data['email'] ?? '');
            $user->setName($data['name'] ?? '');
            $user->setUsername($data['username'] ?? '');
            
            if (isset($data['password'])) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }

            // Set default values
            $user->setRoles(['ROLE_USER']);
            $user->setIsActive(true);
            $user->setEmailVerified(false);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());

            // Validate user
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                return $this->json([
                    'error' => 'Validation failed',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            return $this->json([
                'message' => 'User registered successfully',
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['email']) || !isset($data['password'])) {
                return $this->json([
                    'error' => 'Email and password are required'
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->userRepository->findOneBy(['email' => $data['email']]);

            if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
                return $this->json([
                    'error' => 'Invalid credentials'
                ], Response::HTTP_UNAUTHORIZED);
            }

            if (!$user->getIsActive()) {
                return $this->json([
                    'error' => 'Account is deactivated'
                ], Response::HTTP_FORBIDDEN);
            }

            // Update last login
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            return $this->json([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                    'avatar' => $user->getAvatar(),
                    'plan' => $user->getPlan(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Login failed',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'avatar' => $user->getAvatar(),
                'plan' => $user->getPlan(),
                'emailVerified' => $user->getEmailVerified(),
                'createdAt' => $user->getCreatedAt()->format('c'),
                'lastLoginAt' => $user->getLastLoginAt()?->format('c'),
                'settings' => $user->getSettings(),
                'stats' => [
                    'projectCount' => count($user->getProjects()),
                    'designCount' => $user->getProjects()->map(fn($p) => count($p->getDesigns()))->reduce(fn($a, $b) => $a + $b, 0),
                ]
            ]
        ]);
    }

    #[Route('/profile', name: 'update_profile', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                return $this->json([
                    'error' => 'User not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'error' => 'Invalid JSON data'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Update allowed fields
            if (isset($data['name'])) {
                $user->setName($data['name']);
            }

            if (isset($data['username'])) {
                // Check if username is already taken
                $existingUser = $this->userRepository->findOneBy(['username' => $data['username']]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    return $this->json([
                        'error' => 'Username is already taken'
                    ], Response::HTTP_CONFLICT);
                }
                $user->setUsername($data['username']);
            }

            if (isset($data['avatar'])) {
                $user->setAvatar($data['avatar']);
            }

            if (isset($data['settings']) && is_array($data['settings'])) {
                $user->setSettings($data['settings']);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());

            // Validate user
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                return $this->json([
                    'error' => 'Validation failed',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername(),
                    'avatar' => $user->getAvatar(),
                    'settings' => $user->getSettings(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Profile update failed',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/change-password', name: 'change_password', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                return $this->json([
                    'error' => 'User not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['currentPassword']) || !isset($data['newPassword'])) {
                return $this->json([
                    'error' => 'Current password and new password are required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                return $this->json([
                    'error' => 'Current password is incorrect'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate new password strength
            if (strlen($data['newPassword']) < 8) {
                return $this->json([
                    'error' => 'Password must be at least 8 characters long'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Hash and set new password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($hashedPassword);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Password change failed',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function logout(): JsonResponse
    {
        // With JWT, logout is handled client-side by removing the token
        // We could implement token blacklisting here if needed
        return $this->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
