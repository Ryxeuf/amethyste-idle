<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleListener implements EventSubscriberInterface
{
    public function __construct(private readonly string $defaultLocale = 'fr')
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Si une session existe, utiliser la locale enregistrée
        if ($request->hasPreviousSession() && $locale = $request->getSession()->get('_locale')) {
            $request->setLocale($locale);
        } else {
            // Sinon (première visite ou pas de session) : locale par défaut
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
