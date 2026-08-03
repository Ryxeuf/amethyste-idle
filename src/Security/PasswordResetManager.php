<?php

namespace App\Security;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Le mot de passe oublie (ONB-02) — ferme D2 : perdre son mot de passe
 * revenait a perdre son personnage, son inventaire, sa guilde et sa place
 * dans un foyer.
 *
 * Quatre lois, et ou chacune se tient :
 *
 * - **la reponse est identique** que le compte existe ou non — `requestReset`
 *   rend `void` et ne jette jamais pour un e-mail inconnu : rien ne confirme
 *   a un curieux qu'une adresse a un compte ;
 * - **un seul jeton actif par compte** — la nouvelle demande remplace
 *   l'ancienne (et l'index unique sur `user_id` le tient cote schema) ;
 * - **une heure, usage unique, stocke hache** — le jeton envoye est
 *   `selecteur . verificateur` ; seul `sha256(verificateur)` est en base, la
 *   comparaison passe par `hash_equals`, et la reinitialisation detruit la
 *   ligne avant meme de repondre ;
 * - **toutes les sessions tombent** — le changement de mot de passe suffit :
 *   a la requete suivante, le `ContextListener` de Symfony recharge le compte,
 *   voit un hachage different de celui du jeton de session
 *   (`AbstractToken::hasUserChanged`) et deconnecte. Aucune table de sessions
 *   a purger, le mecanisme vaut pour chaque navigateur ouvert.
 *
 * L'envoi part de `no-reply@amethyste.best` (decision du 2026-08-02 :
 * fournisseur Brevo, `MAILER_DSN` en env — `null://` en test, le DSN de prod
 * se branche quand le compte et le DNS sont operationnels).
 */
class PasswordResetManager
{
    /**
     * Une heure : assez pour un aller-retour de boite aux lettres, trop court
     * pour un jeton qui traine dans un historique de messagerie.
     */
    public const TOKEN_TTL = '+1 hour';

    private const SELECTOR_BYTES = 12;
    private const VERIFIER_BYTES = 24;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly PasswordResetRequestRepository $requestRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        #[Autowire('%app.mailer_from%')]
        private readonly string $mailerFrom,
    ) {
    }

    /**
     * Demande une reinitialisation pour cette adresse.
     *
     * Ne dit **jamais** si l'adresse a un compte : le chemin inconnu sort en
     * silence, le chemin connu envoie l'e-mail. L'appelant affiche le meme
     * message dans les deux cas. Le jeton en clair ne vit que dans l'e-mail —
     * un test l'observe par le contexte du message, jamais par un retour.
     */
    public function requestReset(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => mb_strtolower(trim($email))]);
        if (null === $user || $user->isBanned()) {
            // Reponse identique : le silence ici, le meme message la-haut.
            return;
        }

        // Un seul jeton actif : la nouvelle demande remplace l'ancienne.
        $this->requestRepository->removeActiveRequestFor($user);

        $selector = bin2hex(random_bytes(self::SELECTOR_BYTES));
        $verifier = bin2hex(random_bytes(self::VERIFIER_BYTES));

        $request = new PasswordResetRequest();
        $request->setUser($user);
        $request->setSelector($selector);
        $request->setHashedVerifier(hash('sha256', $verifier));
        $request->setExpiresAt(new \DateTimeImmutable(self::TOKEN_TTL));

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        $this->sendResetEmail($user, $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $selector . $verifier],
            UrlGeneratorInterface::ABSOLUTE_URL,
        ));
    }

    /**
     * La demande que ce jeton designe, si elle est encore valable — `null`
     * pour tout le reste : jeton malforme, selecteur inconnu, verificateur
     * faux, demande expiree. Un seul refus, aucune nuance : la nuance
     * renseignerait l'attaquant.
     */
    public function validateToken(string $token): ?PasswordResetRequest
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

        $verifier = substr($token, $selectorLength);
        if (!hash_equals($request->getHashedVerifier(), hash('sha256', $verifier))) {
            return null;
        }

        return $request;
    }

    /**
     * Reinitialise le mot de passe et consomme la demande.
     *
     * La ligne part dans le meme flush que le nouveau mot de passe : rejouer
     * le jeton apres coup retombe sur « selecteur inconnu », le meme refus
     * que pour un jeton invente.
     */
    public function reset(PasswordResetRequest $request, string $plainPassword): void
    {
        $user = $request->getUser();
        \assert($user instanceof User);

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->remove($request);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // L'invalidation des sessions ne demande rien de plus : le hachage a
        // change, `ContextListener` deconnectera chaque session existante a sa
        // prochaine requete (cf. l'en-tete de classe).
    }

    private function sendResetEmail(User $user, string $resetUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'Amethyste'))
            ->to(new Address((string) $user->getEmail()))
            ->subject($this->translator->trans('security.reset.email.subject'))
            ->htmlTemplate('emails/password_reset.html.twig')
            ->textTemplate('emails/password_reset.txt.twig')
            ->context([
                'reset_url' => $resetUrl,
            ]);

        $this->mailer->send($email);
    }
}
