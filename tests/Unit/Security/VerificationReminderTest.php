<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerificationManager;
use App\Security\VerificationReminder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * J+1, J+3, puis silence (ONB-04).
 *
 * Le compteur sur le compte fait foi : rejouer le passage quotidien ne
 * renvoie rien a qui a deja recu son palier, et il n'existe pas de
 * troisieme rappel — le silence est une decision de design, pas un oubli.
 */
class VerificationReminderTest extends TestCase
{
    public function testDueAccountsGetTheirStageAndTheCounterMoves(): void
    {
        $fresh = new User();
        $fresh->setEmail('j1@exemple.fr');

        $old = new User();
        $old->setEmail('j3@exemple.fr');
        $old->setVerificationReminderCount(1);

        $repository = $this->createMock(UserRepository::class);
        $repository->method('findDueForVerificationReminder')->willReturnCallback(
            fn (int $stage): array => match ($stage) {
                1 => [$fresh],
                2 => [$old],
                default => [],
            },
        );

        $manager = $this->createMock(EmailVerificationManager::class);
        $manager->expects($this->exactly(2))->method('sendVerification');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $sent = (new VerificationReminder($repository, $manager, $entityManager))->remindDueAccounts();

        self::assertSame([1 => 1, 2 => 1], $sent);
        self::assertSame(1, $fresh->getVerificationReminderCount());
        self::assertSame(2, $old->getVerificationReminderCount());
    }

    /**
     * Les seuils : le rappel n° 1 se cherche a J-1, le n° 2 a J-3. C'est la
     * requete qui filtre — le service ne fait que lui passer le bon seuil.
     */
    public function testTheStagesAskForTheRightThresholds(): void
    {
        $now = new \DateTimeImmutable('2026-08-03 09:30:00');
        $asked = [];

        $repository = $this->createMock(UserRepository::class);
        $repository->method('findDueForVerificationReminder')->willReturnCallback(
            function (int $stage, \DateTimeImmutable $threshold) use (&$asked): array {
                $asked[$stage] = $threshold;

                return [];
            },
        );

        $manager = $this->createMock(EmailVerificationManager::class);
        $manager->expects($this->never())->method('sendVerification');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $sent = (new VerificationReminder($repository, $manager, $entityManager))->remindDueAccounts($now);

        self::assertSame([1 => 0, 2 => 0], $sent);
        self::assertEquals(new \DateTimeImmutable('2026-08-02 09:30:00'), $asked[1]);
        self::assertEquals(new \DateTimeImmutable('2026-07-31 09:30:00'), $asked[2]);
        self::assertCount(2, $asked, 'Deux paliers, pas de troisieme : apres J+3, le silence.');
    }
}
