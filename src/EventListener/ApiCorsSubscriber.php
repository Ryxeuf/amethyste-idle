<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CORS pour /api/* (migration API-first, phase 0.3), sans bundle externe.
 *
 * Origins autorises via l'env API_CORS_ALLOWED_ORIGINS (liste separee par
 * virgules d'origins exacts, ex: "capacitor://localhost,https://app.example",
 * ou "*" pour tout autoriser). Vide (defaut) = subscriber inactif, aucun
 * changement de comportement.
 *
 * Access-Control-Allow-Credentials n'est volontairement PAS emis : les
 * clients cross-origin s'authentifient par Bearer (phase 0.2), jamais par
 * cookie — la protection CSRF de la convention v1 reste intacte.
 */
class ApiCorsSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
    private const ALLOWED_HEADERS = 'Content-Type, Authorization, Accept';

    /** @var list<string> */
    private readonly array $allowedOrigins;

    public function __construct(
        #[Autowire('%env(default::API_CORS_ALLOWED_ORIGINS)%')]
        ?string $allowedOrigins,
    ) {
        $this->allowedOrigins = array_values(array_filter(array_map('trim', explode(',', (string) $allowedOrigins))));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Avant le firewall (priorite 8) : le preflight OPTIONS n'est pas authentifie.
            KernelEvents::REQUEST => ['onKernelRequest', 9],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->isMethod('OPTIONS') || !$this->isCorsApiRequest($request)) {
            return;
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $this->applyCorsHeaders($response, $request);
        $response->headers->set('Access-Control-Allow-Methods', self::ALLOWED_METHODS);
        $response->headers->set('Access-Control-Allow-Headers', self::ALLOWED_HEADERS);
        $response->headers->set('Access-Control-Max-Age', '3600');

        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isCorsApiRequest($request)) {
            return;
        }

        $this->applyCorsHeaders($event->getResponse(), $request);
    }

    private function isCorsApiRequest(Request $request): bool
    {
        if ($this->allowedOrigins === []) {
            return false;
        }

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return false;
        }

        $origin = $request->headers->get('Origin');

        return $origin !== null && $this->isOriginAllowed($origin);
    }

    private function isOriginAllowed(string $origin): bool
    {
        return \in_array('*', $this->allowedOrigins, true) || \in_array($origin, $this->allowedOrigins, true);
    }

    private function applyCorsHeaders(Response $response, Request $request): void
    {
        $origin = (string) $request->headers->get('Origin');
        $allowOrigin = \in_array('*', $this->allowedOrigins, true) ? '*' : $origin;

        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);

        $vary = $response->headers->get('Vary');
        if ($vary === null) {
            $response->headers->set('Vary', 'Origin');
        } elseif (!str_contains($vary, 'Origin')) {
            $response->headers->set('Vary', $vary . ', Origin');
        }
    }
}
