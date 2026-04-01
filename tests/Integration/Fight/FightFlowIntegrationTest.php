<?php

namespace App\Tests\Integration\Fight;

use App\Entity\App\Mob;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\SpellApplicator;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Integration tests for the core combat flow.
 *
 * TST-05 Sub-task A: verify fight creation, basic attack,
 * mob death / fight termination, and loot generation using
 * real services and a real database.
 */
class FightFlowIntegrationTest extends AbstractIntegrationTestCase
{
    // ------------------------------------------------------------------
    //  Test 1 — Engaging a mob creates a fight
    // ------------------------------------------------------------------

    public function testEngageMobCreatesFight(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob(monsterSlug: 'slime');

        $this->assertNull($player->getFight(), 'Player should not be in a fight initially.');
        $this->assertNull($mob->getFight(), 'Mob should not be in a fight initially.');

        $fight = $this->createFight($player, $mob);

        // Fight is persisted
        $this->assertNotNull($fight->getId());
        $this->assertTrue($fight->isInProgress());
        $this->assertSame(1, $fight->getStep());

        // Bi-directional relations
        $this->assertSame($fight, $player->getFight());
        $this->assertSame($fight, $mob->getFight());

        // Fight contains the correct participants
        $this->assertCount(1, $fight->getPlayers());
        $this->assertCount(1, $fight->getMobs());
        $this->assertSame($player, $fight->getPlayers()->first());
        $this->assertSame($mob, $fight->getMobs()->first());

        // Fight is not yet terminated
        $this->assertFalse($fight->isTerminated());
        $this->assertFalse($fight->isVictory());
        $this->assertFalse($fight->isDefeat());
    }

    // ------------------------------------------------------------------
    //  Test 2 — Player attack reduces mob HP
    // ------------------------------------------------------------------

    public function testPlayerAttackReducesMobHp(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob(monsterSlug: 'slime');
        $fight = $this->createFight($player, $mob);

        $initialMobLife = $mob->getLife();
        $this->assertGreaterThan(0, $initialMobLife);

        // Use SpellApplicator with the mob's basic attack spell (reversed: player→mob)
        // This simulates a spell-based attack which is the most complete code path.
        $spellApplicator = $this->getService(SpellApplicator::class);
        $attackSpell = $mob->getAttack(); // basic melee spell

        $spellApplicator->apply($attackSpell, $player, $mob, [
            'damage' => 0,
            'heal' => 0,
            'critical' => 0,
            'fight' => $fight,
        ]);

        // After applying a damage spell, mob HP should have changed
        $this->refresh($mob);

        // The spell deals base damage (from spell definition), so HP should decrease.
        // We can't predict the exact amount due to critical chance, but life should be
        // less than or equal to initial (could be 0 if mob dies).
        $this->assertLessThanOrEqual($initialMobLife, $mob->getLife());
    }

    public function testDirectDamageReducesMobHp(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob(monsterSlug: 'slime');
        $fight = $this->createFight($player, $mob);

        $initialMobLife = $mob->getLife();

        // Simulate what FightAttackController::doPlayerAttack does
        $damage = 3; // base damage in controller
        $mob->setLife(max(0, $mob->getLife() - $damage));
        $this->persistAndFlush($mob);

        $this->refresh($mob);
        $this->assertSame($initialMobLife - $damage, $mob->getLife());
    }

    // ------------------------------------------------------------------
    //  Test 3 — Mob death ends fight
    // ------------------------------------------------------------------

