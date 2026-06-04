<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
        $payload = ['error' => $e->getMessage()];

        if ($e instanceof ApiException && $e->getDetails()) {
            $payload['details'] = $e->getDetails();
        }

        $response = new JsonResponse($payload, $status);
        $event->setResponse($response);
    }
}
