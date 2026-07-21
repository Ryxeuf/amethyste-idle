<?php

namespace App\EventListener;

use App\Api\ApiResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Convertit toute exception levee sur /api/* en reponse JSON enveloppee
 * (voir ApiResponse) au lieu d'une page d'erreur HTML ou d'une redirection
 * vers le formulaire de login.
 *
 * Priorite 2 : doit passer AVANT le ExceptionListener de Security (priorite 1)
 * pour que les AccessDeniedException donnent un 401/403 JSON et non un 302.
 */
class ApiExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedException) {
            $status = $this->security->getUser() === null
                ? Response::HTTP_UNAUTHORIZED
                : Response::HTTP_FORBIDDEN;
            $message = $status === Response::HTTP_UNAUTHORIZED
                ? 'Authentication required.'
                : 'Access denied.';

            $event->setResponse(ApiResponse::error(ApiResponse::errorCodeForStatus($status), $message, $status));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $response = ApiResponse::error(
                ApiResponse::errorCodeForStatus($status),
                $exception->getMessage() !== '' ? $exception->getMessage() : Response::$statusTexts[$status] ?? 'Error',
                $status,
            );
            $response->headers->add($exception->getHeaders());
            $event->setResponse($response);

            return;
        }

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = $this->debug ? $exception->getMessage() : 'Internal server error.';

        $event->setResponse(ApiResponse::error(ApiResponse::errorCodeForStatus($status), $message, $status));
    }
}
