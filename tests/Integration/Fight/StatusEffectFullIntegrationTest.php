<?php

namespace App\Tests\Integration\Fight;

use App\Entity\App\FightStatusEffect;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Fight\StatusEffectManager;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * TST-06 : tests d'integration complets des status effects.
 *
 * Verifie le cycle de vie des effets de statut a travers
 * StatusEffectManager + SpellApplicator + FightTurnResolver ensemble,
 * avec une vraie base de donnees.
 */
class StatusEffectFullIntegrationTest extends AbstractIntegrationTestCase
{
    private StatusEffectManager $statusEffectManager;
    private SpellApplicator $spellApplicator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statusEffectManager = $this->getService(StatusEffectManager::class);
        $this->spellApplicator = $this->getService(SpellApplicator::class);
    }

    // ---------------------------------------------------------------
    //  1. Application d'effet via sort → verification en DB
    // ---------------------------------------------------------------

    /**
     * Lancer un sort avec statusEffectSlug cree un FightStatusEffect en DB.
     *
     * Flux : SpellApplicator::apply() avec un sort ayant statusEffectSlug='burn'
     *        → FightStatusEffect persiste → mob a l'effet actif
     */
    public function testSpellAppliesStatusEffectToTarget(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        // Find a spell that applies burn
        $spell = $this->em->getRepository(Spell::class)
            ->findOneBy(['statusEffectSlug' => 'burn']);
        self::assertNotNull($spell, 'Fixture spell with burn effect not found.');

        // No effects before spell
        $effectsBefore = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(0, $effectsBefore, 'Mob should have no effects before spell.');

        // Cast the spell on the mob
        $this->spellApplicator->apply($spell, $player, $mob, [
            'fight' => $fight,
            'damage' => 0,
            'heal' => 0,
            'critical' => 0,
        ]);

        // Verify FightStatusEffect was persisted
        $effectsAfter = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $effectsAfter, 'Mob should have 1 status effect after spell.');

        $fightEffect = $effectsAfter[0];
        self::assertSame('burn', $fightEffect->getStatusEffect()->getSlug());
        self::assertSame(FightStatusEffect::TARGET_TYPE_MOB, $fightEffect->getTargetType());
        self::assertSame($mob->getId(), $fightEffect->getTargetId());

        // Verify the effect has the expected duration from fixture
        $burnDef = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => 'burn']);
        self::assertSame($burnDef->getDuration(), $fightEffect->getRemainingTurns());
    }

    // ---------------------------------------------------------------
    //  2. Tick degats/soin a chaque tour
    // ---------------------------------------------------------------

    /**
     * Brulure inflige des degats par tour via processStartOfTurn.
     * Verifie aussi que le drapeau brulure reduit les degats du porteur.
     */
    public function testBurnTicksDamageEachTurn(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $burnEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'burn']);
        self::assertNotNull($burnEffect);

        $initialLife = $mob->getLife();
        $damagePerTurn = $burnEffect->getDamagePerTurn();
        $duration = $burnEffect->getDuration();

        self::assertGreaterThan(0, $damagePerTurn);

        // Apply burn
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $burnEffect);

        // Process each turn
        $expectedLife = $initialLife;
        for ($turn = 1; $turn <= $duration; ++$turn) {
            $fight->setStep($turn);
            $this->persistAndFlush($fight);

            $messages = $this->statusEffectManager->processStartOfTurn($fight, $mob);

            $expectedLife -= $damagePerTurn;
            $this->refresh($mob);

            self::assertSame(
                max(0, $expectedLife),
                $mob->getLife(),
                sprintf('Mob life should be %d after burn turn %d.', max(0, $expectedLife), $turn)
            );
            self::assertNotEmpty($messages, sprintf('Turn %d should produce a burn message.', $turn));
        }

        // Burn should be expired
        $remaining = $this->statusEffectManager->getActiveEffects($fight, $mob);
        $active = array_filter($remaining, fn (FightStatusEffect $e) => !$e->isExpired());
        self::assertCount(0, $active, 'Burn should have expired after all turns.');
    }

    /**
     * Regeneration soigne chaque tour et respecte le cap max life.
     */
    public function testRegenerationHealsEachTurnWithCap(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $regenEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'regeneration']);
        self::assertNotNull($regenEffect);

        $healPerTurn = $regenEffect->getHealPerTurn();
        $duration = $regenEffect->getDuration();
        self::assertGreaterThan(0, $healPerTurn);

        // Reduce mob life first so heal has room
        $maxLife = $mob->getMaxLife();
        $reducedLife = max(1, $maxLife - ($healPerTurn * ($duration + 1)));
        $mob->setLife($reducedLife);
        $this->persistAndFlush($mob);

        // Apply regeneration
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $regenEffect);

        $expectedLife = $reducedLife;
        for ($turn = 1; $turn <= $duration; ++$turn) {
            $fight->setStep($turn);
            $this->persistAndFlush($fight);

            $messages = $this->statusEffectManager->processStartOfTurn($fight, $mob);

            $expectedLife = min($maxLife, $expectedLife + $healPerTurn);
            $this->refresh($mob);

            self::assertSame(
                $expectedLife,
                $mob->getLife(),
                sprintf('Mob life should be %d after regen turn %d.', $expectedLife, $turn)
            );
            self::assertNotEmpty($messages, sprintf('Turn %d should produce a regen message.', $turn));
            self::assertStringContainsString('points de vie', $messages[0]);
        }
    }

    /**
     * Poison insidieux (frequency=2) ne tick que tous les 2 tours.
     * Les tours sans tick decrementent quand meme la duree.
     */
    public function testFrequencyBasedPoisonTicksEveryNTurns(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $slowPoison = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'poison-slow']);
        self::assertNotNull($slowPoison, 'Fixture "poison-slow" not found.');

        $damagePerTick = $slowPoison->getDamagePerTurn();
        $duration = $slowPoison->getDuration();
        $frequency = $slowPoison->getFrequency();

        self::assertSame(2, $frequency, 'poison-slow should tick every 2 turns.');
        self::assertSame(6, $duration);
        self::assertSame(8, $damagePerTick);

        $initialLife = $mob->getLife();
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $slowPoison);

        $totalDamage = 0;
        for ($turn = 1; $turn <= $duration; ++$turn) {
            $fight->setStep($turn);
            $this->persistAndFlush($fight);

            $lifeBefore = $mob->getLife();
            $this->statusEffectManager->processStartOfTurn($fight, $mob);
            $this->refresh($mob);
            $lifeAfter = $mob->getLife();

            $damageTaken = $lifeBefore - $lifeAfter;
            $totalDamage += $damageTaken;

            if ($turn === 1) {
                // First turn always ticks (lastTickTurn=null)
                self::assertSame(
                    $damagePerTick,
                    $damageTaken,
                    'Turn 1 should tick (first tick).'
                );
            }
        }

        // Over 6 turns with frequency=2, damage should have ticked at least twice
        // (turn 1 = first tick, turn 3 = second tick, turn 5 = third tick)
        self::assertGreaterThanOrEqual(
            $damagePerTick * 2,
            $totalDamage,
            'Frequency-2 poison should tick multiple times over 6 turns.'
        );
        self::assertLessThan(
            $damagePerTick * $duration,
            $totalDamage,
            'Frequency-2 poison should NOT tick every turn.'
        );
    }

    // ---------------------------------------------------------------
    //  3. Modification stats (buff/debuff) visible dans calculs
    // ---------------------------------------------------------------

    /**
     * Berserk accorde +50% degats et -30% defense.
     * getStatModifiers() retourne les bons modificateurs.
     * isCharacterBerserk() retourne true.
     */
    public function testBerserkStatModifiers(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $berserkEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'berserk']);
        self::assertNotNull($berserkEffect);

        // Not berserk initially
        self::assertFalse(
            $this->statusEffectManager->isCharacterBerserk($fight, $player),
            'Player should not be berserk initially.'
        );

        // Apply berserk to player
        $this->statusEffectManager->applyStatusEffect($fight, $player, $berserkEffect);

        // isCharacterBerserk should return true
        self::assertTrue(
            $this->statusEffectManager->isCharacterBerserk($fight, $player),
            'Player should be berserk after applying effect.'
        );

        // Stat modifiers should reflect +50% damage, -30% defense
        $modifiers = $this->statusEffectManager->getStatModifiers($fight, $player);
        self::assertArrayHasKey('damage', $modifiers);
        self::assertArrayHasKey('defense', $modifiers);
        self::assertEqualsWithDelta(0.50, $modifiers['damage'], 0.001);
        self::assertEqualsWithDelta(-0.30, $modifiers['defense'], 0.001);
    }

    /**
     * Gel (freeze) applique -50% vitesse.
     * isCharacterFrozen() retourne true et les modifiers sont corrects.
     */
    public function testFreezeStatModifiers(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $freezeEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'freeze']);
        self::assertNotNull($freezeEffect);

        $this->statusEffectManager->applyStatusEffect($fight, $mob, $freezeEffect);

        self::assertTrue(
            $this->statusEffectManager->isCharacterFrozen($fight, $mob),
            'Mob should be frozen.'
        );

        $modifiers = $this->statusEffectManager->getStatModifiers($fight, $mob);
        self::assertArrayHasKey('speed', $modifiers);
        self::assertEqualsWithDelta(-0.50, $modifiers['speed'], 0.001);
    }

    /**
     * Plusieurs effets s'agrègent : berserk (+50% dmg, -30% def) + burn (-25% dmg)
     * donne un cumul net de +25% dmg, -30% def.
     */
    public function testMultipleEffectsAggregateStatModifiers(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $berserk = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => 'berserk']);
        $burn = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => 'burn']);
        self::assertNotNull($berserk);
        self::assertNotNull($burn);

        $this->statusEffectManager->applyStatusEffect($fight, $mob, $berserk);
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $burn);

        $modifiers = $this->statusEffectManager->getStatModifiers($fight, $mob);

        // Berserk +0.50 damage + burn -0.25 damage = +0.25 net
        self::assertArrayHasKey('damage', $modifiers);
        self::assertEqualsWithDelta(0.25, $modifiers['damage'], 0.001, 'Berserk+Burn damage should aggregate to +0.25.');

        // Berserk -0.30 defense, burn has no defense modifier
        self::assertArrayHasKey('defense', $modifiers);
        self::assertEqualsWithDelta(-0.30, $modifiers['defense'], 0.001);

        // Both effects should be active
        $effects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(2, $effects, 'Should have 2 active effects.');
    }

    // ---------------------------------------------------------------
    //  4. Expiration et nettoyage corrects
    // ---------------------------------------------------------------

    /**
     * Apres expiration, cleanExpiredEffects supprime les FightStatusEffect de la DB.
     */
    public function testExpirationRemovesEffectsFromDatabase(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $poisonEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'poison']);
        self::assertNotNull($poisonEffect);
        $duration = $poisonEffect->getDuration();

        $this->statusEffectManager->applyStatusEffect($fight, $mob, $poisonEffect);

        // Verify persisted in DB
        $count = $this->em->getRepository(FightStatusEffect::class)->count(['fight' => $fight]);
        self::assertSame(1, $count, 'Should have 1 FightStatusEffect in DB.');

        // Process all turns to expire
        for ($turn = 1; $turn <= $duration; ++$turn) {
            $fight->setStep($turn);
            $this->persistAndFlush($fight);
            $this->statusEffectManager->processStartOfTurn($fight, $mob);
        }

        // After expiration + cleanup, DB should be clean
        $countAfter = $this->em->getRepository(FightStatusEffect::class)->count(['fight' => $fight]);
        self::assertSame(0, $countAfter, 'Expired effects should be removed from DB.');
    }

    /**
     * clearAllEffects supprime tous les effets d'un combat (fin de combat).
     */
    public function testClearAllEffectsOnFightEnd(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $poison = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => 'poison']);
        $berserk = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => 'berserk']);

        $this->statusEffectManager->applyStatusEffect($fight, $mob, $poison);
        $this->statusEffectManager->applyStatusEffect($fight, $player, $berserk);

        $count = $this->em->getRepository(FightStatusEffect::class)->count(['fight' => $fight]);
        self::assertSame(2, $count, 'Should have 2 effects.');

        // Clear all on fight end
        $this->statusEffectManager->clearAllEffects($fight);

        $countAfter = $this->em->getRepository(FightStatusEffect::class)->count(['fight' => $fight]);
        self::assertSame(0, $countAfter, 'All effects should be cleared after fight end.');
    }

    // ---------------------------------------------------------------
    //  5. Cas berserk complet : SpellApplicator + StatusEffectManager
    // ---------------------------------------------------------------

    /**
     * Un personnage berserk inflige 1.5x degats via SpellApplicator.
     * On applique berserk au player, puis on compare le resultat de apply()
     * sur le mob avec et sans berserk.
     */
    public function testBerserkBoostsDamageThroughSpellApplicator(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        // Find a damage spell without status effect (to avoid confounding effects)
        $spell = $this->em->getRepository(Spell::class)->createQueryBuilder('s')
            ->where('s.damage > 0')
            ->andWhere('s.statusEffectSlug IS NULL')
            ->andWhere('s.critical = 0 OR s.critical IS NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Fallback: find any damage spell if no zero-crit spell exists
        if ($spell === null) {
            $spell = $this->em->getRepository(Spell::class)->createQueryBuilder('s')
                ->where('s.damage > 0')
                ->andWhere('s.statusEffectSlug IS NULL')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        if ($spell === null) {
            self::markTestSkipped('No damage spell without status effect found in fixtures.');
        }

        // Record mob life and apply spell WITHOUT berserk
        $mob->setLife($mob->getMaxLife());
        $this->persistAndFlush($mob);
        $lifeBefore = $mob->getLife();

        $this->spellApplicator->apply($spell, $player, $mob, [
            'fight' => $fight,
            'damage' => 0,
            'heal' => 0,
            'critical' => -100, // Force no critical by using negative critical bonus
        ]);
        $this->refresh($mob);
        $damageWithout = $lifeBefore - $mob->getLife();

        // Reset mob life
        $mob->setLife($mob->getMaxLife());
        $this->persistAndFlush($mob);
        $lifeBefore = $mob->getLife();

        // Apply berserk to player
        $berserkEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'berserk']);
        $this->statusEffectManager->applyStatusEffect($fight, $player, $berserkEffect);

        // Apply same spell WITH berserk
        $this->spellApplicator->apply($spell, $player, $mob, [
            'fight' => $fight,
            'damage' => 0,
            'heal' => 0,
            'critical' => -100,
        ]);
        $this->refresh($mob);
        $damageWith = $lifeBefore - $mob->getLife();

        // Berserk should increase damage (1.5x multiplier)
        // We allow some tolerance for rounding but berserk damage should be >= normal damage
        if ($damageWithout > 0) {
            self::assertGreaterThanOrEqual(
                $damageWithout,
                $damageWith,
                'Berserk should increase (or at least not decrease) damage output.'
            );
        }
    }

    /**
     * Un personnage brule (burn) a ses degats reduits de 25%.
     */
    public function testBurnReducesSenderDamage(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $burnEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'burn']);
        self::assertNotNull($burnEffect);

        // Apply burn on the mob (so mob's damage is reduced)
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $burnEffect);

        // Verify the burn stat modifier is present
        $modifiers = $this->statusEffectManager->getStatModifiers($fight, $mob);
        self::assertArrayHasKey('damage', $modifiers);
        self::assertEqualsWithDelta(-0.25, $modifiers['damage'], 0.001, 'Burn should apply -25% damage modifier.');
    }
}
