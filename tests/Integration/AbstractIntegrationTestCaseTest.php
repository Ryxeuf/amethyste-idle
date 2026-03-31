<?php

namespace App\Tests\Integration;

use App\Entity\App\Player;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Validates that AbstractIntegrationTestCase works:
 * - boots the kernel
 * - accesses real DB with fixtures
 * - transaction wrapping prevents data pollution
 */
class AbstractIntegrationTestCaseTest extends AbstractIntegrationTestCase
{
    public function testKernelBootsAndEntityManagerIsAvailable(): void
    {
        self::assertInstanceOf(EntityManagerInterface::class, $this->em);
    }

    public function testGetPlayerReturnsFixturePlayer(): void
    {
        $player = $this->getPlayer();

        self::assertInstanceOf(Player::class, $player);
        self::assertSame('Rémy', $player->getName());
        self::assertNotNull($player->getId());
    }

    public function testGetUserReturnsFixtureUser(): void
    {
        $user = $this->getUser();

        self::assertInstanceOf(User::class, $user);
        self::assertSame('remy@amethyste.game', $user->getEmail());
    }

    public function testGetServiceReturnsRealService(): void
    {
        $em = $this->getService(EntityManagerInterface::class);

        self::assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testTransactionRollbackIsolatesTests(): void
    {
        // Modify the player's gils
        $player = $this->getPlayer();
        $originalGils = $player->getGils();
        $player->setGils($originalGils + 99999);
        $this->em->flush();

        // Verify the change is visible within this test
        self::assertSame($originalGils + 99999, $player->getGils());
    }

    /**
     * This test runs AFTER testTransactionRollbackIsolatesTests.
     * The gils modification should have been rolled back.
     *
     * @depends testTransactionRollbackIsolatesTests
     */
    public function testPreviousTestChangesWereRolledBack(): void
    {
        $player = $this->getPlayer();

        // The player's gils should NOT include the +99999 from the previous test
        self::assertLessThan(
            99999,
            $player->getGils(),
            'Transaction rollback failed — previous test changes leaked.',
        );
    }

    public function testGetMobReturnsNullOrMob(): void
    {
        $mob = $this->getMob();

        // Mob may or may not exist depending on fixtures/map, but should not throw
        if ($mob !== null) {
            self::assertNotNull($mob->getMonster());
        } else {
            self::assertTrue(true, 'No mob on player map — acceptable.');
        }
    }

    public function testRefreshEntityReloadsFromDatabase(): void
    {
        $player = $this->getPlayer();
        $playerId = $player->getId();
        $player->setGils($player->getGils() + 1);
        $this->em->flush();

        // Refresh reloads from DB after clearing the identity map
        $reloaded = $this->refreshEntity(Player::class, $playerId);

        self::assertNotNull($reloaded);
        self::assertSame($playerId, $reloaded->getId());
    }
}
