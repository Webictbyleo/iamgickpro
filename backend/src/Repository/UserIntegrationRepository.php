<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserIntegration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserIntegration>
 */
class UserIntegrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserIntegration::class);
    }

    /**
     * Find integration by user and service name
     */
    public function findByUserAndService(User $user, string $serviceName): ?UserIntegration
    {
        return $this->findOneBy([
            'user' => $user,
            'serviceName' => $serviceName
        ]);
    }

    /**
     * Get all integrations for a user
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['serviceName' => 'ASC']);
    }

    /**
     * Check if user has configured integration for service
     */
    public function hasIntegration(User $user, string $serviceName): bool
    {
        return $this->findByUserAndService($user, $serviceName) !== null;
    }

    /**
     * Get count of integrations by service
     */
    public function getIntegrationCountByService(): array
    {
        $result = $this->createQueryBuilder('ui')
            ->select('ui.serviceName, COUNT(ui.id) as count')
            ->groupBy('ui.serviceName')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['serviceName']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Remove all integrations for a user
     */
    public function removeAllForUser(User $user): void
    {
        $this->createQueryBuilder('ui')
            ->delete()
            ->where('ui.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
