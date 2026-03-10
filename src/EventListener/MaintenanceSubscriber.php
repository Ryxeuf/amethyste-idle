<?php

namespace App\EventListener;

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
