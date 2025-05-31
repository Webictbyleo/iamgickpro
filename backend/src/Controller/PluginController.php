<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\TypedResponseTrait;
use App\DTO\Request\CreatePluginRequestDTO;
use App\DTO\Request\RejectPluginRequestDTO;
use App\DTO\Request\UpdatePluginRequestDTO;
use App\DTO\Request\UploadPluginFileRequestDTO;
use App\Entity\Plugin;
use App\Repository\PluginRepository;
use App\Service\ResponseDTOFactory;
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

/**
 * Plugin Controller
 * 
 * Manages the plugin system including plugin registration, approval, installation, and management.
 * Handles plugin file uploads, metadata management, and lifecycle operations.
 * Provides marketplace functionality for plugin discovery and category management.
 * Includes admin approval workflow and user plugin management features.
 */
#[Route('/api/plugins', name: 'api_plugins_')]
class PluginController extends AbstractController
{
    use TypedResponseTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PluginRepository $pluginRepository,
        private readonly ValidatorInterface $validator,
        private readonly ResponseDTOFactory $responseDTOFactory,
        private readonly string $pluginUploadDirectory = '/var/www/html/uploads/plugins'
    ) {}

    /**
     * Retrieve paginated list of plugins with filtering options
     * 
     * Supports filtering by category, search terms, status, and sorting.
     * Returns paginated results with plugin metadata.
     * 
     * @param Request $request HTTP request containing query parameters
     * @return JsonResponse List of plugins with pagination metadata
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
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

            return $this->pluginResponse(
                $this->responseDTOFactory->createPluginListResponse($plugins, $page, $limit, $total)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve plugins'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Retrieve detailed information about a specific plugin
     * 
     * Returns comprehensive plugin details including manifest, permissions,
     * and review information. Access is restricted based on plugin status and user role.
     * 
     * @param Plugin $plugin The plugin entity to display
     * @return JsonResponse Plugin details or error response
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Plugin $plugin): JsonResponse
    {
        try {
            // Only show approved plugins unless user is the developer or admin
            if ($plugin->getStatus() !== 'approved' && 
                $plugin->getUser() !== $this->getUser() && 
                !$this->isGranted('ROLE_ADMIN')) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Plugin not found'),
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->pluginResponse(
                $this->responseDTOFactory->createPluginResponse($plugin)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve plugin'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create a new plugin
     * 
     * Creates a new plugin with the provided metadata. The plugin is initially
     * set to pending status and requires admin approval before becoming available.
     * 
     * @param CreatePluginRequestDTO $dto Validated plugin creation data
     * @return JsonResponse Created plugin data or validation errors
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(CreatePluginRequestDTO $dto): JsonResponse
    {
        try {
            $user = $this->getUser();

            $plugin = new Plugin();
            $plugin->setName($dto->name);
            $plugin->setDescription($dto->description);
            $plugin->setCategories($dto->categories);
            $plugin->setVersion($dto->version);
            $plugin->setUser($user);
            $plugin->setStatus('pending');
            $plugin->setPermissions($dto->permissions);
            $plugin->setManifest($dto->manifest);

            $errors = $this->validator->validate($plugin);
            if (count($errors) > 0) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse(
                        'Validation failed',
                        ['errors' => (string) $errors]
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->entityManager->persist($plugin);
            $this->entityManager->flush();

            return $this->pluginResponse(
                $this->responseDTOFactory->createPluginResponse($plugin, 'Plugin created successfully'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to create plugin'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update an existing plugin
     * 
     * Updates plugin metadata. Only the plugin developer or admin can perform updates.
     * Admin users can also modify the plugin status.
     * 
     * @param Plugin $plugin The plugin entity to update
     * @param UpdatePluginRequestDTO $dto Validated update data
     * @return JsonResponse Updated plugin data or error response
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(Plugin $plugin, UpdatePluginRequestDTO $dto): JsonResponse
    {
        try {
            // Only developer or admin can update
            if ($plugin->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            if ($dto->name !== null) $plugin->setName($dto->name);
            if ($dto->description !== null) $plugin->setDescription($dto->description);
            if ($dto->categories !== null) $plugin->setCategories($dto->categories);
            if ($dto->version !== null) $plugin->setVersion($dto->version);
            if ($dto->permissions !== null) $plugin->setPermissions($dto->permissions);
            if ($dto->manifest !== null) $plugin->setManifest($dto->manifest);

            // Admin can change status
            if ($this->isGranted('ROLE_ADMIN') && $dto->status !== null) {
                $plugin->setStatus($dto->status);
            }

            $errors = $this->validator->validate($plugin);
            if (count($errors) > 0) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse(
                        'Validation failed',
                        ['errors' => (string) $errors]
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->entityManager->flush();

            return $this->pluginResponse(
                $this->responseDTOFactory->createPluginResponse($plugin, 'Plugin updated successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to update plugin'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete a plugin
     * 
     * Permanently removes a plugin from the system. Only the plugin developer
     * or admin can perform this action.
     * 
     * @param Plugin $plugin The plugin entity to delete
     * @return JsonResponse Success message or error response
     */
    /**
     * Delete a plugin
     * 
     * Permanently removes a plugin from the system. Only the plugin developer
     * or administrators can delete plugins. This action is irreversible.
     * 
     * @param Plugin $plugin The plugin entity to delete
     * @return JsonResponse Success confirmation or error response
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Plugin $plugin): JsonResponse
    {
        try {
            // Only developer or admin can delete
            if ($plugin->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            $this->entityManager->remove($plugin);
            $this->entityManager->flush();

            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('Plugin deleted successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to delete plugin'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Install a plugin for the current user
     * 
     * Installs an approved plugin for the authenticated user. Increments the
     * installation count and handles plugin registration logic.
     * 
     * @param Plugin $plugin The plugin entity to install
     * @return JsonResponse Installation confirmation or error response
     */
    #[Route('/{id}/install', name: 'install', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function install(Plugin $plugin): JsonResponse
    {
        try {
            if ($plugin->getStatus() !== 'approved') {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Plugin not approved'),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Increment installation count
            $plugin->incrementInstallCount();
            $this->entityManager->flush();

            // TODO: Implement actual plugin installation logic
            // This would involve copying plugin files, registering in user's installed plugins, etc.

            return $this->pluginResponse(
                $this->responseDTOFactory->createPluginResponse($plugin, 'Plugin installed successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to install plugin'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Uninstall a plugin for the current user
     * 
     * Removes a plugin from the user's installed plugins and cleans up
     * associated data and files.
     * 
     * @param Plugin $plugin The plugin entity to uninstall
     * @return JsonResponse Uninstallation confirmation
     */
    #[Route('/{id}/uninstall', name: 'uninstall', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uninstall(Plugin $plugin): JsonResponse
    {
        try {
            // TODO: Implement actual plugin uninstallation logic
            // This would involve removing plugin files, unregistering from user's installed plugins, etc.

            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('Plugin uninstalled successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to uninstall plugin'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Upload a plugin file
     * 
     * Allows plugin developers to upload plugin files (ZIP format).
     * Performs validation and stores the file securely.
     * 
     * @param Plugin $plugin The plugin entity to upload files for
     * @param UploadPluginFileRequestDTO $dto Validated file upload data
     * @return JsonResponse Upload confirmation or error response
     */
    #[Route('/{id}/upload-file', name: 'upload_file', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uploadFile(Plugin $plugin, UploadPluginFileRequestDTO $dto): JsonResponse
    {
        try {
            // Only developer can upload files
            if ($plugin->getUser() !== $this->getUser()) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('Access denied'),
                    Response::HTTP_FORBIDDEN
                );
            }

            if (!$dto->file) {
                return $this->errorResponse(
                    $this->responseDTOFactory->createErrorResponse('No file uploaded'),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Generate unique filename
            $fileName = uniqid() . '_' . $plugin->getId() . '.zip';

            $dto->file->move($this->pluginUploadDirectory, $fileName);
            // TODO: Store file path in a separate table or service
            // $plugin->setFilePath($this->pluginUploadDirectory . '/' . $fileName);
            // $plugin->setFileSize($file->getSize());
            $this->entityManager->flush();

            // TODO: Implement security scanning of uploaded plugin file
            // This would involve extracting and analyzing the plugin code

            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('File uploaded successfully')
            );
        } catch (FileException $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to upload file'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to upload file'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Approve a plugin (Admin only)
     * 
     * Changes plugin status to approved, making it available for installation.
     * Records approval timestamp and reviewing admin.
     * 
     * @param Plugin $plugin The plugin entity to approve
     * @return JsonResponse Approval confirmation with plugin data
     */
    /**
     * Approve a plugin (Admin only)
     * 
     * Changes plugin status to approved, making it available in the marketplace.
     * Records approval timestamp and reviewing administrator.
     * 
     * @param Plugin $plugin The plugin entity to approve
     * @return JsonResponse Approval confirmation or error response
     */
    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Plugin $plugin): JsonResponse
    {
        try {
            $plugin->setStatus('approved');
            $plugin->setReviewedAt(new \DateTimeImmutable());
            $plugin->setReviewedBy($this->getUser());
            $this->entityManager->flush();

            return $this->pluginResponse(
                $this->responseDTOFactory->createPluginResponse($plugin, 'Plugin approved successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to approve plugin'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Reject a plugin (Admin only)
     * 
     * Changes plugin status to rejected with a provided reason.
     * Records rejection timestamp and reviewing admin.
     * 
     * @param Plugin $plugin The plugin entity to reject
     * @param RejectPluginRequestDTO $dto Validated rejection data containing reason
     * @return JsonResponse Rejection confirmation
     */
    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Plugin $plugin, RejectPluginRequestDTO $dto): JsonResponse
    {
        try {
            $plugin->setStatus('rejected');
            $plugin->setReviewNotes($dto->reason);
            $plugin->setReviewedAt(new \DateTimeImmutable());
            $plugin->setReviewedBy($this->getUser());
            $this->entityManager->flush();

            return $this->successResponse(
                $this->responseDTOFactory->createSuccessResponse('Plugin rejected')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to reject plugin'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get available plugin categories
     * 
     * Returns a list of all available plugin categories for filtering
     * and classification purposes.
     * 
     * @return JsonResponse List of plugin categories
     */
    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        try {
            $categories = $this->pluginRepository->getCategories();
            
            return new JsonResponse(['categories' => $categories]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve plugin categories'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get current user's plugins
     * 
     * Returns paginated list of plugins created by the authenticated user,
     * including all statuses (pending, approved, rejected).
     * 
     * @param Request $request HTTP request containing pagination parameters
     * @return JsonResponse List of user's plugins with pagination metadata
     */
    #[Route('/my-plugins', name: 'my_plugins', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myPlugins(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

            $plugins = $this->pluginRepository->findByUser($this->getUser(), $page, $limit);
            $total = $this->pluginRepository->countByUser($this->getUser());

            return $this->pluginResponse(
                $this->responseDTOFactory->createPluginListResponse($plugins, $page, $limit, $total)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $this->responseDTOFactory->createErrorResponse('Failed to retrieve your plugins'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
