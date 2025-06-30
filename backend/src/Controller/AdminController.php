<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Response\ErrorResponseDTO;
use App\Entity\User;
use App\Entity\SubscriptionPlan;
use App\Repository\UserRepository;
use App\Service\ResponseDTOFactory;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
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
 * Admin Controller
 * 
 * Provides administrative endpoints for user management, system monitoring,
 * and platform administration. All endpoints require ROLE_ADMIN access.
 */
#[Route('/api/admin', name: 'api_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly ResponseDTOFactory $responseDTOFactory,
        private readonly AuthService $authService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get all users with pagination and filtering
     * 
     * Returns a paginated list of all platform users with filtering options
     * for user management tasks.
     * 
     * @param Request $request HTTP request with pagination and filter parameters
     * @return JsonResponse Paginated user list or error response
     */
    #[Route('/users', name: 'users_list', methods: ['GET'])]
    public function listUsers(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
            $search = $request->query->get('search', '');
            $status = $request->query->get('status', ''); // active, inactive, verified, unverified
            $role = $request->query->get('role', ''); // admin, user

            $queryBuilder = $this->userRepository->createQueryBuilder('u')
                ->where('u.deletedAt IS NULL')
                ->orderBy('u.createdAt', 'DESC');

            // Apply search filter
            if (!empty($search)) {
                $queryBuilder
                    ->andWhere('u.email LIKE :search OR u.firstName LIKE :search OR u.lastName LIKE :search OR u.username LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            // Apply status filter
            if (!empty($status)) {
                switch ($status) {
                    case 'active':
                        $queryBuilder->andWhere('u.isActive = true');
                        break;
                    case 'inactive':
                        $queryBuilder->andWhere('u.isActive = false');
                        break;
                    case 'verified':
                        $queryBuilder->andWhere('u.emailVerified = true');
                        break;
                    case 'unverified':
                        $queryBuilder->andWhere('u.emailVerified = false');
                        break;
                }
            }

            // Apply role filter
            if (!empty($role)) {
                if ($role === 'admin') {
                    $queryBuilder->andWhere('u.roles LIKE :adminRole')
                        ->setParameter('adminRole', '%ROLE_ADMIN%');
                } elseif ($role === 'user') {
                    $queryBuilder->andWhere('u.roles NOT LIKE :adminRole OR u.roles = :emptyRoles')
                        ->setParameter('adminRole', '%ROLE_ADMIN%')
                        ->setParameter('emptyRoles', '[]');
                }
            }

            // Get total count for pagination
            $totalQuery = clone $queryBuilder;
            $totalCount = $totalQuery->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $users = $queryBuilder
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $usersData = array_map(function (User $user) {
                return $this->formatUserForAdmin($user);
            }, $users);

            return new JsonResponse([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => [
                    'users' => $usersData,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => (int) ceil($totalCount / $limit),
                        'total_items' => (int) $totalCount,
                        'items_per_page' => $limit,
                        'has_next' => $page < ceil($totalCount / $limit),
                        'has_prev' => $page > 1
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve users for admin', [
                'error' => $e->getMessage(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve users'
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific user
     * 
     * @param int $id User ID
     * @return JsonResponse User details or error response
     */
    #[Route('/users/{id}', name: 'user_details', methods: ['GET'])]
    public function getUserDetails(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $userDetails = $this->formatUserDetailsForAdmin($user);

            return new JsonResponse([
                'success' => true,
                'message' => 'User details retrieved successfully',
                'data' => ['user' => $userDetails]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve user details', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve user details'
            ], 500);
        }
    }

    /**
     * Update user status (activate/deactivate)
     * 
     * @param int $id User ID
     * @param Request $request Request containing status change
     * @return JsonResponse Success or error response
     */
    #[Route('/users/{id}/status', name: 'user_update_status', methods: ['PUT'])]
    public function updateUserStatus(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['active'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Active status is required'
                ], 400);
            }

            $isActive = (bool) $data['active'];
            
            if ($isActive) {
                $this->authService->activateUser($user);
                $action = 'activated';
            } else {
                $this->authService->deactivateUser($user);
                $action = 'deactivated';
            }

            $this->logger->info('User status updated by admin', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'action' => $action,
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => "User {$action} successfully",
                'data' => ['user' => $this->formatUserForAdmin($user)]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update user status', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to update user status'
            ], 500);
        }
    }

    /**
     * Update user roles
     * 
     * @param int $id User ID
     * @param Request $request Request containing role changes
     * @return JsonResponse Success or error response
     */
    #[Route('/users/{id}/roles', name: 'user_update_roles', methods: ['PUT'])]
    public function updateUserRoles(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['roles']) || !is_array($data['roles'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Roles array is required'
                ], 400);
            }

            $allowedRoles = ['ROLE_USER', 'ROLE_ADMIN'];
            $newRoles = array_intersect($data['roles'], $allowedRoles);
            
            // Ensure ROLE_USER is always present
            if (!in_array('ROLE_USER', $newRoles)) {
                $newRoles[] = 'ROLE_USER';
            }

            $user->setRoles(array_unique($newRoles));
            $this->entityManager->flush();

            $this->logger->info('User roles updated by admin', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'new_roles' => $newRoles,
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'User roles updated successfully',
                'data' => ['user' => $this->formatUserForAdmin($user)]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update user roles', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to update user roles'
            ], 500);
        }
    }

    /**
     * Get platform statistics
     * 
     * @return JsonResponse Platform stats or error response
     */
    #[Route('/stats', name: 'platform_stats', methods: ['GET'])]
    public function getPlatformStats(): JsonResponse
    {
        try {
            $stats = [
                'users' => [
                    'total' => $this->userRepository->createQueryBuilder('u')
                        ->select('COUNT(u.id)')
                        ->where('u.deletedAt IS NULL')
                        ->getQuery()
                        ->getSingleScalarResult(),
                    'active' => $this->userRepository->createQueryBuilder('u')
                        ->select('COUNT(u.id)')
                        ->where('u.deletedAt IS NULL')
                        ->andWhere('u.isActive = true')
                        ->getQuery()
                        ->getSingleScalarResult(),
                    'verified' => $this->userRepository->createQueryBuilder('u')
                        ->select('COUNT(u.id)')
                        ->where('u.deletedAt IS NULL')
                        ->andWhere('u.emailVerified = true')
                        ->getQuery()
                        ->getSingleScalarResult(),
                    'admins' => $this->userRepository->createQueryBuilder('u')
                        ->select('COUNT(u.id)')
                        ->where('u.deletedAt IS NULL')
                        ->andWhere('u.roles LIKE :adminRole')
                        ->setParameter('adminRole', '%ROLE_ADMIN%')
                        ->getQuery()
                        ->getSingleScalarResult(),
                ],
                'recent_registrations' => $this->userRepository->createQueryBuilder('u')
                    ->select('COUNT(u.id)')
                    ->where('u.deletedAt IS NULL')
                    ->andWhere('u.createdAt >= :lastWeek')
                    ->setParameter('lastWeek', new \DateTimeImmutable('-7 days'))
                    ->getQuery()
                    ->getSingleScalarResult(),
            ];

            return new JsonResponse([
                'success' => true,
                'message' => 'Platform statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve platform stats', [
                'error' => $e->getMessage(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve platform statistics'
            ], 500);
        }
    }

    /**
     * Format user data for admin interface
     */
    private function formatUserForAdmin(User $user): array
    {
        return [
            'id' => $user->getId(),
            'uuid' => $user->getUuid(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'isActive' => $user->getIsActive(),
            'emailVerified' => $user->getEmailVerified(),
            'plan' => $user->getPlan(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'lastLoginAt' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
            'failedLoginAttempts' => $user->getFailedLoginAttempts(),
            'isLocked' => $user->getLockedUntil() && $user->getLockedUntil() > new \DateTimeImmutable(),
        ];
    }

    /**
     * Format detailed user data for admin interface
     */
    private function formatUserDetailsForAdmin(User $user): array
    {
        $basicData = $this->formatUserForAdmin($user);
        
        // Add additional details
        $basicData['jobTitle'] = $user->getJobTitle();
        $basicData['company'] = $user->getCompany();
        $basicData['website'] = $user->getWebsite();
        $basicData['portfolio'] = $user->getPortfolio();
        $basicData['bio'] = $user->getBio();
        $basicData['socialLinks'] = $user->getSocialLinks();
        $basicData['timezone'] = $user->getTimezone();
        $basicData['language'] = $user->getLanguage();
        $basicData['avatar'] = $user->getAvatar();
        $basicData['settings'] = $user->getSettings();
        
        // Add counts
        $basicData['counts'] = [
            'projects' => $user->getProjects()->count(),
            'mediaFiles' => $user->getMediaFiles()->count(),
            'exportJobs' => $user->getExportJobs()->count(),
            'subscriptions' => $user->getSubscriptions()->count(),
        ];
        
        return $basicData;
    }
}
