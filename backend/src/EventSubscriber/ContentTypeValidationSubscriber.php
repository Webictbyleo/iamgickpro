<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Attribute\RequireContentType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use ReflectionMethod;

class ContentTypeValidationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        
        if (!is_array($controller)) {
            return;
        }

        $reflectionMethod = new ReflectionMethod($controller[0], $controller[1]);
        $attributes = $reflectionMethod->getAttributes(RequireContentType::class);

        if (empty($attributes)) {
            return;
        }

        $attribute = $attributes[0]->newInstance();
        $request = $event->getRequest();
        $contentType = $request->headers->get('Content-Type', '');

        $validContentTypes = $attribute->getContentTypes();
        $isValid = false;

        foreach ($validContentTypes as $validType) {
            if (str_starts_with($contentType, $validType)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            throw new BadRequestHttpException(
                sprintf(
                    '%s. Expected: %s, Got: %s',
                    $attribute->message,
                    implode(' or ', $validContentTypes),
                    $contentType ?: 'none'
                )
            );
        }
    }
}
