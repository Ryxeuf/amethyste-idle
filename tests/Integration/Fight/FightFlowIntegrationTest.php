<?php

namespace App\Tests\Integration\Fight;

use App\Entity\App\Fight;
use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\Handler\FightHandler;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\SpellApplicator;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * TST-05A — Integration tests for the full combat flow.
 *
 * Tests use real services and a real database (with fixtures).
 * Each test runs in a transaction that is rolled back afterward.
 */
class FightFlowIntegrationTest extends AbstractIntegrationTestCase
{
    private FightHandler $fightHandler;
    private SpellApplicator $spellApplicator;
    private MobActionHandler $mobActionHandler;
    private FightTurnResolver $turnResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fightHandler = $this->getService(FightHandler::class);
        $this->spellApplicator = $this->getService(SpellApplicator::class);
        $this->mobActionHandler = $this->getService(MobActionHandler::class);
        $this->turnResolver = $this->getService(FightTurnResolver::class);
    }

    /**
     * Engager un mob via FightHandler cree un Fight en DB avec le joueur et le mob lies.
     */
    public function testEngageMobCreatesFight(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap(), 'slime');

        self::assertNull($player->getFight(), 'Le joueur ne doit pas etre en combat avant engagement.');
        self::assertNull($mob->getFight(), 'Le mob ne doit pas etre en combat avant engagement.');

        $fight = $this->fightHandler->startFight($player, $mob);

        // Fight persisted
        self::assertNotNull($fight->getId(), 'Le combat doit etre persiste en DB.');
        self::assertTrue($fight->isInProgress(), 'Le combat doit etre en cours.');
        self::assertFalse($fight->isTerminated(), 'Le combat ne doit pas etre termine.');

        // Player and mob associated
        self::assertSame($fight->getId(), $player->getFight()?->getId(), 'Le joueur doit etre lie au combat.');
        self::assertSame($fight->getId(), $mob->getFight()?->getId(), 'Le mob doit etre lie au combat.');

        // Collections populated
        self::assertCount(1, $fight->getPlayers(), 'Le combat doit avoir 1 joueur.');
        self::assertCount(1, $fight->getMobs(), 'Le combat doit avoir 1 mob.');

        // Verify in DB via fresh query
        $this->em->clear();
        $fightFromDb = $this->em->getRepository(Fight::class)->find($fight->getId());
        self::assertNotNull($fightFromDb, 'Le combat doit exister en DB.');
        self::assertTrue($fightFromDb->isInProgress());
    }

    /**
     * Une attaque basique via SpellApplicator reduit les HP du mob.
     */
    public function testPlayerAttackReducesMobHp(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap(), 'slime');
        $fight = $this->fightHandler->startFight($player, $mob);

        $initialMobLife = $mob->getLife();
        self::assertGreaterThan(0, $initialMobLife, 'Le mob doit avoir des PV.');

        // Use the monster's own attack spell to test SpellApplicator
        // (player attacking with a spell — the real integration path)
        $attackSpell = $mob->getMonster()->getAttack();
        self::assertNotNull($attackSpell, 'Le monstre doit avoir un sort d\'attaque.');

        // Apply damage from player to mob using a known spell
        // We use the monster's attack spell for simplicity — damage = 1 base
        $messages = $this->spellApplicator->apply(
            $attackSpell,
            $player,
            $mob,
            ['fight' => $fight]
        );

        $this->refresh($mob);

        // Mob should have lost some HP (damage >= 1)
        self::assertLessThan($initialMobLife, $mob->getLife(), 'Les PV du mob doivent diminuer apres une attaque.');
        self::assertGreaterThanOrEqual(0, $mob->getLife(), 'Les PV du mob ne doivent pas etre negatifs.');
        self::assertIsArray($messages, 'SpellApplicator doit retourner un tableau de messages.');
    }

    /**
     * Quand un mob meurt (0 HP), le combat est marque comme termine et victoire.
     * Les events MobDeadEvent sont dispatches (bestiary, achievements, quests).
     */
    public function testMobDeathEndsFight(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap(), 'slime');
        $fight = $this->fightHandler->startFight($player, $mob);

        // Get a spell that does damage
        $attackSpell = $mob->getMonster()->getAttack();

        // Kill the mob by attacking repeatedly until it dies
        $maxTurns = 50;
        $turn = 0;
        while (!$mob->isDead() && $turn < $maxTurns) {
            $this->spellApplicator->apply($attackSpell, $player, $mob, ['fight' => $fight]);
            $this->refresh($mob);
            ++$turn;
        }

        self::assertTrue($mob->isDead(), 'Le mob doit etre mort apres les attaques.');
        self::assertEquals(0, $mob->getLife(), 'Les PV du mob doivent etre a 0.');
        self::assertNotNull($mob->getDiedAt(), 'Le mob doit avoir une date de mort.');

        // Fight state
        self::assertTrue($fight->isTerminated(), 'Le combat doit etre termine.');
        self::assertTrue($fight->isVictory(), 'Le combat doit etre une victoire.');
        self::assertFalse($fight->isDefeat(), 'Le combat ne doit pas etre une defaite.');
    }

    /**
     * Apres la mort d'un mob, le LootGenerator (via MobDeadEvent) genere le loot.
     * Les items sont persistes en DB et lies au mob.
     */
    public function testLootAfterVictory(): void
    {
        $player = $this->getPlayer();
        // Use a mob with known loot — slime has guaranteed or chance drops
        $mob = $this->getMob($player->getMap(), 'slime');
        $fight = $this->fightHandler->startFight($player, $mob);

        $attackSpell = $mob->getMonster()->getAttack();

        // Kill the mob
        $maxTurns = 50;
        $turn = 0;
        while (!$mob->isDead() && $turn < $maxTurns) {
            $this->spellApplicator->apply($attackSpell, $player, $mob, ['fight' => $fight]);
            $this->refresh($mob);
            ++$turn;
        }

        self::assertTrue($mob->isDead(), 'Le mob doit etre mort.');
        self::assertTrue($fight->isVictory(), 'Le combat doit etre une victoire.');

        // LootGenerator is an event subscriber on MobDeadEvent — it was already dispatched
        // by SpellApplicator when the mob died. Check that loot items are persisted.
        $lootItems = $this->em->getRepository(PlayerItem::class)->findBy(['mob' => $mob]);

        // The slime may or may not drop items depending on probability.
        // We verify the structure: if items exist, they must be valid.
        self::assertIsArray($lootItems, 'La requete loot doit retourner un tableau.');

        foreach ($lootItems as $item) {
            self::assertInstanceOf(PlayerItem::class, $item);
            self::assertNotNull($item->getGenericItem(), 'Chaque loot doit avoir un item generique.');
            self::assertSame($mob->getId(), $item->getMob()?->getId(), 'Le loot doit etre lie au mob.');
        }

        // Verify bestiary was updated (MobDeadEvent → BestiaryListener)
        $monsterSlug = $mob->getMonster()->getSlug();
        $bestiary = $this->em->createQueryBuilder()
            ->select('b')
            ->from(\App\Entity\App\PlayerBestiary::class, 'b')
            ->join('b.monster', 'm')
            ->where('b.player = :player')
            ->andWhere('m.slug = :slug')
            ->setParameter('player', $player)
            ->setParameter('slug', $monsterSlug)
            ->getQuery()
            ->getOneOrNullResult();

        self::assertNotNull($bestiary, 'Le bestiaire doit etre mis a jour apres avoir tue le mob.');
    }

    /**
     * Les mobs agissent via MobActionHandler et infligent des degats au joueur.
     */
    public function testMobActionDamagesPlayer(): void
    {
        $player = $this->getPlayer();
        // Use skeleton which has spell_chance=25 and higher stats
        $mob = $this->getMob(null, 'skeleton');
        $fight = $this->fightHandler->startFight($player, $mob);

        $initialPlayerLife = $player->getLife();

        // Run mob action multiple times — at least one should hit
        $wasHit = false;
        for ($i = 0; $i < 20; ++$i) {
            $result = $this->mobActionHandler->doAction($fight);
            $this->refresh($player);

            if ($player->getLife() < $initialPlayerLife) {
                $wasHit = true;
                break;
            }
        }

        self::assertTrue($wasHit, 'Le mob doit avoir touche le joueur au moins une fois en 20 tentatives.');
        self::assertLessThan($initialPlayerLife, $player->getLife(), 'Les PV du joueur doivent avoir diminue.');
    }

    /**
     * Un combat complet (engagement → tours → victoire) fonctionne de bout en bout.
     */
    public function testFullCombatFlowToVictory(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap(), 'slime');
        $fight = $this->fightHandler->startFight($player, $mob);

        $attackSpell = $mob->getMonster()->getAttack();

        // Simulate a full combat: player attacks, mob retaliates, repeat
        $maxRounds = 100;
        $round = 0;

        while (!$fight->isTerminated() && $round < $maxRounds) {
            // Player attacks mob
            if (!$player->isDead() && !$mob->isDead()) {
                $this->spellApplicator->apply($attackSpell, $player, $mob, ['fight' => $fight]);
                $this->refresh($mob);
            }

            // Mob attacks player (if still alive)
            if (!$mob->isDead() && !$player->isDead()) {
                $this->mobActionHandler->doAction($fight);
                $this->refresh($player);
            }

            $fight->setStep($fight->getStep() + 1);
            ++$round;
        }

        self::assertTrue($fight->isTerminated(), 'Le combat doit se terminer.');

        if ($fight->isVictory()) {
            self::assertTrue($mob->isDead(), 'En cas de victoire, le mob doit etre mort.');
        } else {
            self::assertTrue($player->isDead(), 'En cas de defaite, le joueur doit etre mort.');
        }
    }
}