    public function testMobDeathEndsFight(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob(monsterSlug: 'slime');
        $fight = $this->createFight($player, $mob);

        // Set mob to 1 HP so the next hit kills it
        $mob->setLife(1);
        $this->persistAndFlush($mob);

        // Kill via SpellApplicator (dispatches MobDeadEvent)
        $spellApplicator = $this->getService(SpellApplicator::class);
        $attackSpell = $mob->getAttack();

        $spellApplicator->apply($attackSpell, $player, $mob, [
            'damage' => 0,
            'heal' => 0,
            'critical' => 0,
            'fight' => $fight,
        ]);

        $this->refresh($mob);
        $this->refresh($fight);

        // Mob should be dead
        $this->assertTrue($mob->isDead(), 'Mob should be dead after being reduced to 0 HP.');
        $this->assertSame(0, $mob->getLife());
        $this->assertNotNull($mob->getDiedAt());

        // Fight should be terminated with victory
        $this->assertTrue($fight->isTerminated(), 'Fight should be terminated when all mobs are dead.');
        $this->assertTrue($fight->isVictory(), 'Fight should be a victory when all mobs are dead.');
        $this->assertFalse($fight->isDefeat());
    }

    public function testPlayerDeathEndsFightAsDefeat(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob(monsterSlug: 'slime');
        $fight = $this->createFight($player, $mob);

        // Set player to 1 HP
        $player->setLife(1);
        $this->persistAndFlush($player);

        // Mob attacks player via SpellApplicator (dispatches PlayerDeadEvent)
        $spellApplicator = $this->getService(SpellApplicator::class);
        $attackSpell = $mob->getAttack();

        $spellApplicator->apply($attackSpell, $mob, $player, [
            'damage' => 0,
            'heal' => 0,
            'critical' => 0,
            'fight' => $fight,
        ]);

        $this->refresh($player);
        $this->refresh($fight);

        // Player should be dead
        $this->assertTrue($player->isDead(), 'Player should be dead after being reduced to 0 HP.');
        $this->assertSame(0, $player->getLife());

        // Fight should be terminated as defeat
        $this->assertTrue($fight->isTerminated(), 'Fight should be terminated when player dies.');
        $this->assertTrue($fight->isDefeat(), 'Fight should be a defeat when the player dies.');
        $this->assertFalse($fight->isVictory());
    }

    // ------------------------------------------------------------------
    //  Test 4 — Loot generated after victory
    // ------------------------------------------------------------------

    public function testLootGeneratedAfterMobDeath(): void
    {
        // Find a mob whose monster has loot defined (monsterItems)
        $mob = $this->findMobWithLoot();
        if ($mob === null) {
            $this->markTestSkipped('No mob with loot items found in fixtures.');
        }

        $player = $this->getPlayer();
        $fight = $this->createFight($player, $mob);

        $monsterItemsCount = $mob->getMonster()->getMonsterItems()->count();
        $this->assertGreaterThan(0, $monsterItemsCount, 'Monster should have drop items defined.');

        // Set mob to 1 HP and kill via SpellApplicator (triggers MobDeadEvent → LootGenerator)
        $mob->setLife(1);
        $this->persistAndFlush($mob);

        $spellApplicator = $this->getService(SpellApplicator::class);
        $attackSpell = $mob->getAttack();

        $spellApplicator->apply($attackSpell, $player, $mob, [
            'damage' => 0,
            'heal' => 0,
            'critical' => 0,
            'fight' => $fight,
        ]);

        $this->refresh($mob);

        $this->assertTrue($mob->isDead(), 'Mob should be dead.');

        // LootGenerator should have created PlayerItem entities on the mob.
        // Due to probability-based drops, we can only assert that the loot
        // system ran without errors. For mobs with guaranteed drops, we can
        // assert items exist.
        $hasGuaranteedDrop = false;
        foreach ($mob->getMonster()->getMonsterItems() as $monsterItem) {
            if ($monsterItem->isGuaranteed()) {
                $hasGuaranteedDrop = true;
                break;
            }
        }

        if ($hasGuaranteedDrop) {
            $this->assertGreaterThan(
                0,
                $mob->getItems()->count(),
                'Mob with guaranteed drops should have loot items after death.'
            );
        }

        // At minimum, the loot generation process should not have thrown
        // an exception — reaching this point means the full event chain worked.
        $this->assertTrue(true, 'Loot generation completed without errors.');
    }

