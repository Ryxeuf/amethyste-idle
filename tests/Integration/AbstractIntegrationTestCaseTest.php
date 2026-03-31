<?php

namespace App\Tests\Integration;

/**
 * Verification test: ensures the AbstractIntegrationTestCase boots correctly,
 * can retrieve fixture entities, and that transaction rollback works.
 */
class AbstractIntegrationTestCaseTest extends AbstractIntegrationTestCase
{
    public function testKernelBootsAndPlayerIsAvailable(): void
    {
        $player = $this->getPlayer();

        $this->assertSame('Rémy', $player->getName());
        $this->assertNotNull($player->getId());
    }

    public function testGetUserReturnsFixtureUser(): void
    {
        $user = $this->getUser();

        $this->assertSame('remy@amethyste.game', $user->getEmail());
    }

    public function testGetMapReturnsFixtureMap(): void
    {
        $map = $this->getMap();

        $this->assertSame('Carte de test', $map->getName());
        $this->assertNotNull($map->getId());
    }

    public function testGetMobReturnsAvailableMob(): void
    {
        $mob = $this->getMob();

        $this->assertNotNull($mob->getId());
        $this->assertNotNull($mob->getMonster());
        $this->assertNull($mob->getFight(), 'Mob should not be in a fight.');
    }

    public function testCreateFightPersistsInDatabase(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob();

        $fight = $this->createFight($player, $mob);

        $this->assertNotNull($fight->getId());
        $this->assertTrue($fight->isInProgress());
        $this->assertSame($fight, $player->getFight());
        $this->assertSame($fight, $mob->getFight());
    }

    /**
     * Verify that the previous test's Fight was rolled back.
     */
    public function testTransactionRollbackIsolatesTests(): void
    {
        $player = $this->getPlayer();

        $this->assertNull(
            $player->getFight(),
            'Player should not have a fight — previous test should have been rolled back.'
        );
    }

    public function testGetFightReturnsNullWhenNoActiveFight(): void
    {
        $fight = $this->getFight();

        $this->assertNull($fight);
    }

    public function testPersistAndFlushWorksWithinTransaction(): void
    {
        $player = $this->getPlayer();
        $originalEnergy = $player->getEnergy();

        $player->setEnergy($originalEnergy - 10);
        $this->persistAndFlush($player);

        $this->refresh($player);
        $this->assertSame($originalEnergy - 10, $player->getEnergy());
    }

    public function testGetServiceReturnsContainerService(): void
    {
        $em = $this->getService(\Doctrine\ORM\EntityManagerInterface::class);

        $this->assertInstanceOf(\Doctrine\ORM\EntityManagerInterface::class, $em);
    }
}
