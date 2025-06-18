<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\RegisterRequestDTO;
use App\DTO\LoginRequestDTO;
use App\DTO\ChangePasswordRequestDTO;
use App\DTO\Response\AuthResponseDTO;
use App\DTO\Response\ErrorResponseDTO;
use App\DTO\Response\SuccessResponseDTO;
use App\DTO\Response\UserProfileResponseDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ResponseDTOFactory;
use App\Controller\Trait\TypedResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Authentication Controller
 * 
 * Handles user authentication, registration, profile management, and password operations.
 * All endpoints return JSON responses with consistent error handling.
 */
#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    use TypedResponseTrait;
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ResponseDTOFactory $responseDTOFactory,
    ) {}

    /**
     * Register a new user account
     * 
     * Creates a new user with the provided information and returns a JWT token for immediate authentication.
     * 
     * @param RegisterRequestDTO $dto User registration data
     * @return JsonResponse<AuthResponseDTO|ErrorResponseDTO> User data with JWT token or error response
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(RegisterRequestDTO $dto): JsonResponse
    {
        try {
            // Check if user already exists
            if ($this->userRepository->findOneBy(['email' => $dto->email])) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'User with this email already exists'
                );
                return $this->errorResponse($errorResponse, Response::HTTP_CONFLICT);
            }

            // Check if username is already taken (if provided)
            if ($dto->username && $this->userRepository->findOneBy(['username' => $dto->username])) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Username is already taken'
                );
                return $this->errorResponse($errorResponse, Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($dto->email);
            $user->setFirstName($this->sanitizeInput($dto->firstName));
            $user->setLastName($this->sanitizeInput($dto->lastName));
            
            if ($dto->username) {
                $user->setUsername(trim($dto->username)); // Username doesn't need HTML sanitization
            }
            
            $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
            $user->setPassword($hashedPassword);

            // Set default values
            $user->setRoles(['ROLE_USER']);
            $user->setIsActive(true);
            $user->setEmailVerified(false);
            // Note: createdAt is set automatically in constructor
            // updatedAt is set automatically via touch() method in setters

            // Validate entity (additional business rules)
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'User validation failed',
                    $errorMessages
                );
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            $authResponse = $this->responseDTOFactory->createAuthResponse(
                'User registered successfully',
                $token,
                $user
            );

            return $this->authResponse($authResponse, Response::HTTP_CREATED);

        } catch (BadRequestHttpException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Registration failed',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Authenticate user and return JWT token
     * 
     * Validates user credentials and returns a JWT token with user information.
     * Updates the user's last login timestamp.
     * 
     * @param LoginRequestDTO $dto User credentials (email and password)
     * @return JsonResponse<AuthResponseDTO|ErrorResponseDTO> JWT token with user data or error response
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(LoginRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->userRepository->findOneBy(['email' => $dto->email]);

            if (!$user || !$this->passwordHasher->isPasswordValid($user, $dto->password)) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Invalid credentials');
                return $this->errorResponse($errorResponse, Response::HTTP_UNAUTHORIZED);
            }

            if (!$user->getIsActive()) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('Account is deactivated');
                return $this->errorResponse($errorResponse, Response::HTTP_FORBIDDEN);
            }

            // Update last login timestamp
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            $authResponse = $this->responseDTOFactory->createAuthResponse(
                'Login successful',
                $token,
                $user
            );

            return $this->authResponse($authResponse);

        } catch (BadRequestHttpException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Login failed',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get current authenticated user profile
     * 
     * Returns detailed information about the currently authenticated user,
     * including profile data, statistics, and account status.
     * 
     * @return JsonResponse<UserProfileResponseDTO|ErrorResponseDTO> User profile data or error response
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            $profileResponse = $this->responseDTOFactory->createUserProfileResponse($user);
            return $this->userProfileResponse($profileResponse);

        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Failed to retrieve user profile',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Change user password
     * 
     * Updates the authenticated user's password after validating the current password.
     * Enforces password strength requirements.
     * 
     * @param ChangePasswordRequestDTO $dto Current and new password data
     * @return JsonResponse<SuccessResponseDTO|ErrorResponseDTO> Success message or error response
     */
    #[Route('/change-password', name: 'change_password', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function changePassword(ChangePasswordRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse('User not found');
                return $this->errorResponse($errorResponse, Response::HTTP_NOT_FOUND);
            }

            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $dto->currentPassword)) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'Current password is incorrect'
                );
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            // Ensure new password is different from current
            if ($this->passwordHasher->isPasswordValid($user, $dto->newPassword)) {
                $errorResponse = $this->responseDTOFactory->createErrorResponse(
                    'New password must be different from current password'
                );
                return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
            }

            // Hash and set new password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->newPassword);
            $user->setPassword($hashedPassword);

            $this->entityManager->flush();

            $successResponse = $this->responseDTOFactory->createSuccessResponse(
                'Password changed successfully'
            );

            return $this->successResponse($successResponse);

        } catch (BadRequestHttpException $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse($e->getMessage());
            return $this->errorResponse($errorResponse, Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Password change failed',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout user
     * 
     * Since JWT tokens are stateless, logout is primarily handled client-side by removing the token.
     * This endpoint provides a standardized logout response and could be extended with token blacklisting.
     * 
     * @return JsonResponse<SuccessResponseDTO|ErrorResponseDTO> Success message
     */
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function logout(): JsonResponse
    {
        try {
            // With JWT, logout is handled client-side by removing the token
            // Future enhancement: implement token blacklisting here if needed
            
            $successResponse = $this->responseDTOFactory->createSuccessResponse(
                'Logged out successfully'
            );
            
            return $this->successResponse($successResponse);
            
        } catch (\Exception $e) {
            $errorResponse = $this->responseDTOFactory->createErrorResponse(
                'Logout failed',
                [$e->getMessage()]
            );
            return $this->errorResponse($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Sanitize user input to prevent XSS attacks
     */
    private function sanitizeInput(string $input): string
    {
        // Remove HTML tags and encode special characters
        $sanitized = strip_tags(trim($input));
        
        // Additional encoding for special characters
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $sanitized;
    }
}
