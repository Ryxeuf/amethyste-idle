<?php

namespace App\Security;

use App\Entity\EmailVerificationRequest;
use App\Entity\User;
use App\Repository\EmailVerificationRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * L'envoi et la constatation de la verification d'e-mail (ONB-04).
 *
 * Meme anatomie de jeton que le mot de passe oublie (ONB-02) : selecteur +
 * verificateur, seul le hachage en base, un seul actif par compte, le renvoi
 * remplace. Deux differences assumees :
 *
 * - **48 heures** au lieu d'une : la verification n'ouvre pas le compte,
 *   seulement le marche et le social — l'e-mail peut attendre le retour de
 *   week-end, et les rappels J+1/J+3 regenerent un jeton frais de toute facon ;
 * - **l'envoi ne casse jamais l'appelant** : une inscription ne doit pas
 *   echouer parce que le relais SMTP tousse. L'erreur de transport est
 *   avalee — le joueur a « renvoyer le lien » sur l'ecran de porte, et les
 *   rappels reessaieront.
 *
 * La verification elle-meme est **a sens unique et idempotente** : une fois
 * `emailVerifiedAt` pose, rien ne le retire (aucun blocage retroactif), et
 * verifier un compte deja verifie n'est pas une erreur.
 */
class EmailVerificationManager
{
    public const TOKEN_TTL = '+48 hours';

    private const SELECTOR_BYTES = 12;
    private const VERIFIER_BYTES = 24;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailVerificationRequestRepository $requestRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        #[Autowire('%app.mailer_from%')]
        private readonly string $mailerFrom,
    ) {
    }

    /**
     * Envoie (ou renvoie) le lien de verification. Le jeton precedent est
     * remplace ; un compte deja verifie ne recoit rien.
     *
     * `$reminder` ne change que le sujet et l'accroche : J+1 et J+3 relancent
     * avec un jeton frais, puis silence (GAME_ONBOARDING §3.2).
     */
    public function sendVerification(User $user, bool $reminder = false): void
    {
        if ($user->isEmailVerified() || $user->isBanned()) {
            return;
        }

        $this->requestRepository->removeActiveRequestFor($user);

        $selector = bin2hex(random_bytes(self::SELECTOR_BYTES));
        $verifier = bin2hex(random_bytes(self::VERIFIER_BYTES));

        $request = new EmailVerificationRequest();
        $request->setUser($user);
        $request->setSelector($selector);
        $request->setHashedVerifier(hash('sha256', $verifier));
        $request->setExpiresAt(new \DateTimeImmutable(self::TOKEN_TTL));

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        $verifyUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $selector . $verifier],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $subjectKey = $reminder
            ? 'security.verification.email.reminder_subject'
            : 'security.verification.email.subject';

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'Amethyste'))
            ->to(new Address((string) $user->getEmail()))
            ->subject($this->translator->trans($subjectKey))
            ->htmlTemplate('emails/email_verification.html.twig')
            ->textTemplate('emails/email_verification.txt.twig')
            ->context([
                'verify_url' => $verifyUrl,
                'reminder' => $reminder,
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            // L'inscription ne doit pas echouer parce que le relais tousse :
            // « renvoyer le lien » et les rappels J+1/J+3 reessaieront.
        }
    }

    /**
     * Constate la verification que ce jeton designe.
     *
     * Rend l'utilisateur verifie, ou `null` pour tout le reste — jeton
     * malforme, selecteur inconnu, verificateur faux, jeton expire. Le jeton
     * est consomme dans le meme flush que `emailVerifiedAt` : rejoue, il
     * retombe sur « selecteur inconnu ».
     */
    public function verify(string $token): ?User
    {
        $selectorLength = self::SELECTOR_BYTES * 2;
        $verifierLength = self::VERIFIER_BYTES * 2;
        if (\strlen($token) !== $selectorLength + $verifierLength || !ctype_xdigit($token)) {
            return null;
        }

        $request = $this->requestRepository->findOneBySelector(substr($token, 0, $selectorLength));
        if (null === $request || $request->isExpired()) {
            return null;
        }

        if (!hash_equals($request->getHashedVerifier(), hash('sha256', substr($token, $selectorLength)))) {
            return null;
        }

        $user = $request->getUser();
        \assert($user instanceof User);

        // Idempotent par construction : si un autre canal a verifie entre
        // l'envoi et le clic, on ne repose pas la date — la premiere fait foi.
        if (!$user->isEmailVerified()) {
            $user->setEmailVerifiedAt(new \DateTimeImmutable());
        }

        $this->entityManager->remove($request);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
