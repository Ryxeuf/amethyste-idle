<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Security\EmailVerificationGate;
use App\Security\EmailVerificationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * La verification d'e-mail : le clic et l'ecran de porte (ONB-04).
 *
 * Le clic (`/verify-email/{token}`) est public — on verifie souvent depuis un
 * autre appareil que celui du jeu. L'ecran de porte (`/game/verification`)
 * dit ce qui est verrouille, pourquoi, et porte le seul bouton « renvoyer le
 * lien » — limite en debit, car chaque renvoi est un e-mail sortant.
 */
class VerificationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerificationManager $verificationManager,
        private readonly EmailVerificationGate $gate,
        private readonly RateLimiterFactoryInterface $emailVerificationResendLimiter,
    ) {
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verify(string $token): Response
    {
        $verified = $this->verificationManager->verify($token);

        if (null === $verified) {
            // Un seul refus pour tout : jeton invente, expire, deja consomme.
            // Un compte deja verifie qui reclique tombe ici — et l'ecran de
            // porte le lui dira mieux qu'une erreur.
            $this->addFlash('error', 'security.verification.invalid_token');

            return $this->getUser() !== null
                ? $this->redirectToRoute('app_verification_gate')
                : $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'security.verification.done');

        return $this->getUser() !== null
            ? $this->redirectToRoute('app_game')
            : $this->redirectToRoute('app_login');
    }

    #[Route('/game/verification', name: 'app_verification_gate', methods: ['GET'])]
    public function gate(Request $request): Response
    {
        $user = $this->currentUser();

        return $this->render('security/verification_gate.html.twig', [
            'verified' => $this->gate->isVerified($user),
            'channels' => EmailVerificationGate::CHANNELS,
            'from' => $request->query->get('from'),
        ]);
    }

    #[Route('/game/verification/resend', name: 'app_verification_resend', methods: ['POST'])]
    public function resend(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('verification_resend', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'security.reset.invalid_csrf');

            return $this->redirectToRoute('app_verification_gate');
        }

        $user = $this->currentUser();
        if (null === $user || $this->gate->isVerified($user)) {
            return $this->redirectToRoute('app_verification_gate');
        }

        $limiter = $this->emailVerificationResendLimiter->create((string) $user->getId());
        if (!$limiter->consume()->isAccepted()) {
            $this->addFlash('error', 'security.verification.resend_rate_limited');

            return $this->redirectToRoute('app_verification_gate');
        }

        $this->verificationManager->sendVerification($user);
        $this->addFlash('success', 'security.verification.resent');

        return $this->redirectToRoute('app_verification_gate');
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
