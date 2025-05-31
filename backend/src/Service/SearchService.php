<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\MediaRepository;
use App\Repository\TemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class SearchService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository,
        private MediaRepository $mediaRepository,
        private TemplateRepository $templateRepository,
        private LoggerInterface $logger
    ) {
    }

    public function search(User $user, string $query, string $type = 'all', int $page = 1, int $limit = 20): array
    {
        $results = [
            'items' => [],
            'total' => 0
        ];

        switch ($type) {
            case 'projects':
                $results = $this->searchProjects($user, $query, $page, $limit);
                break;
            case 'templates':
                $results = $this->searchTemplates($user, $query, '', '', $page, $limit);
                break;
            case 'media':
                $results = $this->searchMedia($user, $query, '', $page, $limit);
                break;
            case 'all':
            default:
                $results = $this->searchAll($user, $query, $page, $limit);
                break;
        }

        $this->logger->info('Search performed', [
            'user_id' => $user->getId(),
            'query' => $query,
            'type' => $type,
            'results_count' => count($results['items']),
            'total' => $results['total']
        ]);

        return $results;
    }

    public function searchProjects(User $user, string $query, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->projectRepository->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.name LIKE :query OR p.description LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.updatedAt', 'DESC');

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        $projects = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => array_map([$this, 'formatProject'], $projects),
            'total' => (int) $total
        ];
    }

    public function searchTemplates(User $user, string $query, string $category = '', string $tags = '', int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->templateRepository->createQueryBuilder('t')
            ->where('t.isActive = :active')
            ->setParameter('active', true);

        if (!empty($query)) {
            $qb->andWhere('t.name LIKE :query OR t.description LIKE :query OR t.tags LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if (!empty($category)) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }

        if (!empty($tags)) {
            $tagArray = explode(',', $tags);
            foreach ($tagArray as $index => $tag) {
                $paramName = 'tag' . $index;
                $qb->andWhere('t.tags LIKE :' . $paramName)
                   ->setParameter($paramName, '%' . trim($tag) . '%');
            }
        }

        $qb->orderBy('t.popularity', 'DESC')
           ->addOrderBy('t.createdAt', 'DESC');

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

        $templates = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => array_map([$this, 'formatTemplate'], $templates),
            'total' => (int) $total
        ];
    }

    public function searchMedia(User $user, string $query, string $type = '', int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->mediaRepository->createQueryBuilder('m')
            ->where('m.user = :user')
            ->setParameter('user', $user);

        if (!empty($query)) {
            $qb->andWhere('m.filename LIKE :query OR m.originalName LIKE :query OR m.tags LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if (!empty($type)) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $type);
        }

        $qb->orderBy('m.createdAt', 'DESC');

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

        $media = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => array_map([$this, 'formatMedia'], $media),
            'total' => (int) $total
        ];
    }

    public function getSearchSuggestions(User $user, string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $suggestions = [];

        // Get project suggestions
        $projects = $this->projectRepository->createQueryBuilder('p')
            ->select('p.name')
            ->where('p.user = :user')
            ->andWhere('p.name LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($projects as $project) {
            $suggestions[] = [
                'text' => $project['name'],
                'type' => 'project'
            ];
        }

        // Get template suggestions
        $templates = $this->templateRepository->createQueryBuilder('t')
            ->select('t.name')
            ->where('t.isActive = :active')
            ->andWhere('t.name LIKE :query')
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($templates as $template) {
            $suggestions[] = [
                'text' => $template['name'],
                'type' => 'template'
            ];
        }

        return array_slice($suggestions, 0, $limit);
    }

    private function searchAll(User $user, string $query, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $results = [];

        // Search projects (limit to 1/3 of results)
        $projectLimit = (int) ceil($limit / 3);
        $projectResults = $this->searchProjects($user, $query, 1, $projectLimit);
        
        // Search templates (limit to 1/3 of results)
        $templateLimit = (int) ceil($limit / 3);
        $templateResults = $this->searchTemplates($user, $query, '', '', 1, $templateLimit);
        
        // Search media (remaining results)
        $mediaLimit = $limit - count($projectResults['items']) - count($templateResults['items']);
        $mediaResults = $this->searchMedia($user, $query, '', 1, max(1, $mediaLimit));

        // Combine results
        $allResults = [];
        
        foreach ($projectResults['items'] as $item) {
            $item['result_type'] = 'project';
            $allResults[] = $item;
        }
        
        foreach ($templateResults['items'] as $item) {
            $item['result_type'] = 'template';
            $allResults[] = $item;
        }
        
        foreach ($mediaResults['items'] as $item) {
            $item['result_type'] = 'media';
            $allResults[] = $item;
        }

        $total = $projectResults['total'] + $templateResults['total'] + $mediaResults['total'];

        return [
            'items' => array_slice($allResults, $offset, $limit),
            'total' => $total
        ];
    }

    private function formatProject(object $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'thumbnail' => $project->getThumbnail(),
            'updatedAt' => $project->getUpdatedAt()->format('c'),
            'type' => 'project'
        ];
    }

    private function formatTemplate(object $template): array
    {
        return [
            'id' => $template->getId(),
            'name' => $template->getName(),
            'description' => $template->getDescription(),
            'thumbnail' => $template->getThumbnail(),
            'category' => $template->getCategory(),
            'tags' => $template->getTags(),
            'isPremium' => $template->getIsPremium(),
            'type' => 'template'
        ];
    }

    private function formatMedia(object $media): array
    {
        return [
            'id' => $media->getId(),
            'filename' => $media->getFilename(),
            'originalName' => $media->getOriginalName(),
            'type' => $media->getType(),
            'size' => $media->getSize(),
            'url' => $media->getUrl(),
            'thumbnail' => $media->getThumbnail(),
            'createdAt' => $media->getCreatedAt()->format('c'),
            'type' => 'media'
        ];
    }
}
