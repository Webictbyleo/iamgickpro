<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 9999)]
#[AsEventListener(event: KernelEvents::RESPONSE, priority: 9999)]
class CorsListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse(['status' => 'OK'], Response::HTTP_OK);
            $this->addCorsHeaders($response);
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only add CORS headers to API routes
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            $this->addCorsHeaders($response);
        }
    }

    private function addCorsHeaders(Response $response): void
    {
        // Allow requests from frontend development server and production domains
        $allowedOrigins = [
            'http://localhost:5173', // Vite dev server
            'http://127.0.0.1:5173',
            'http://localhost:3000',  // Alternative dev ports
            'http://127.0.0.1:3000',
            'http://localhost:8080',
            'http://127.0.0.1:8080',
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origin, $allowedOrigins) || $_ENV['APP_ENV'] === 'dev') {
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-Custom-Auth');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Length, Content-Range, Link');
        $response->headers->set('Access-Control-Max-Age', '3600');
        $response->headers->set('Access-Control-Allow-Credentials', 'false');

        // Ensure proper content type for OPTIONS responses
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Content-Length', '0');
        }
    }
}
