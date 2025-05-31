<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/search')]
#[IsGranted('ROLE_USER')]
class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchService $searchService
    ) {
    }

    #[Route('', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', 'all'); // all, templates, media, projects
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        
        if (empty($query)) {
            return $this->json(['error' => 'Search query is required'], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $results = $this->searchService->search($user, $query, $type, $page, $limit);
            
            return $this->json([
                'success' => true,
                'query' => $query,
                'type' => $type,
                'results' => $results['items'],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $results['total'],
                    'pages' => (int) ceil($results['total'] / $limit)
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Search failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/templates', name: 'search_templates', methods: ['GET'])]
    public function searchTemplates(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $query = $request->query->get('q', '');
        $category = $request->query->get('category', '');
        $tags = $request->query->get('tags', '');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        
        try {
            $results = $this->searchService->searchTemplates($user, $query, $category, $tags, $page, $limit);
            
            return $this->json([
                'success' => true,
                'templates' => $results['items'],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $results['total'],
                    'pages' => (int) ceil($results['total'] / $limit)
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Template search failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/media', name: 'search_media', methods: ['GET'])]
    public function searchMedia(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', ''); // image, video, audio
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        
        try {
            $results = $this->searchService->searchMedia($user, $query, $type, $page, $limit);
            
            return $this->json([
                'success' => true,
                'media' => $results['items'],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $results['total'],
                    'pages' => (int) ceil($results['total'] / $limit)
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Media search failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/projects', name: 'search_projects', methods: ['GET'])]
    public function searchProjects(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $query = $request->query->get('q', '');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        
        try {
            $results = $this->searchService->searchProjects($user, $query, $page, $limit);
            
            return $this->json([
                'success' => true,
                'projects' => $results['items'],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $results['total'],
                    'pages' => (int) ceil($results['total'] / $limit)
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Project search failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/suggestions', name: 'search_suggestions', methods: ['GET'])]
    public function getSearchSuggestions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $query = $request->query->get('q', '');
        $limit = (int) $request->query->get('limit', 10);
        
        try {
            $suggestions = $this->searchService->getSearchSuggestions($user, $query, $limit);
            
            return $this->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to get suggestions'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
