<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleListener implements EventSubscriberInterface
{
    private $defaultLocale;

    public function __construct(string $defaultLocale = 'fr')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Essayer de voir si la locale est définie dans la session
        if ($locale = $request->getSession()->get('_locale')) {
            $request->setLocale($locale);
        } else {
            // Si aucune locale n'est définie, utiliser la locale par défaut
            $request->setLocale($this->defaultLocale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Doit être enregistré avant le Locale listener par défaut
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
} 