<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * ONB-03 — un bannissement prend effet tout de suite, pas a la prochaine
 * connexion.
 *
 * `UserChecker` refuse le banni **au login**. Il ne voit rien du joueur deja
 * connecte : Symfony ne rejoue pas le controle d'utilisateur a chaque requete,
 * et une session ouverte survivrait donc au bannissement — jusqu'a un mois avec
 * le « se souvenir de moi ». Or on bannit rarement quelqu'un qui est hors ligne.
 *
 * Ici, le message dit la verite. Il n'y a plus d'oracle a proteger : la personne
 * est authentifiee, elle sait deja que son compte existe.
 */
class BannedUserSubscriber implements EventSubscriberInterface
{
    public const BAN_MESSAGE = 'Votre compte a ete suspendu.';

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Apres le pare-feu (8), donc avec un utilisateur resolu, mais avant le
        // controleur : un banni ne doit executer aucune action de jeu.
        return [KernelEvents::REQUEST => ['onKernelRequest', 6]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User || !$user->isBanned()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');

        // La deconnexion et l'ecran de connexion restent atteignables : sinon la
        // redirection tourne en rond et la session bannie ne peut pas se fermer.
        if (in_array($route, ['app_logout', 'app_login', 'app_home'], true)) {
            return;
        }

        $this->security->logout(false);

        $session = $event->getRequest()->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', self::BAN_MESSAGE);
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
    }
}
