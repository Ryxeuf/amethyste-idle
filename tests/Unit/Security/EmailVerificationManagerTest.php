<?php

namespace App\Tests\Unit\Security;

use App\Entity\EmailVerificationRequest;
use App\Entity\User;
use App\Repository\EmailVerificationRequestRepository;
use App\Security\EmailVerificationManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * L'envoi et la constatation de la verification (ONB-04).
 *
 * Meme anatomie de jeton que le mot de passe oublie, memes lois : un actif
 * par compte, stocke hache, usage unique. En plus : l'idempotence (verifier
 * un compte deja verifie n'envoie rien), et le rappel qui ne change que le
 * ton, jamais le mecanisme.
 */
class EmailVerificationManagerTest extends TestCase
{
    private const VERIFIER = 'c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6';

    private EntityManagerInterface&MockObject $entityManager;
    private EmailVerificationRequestRepository&MockObject $requestRepository;
    private MailerInterface&MockObject $mailer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->requestRepository = $this->createMock(EmailVerificationRequestRepository::class);
        $this->mailer = $this->createMock(MailerInterface::class);
    }

    /**
     * Un compte deja verifie ne recoit rien : la porte ne se referme jamais,
     * et on ne renvoie pas un lien qui n'ouvre rien.
     */
    public function testAVerifiedAccountGetsNoEmail(): void
    {
        $user = new User();
        $user->setEmail('verifie@exemple.fr');
        $user->setEmailVerifiedAt(new \DateTimeImmutable());

        $this->mailer->expects($this->never())->method('send');
        $this->entityManager->expects($this->never())->method('persist');

        $this->manager()->sendVerification($user);
    }

    public function testABannedAccountGetsNoEmail(): void
    {
        $user = new User();
        $user->setEmail('banni@exemple.fr');
        $user->setIsBanned(true);

        $this->mailer->expects($this->never())->method('send');

        $this->manager()->sendVerification($user);
    }

    /**
     * Le chemin nominal : le jeton precedent est remplace, le lien part, la
     * base n'a que le hachage, et la fenetre est de 48 heures.
     */
    public function testSendStoresTheHashAndMailsTheLink(): void
    {
        $user = new User();
        $user->setEmail('joueur@exemple.fr');

        $this->requestRepository->expects($this->once())->method('removeActiveRequestFor')->with($user);

        $persisted = null;
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted = $entity;
        });

        $sent = null;
        $this->mailer->expects($this->once())->method('send')->willReturnCallback(function (TemplatedEmail $email) use (&$sent): void {
            $sent = $email;
        });

        $this->manager()->sendVerification($user);

        self::assertInstanceOf(EmailVerificationRequest::class, $persisted);
        self::assertNotNull($sent);

        $url = $sent->getContext()['verify_url'];
        $token = substr($url, \strlen('https://game.test/verify-email/'));

        self::assertSame(72, \strlen($token));
        self::assertTrue(ctype_xdigit($token));
        self::assertSame($persisted->getSelector(), substr($token, 0, 24));
        self::assertSame(hash('sha256', substr($token, 24)), $persisted->getHashedVerifier());
        self::assertFalse($persisted->isExpired(new \DateTimeImmutable('+47 hours')), '48 heures, pas une.');
        self::assertTrue($persisted->isExpired(new \DateTimeImmutable('+49 hours')));
        self::assertSame('no-reply@amethyste.best', $sent->getFrom()[0]->getAddress());
        self::assertNotNull($sent->getTextTemplate(), 'Le repli texte fait partie du contrat.');
        self::assertFalse($sent->getContext()['reminder']);
        self::assertSame('security.verification.email.subject', $sent->getSubject());
    }

    /**
     * Le rappel (J+1/J+3) regenere un jeton frais et ne change que le ton.
     */
    public function testAReminderOnlyChangesTheTone(): void
    {
        $user = new User();
        $user->setEmail('distrait@exemple.fr');

        $sent = null;
        $this->mailer->method('send')->willReturnCallback(function (TemplatedEmail $email) use (&$sent): void {
            $sent = $email;
        });

        $this->manager()->sendVerification($user, reminder: true);

        self::assertNotNull($sent);
        self::assertTrue($sent->getContext()['reminder']);
        self::assertSame('security.verification.email.reminder_subject', $sent->getSubject());
    }

    public function testAMalformedTokenIsRefused(): void
    {
        self::assertNull($this->manager()->verify('pas-un-jeton'));
        self::assertNull($this->manager()->verify(str_repeat('z', 72)));
    }

    public function testAnExpiredTokenIsRefused(): void
    {
        $request = $this->storedRequest('-1 minute');
        $this->requestRepository->method('findOneBySelector')->willReturn($request);

        self::assertNull($this->manager()->verify($request->getSelector() . self::VERIFIER));
    }

    public function testAWrongVerifierIsRefusedEvenWithAKnownSelector(): void
    {
        $request = $this->storedRequest('+30 minutes');
        $this->requestRepository->method('findOneBySelector')->willReturn($request);

        self::assertNull($this->manager()->verify($request->getSelector() . str_repeat('b', 48)));
    }

    /**
     * Le clic constate : `emailVerifiedAt` est pose, le jeton est consomme
     * dans le meme flush — rejoue, il retombera sur « selecteur inconnu ».
     */
    public function testAValidTokenVerifiesAndConsumes(): void
    {
        $user = new User();
        $user->setEmail('joueur@exemple.fr');

        $request = $this->storedRequest('+30 minutes');
        $request->setUser($user);
        $this->requestRepository->method('findOneBySelector')->willReturn($request);

        $this->entityManager->expects($this->once())->method('remove')->with($request);
        $this->entityManager->expects($this->once())->method('flush');

        $verified = $this->manager()->verify($request->getSelector() . self::VERIFIER);

        self::assertSame($user, $verified);
        self::assertTrue($user->isEmailVerified());
    }

    /**
     * Verifier un compte deja verifie n'est pas une erreur, et ne repose pas
     * la date : la premiere verification fait foi (aucun effet retroactif,
     * dans aucun sens).
     */
    public function testVerifyingTwiceKeepsTheFirstDate(): void
    {
        $firstDate = new \DateTimeImmutable('-2 days');
        $user = new User();
        $user->setEmail('joueur@exemple.fr');
        $user->setEmailVerifiedAt($firstDate);

        $request = $this->storedRequest('+30 minutes');
        $request->setUser($user);
        $this->requestRepository->method('findOneBySelector')->willReturn($request);

        $this->manager()->verify($request->getSelector() . self::VERIFIER);

        self::assertSame($firstDate, $user->getEmailVerifiedAt());
    }

    private function storedRequest(string $expiresIn): EmailVerificationRequest
    {
        $request = new EmailVerificationRequest();
        $request->setSelector('a1b2c3d4e5f60718293a4b5c');
        $request->setHashedVerifier(hash('sha256', self::VERIFIER));
        $request->setExpiresAt(new \DateTimeImmutable($expiresIn));

        return $request;
    }

    private function manager(): EmailVerificationManager
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            fn (string $route, array $params): string => 'https://game.test/verify-email/' . $params['token'],
        );

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $key): string => $key);

        return new EmailVerificationManager(
            $this->entityManager,
            $this->requestRepository,
            $this->mailer,
            $urlGenerator,
            $translator,
            'no-reply@amethyste.best',
        );
    }
}
