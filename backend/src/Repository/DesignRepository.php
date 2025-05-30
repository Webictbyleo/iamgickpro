<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Design;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Design>
 */
class DesignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Design::class);
    }

    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.project = :project')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUuid(string $uuid): ?Design
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.uuid = :uuid')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByProjectAndUuid(Project $project, string $uuid): ?Design
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.project = :project')
            ->andWhere('d.uuid = :uuid')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentDesigns(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchUserDesigns(User $user, string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.name LIKE :query')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findDesignsWithLayerCount(Project $project): array
    {
        return $this->createQueryBuilder('d')
            ->select('d', 'COUNT(l.id) as layersCount')
            ->leftJoin('d.layers', 'l')
            ->andWhere('d.project = :project')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->groupBy('d.id')
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAnimatedDesigns(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.hasAnimation = :animated')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('animated', true)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDimensions(int $width, int $height, int $tolerance = 50): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.width BETWEEN :minWidth AND :maxWidth')
            ->andWhere('d.height BETWEEN :minHeight AND :maxHeight')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('minWidth', $width - $tolerance)
            ->setParameter('maxWidth', $width + $tolerance)
            ->setParameter('minHeight', $height - $tolerance)
            ->setParameter('maxHeight', $height + $tolerance)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUserDesigns(User $user): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->join('d.project', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countProjectDesigns(Project $project): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.project = :project')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findDesignsUpdatedBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.updatedAt >= :start')
            ->andWhere('d.updatedAt <= :end')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findComplexDesigns(int $minLayers = 10): array
    {
        return $this->createQueryBuilder('d')
            ->select('d', 'COUNT(l.id) as layersCount')
            ->leftJoin('d.layers', 'l')
            ->andWhere('d.deletedAt IS NULL')
            ->groupBy('d.id')
            ->having('layersCount >= :minLayers')
            ->setParameter('minLayers', $minLayers)
            ->orderBy('layersCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findDuplicateDesigns(Design $design): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.id != :currentId')
            ->andWhere('d.width = :width')
            ->andWhere('d.height = :height')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('currentId', $design->getId())
            ->setParameter('width', $design->getWidth())
            ->setParameter('height', $design->getHeight())
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPopularCanvasSizes(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.width as width', 'd.height as height', 'COUNT(d.id) as count')
            ->andWhere('d.deletedAt IS NULL')
            ->groupBy('d.width', 'd.height')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
