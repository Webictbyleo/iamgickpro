<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for SubscriptionPlan entity
 * 
 * @extends ServiceEntityRepository<SubscriptionPlan>
 */
class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    /**
     * Find all active plans ordered by sort order
     */
    public function findActivePlans(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find default plan
     */
    public function findDefaultPlan(): ?SubscriptionPlan
    {
        return $this->createQueryBuilder('p')
            ->where('p.isDefault = :isDefault')
            ->andWhere('p.isActive = :active')
            ->setParameter('isDefault', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find plan by code (case-insensitive)
     */
    public function findByCode(string $code): ?SubscriptionPlan
    {
        return $this->createQueryBuilder('p')
            ->where('LOWER(p.code) = LOWER(:code)')
            ->andWhere('p.isActive = :active')
            ->setParameter('code', $code)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find plan by name (case-insensitive)
     */
    public function findByName(string $name): ?SubscriptionPlan
    {
        return $this->createQueryBuilder('p')
            ->where('LOWER(p.name) = LOWER(:name)')
            ->andWhere('p.isActive = :active')
            ->setParameter('name', $name)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find plans with pricing in a specific range
     */
    public function findByPriceRange(float $minPrice, float $maxPrice, bool $monthly = true): array
    {
        $priceField = $monthly ? 'p.monthlyPrice' : 'p.yearlyPrice';
        
        return $this->createQueryBuilder('p')
            ->where($priceField . ' >= :minPrice')
            ->andWhere($priceField . ' <= :maxPrice')
            ->andWhere('p.isActive = :active')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->setParameter('active', true)
            ->orderBy('p.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count active plans
     */
    public function countActivePlans(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