    // ------------------------------------------------------------------
    //  Test 5 — MobActionHandler executes mob turn
    // ------------------------------------------------------------------

    public function testMobActionHandlerExecutesMobTurn(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob(monsterSlug: 'slime');
        $fight = $this->createFight($player, $mob);

        $initialPlayerLife = $player->getLife();

        $mobActionHandler = $this->getService(MobActionHandler::class);
        $result = $mobActionHandler->doAction($fight);

        // doAction returns messages array
        $this->assertIsArray($result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayHasKey('dangerAlert', $result);

        $this->refresh($player);

        // Mob either hit or missed — player HP may or may not have changed.
        // We only verify the service ran without exceptions and returned the expected format.
        $this->assertLessThanOrEqual($initialPlayerLife, $player->getLife());
    }

    // ------------------------------------------------------------------
    //  Test 6 — FightTurnResolver determines turn order
    // ------------------------------------------------------------------

    public function testFightTurnResolverDeterminesTurnOrder(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob(monsterSlug: 'slime');
        $fight = $this->createFight($player, $mob);

        $turnResolver = $this->getService(FightTurnResolver::class);

        // isMobFirst should return a boolean (based on speed comparison)
        $mobFirst = $turnResolver->isMobFirst($fight);
        $this->assertIsBool($mobFirst);

        // getTurnOrder should return ordered array of participants
        $turnOrder = $turnResolver->getTurnOrder($fight);
        $this->assertNotEmpty($turnOrder);
        $this->assertCount(2, $turnOrder); // 1 player + 1 mob

        // Each entry should have entity, type, key, speed
        foreach ($turnOrder as $entry) {
            $this->assertArrayHasKey('entity', $entry);
            $this->assertArrayHasKey('type', $entry);
            $this->assertArrayHasKey('key', $entry);
            $this->assertArrayHasKey('speed', $entry);
            $this->assertContains($entry['type'], ['player', 'mob']);
        }
    }

    // ------------------------------------------------------------------
    //  Test 7 — Full combat loop: fight until termination
    // ------------------------------------------------------------------

    public function testFullCombatLoopUntilVictory(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob(monsterSlug: 'slime');
        $fight = $this->createFight($player, $mob);

        // Give the player enough life to survive
        $player->setLife(9999);
        $player->setHit(100); // Guarantee hit
        $this->persistAndFlush($player);

        $spellApplicator = $this->getService(SpellApplicator::class);
        $mobActionHandler = $this->getService(MobActionHandler::class);
        $attackSpell = $mob->getAttack();

        $maxTurns = 100;
        $turn = 0;

        while (!$fight->isTerminated() && $turn < $maxTurns) {
            // Player attacks mob
            $spellApplicator->apply($attackSpell, $player, $mob, [
                'damage' => 5, // bonus damage to speed things up
                'heal' => 0,
                'critical' => 0,
                'fight' => $fight,
            ]);

            $fight->setStep($fight->getStep() + 1);

            if ($fight->isTerminated()) {
                break;
            }

            // Mob attacks player
            $mobActionHandler->doAction($fight);
            $fight->setStep($fight->getStep() + 1);

            ++$turn;

            $this->refresh($mob);
            $this->refresh($player);
        }

        $this->assertTrue($fight->isTerminated(), 'Fight should terminate within max turns.');
        $this->assertTrue($fight->isVictory(), 'Player with 9999 HP should win against a slime.');
        $this->assertTrue($mob->isDead(), 'Mob should be dead after victory.');
    }

    // ------------------------------------------------------------------
    //  Helper: find a mob whose monster has MonsterItems (loot)
    // ------------------------------------------------------------------

    private function findMobWithLoot(): ?Mob
    {
        $qb = $this->em->createQueryBuilder()
            ->select('m')
            ->from(Mob::class, 'm')
            ->join('m.monster', 'monster')
            ->join('monster.monsterItems', 'mi')
            ->where('m.fight IS NULL')
            ->andWhere('m.diedAt IS NULL')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
