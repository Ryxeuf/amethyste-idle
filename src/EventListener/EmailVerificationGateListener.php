<?php

namespace App\EventListener;

use App\Entity\User;
use App\Security\Attribute\RequiresVerifiedEmail;
use App\Security\EmailVerificationGate;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * L'aiguillage de la porte de verification (ONB-04).
 *
 * Une action marquee `#[RequiresVerifiedEmail]` ne s'execute pas pour un
 * compte non verifie : une requete JSON recoit un 403 qui dit pourquoi, une
 * navigation est conduite a l'ecran de porte — qui montre ce qui est
 * verrouille, pourquoi, et « renvoyer le lien ». **Le blocage se lit comme
 * une porte, jamais comme une panne.**
 *
 * Le listener ne decide rien : il demande a `EmailVerificationGate` (le point
 * unique) et met en forme le refus. Fermer une porte de plus = poser
 * l'attribut sur l'action.
 */
#[AsEventListener(event: KernelEvents::CONTROLLER)]
class EmailVerificationGateListener
{
    public function __construct(
        private readonly Security $security,
        private readonly EmailVerificationGate $gate,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(ControllerEvent $event): void
    {
        /** @var list<RequiresVerifiedEmail> $attributes */
        $attributes = $event->getAttributes(RequiresVerifiedEmail::class);
        if ($attributes === []) {
            return;
        }

        $user = $this->security->getUser();
        $user = $user instanceof User ? $user : null;

        $channel = $attributes[0]->channel;
        if ($this->gate->allows($user, $channel)) {
            return;
        }

        $request = $event->getRequest();

        // Les canaux JSON (API v1, actions XHR du jeu) recoivent un refus
        // structure ; la navigation classique est conduite a l'ecran de porte.
        $wantsJson = str_starts_with($request->getPathInfo(), '/api/')
            || $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept'), 'json');

        if ($wantsJson) {
            $response = new JsonResponse([
                'error' => $this->translator->trans('security.verification.locked'),
                'code' => 'email_verification_required',
                'channel' => $channel,
            ], Response::HTTP_FORBIDDEN);
        } else {
            $response = new RedirectResponse(
                $this->urlGenerator->generate('app_verification_gate', ['from' => $channel]),
            );
        }

        $event->setController(static fn (): Response => $response);
    }
}
