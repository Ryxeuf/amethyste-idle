<?php

namespace App\Tests\Unit\Security;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
use App\Repository\UserRepository;
use App\Security\PasswordResetManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Le mot de passe oublie (ONB-02) — les quatre lois du jalon.
 *
 * La reponse est identique que le compte existe ou non ; un seul jeton actif
 * par compte ; une heure, usage unique, stocke hache ; et le rejeu retombe
 * sur le refus commun. L'invalidation des sessions, elle, decoule du
 * changement de hachage (ContextListener) — ce test verifie que le hachage
 * change et que la demande est detruite dans le meme geste.
 */
class PasswordResetManagerTest extends TestCase
{
    private const VERIFIER = 'c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6';

    private EntityManagerInterface&MockObject $entityManager;
    private UserRepository&MockObject $userRepository;
    private PasswordResetRequestRepository&MockObject $requestRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private MailerInterface&MockObject $mailer;
    private UrlGeneratorInterface&MockObject $urlGenerator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->requestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')->willReturnCallback(
            fn (string $route, array $params): string => 'https://game.test/reset-password/' . $params['token'],
        );
    }

    /**
     * Reponse identique : une adresse inconnue ne produit ni ligne, ni
     * e-mail, ni exception. Rien n'apprend a un curieux qu'un compte existe.
     */
    public function testAnUnknownEmailProducesNothingAndStaysSilent(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $this->entityManager->expects($this->never())->method('persist');
        $this->mailer->expects($this->never())->method('send');

        $this->manager()->requestReset('inconnue@exemple.fr');
    }

    /**
     * Un compte banni ne recoit pas de lien : la porte de connexion lui est
     * deja fermee (ONB-03), celle-ci suit — avec le meme silence.
     */
    public function testABannedAccountGetsTheSameSilence(): void
    {
        $user = new User();
        $user->setEmail('banni@exemple.fr');
        $user->setIsBanned(true);
        $this->userRepository->method('findOneBy')->willReturn($user);

        $this->mailer->expects($this->never())->method('send');

        $this->manager()->requestReset('banni@exemple.fr');
    }

    /**
     * Le chemin nominal : la demande remplace l'ancienne, le jeton part par
     * e-mail, et la base n'en garde que le hachage.
     */
    public function testARequestStoresTheHashAndMailsTheToken(): void
    {
        $user = new User();
        $user->setEmail('joueur@exemple.fr');
        $this->userRepository->method('findOneBy')->willReturn($user);

        // Un seul jeton actif par compte : l'ancien part d'abord.
        $this->requestRepository->expects($this->once())->method('removeActiveRequestFor')->with($user);

        $persisted = null;
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted = $entity;
        });

        $sent = null;
        $this->mailer->expects($this->once())->method('send')->willReturnCallback(function (TemplatedEmail $email) use (&$sent): void {
            $sent = $email;
        });

        $this->manager()->requestReset('Joueur@Exemple.fr');

        self::assertInstanceOf(PasswordResetRequest::class, $persisted);
        self::assertNotNull($sent);

        $url = $sent->getContext()['reset_url'];
        $token = substr($url, \strlen('https://game.test/reset-password/'));

        // 24 hex de selecteur + 48 hex de verificateur.
        self::assertSame(72, \strlen($token));
        self::assertTrue(ctype_xdigit($token));
        self::assertSame($persisted->getSelector(), substr($token, 0, 24));
        // La base ne connait que le hachage — jamais le verificateur.
        self::assertSame(hash('sha256', substr($token, 24)), $persisted->getHashedVerifier());
        self::assertFalse($persisted->isExpired(), 'La demande naît valable.');
        self::assertTrue(
            $persisted->isExpired(new \DateTimeImmutable('+61 minutes')),
            'Une heure, puis rien.'
        );

        // L'expediteur acte : no-reply@amethyste.best (2026-08-02).
        self::assertSame('no-reply@amethyste.best', $sent->getFrom()[0]->getAddress());
        self::assertNotNull($sent->getTextTemplate(), 'Le repli texte fait partie du contrat.');
    }

    public function testAMalformedTokenIsRefused(): void
    {
        self::assertNull($this->manager()->validateToken('pas-un-jeton'));
        self::assertNull($this->manager()->validateToken(str_repeat('z', 72)));
        self::assertNull($this->manager()->validateToken(str_repeat('a', 71)));
    }

    public function testAWrongVerifierIsRefusedEvenWithAKnownSelector(): void
    {
        $request = $this->storedRequest('+30 minutes');
        $this->requestRepository->method('findOneBySelector')->willReturn($request);

        $token = $request->getSelector() . str_repeat('b', 48);

        self::assertNull($this->manager()->validateToken($token));
    }

    /**
     * Un jeton expire est un refus — le meme que pour un jeton invente,
     * aucune nuance qui renseignerait l'attaquant.
     */
    public function testAnExpiredTokenIsRefused(): void
    {
        $request = $this->storedRequest('-1 minute');
        $this->requestRepository->method('findOneBySelector')->willReturn($request);

        self::assertNull($this->manager()->validateToken($request->getSelector() . self::VERIFIER));
    }

    public function testAValidTokenReturnsItsRequest(): void
    {
        $request = $this->storedRequest('+30 minutes');
        $this->requestRepository->method('findOneBySelector')->willReturn($request);

        self::assertSame($request, $this->manager()->validateToken($request->getSelector() . self::VERIFIER));
    }

    /**
     * La reinitialisation consomme la demande dans le meme flush que le
     * nouveau hachage : le rejeu retombe sur « selecteur inconnu ».
     */
    public function testResetConsumesTheRequestAndChangesTheHash(): void
    {
        $user = new User();
        $user->setEmail('joueur@exemple.fr');
        $user->setPassword('ancien-hachage');

        $request = $this->storedRequest('+30 minutes');
        $request->setUser($user);

        $this->passwordHasher->method('hashPassword')->willReturn('nouveau-hachage');

        // Usage unique : la ligne part, le rejeu ne trouvera plus rien.
        $this->entityManager->expects($this->once())->method('remove')->with($request);
        $this->entityManager->expects($this->once())->method('flush');

        $this->manager()->reset($request, 'nouveau-mot-de-passe-long');

        // Le hachage change : c'est lui qui fait tomber toutes les sessions
        // ouvertes (ContextListener compare le hachage du jeton de session a
        // celui de la base et deconnecte quand ils divergent).
        self::assertSame('nouveau-hachage', $user->getPassword());
    }

    private function storedRequest(string $expiresIn): PasswordResetRequest
    {
        $request = new PasswordResetRequest();
        $request->setSelector('a1b2c3d4e5f60718293a4b5c');
        $request->setHashedVerifier(hash('sha256', self::VERIFIER));
        $request->setExpiresAt(new \DateTimeImmutable($expiresIn));

        return $request;
    }

    private function manager(): PasswordResetManager
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $key): string => $key);

        return new PasswordResetManager(
            $this->entityManager,
            $this->userRepository,
            $this->requestRepository,
            $this->passwordHasher,
            $this->mailer,
            $this->urlGenerator,
            $translator,
            'no-reply@amethyste.best',
        );
    }
}
