<?php

namespace App\Tests\Integration\Fight;

use App\Entity\App\FightStatusEffect;
use App\Entity\Game\StatusEffect;
use App\GameEngine\Fight\StatusEffectManager;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * TST-05 sous-tache B : tests d'integration des effets de statut en combat.
 *
 * Verifie StatusEffectManager avec une vraie DB et des fixtures reelles.
 */
class StatusEffectIntegrationTest extends AbstractIntegrationTestCase
{
    private StatusEffectManager $statusEffectManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statusEffectManager = $this->getService(StatusEffectManager::class);
    }

    /**
     * Poison inflige des degats a chaque tour et expire apres sa duree.
     */
    public function testPoisonTicksDamagePerTurn(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        // Fetch the poison status effect from fixtures (3 dmg/turn, 3 turns, 100% chance)
        $poison = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => 'poison']);
        self::assertNotNull($poison, 'Fixture status effect "poison" not found.');
        self::assertSame(3, $poison->getDuration());
        self::assertSame(3, $poison->getDamagePerTurn());

        // Apply poison to the mob
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $poison);

        // Verify the effect was persisted
        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $activeEffects);
        self::assertSame('poison', $activeEffects[0]->getStatusEffect()->getSlug());
        self::assertSame(3, $activeEffects[0]->getRemainingTurns());

        $initialLife = $mob->getLife();
        self::assertGreaterThan(0, $initialLife);

        // --- Turn 1: poison ticks ---
        $fight->setStep(1);
        $this->em->flush();
        $messages = $this->statusEffectManager->processStartOfTurn($fight, $mob);

        self::assertNotEmpty($messages, 'Poison should produce a message on tick.');
        $this->refresh($mob);
        self::assertSame($initialLife - 3, $mob->getLife(), 'Mob should lose 3 HP from poison tick (turn 1).');

        // Remaining turns should be 2
        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $activeEffects);
        self::assertSame(2, $activeEffects[0]->getRemainingTurns());

        // --- Turn 2: poison ticks again ---
        $fight->setStep(2);
        $this->em->flush();
        $messages = $this->statusEffectManager->processStartOfTurn($fight, $mob);

        self::assertNotEmpty($messages);
        $this->refresh($mob);
        self::assertSame($initialLife - 6, $mob->getLife(), 'Mob should lose 6 HP total from poison (turn 2).');

        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $activeEffects);
        self::assertSame(1, $activeEffects[0]->getRemainingTurns());

        // --- Turn 3: last tick, then poison expires ---
        $fight->setStep(3);
        $this->em->flush();
        $messages = $this->statusEffectManager->processStartOfTurn($fight, $mob);

        self::assertNotEmpty($messages);
        $this->refresh($mob);
        self::assertSame($initialLife - 9, $mob->getLife(), 'Mob should lose 9 HP total from poison (turn 3).');

        // Effect should be expired and cleaned
        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        $nonExpired = array_filter($activeEffects, fn (FightStatusEffect $e) => !$e->isExpired());
        self::assertCount(0, $nonExpired, 'Poison should have expired after 3 turns.');
    }

    /**
     * Le silence empeche le lancement de sorts. Une fois expire, le sort redevient possible.
     */
    public function testSilencePreventsSpellCasting(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        // Fetch silence effect from fixtures (3 turns, 100% chance)
        $silence = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => 'silence']);
        self::assertNotNull($silence, 'Fixture status effect "silence" not found.');
        self::assertSame(StatusEffect::TYPE_SILENCE, $silence->getType());
        self::assertSame(3, $silence->getDuration());

        // Player should not be silenced initially
        self::assertFalse(
            $this->statusEffectManager->isCharacterSilenced($fight, $player),
            'Player should not be silenced before effect is applied.'
        );

        // Apply silence to the player
        $this->statusEffectManager->applyStatusEffect($fight, $player, $silence);

        // Player should be silenced
        self::assertTrue(
            $this->statusEffectManager->isCharacterSilenced($fight, $player),
            'Player should be silenced after effect is applied.'
        );

        // Verify remaining turns
        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $player);
        self::assertCount(1, $activeEffects);
        self::assertSame(3, $activeEffects[0]->getRemainingTurns());

        // --- Process 3 turns to expire the silence ---
        for ($turn = 1; $turn <= 3; ++$turn) {
            $fight->setStep($turn);
            $this->em->flush();
            $this->statusEffectManager->processStartOfTurn($fight, $player);
        }

        // After 3 turns, silence should have expired
        self::assertFalse(
            $this->statusEffectManager->isCharacterSilenced($fight, $player),
            'Player should no longer be silenced after effect expires.'
        );

        // No active effects remaining
        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $player);
        $nonExpired = array_filter($activeEffects, fn (FightStatusEffect $e) => !$e->isExpired());
        self::assertCount(0, $nonExpired, 'Silence effect should be fully expired.');
    }

    /**
     * Reappliquer le meme effet ne stack pas : la duree est simplement reinitialise.
     */
    public function testEffectRefreshResetsDuration(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $poison = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => 'poison']);
        self::assertNotNull($poison);

        // Apply poison (3 turns)
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $poison);

        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $activeEffects);
        self::assertSame(3, $activeEffects[0]->getRemainingTurns());

        // Consume 2 turns
        $fight->setStep(1);
        $this->em->flush();
        $this->statusEffectManager->processStartOfTurn($fight, $mob);

        $fight->setStep(2);
        $this->em->flush();
        $this->statusEffectManager->processStartOfTurn($fight, $mob);

        // Verify 1 turn remaining
        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $activeEffects);
        self::assertSame(1, $activeEffects[0]->getRemainingTurns());

        // Re-apply poison → should refresh to 3 turns, NOT create a second effect
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $poison);

        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $activeEffects, 'Effect should not stack — only one FightStatusEffect expected.');
        self::assertSame(3, $activeEffects[0]->getRemainingTurns(), 'Duration should be refreshed to full (3 turns).');
        self::assertNull($activeEffects[0]->getLastTickTurn(), 'Last tick turn should be reset on refresh.');
    }
}
