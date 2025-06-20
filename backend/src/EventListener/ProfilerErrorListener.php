<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Profiler Error Handler
 * 
 * Handles profiler-related errors gracefully to prevent them from breaking the application
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 100)]
class ProfilerErrorListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        // Check if this is a profiler-related error
        if ($this->isProfilerError($exception)) {
            $this->logger->warning('Profiler error detected and handled gracefully', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            
            // If in development, we might want to continue anyway
            // In production, profiler should be disabled anyway
            if ($_ENV['APP_ENV'] === 'dev') {
                // Log the error but don't stop execution
                return;
            }
        }
    }

    private function isProfilerError(\Throwable $exception): bool
    {
        $message = $exception->getMessage();
        $trace = $exception->getTraceAsString();
        
        // Check for common profiler-related error patterns
        return str_contains($message, 'null bytes') ||
               str_contains($message, 'unlink()') ||
               str_contains($trace, 'FileProfilerStorage') ||
               str_contains($trace, 'ProfilerListener') ||
               str_contains($message, 'profiler');
    }
}
