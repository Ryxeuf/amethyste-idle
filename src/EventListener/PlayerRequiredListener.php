<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class PlayerRequiredListener
{
    private const ALLOWED_PREFIXES = [
        '/game/character',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/game')) {
            return;
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        if ($user->getPlayers()->isEmpty()) {
            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate('app_character_create')
            ));
        }
    }
}
