<?php

namespace App\Controller\Security;

use App\Form\RegistrationFormType;
use App\Security\PasswordResetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Le mot de passe oublie (ONB-02) — les deux ecrans du parcours.
 *
 * La demande repond **la meme chose** que l'adresse ait un compte ou non :
 * un seul message, une seule redirection, aucun embranchement observable.
 * Les deux limiteurs suivent le modele du login (ONB-03) : une borne par
 * adresse visee (on ne bombarde pas la boite d'un joueur), une par IP (on ne
 * sonde pas l'annuaire depuis une machine).
 *
 * Le mot de passe reprend la regle de l'inscription — meme longueur minimale,
 * meme vocabulaire : un joueur qui reinitialise ne doit pas decouvrir une
 * politique differente de celle du jour ou il s'est inscrit.
 */
class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetManager $resetManager,
        private readonly RateLimiterFactoryInterface $passwordResetLocalLimiter,
        private readonly RateLimiterFactoryInterface $passwordResetGlobalLimiter,
    ) {
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function request(Request $request): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_game');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot_password', (string) $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'security.reset.invalid_csrf');

                return $this->redirectToRoute('app_forgot_password');
            }

            $email = mb_strtolower(trim((string) $request->request->get('email')));

            // Deux bornes, comme le login : l'adresse visee et la machine qui
            // demande. Le refus est un vrai refus (429), pas un faux succes —
            // un limiteur silencieux cacherait une panne d'envoi legitime.
            $local = $this->passwordResetLocalLimiter->create($email !== '' ? $email : 'empty');
            $global = $this->passwordResetGlobalLimiter->create($request->getClientIp() ?? 'unknown');

            if (!$global->consume()->isAccepted() || !$local->consume()->isAccepted()) {
                $this->addFlash('error', 'security.reset.rate_limited');

                return $this->render('security/forgot_password.html.twig', [
                    'sent' => false,
                ], new Response('', Response::HTTP_TOO_MANY_REQUESTS));
            }

            if ($email !== '') {
                $this->resetManager->requestReset($email);
            }

            // Reponse identique que le compte existe ou non : le meme ecran,
            // le meme message, via la meme redirection.
            return $this->redirectToRoute('app_forgot_password', ['sent' => 1]);
        }

        return $this->render('security/forgot_password.html.twig', [
            'sent' => $request->query->getBoolean('sent'),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_game');
        }

        $resetRequest = $this->resetManager->validateToken($token);
        if (null === $resetRequest) {
            // Un seul refus pour tout : jeton invente, expire, deja consomme.
            $this->addFlash('error', 'security.reset.invalid_token');

            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password', (string) $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'security.reset.invalid_csrf');

                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            $plainPassword = (string) $request->request->get('password');
            if (mb_strlen($plainPassword) < RegistrationFormType::PASSWORD_MIN_LENGTH) {
                $this->addFlash('error', 'security.reset.password_too_short');

                return $this->render('security/reset_password.html.twig', [
                    'token' => $token,
                    'password_min_length' => RegistrationFormType::PASSWORD_MIN_LENGTH,
                ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $this->resetManager->reset($resetRequest, $plainPassword);

            $this->addFlash('success', 'security.reset.done');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'password_min_length' => RegistrationFormType::PASSWORD_MIN_LENGTH,
        ]);
    }
}
