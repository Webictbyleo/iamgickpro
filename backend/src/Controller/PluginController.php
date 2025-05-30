<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Plugin;
use App\Repository\PluginRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/plugins', name: 'api_plugins_')]
class PluginController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PluginRepository $pluginRepository,
        private readonly ValidatorInterface $validator,
        private readonly string $pluginUploadDirectory = '/var/www/html/uploads/plugins'
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $category = $request->query->get('category');
        $search = $request->query->get('search');
        $status = $request->query->get('status', 'approved');
        $sortBy = $request->query->get('sort_by', 'downloads');
        $sortOrder = $request->query->get('sort_order', 'desc');

        $plugins = $this->pluginRepository->findByFilters([
            'category' => $category,
            'search' => $search,
            'status' => $status,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'page' => $page,
            'limit' => $limit,
        ]);

        $total = $this->pluginRepository->countByFilters([
            'category' => $category,
            'search' => $search,
            'status' => $status,
        ]);

        return $this->json([
            'plugins' => array_map([$this, 'serializePlugin'], $plugins),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ],
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Plugin $plugin): JsonResponse
    {
        // Only show approved plugins unless user is the developer or admin
        if ($plugin->getStatus() !== 'approved' && 
            $plugin->getUser() !== $this->getUser() && 
            !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Plugin not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializePlugin($plugin, true));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $plugin = new Plugin();
        $plugin->setName($data['name'] ?? '');
        $plugin->setDescription($data['description'] ?? '');
        $plugin->setCategories($data['categories'] ?? []);
        $plugin->setVersion($data['version'] ?? '1.0.0');
        $plugin->setUser($user);
        $plugin->setStatus('pending');
        $plugin->setPermissions($data['permissions'] ?? []);
        $plugin->setManifest($data['manifest'] ?? []);

        $errors = $this->validator->validate($plugin);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($plugin);
        $this->entityManager->flush();

        return $this->json($this->serializePlugin($plugin), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(Plugin $plugin, Request $request): JsonResponse
    {
        // Only developer or admin can update
        if ($plugin->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $plugin->setName($data['name']);
        if (isset($data['description'])) $plugin->setDescription($data['description']);
        if (isset($data['categories'])) $plugin->setCategories($data['categories']);
        if (isset($data['version'])) $plugin->setVersion($data['version']);
        if (isset($data['permissions'])) $plugin->setPermissions($data['permissions']);
        if (isset($data['manifest'])) $plugin->setManifest($data['manifest']);

        // Admin can change status
        if ($this->isGranted('ROLE_ADMIN') && isset($data['status'])) {
            $plugin->setStatus($data['status']);
        }

        $errors = $this->validator->validate($plugin);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($this->serializePlugin($plugin));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Plugin $plugin): JsonResponse
    {
        // Only developer or admin can delete
        if ($plugin->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($plugin);
        $this->entityManager->flush();

        return $this->json(['message' => 'Plugin deleted successfully']);
    }

    #[Route('/{id}/install', name: 'install', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function install(Plugin $plugin): JsonResponse
    {
        if ($plugin->getStatus() !== 'approved') {
            return $this->json(['error' => 'Plugin not approved'], Response::HTTP_BAD_REQUEST);
        }

        // Increment installation count
        $plugin->incrementInstallCount();
        $this->entityManager->flush();

        // TODO: Implement actual plugin installation logic
        // This would involve copying plugin files, registering in user's installed plugins, etc.

        return $this->json([
            'message' => 'Plugin installed successfully',
            'plugin' => $this->serializePlugin($plugin),
        ]);
    }

    #[Route('/{id}/uninstall', name: 'uninstall', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uninstall(Plugin $plugin): JsonResponse
    {
        // TODO: Implement actual plugin uninstallation logic
        // This would involve removing plugin files, unregistering from user's installed plugins, etc.

        return $this->json(['message' => 'Plugin uninstalled successfully']);
    }

    #[Route('/{id}/upload-file', name: 'upload_file', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uploadFile(Plugin $plugin, Request $request): JsonResponse
    {
        // Only developer can upload files
        if ($plugin->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        // Validate file type (only allow zip files for now)
        $allowedTypes = ['application/zip', 'application/x-zip-compressed'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->json(['error' => 'Invalid file type. Only ZIP files are allowed.'], Response::HTTP_BAD_REQUEST);
        }

        // Generate unique filename
        $fileName = uniqid() . '_' . $plugin->getId() . '.zip';

        try {
            $file->move($this->pluginUploadDirectory, $fileName);
            // TODO: Store file path in a separate table or service
            // $plugin->setFilePath($this->pluginUploadDirectory . '/' . $fileName);
            // $plugin->setFileSize($file->getSize());
            $this->entityManager->flush();

            // TODO: Implement security scanning of uploaded plugin file
            // This would involve extracting and analyzing the plugin code

            return $this->json([
                'message' => 'File uploaded successfully',
                'file_path' => $fileName,
            ]);
        } catch (FileException $e) {
            return $this->json(['error' => 'Failed to upload file'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Plugin $plugin): JsonResponse
    {
        $plugin->setStatus('approved');
        $plugin->setReviewedAt(new \DateTimeImmutable());
        $plugin->setReviewedBy($this->getUser());
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Plugin approved successfully',
            'plugin' => $this->serializePlugin($plugin),
        ]);
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Plugin $plugin, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'No reason provided';

        $plugin->setStatus('rejected');
        $plugin->setReviewNotes($reason);
        $plugin->setReviewedAt(new \DateTimeImmutable());
        $plugin->setReviewedBy($this->getUser());
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Plugin rejected',
            'reason' => $reason,
        ]);
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        $categories = $this->pluginRepository->getCategories();
        
        return $this->json(['categories' => $categories]);
    }

    #[Route('/my-plugins', name: 'my_plugins', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myPlugins(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $plugins = $this->pluginRepository->findByUser($this->getUser(), $page, $limit);
        $total = $this->pluginRepository->countByUser($this->getUser());

        return $this->json([
            'plugins' => array_map([$this, 'serializePlugin'], $plugins),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ],
        ]);
    }

    private function serializePlugin(Plugin $plugin, bool $detailed = false): array
    {
        $data = [
            'id' => $plugin->getId(),
            'name' => $plugin->getName(),
            'description' => $plugin->getDescription(),
            'categories' => $plugin->getCategories(),
            'version' => $plugin->getVersion(),
            'status' => $plugin->getStatus(),
            'install_count' => $plugin->getInstallCount(),
            'rating' => $plugin->getRating(),
            'rating_count' => $plugin->getRatingCount(),
            'created_at' => $plugin->getCreatedAt()->format('c'),
            'updated_at' => $plugin->getUpdatedAt()->format('c'),
            'developer' => [
                'id' => $plugin->getUser()->getId(),
                'username' => $plugin->getUser()->getUsername(),
                'email' => $plugin->getUser()->getEmail(),
            ],
        ];

        if ($detailed) {
            $data['permissions'] = $plugin->getPermissions();
            $data['manifest'] = $plugin->getManifest();
            $data['security_scan'] = $plugin->getSecurityScan();
            $data['review_notes'] = $plugin->getReviewNotes();
            
            if ($plugin->getReviewedAt()) {
                $data['reviewed_at'] = $plugin->getReviewedAt()->format('c');
            }
            
            if ($plugin->getReviewedBy()) {
                $data['reviewed_by'] = [
                    'id' => $plugin->getReviewedBy()->getId(),
                    'username' => $plugin->getReviewedBy()->getUsername(),
                ];
            }
        }

        return $data;
    }
}
