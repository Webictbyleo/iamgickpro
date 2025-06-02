<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository for User entity operations.
 * 
 * Provides methods for user authentication, querying, and management operations.
 * Implements password upgrading functionality for security enhancements.
 * Handles soft deletes and user verification status.
 * 
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     * 
     * This method is called by Symfony's security system to automatically
     * upgrade password hashes when better algorithms become available.
     * 
     * @param PasswordAuthenticatedUserInterface $user The user whose password needs upgrading
     * @param string $newHashedPassword The new hashed password
     * @throws UnsupportedUserException If the user is not an instance of User entity
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find a user by email address.
     * 
     * Searches for a user with the specified email address. Used primarily
     * for authentication and user lookup operations.
     * 
     * @param string $email The email address to search for
     * @return User|null The User entity if found, null otherwise
     */
    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a user by UUID.
     * 
     * Searches for a user with the specified UUID. UUIDs are used for
     * public-facing user identification.
     * 
     * @param string $uuid The UUID to search for
     * @return User|null The User entity if found, null otherwise
     */
    public function findByUuid(string $uuid): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active users.
     * 
     * Retrieves users who have verified their email accounts and are not
     * soft-deleted. Results are ordered by creation date (newest first).
     * 
     * @return User[] Array of active User entities
     */
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isVerified = :verified')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('verified', true)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with their project count.
     * 
     * Retrieves all users along with the count of projects each user has created.
     * Results are ordered by project count (highest first). Useful for analytics
     * and identifying power users.
     * 
     * @return array Array containing User entities with projectsCount field
     */
    public function findUsersWithProjectsCount(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u', 'COUNT(p.id) as projectsCount')
            ->leftJoin('u.projects', 'p')
            ->andWhere('u.deletedAt IS NULL')
            ->groupBy('u.id')
            ->orderBy('projectsCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently active users.
     * 
     * Retrieves users who have either logged in or updated their profile
     * within the specified number of days. Useful for engagement analytics.
     * 
     * @param int $days Number of days to look back for activity (default: 30)
     * @return User[] Array of recently active User entities
     */
    public function findRecentlyActive(int $days = 30): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('u')
            ->andWhere('u.lastLoginAt >= :since OR u.updatedAt >= :since')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('since', $since)
            ->orderBy('u.lastLoginAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search users by name or email.
     * 
     * Performs a case-insensitive search across first name, last name,
     * and email fields. Uses LIKE pattern matching for flexible searching.
     * 
     * @param string $query The search query to match against user fields
     * @param int $limit Maximum number of results to return (default: 20)
     * @return User[] Array of User entities matching the search criteria
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.firstName LIKE :query OR u.lastName LIKE :query OR u.email LIKE :query')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.firstName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total users in the system.
     * 
     * Returns the total number of users, excluding soft-deleted accounts.
     * Used for dashboard statistics and analytics.
     * 
     * @return int Total number of users
     */
    public function countTotalUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count verified users in the system.
     * 
     * Returns the number of users who have verified their email addresses,
     * excluding soft-deleted accounts. Used for measuring user engagement.
     * 
     * @return int Number of verified users
     */
    public function countVerifiedUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.isVerified = :verified')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find users created within a date range.
     * 
     * Retrieves users who registered between the specified start and end dates.
     * Useful for signup analytics and tracking user growth over time.
     * 
     * @param \DateTimeInterface $start The start date for the range
     * @param \DateTimeInterface $end The end date for the range
     * @return User[] Array of User entities created within the date range
     */
    public function findUsersCreatedBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.createdAt >= :start')
            ->andWhere('u.createdAt <= :end')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
