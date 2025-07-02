<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Response\ErrorResponseDTO;
use App\Entity\User;
use App\Entity\SubscriptionPlan;
use App\Entity\PlanLimit;
use App\Entity\PlanFeature;
use App\Repository\UserRepository;
use App\Service\ResponseDTOFactory;
use App\Service\AuthService;
use App\Service\AnalyticsService;
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
        private readonly AnalyticsService $analyticsService,
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

    /**
     * Assign plan to user
     * 
     * @param int $userId User ID
     * @param Request $request Request containing plan assignment data
     * @return JsonResponse Success or error response
     */
    #[Route('/users/{userId}/plan', name: 'user_assign_plan', methods: ['PUT'])]
    public function assignPlanToUser(int $userId, Request $request): JsonResponse
    {
        try {
            $user = $this->userRepository->find($userId);
            
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['plan_code'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Plan code is required'
                ], 400);
            }

            $plan = $this->entityManager->getRepository(SubscriptionPlan::class)
                ->findOneBy(['code' => $data['plan_code']]);

            if (!$plan) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Plan not found'
                ], 404);
            }

            $user->setPlan($plan->getCode());
            $this->entityManager->flush();

            $this->logger->info('Plan assigned to user by admin', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'plan_code' => $plan->getCode(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Plan assigned successfully',
                'data' => ['user' => $this->formatUserForAdmin($user)]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to assign plan to user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to assign plan to user'
            ], 500);
        }
    }

    /**
     * Bulk assign plan to multiple users
     * 
     * @param Request $request Request containing user IDs and plan assignment data
     * @return JsonResponse Success or error response
     */
    #[Route('/users/bulk/plan', name: 'users_bulk_assign_plan', methods: ['PUT'])]
    public function bulkAssignPlanToUsers(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['user_ids']) || !is_array($data['user_ids'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User IDs array is required'
                ], 400);
            }

            if (!isset($data['plan_code'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Plan code is required'
                ], 400);
            }

            $userIds = array_map('intval', $data['user_ids']);
            $planCode = $data['plan_code'];

            // Validate plan exists
            $plan = $this->entityManager->getRepository(SubscriptionPlan::class)
                ->findOneBy(['code' => $planCode]);

            if (!$plan) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Plan not found'
                ], 404);
            }

            // Get all users
            $users = $this->userRepository->findBy(['id' => $userIds]);
            
            if (count($users) !== count($userIds)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Some users not found'
                ], 404);
            }

            // Update all users
            $updatedUsers = [];
            foreach ($users as $user) {
                $user->setPlan($plan->getCode());
                $updatedUsers[] = $this->formatUserForAdmin($user);
            }

            $this->entityManager->flush();

            $this->logger->info('Bulk plan assignment completed by admin', [
                'user_count' => count($users),
                'user_ids' => $userIds,
                'plan_code' => $plan->getCode(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => "Plan assigned to " . count($users) . " users successfully",
                'data' => ['users' => $updatedUsers]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to bulk assign plan to users', [
                'error' => $e->getMessage(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to bulk assign plan to users'
            ], 500);
        }
    }

    // ========================================
    // PLAN MANAGEMENT ENDPOINTS
    // ========================================

    /**
     * Get all subscription plans (Admin only)
     * 
     * Returns all subscription plans including inactive ones for admin management.
     * Only accessible to users with ROLE_ADMIN.
     * 
     * @return JsonResponse List of all plans with their limits and features
     */
    #[Route('/plans', name: 'admin_plans_list', methods: ['GET'])]
    public function listPlans(): JsonResponse
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
    #[Route('/plans', name: 'admin_plans_create', methods: ['POST'])]
    public function createPlan(Request $request): JsonResponse
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

    /**
     * Update a subscription plan (Admin only)
     * 
     * Updates an existing subscription plan with new data.
     * 
     * @param int $id Plan ID
     * @param Request $request Plan data to update
     * @return JsonResponse Updated plan data or validation errors
     */
    #[Route('/plans/{id}', name: 'admin_plans_update', methods: ['PUT'])]
    public function updatePlan(int $id, Request $request): JsonResponse
    {
        try {
            $plan = $this->entityManager->getRepository(SubscriptionPlan::class)->find($id);

            if (!$plan) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Plan not found'
                ], 404);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ], 400);
            }

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

            $this->entityManager->flush();

            $userId = $this->getUser()?->getUserIdentifier() ?? 'unknown';
            $this->logger->info('Subscription plan updated', [
                'plan_id' => $plan->getId(),
                'plan_code' => $plan->getCode(),
                'admin_user' => $userId
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Plan updated successfully',
                'data' => ['plan' => $this->formatPlanForAdmin($plan)]
            ]);
        } catch (\Exception $e) {
            $userId = $this->getUser()?->getUserIdentifier() ?? 'unknown';
            $this->logger->error('Failed to update plan', [
                'plan_id' => $id,
                'error' => $e->getMessage(),
                'admin_user' => $userId
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to update plan'
            ], 500);
        }
    }

    /**
     * Delete a subscription plan (Admin only)
     * 
     * Deletes a subscription plan. Cannot delete plans that have active subscriptions.
     * 
     * @param int $id Plan ID
     * @return JsonResponse Success or error response
     */
    #[Route('/plans/{id}', name: 'admin_plans_delete', methods: ['DELETE'])]
    public function deletePlan(int $id): JsonResponse
    {
        try {
            $plan = $this->entityManager->getRepository(SubscriptionPlan::class)->find($id);

            if (!$plan) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Plan not found'
                ], 404);
            }

            // Check if plan is in use by users
            $usersCount = $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.plan = :planCode')
                ->setParameter('planCode', $plan->getCode())
                ->getQuery()
                ->getSingleScalarResult();

            if ($usersCount > 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Cannot delete plan with active subscriptions'
                ], 400);
            }

            $this->entityManager->remove($plan);
            $this->entityManager->flush();

            $userId = $this->getUser()?->getUserIdentifier() ?? 'unknown';
            $this->logger->info('Subscription plan deleted', [
                'plan_id' => $plan->getId(),
                'plan_code' => $plan->getCode(),
                'admin_user' => $userId
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Plan deleted successfully'
            ]);
        } catch (\Exception $e) {
            $userId = $this->getUser()?->getUserIdentifier() ?? 'unknown';
            $this->logger->error('Failed to delete plan', [
                'plan_id' => $id,
                'error' => $e->getMessage(),
                'admin_user' => $userId
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to delete plan'
            ], 500);
        }
    }

    // ========================================
    // ANALYTICS ENDPOINTS
    // ========================================

    /**
     * Get comprehensive analytics data
     * 
     * @param Request $request Request with analytics parameters
     * @return JsonResponse Analytics data or error response
     */
    #[Route('/analytics', name: 'admin_analytics', methods: ['GET'])]
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query->get('startDate');
            $endDate = $request->query->get('endDate');
            $granularity = $request->query->get('granularity', 'day');

            // Validate granularity
            if (!in_array($granularity, ['day', 'week', 'month'])) {
                $granularity = 'day';
            }

            // Set default date range if not provided
            if (!$startDate || !$endDate) {
                $endDate = new \DateTimeImmutable();
                $startDate = $endDate->modify('-30 days');
            } else {
                $startDate = new \DateTimeImmutable($startDate);
                $endDate = new \DateTimeImmutable($endDate);
            }

            // Get analytics data from service
            $analyticsData = $this->analyticsService->getAdminAnalytics($startDate, $endDate, $granularity);

            return new JsonResponse([
                'success' => true,
                'message' => 'Analytics data retrieved successfully',
                'data' => $analyticsData
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve analytics data', [
                'error' => $e->getMessage(),
                'admin_user' => $this->getUser()?->getUserIdentifier() ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS
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
