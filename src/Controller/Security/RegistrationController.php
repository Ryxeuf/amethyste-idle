<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerificationManager;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ONB-01 — l'inscription : le compte peut naitre.
 *
 * Avant ce jalon, `/register` levait un 404 : aucun compte n'existait hors
 * fixtures, et le jeu n'avait litteralement aucun joueur possible.
 *
 * Le compte nait **non verifie et pleinement jouable** (decision A1) : rien
 * n'attend un clic dans une boite aux lettres avant de jouer. La porte que la
 * verification ouvre — marche, guildes, chat — est posee par ONB-04.
 *
 * Le pas suivant est la creation de personnage : le tunnel en quatre pas
 * (ONB-05) reprendra cette redirection sans la deplacer.
 */
class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Security $security,
        private readonly RateLimiterFactoryInterface $registrationLimiter,
        private readonly EmailVerificationManager $verificationManager,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_game');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $this->registrationLimiter->create($request->getClientIp() ?? 'unknown');

            if (!$limiter->consume()->isAccepted()) {
                $this->addFlash('error', 'registration.rate_limited');

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                    'password_min_length' => RegistrationFormType::PASSWORD_MIN_LENGTH,
                ], new Response('', Response::HTTP_TOO_MANY_REQUESTS));
            }

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $user->setRoles(['ROLE_PLAYER']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // ONB-04 : le lien de verification part tout de suite — mais le
            // compte est pleinement jouable sans lui (decision A1). Un envoi
            // qui echoue n'empeche rien : « renvoyer le lien » et les rappels
            // J+1/J+3 reessaieront.
            $this->verificationManager->sendVerification($user);

            $this->security->login($user, LoginFormAuthenticator::class, 'main');

            return $this->redirectToRoute('app_character_create');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'password_min_length' => RegistrationFormType::PASSWORD_MIN_LENGTH,
        ]);
    }
}
