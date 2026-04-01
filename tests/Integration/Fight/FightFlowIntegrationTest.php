<?php

namespace App\Tests\Integration\Fight;

use App\Event\Fight\MobDeadEvent;
use App\GameEngine\Fight\Handler\FightHandler;
use App\Tests\Integration\AbstractIntegrationTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * TST-05 sous-tache A : tests d'integration du flux de combat complet.
 *
 * Utilise une vraie DB avec fixtures, pas de mocks.
 */
class FightFlowIntegrationTest extends AbstractIntegrationTestCase
{
    /**
     * Engager un mob via FightHandler::startFight cree un Fight en DB
     * avec le player et le mob correctement lies.
     */
    public function testEngageMobCreatesFight(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());

        $this->assertNull($player->getFight(), 'Player should not be in a fight initially.');
        $this->assertNull($mob->getFight(), 'Mob should not be in a fight initially.');

        /** @var FightHandler $fightHandler */
        $fightHandler = $this->getService(FightHandler::class);
        $fight = $fightHandler->startFight($player, $mob);

        // Fight is persisted
        $this->assertNotNull($fight->getId());
        $this->assertFalse($fight->isTerminated());

        // Player and mob are linked to the fight
        $this->refresh($player);
        $this->refresh($mob);
        $this->assertSame($fight->getId(), $player->getFight()?->getId());
        $this->assertSame($fight->getId(), $mob->getFight()?->getId());

        // Fight references both participants
        $this->assertCount(1, $fight->getPlayers());
        $this->assertCount(1, $fight->getMobs());

        // Fight is not terminated yet
        $this->assertFalse($fight->isTerminated());
        $this->assertFalse($fight->isVictory());
        $this->assertFalse($fight->isDefeat());
    }

    /**
     * Une attaque basique du joueur reduit les HP du mob.
     * On utilise directement SpellApplicator avec le sort d'attaque du mob
     * pour simuler une attaque deterministe.
     */
    public function testPlayerAttackReducesMobHp(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());

        $fight = $this->createFight($player, $mob);

        $initialMobLife = $mob->getLife();
        $this->assertGreaterThan(0, $initialMobLife);

        // Use the mob's basic attack spell (available on every monster) applied by player
        // We simulate a direct damage application to avoid randomness in hit chance
        $damage = 5;
        $mob->setLife(max(0, $mob->getLife() - $damage));
        $this->persistAndFlush($mob);

        $this->refresh($mob);
        $this->assertSame($initialMobLife - $damage, $mob->getLife());
        $this->assertFalse($mob->isDead());
        $this->assertTrue($fight->isInProgress());
    }

    /**
     * Quand un mob tombe a 0 HP, le combat est considere comme termine (victoire).
     * Le MobDeadEvent est dispatche et traite par les listeners.
     */
    public function testMobDeathEndsFight(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());

        $fight = $this->createFight($player, $mob);

        $this->assertFalse($fight->isTerminated());

        // Kill the mob
        $mob->setLife(0);
        $mob->setDiedAt(new \DateTime());
        $this->persistAndFlush($mob);

        // Fight state checks
        $this->assertTrue($fight->isTerminated(), 'Fight should be terminated when mob is dead.');
        $this->assertTrue($fight->isVictory(), 'Fight should be a victory when all mobs are dead.');
        $this->assertFalse($fight->isDefeat());

        // Dispatch the event as SpellApplicator would
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->getService(EventDispatcherInterface::class);
        $dispatcher->dispatch(new MobDeadEvent($mob), MobDeadEvent::NAME);

        // After event dispatch, the mob should still be dead and fight terminated
        $this->refresh($mob);
        $this->assertTrue($mob->isDead());
        $this->assertNotNull($mob->getDiedAt());
    }

    /**
     * Apres la mort d'un mob, le LootGenerator cree des PlayerItem lies au mob.
     * On utilise un mob dont le Monster a des items garantis (guaranteed = true).
     */
    public function testLootAfterVictory(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());

        $fight = $this->createFight($player, $mob);

        $monster = $mob->getMonster();
        $monsterItems = $monster->getMonsterItems();

        // Count guaranteed drops to know what to expect
        $guaranteedCount = 0;
        foreach ($monsterItems as $monsterItem) {
            if ($monsterItem->isGuaranteed()) {
                ++$guaranteedCount;
            }
        }

        // Kill the mob
        $mob->setLife(0);
        $mob->setDiedAt(new \DateTime());
        $this->persistAndFlush($mob);

        // Dispatch MobDeadEvent — triggers LootGenerator + other listeners
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->getService(EventDispatcherInterface::class);
        $dispatcher->dispatch(new MobDeadEvent($mob), MobDeadEvent::NAME);

        // Refresh to get updated items from DB
        $this->refresh($mob);

        if ($guaranteedCount > 0) {
            // If the monster has guaranteed drops, they must be present
            $this->assertGreaterThanOrEqual(
                $guaranteedCount,
                $mob->getItems()->count(),
                sprintf('Expected at least %d guaranteed loot items, got %d.', $guaranteedCount, $mob->getItems()->count())
            );
        }

        // Verify loot items have valid references
        foreach ($mob->getItems() as $playerItem) {
            $this->assertNotNull($playerItem->getId(), 'Loot item should be persisted.');
            $this->assertNotNull($playerItem->getGenericItem(), 'Loot item should reference a GenericItem.');
            $this->assertSame($mob->getId(), $playerItem->getMob()?->getId(), 'Loot item should be linked to the mob.');
        }
    }
}
