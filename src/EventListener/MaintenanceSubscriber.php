<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    private const FLAG_FILE = 'var/maintenance.flag';

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly Environment $twig,
        private readonly Security $security,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $flagPath = $this->kernel->getProjectDir() . '/' . self::FLAG_FILE;
        if (!is_file($flagPath)) {
            return;
        }

        // Allow admins to access the site during maintenance
        $path = $event->getRequest()->getPathInfo();
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/login')) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $content = $this->twig->render('maintenance.html.twig');
        $event->setResponse(new Response($content, 503, [
            'Retry-After' => '60',
            'Content-Type' => 'text/html; charset=UTF-8',
        ]));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 512]],
        ];
    }
}
