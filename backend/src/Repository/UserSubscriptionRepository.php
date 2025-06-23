<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for UserSubscription entity
 * 
 * @extends ServiceEntityRepository<UserSubscription>
 */
class UserSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSubscription::class);
    }

    /**
     * Find active subscription for a user
     */
    public function findActiveSubscriptionForUser(User $user): ?UserSubscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.status = :status')
            ->andWhere('(s.endDate IS NULL OR s.endDate > :now)')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active subscriptions for a user
     */
    public function findActiveSubscriptionsForUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.status = :status')
            ->andWhere('(s.endDate IS NULL OR s.endDate > :now)')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all subscriptions for a user (active and inactive)
     */
    public function findAllUserSubscriptions(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired subscriptions that should be marked as inactive
     */
    public function findExpiredSubscriptions(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.endDate IS NOT NULL')
            ->andWhere('s.endDate <= :now')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Find subscriptions expiring within a specific period (for notifications)
     */
    public function findSubscriptionsExpiringWithin(\DateInterval $period): array
    {
        $expiryDate = (new \DateTimeImmutable())->add($period);
        
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.endDate IS NOT NULL')
            ->andWhere('s.endDate <= :expiryDate')
            ->andWhere('s.endDate > :now')
            ->setParameter('status', 'active')
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Find subscriptions by plan
     */
    public function findByPlan(string $planCode): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.subscriptionPlan', 'p')
            ->where('p.code = :planCode')
            ->setParameter('planCode', $planCode)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count active subscriptions for a plan
     */
    public function countActiveSubscriptionsForPlan(string $planCode): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.subscriptionPlan', 'p')
            ->where('p.code = :planCode')
            ->andWhere('s.status = :status')
            ->andWhere('(s.endDate IS NULL OR s.endDate > :now)')
            ->setParameter('planCode', $planCode)
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get subscription statistics
     */
    public function getSubscriptionStats(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('p.name as plan_name, COUNT(s.id) as subscription_count')
            ->join('s.subscriptionPlan', 'p')
            ->where('s.status = :status')
            ->andWhere('(s.endDate IS NULL OR s.endDate > :now)')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTimeImmutable())
            ->groupBy('p.id')
            ->orderBy('subscription_count', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
