<?php

namespace App\Tests\Integration\Fight;

use App\Entity\App\FightStatusEffect;
use App\Entity\Game\StatusEffect;
use App\GameEngine\Fight\StatusEffectManager;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * TST-05 sous-tache B : tests d'integration des status effects en combat.
 *
 * Verifie le cycle de vie complet des effets de statut avec une vraie DB :
 * application, tick de degats, expiration, silence et refresh de duree.
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
     * Poison applique sur un mob inflige des degats chaque tour
     * et expire apres la duree prevue.
     *
     * Flux : appliquer poison → processStartOfTurn x3 → degats a chaque tour → effet expire
     */
    public function testPoisonTicksDamagePerTurn(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $poisonEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'poison']);
        self::assertNotNull($poisonEffect, 'Fixture "poison" status effect not found.');

        $initialLife = $mob->getLife();
        $damagePerTurn = $poisonEffect->getDamagePerTurn();
        $duration = $poisonEffect->getDuration();

        self::assertGreaterThan(0, $damagePerTurn);
        self::assertGreaterThan(0, $duration);

        // Apply poison to mob (chance=100, always applies)
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $poisonEffect);

        // Verify effect is persisted in DB
        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $activeEffects);
        self::assertSame($poisonEffect->getId(), $activeEffects[0]->getStatusEffect()->getId());
        self::assertSame($duration, $activeEffects[0]->getRemainingTurns());

        // Simulate each turn: poison ticks damage and decrements
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
                sprintf('After turn %d, mob life should be %d.', $turn, max(0, $expectedLife))
            );

            // Verify a damage message was produced
            self::assertNotEmpty($messages, sprintf('Turn %d should produce a poison message.', $turn));
            self::assertStringContainsString('dégâts', $messages[0]);
        }

        // After all turns, the effect should be expired and cleaned
        $remainingEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        $nonExpired = array_filter($remainingEffects, fn (FightStatusEffect $e) => !$e->isExpired());
        self::assertCount(0, $nonExpired, 'Poison should have expired after all turns.');
    }

    /**
     * Silence empeche le lancement de sorts.
     * Apres expiration, le personnage peut de nouveau lancer des sorts.
     *
     * Flux : appliquer silence → isCharacterSilenced = true → expirer → isCharacterSilenced = false
     */
    public function testSilencePreventsSpellCasting(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $silenceEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'silence']);
        self::assertNotNull($silenceEffect, 'Fixture "silence" status effect not found.');
        self::assertSame(StatusEffect::TYPE_SILENCE, $silenceEffect->getType());

        $duration = $silenceEffect->getDuration();
        self::assertGreaterThan(0, $duration);

        // Before silence: mob can cast spells
        self::assertFalse(
            $this->statusEffectManager->isCharacterSilenced($fight, $mob),
            'Mob should not be silenced before applying the effect.'
        );

        // Apply silence
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $silenceEffect);

        // After application: mob is silenced
        self::assertTrue(
            $this->statusEffectManager->isCharacterSilenced($fight, $mob),
            'Mob should be silenced after applying silence.'
        );

        // Process turns until expiration
        for ($turn = 1; $turn <= $duration; ++$turn) {
            $fight->setStep($turn);
            $this->persistAndFlush($fight);

            // During active turns, mob should remain silenced
            if ($turn < $duration) {
                self::assertTrue(
                    $this->statusEffectManager->isCharacterSilenced($fight, $mob),
                    sprintf('Mob should still be silenced during turn %d.', $turn)
                );
            }

            $this->statusEffectManager->processStartOfTurn($fight, $mob);
        }

        // After all turns processed, silence has expired
        self::assertFalse(
            $this->statusEffectManager->isCharacterSilenced($fight, $mob),
            'Mob should no longer be silenced after the effect expired.'
        );
    }

    /**
     * Reappliquer le meme effet reset la duree au lieu d'empiler.
     *
     * Flux : appliquer poison → consommer 1 tour → reappliquer → duree = duree originale
     */
    public function testEffectRefreshResetsDuration(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());
        $fight = $this->createFight($player, $mob);

        $poisonEffect = $this->em->getRepository(StatusEffect::class)
            ->findOneBy(['slug' => 'poison']);
        self::assertNotNull($poisonEffect, 'Fixture "poison" status effect not found.');

        $fullDuration = $poisonEffect->getDuration();

        // Apply poison
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $poisonEffect);

        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $activeEffects, 'Should have exactly one poison effect.');
        self::assertSame($fullDuration, $activeEffects[0]->getRemainingTurns());

        // Process one turn to decrement
        $fight->setStep(1);
        $this->persistAndFlush($fight);
        $this->statusEffectManager->processStartOfTurn($fight, $mob);

        // Verify duration decremented
        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        $nonExpired = array_filter($activeEffects, fn (FightStatusEffect $e) => !$e->isExpired());
        self::assertCount(1, $nonExpired, 'Should still have one active poison effect after 1 turn.');
        $effectAfterTick = reset($nonExpired);
        self::assertSame($fullDuration - 1, $effectAfterTick->getRemainingTurns());

        // Re-apply the same effect → should refresh duration, not create a second one
        $this->statusEffectManager->applyStatusEffect($fight, $mob, $poisonEffect);

        // Should still have exactly 1 effect (no stacking)
        $activeEffects = $this->statusEffectManager->getActiveEffects($fight, $mob);
        self::assertCount(1, $activeEffects, 'Re-applying should refresh, not stack.');

        // Duration should be reset to full
        self::assertSame(
            $fullDuration,
            $activeEffects[0]->getRemainingTurns(),
            'Duration should be reset to full after re-application.'
        );
    }
}
