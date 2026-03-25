<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\FightStatusEffect;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerStatusEffect;
use App\Entity\Game\StatusEffect;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\StatusEffectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusEffectManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private CombatLogger&MockObject $combatLogger;
    private EntityRepository&MockObject $fightStatusEffectRepo;
    private EntityRepository&MockObject $playerStatusEffectRepo;
    private StatusEffectManager $manager;
    private Fight&MockObject $fight;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->fightStatusEffectRepo = $this->createMock(EntityRepository::class);
        $this->playerStatusEffectRepo = $this->createMock(EntityRepository::class);
        $this->combatLogger = $this->createMock(CombatLogger::class);

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function (string $class) {
                if ($class === FightStatusEffect::class) {
                    return $this->fightStatusEffectRepo;
                }
                if ($class === PlayerStatusEffect::class) {
                    return $this->playerStatusEffectRepo;
                }

                return $this->createMock(EntityRepository::class);
            });

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $this->fight = $this->createMock(Fight::class);
        $this->combatLogger = $this->createMock(CombatLogger::class);

        $this->manager = new StatusEffectManager($this->entityManager, $this->combatLogger);
    }

    /**
     * Cree un StatusEffect avec les proprietes donnees.
     */
    private function createStatusEffect(
        string $type = StatusEffect::TYPE_POISON,
        int $duration = 3,
        int $chance = 100,
        ?int $damagePerTurn = null,
        ?int $healPerTurn = null,
        string $name = 'Effet',
        ?string $category = null,
        ?int $frequency = null,
        ?int $realTimeDuration = null,
    ): StatusEffect {
        $effect = new StatusEffect();
        $effect->setType($type);
        $effect->setDuration($duration);
        $effect->setChance($chance);
        $effect->setDamagePerTurn($damagePerTurn);
        $effect->setHealPerTurn($healPerTurn);
        $effect->setName($name);
        $effect->setSlug(strtolower($name));
        $effect->setCategory($category);
        $effect->setFrequency($frequency);
        $effect->setRealTimeDuration($realTimeDuration);

        return $effect;
    }

    private function createPlayerMock(int $id = 1, int $life = 100, int $maxLife = 100, string $name = 'Heros'): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $currentLife = $life;

        $player->method('getId')->willReturn($id);
        $player->method('getMaxLife')->willReturn($maxLife);
        $player->method('getName')->willReturn($name);
        $player->method('getLife')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife;
        });
        $player->method('setLife')->willReturnCallback(function (int $l) use (&$currentLife) {
            $currentLife = $l;
        });
        $player->method('isDead')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife <= 0;
        });
        $player->method('setDiedAt');

        return $player;
    }

    private function createMobMock(int $id = 1, int $life = 100, int $maxLife = 100, string $name = 'Gobelin'): Mob&MockObject
    {
        $mob = $this->createMock(Mob::class);
        $currentLife = $life;

        $mob->method('getId')->willReturn($id);
        $mob->method('getMaxLife')->willReturn($maxLife);
        $mob->method('getName')->willReturn($name);
        $mob->method('getLife')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife;
        });
        $mob->method('setLife')->willReturnCallback(function (int $l) use (&$currentLife) {
            $currentLife = $l;
        });
        $mob->method('isDead')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife <= 0;
        });
        $mob->method('setDiedAt');
        $mob->method('isSummoned')->willReturn(false);

        return $mob;
    }

    // --- Tests applyStatusEffect ---

    public function testApplyStatusEffectCreatesNewFightStatusEffect(): void
    {
        $player = $this->createPlayerMock();
        $effect = $this->createStatusEffect(chance: 100);

        // Pas d'effet existant
        $this->fightStatusEffectRepo->method('findOneBy')->willReturn(null);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist')
            ->with($this->isInstanceOf(FightStatusEffect::class));

        $this->manager->applyStatusEffect($this->fight, $player, $effect);
    }

    public function testApplyStatusEffectRefreshesExistingEffect(): void
    {
        $player = $this->createPlayerMock();
        $effect = $this->createStatusEffect(duration: 5, chance: 100);

        $existingEffect = $this->createMock(FightStatusEffect::class);
        $existingEffect->expects($this->once())
            ->method('setRemainingTurns')
            ->with(5);
        $existingEffect->expects($this->once())
            ->method('setAppliedAt');
        $existingEffect->expects($this->once())
            ->method('setLastTickTurn')
            ->with(null);

        $this->fightStatusEffectRepo->method('findOneBy')->willReturn($existingEffect);

        $this->manager->applyStatusEffect($this->fight, $player, $effect);
    }

    public function testApplyStatusEffectSkipsWhenChanceFails(): void
    {
        $player = $this->createPlayerMock();
        // chance = 0 => random_int(1,100) > 0 => toujours vrai => skip
        $effect = $this->createStatusEffect(chance: 0);

        $this->fightStatusEffectRepo->expects($this->never())->method('findOneBy');

        $this->manager->applyStatusEffect($this->fight, $player, $effect);
    }

    // --- Tests processStartOfTurn ---

    public function testProcessStartOfTurnHandlesDotDamage(): void
    {
        $player = $this->createPlayerMock(life: 100, maxLife: 100);
        $this->fight->method('getStep')->willReturn(1);

        $poisonEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_POISON,
            damagePerTurn: 15,
            name: 'Poison',
            category: StatusEffect::CATEGORY_DOT,
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($poisonEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->method('getLastTickTurn')->willReturn(null);
        $fightStatusEffect->expects($this->once())->method('decrementTurn');
        $fightStatusEffect->expects($this->once())->method('setLastTickTurn')->with(1);

        $this->fightStatusEffectRepo->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($fightStatusEffect) {
                if (isset($criteria['targetType'])) {
                    return [$fightStatusEffect];
                }

                return [$fightStatusEffect];
            });

        $messages = $this->manager->processStartOfTurn($this->fight, $player);

        $this->assertSame(85, $player->getLife());
        $this->assertNotEmpty($messages);
        $this->assertTrue(
            str_contains($messages[0], '15') && str_contains($messages[0], 'Poison'),
            'Le message devrait mentionner les degats de poison'
        );
    }

    public function testProcessStartOfTurnHandlesHotHealing(): void
    {
        $player = $this->createPlayerMock(life: 70, maxLife: 100);
        $this->fight->method('getStep')->willReturn(1);

        $regenEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_REGENERATION,
            healPerTurn: 10,
            name: 'Regeneration',
            category: StatusEffect::CATEGORY_HOT,
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($regenEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->method('getLastTickTurn')->willReturn(null);
        $fightStatusEffect->expects($this->once())->method('decrementTurn');
        $fightStatusEffect->expects($this->once())->method('setLastTickTurn')->with(1);

        $this->fightStatusEffectRepo->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($fightStatusEffect) {
                if (isset($criteria['targetType'])) {
                    return [$fightStatusEffect];
                }

                return [$fightStatusEffect];
            });

        $messages = $this->manager->processStartOfTurn($this->fight, $player);

        $this->assertSame(80, $player->getLife());
        $this->assertNotEmpty($messages);
        $this->assertTrue(
            str_contains($messages[0], '10') && str_contains($messages[0], 'Regeneration'),
            'Le message devrait mentionner le soin de regeneration'
        );
    }

    public function testProcessStartOfTurnHealingCappedAtMaxLife(): void
    {
        $player = $this->createPlayerMock(life: 95, maxLife: 100);
        $this->fight->method('getStep')->willReturn(1);

        $regenEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_REGENERATION,
            healPerTurn: 20,
            name: 'Regen',
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($regenEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->method('getLastTickTurn')->willReturn(null);
        $fightStatusEffect->method('decrementTurn');
        $fightStatusEffect->method('setLastTickTurn');

        $this->fightStatusEffectRepo->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($fightStatusEffect) {
                if (isset($criteria['targetType'])) {
                    return [$fightStatusEffect];
                }

                return [$fightStatusEffect];
            });

        $this->manager->processStartOfTurn($this->fight, $player);

        // 95 + 20 = 115, mais cap a 100
        $this->assertSame(100, $player->getLife());
    }

    public function testProcessStartOfTurnDotCanKill(): void
    {
        $player = $this->createPlayerMock(life: 5, maxLife: 100);
        $this->fight->method('getStep')->willReturn(1);

        $poisonEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_POISON,
            damagePerTurn: 20,
            name: 'Poison',
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($poisonEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->method('getLastTickTurn')->willReturn(null);
        $fightStatusEffect->method('decrementTurn');
        $fightStatusEffect->method('setLastTickTurn');

        $this->fightStatusEffectRepo->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($fightStatusEffect) {
                if (isset($criteria['targetType'])) {
                    return [$fightStatusEffect];
                }

                return [$fightStatusEffect];
            });

        $player->expects($this->atLeastOnce())->method('setDiedAt');

        $this->manager->processStartOfTurn($this->fight, $player);

        // 5 - 20 = -15 => cap a 0
        $this->assertSame(0, $player->getLife());
    }

    public function testProcessStartOfTurnReturnsEmptyWhenNoEffects(): void
    {
        $player = $this->createPlayerMock();
        $this->fight->method('getStep')->willReturn(1);

        $this->fightStatusEffectRepo->method('findBy')->willReturn([]);

        $messages = $this->manager->processStartOfTurn($this->fight, $player);

        $this->assertEmpty($messages);
    }

    // --- Tests frequency-based ticking ---

    public function testProcessStartOfTurnSkipsTickWhenFrequencyNotReached(): void
    {
        $player = $this->createPlayerMock(life: 100, maxLife: 100);
        $this->fight->method('getStep')->willReturn(3);

        // Effect with frequency 3 (tick every 3 turns), last ticked at turn 2
        $poisonEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_POISON,
            damagePerTurn: 10,
            name: 'Poison lent',
            category: StatusEffect::CATEGORY_DOT,
            frequency: 3,
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($poisonEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->method('getLastTickTurn')->willReturn(2);
        // Should still decrement turn even without ticking
        $fightStatusEffect->expects($this->once())->method('decrementTurn');
        // Should NOT set lastTickTurn (no tick occurred)
        $fightStatusEffect->expects($this->never())->method('setLastTickTurn');

        $this->fightStatusEffectRepo->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($fightStatusEffect) {
                if (isset($criteria['targetType'])) {
                    return [$fightStatusEffect];
                }

                return [$fightStatusEffect];
            });

        $messages = $this->manager->processStartOfTurn($this->fight, $player);

        // No damage should be applied (tick skipped)
        $this->assertSame(100, $player->getLife());
        $this->assertEmpty($messages);
    }

    public function testProcessStartOfTurnTicksWhenFrequencyReached(): void
    {
        $player = $this->createPlayerMock(life: 100, maxLife: 100);
        $this->fight->method('getStep')->willReturn(5);

        // Effect with frequency 2 (tick every 2 turns), last ticked at turn 3
        $poisonEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_POISON,
            damagePerTurn: 8,
            name: 'Poison insidieux',
            category: StatusEffect::CATEGORY_DOT,
            frequency: 2,
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($poisonEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->method('getLastTickTurn')->willReturn(3);
        $fightStatusEffect->expects($this->once())->method('decrementTurn');
        $fightStatusEffect->expects($this->once())->method('setLastTickTurn')->with(5);

        $this->fightStatusEffectRepo->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($fightStatusEffect) {
                if (isset($criteria['targetType'])) {
                    return [$fightStatusEffect];
                }

                return [$fightStatusEffect];
            });

        $messages = $this->manager->processStartOfTurn($this->fight, $player);

        $this->assertSame(92, $player->getLife());
        $this->assertNotEmpty($messages);
    }

    public function testProcessStartOfTurnFirstTickAlwaysOccurs(): void
    {
        $player = $this->createPlayerMock(life: 100, maxLife: 100);
        $this->fight->method('getStep')->willReturn(1);

        // Effect with frequency 5, never ticked
        $poisonEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_POISON,
            damagePerTurn: 10,
            name: 'Poison lent',
            frequency: 5,
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($poisonEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->method('getLastTickTurn')->willReturn(null);
        $fightStatusEffect->expects($this->once())->method('setLastTickTurn')->with(1);
        $fightStatusEffect->expects($this->once())->method('decrementTurn');

        $this->fightStatusEffectRepo->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($fightStatusEffect) {
                if (isset($criteria['targetType'])) {
                    return [$fightStatusEffect];
                }

                return [$fightStatusEffect];
            });

        $messages = $this->manager->processStartOfTurn($this->fight, $player);

        // First tick always applies damage
        $this->assertSame(90, $player->getLife());
        $this->assertNotEmpty($messages);
    }

    // --- Tests isCharacterParalyzed/Frozen/Silenced/Berserk ---

    public function testIsCharacterParalyzedReturnsTrueWhenParalysisActive(): void
    {
        $player = $this->createPlayerMock();

        $paralysisEffect = $this->createStatusEffect(type: StatusEffect::TYPE_PARALYSIS);

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($paralysisEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);

        $this->fightStatusEffectRepo->method('findBy')->willReturn([$fightStatusEffect]);

        $this->assertTrue($this->manager->isCharacterParalyzed($this->fight, $player));
    }

    public function testIsCharacterParalyzedReturnsFalseWhenNoEffect(): void
    {
        $player = $this->createPlayerMock();

        $this->fightStatusEffectRepo->method('findBy')->willReturn([]);

        $this->assertFalse($this->manager->isCharacterParalyzed($this->fight, $player));
    }

    public function testIsCharacterFrozenReturnsTrueWhenFreezeActive(): void
    {
        $player = $this->createPlayerMock();

        $freezeEffect = $this->createStatusEffect(type: StatusEffect::TYPE_FREEZE);

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($freezeEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);

        $this->fightStatusEffectRepo->method('findBy')->willReturn([$fightStatusEffect]);

        $this->assertTrue($this->manager->isCharacterFrozen($this->fight, $player));
    }

    public function testIsCharacterFrozenReturnsFalseWhenNoEffect(): void
    {
        $player = $this->createPlayerMock();

        $this->fightStatusEffectRepo->method('findBy')->willReturn([]);

        $this->assertFalse($this->manager->isCharacterFrozen($this->fight, $player));
    }

    public function testIsCharacterSilencedReturnsTrueWhenSilenceActive(): void
    {
        $player = $this->createPlayerMock();

        $silenceEffect = $this->createStatusEffect(type: StatusEffect::TYPE_SILENCE);

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($silenceEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);

        $this->fightStatusEffectRepo->method('findBy')->willReturn([$fightStatusEffect]);

        $this->assertTrue($this->manager->isCharacterSilenced($this->fight, $player));
    }

    public function testIsCharacterBerserkReturnsTrueWhenBerserkActive(): void
    {
        $mob = $this->createMobMock();

        $berserkEffect = $this->createStatusEffect(type: StatusEffect::TYPE_BERSERK);

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($berserkEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);

        $this->fightStatusEffectRepo->method('findBy')->willReturn([$fightStatusEffect]);

        $this->assertTrue($this->manager->isCharacterBerserk($this->fight, $mob));
    }

    public function testIsCharacterBerserkReturnsFalseWhenNoEffect(): void
    {
        $mob = $this->createMobMock();

        $this->fightStatusEffectRepo->method('findBy')->willReturn([]);

        $this->assertFalse($this->manager->isCharacterBerserk($this->fight, $mob));
    }

    public function testIsCharacterParalyzedReturnsFalseWhenEffectExpired(): void
    {
        $player = $this->createPlayerMock();

        $paralysisEffect = $this->createStatusEffect(type: StatusEffect::TYPE_PARALYSIS);

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($paralysisEffect);
        $fightStatusEffect->method('isExpired')->willReturn(true);

        $this->fightStatusEffectRepo->method('findBy')->willReturn([$fightStatusEffect]);

        $this->assertFalse($this->manager->isCharacterParalyzed($this->fight, $player));
    }

    // --- Tests getActiveEffects ---

    public function testGetActiveEffectsReturnsEffectsFromRepository(): void
    {
        $player = $this->createPlayerMock(id: 42);

        $effect1 = $this->createMock(FightStatusEffect::class);
        $effect2 = $this->createMock(FightStatusEffect::class);

        $this->fightStatusEffectRepo->method('findBy')
            ->with([
                'fight' => $this->fight,
                'targetType' => FightStatusEffect::TARGET_TYPE_PLAYER,
                'targetId' => 42,
            ])
            ->willReturn([$effect1, $effect2]);

        $result = $this->manager->getActiveEffects($this->fight, $player);

        $this->assertCount(2, $result);
    }

    public function testGetActiveEffectsReturnsMobType(): void
    {
        $mob = $this->createMobMock(id: 7);

        $this->fightStatusEffectRepo->expects($this->once())
            ->method('findBy')
            ->with([
                'fight' => $this->fight,
                'targetType' => FightStatusEffect::TARGET_TYPE_MOB,
                'targetId' => 7,
            ])
            ->willReturn([]);

        $this->manager->getActiveEffects($this->fight, $mob);
    }

    // --- Tests getStatModifiers ---

    public function testGetStatModifiersAggregatesValues(): void
    {
        $player = $this->createPlayerMock();

        $effect1 = $this->createStatusEffect(type: StatusEffect::TYPE_BERSERK);
        $effect1->setStatModifier(['attack' => 1.5, 'defense' => -0.5]);

        $effect2 = $this->createStatusEffect(type: StatusEffect::TYPE_SHIELD);
        $effect2->setStatModifier(['defense' => 0.3]);

        $fse1 = $this->createMock(FightStatusEffect::class);
        $fse1->method('getStatusEffect')->willReturn($effect1);

        $fse2 = $this->createMock(FightStatusEffect::class);
        $fse2->method('getStatusEffect')->willReturn($effect2);

        $this->fightStatusEffectRepo->method('findBy')->willReturn([$fse1, $fse2]);

        $modifiers = $this->manager->getStatModifiers($this->fight, $player);

        $this->assertSame(1.5, $modifiers['attack']);
        // -0.5 + 0.3 = -0.2
        $this->assertEqualsWithDelta(-0.2, $modifiers['defense'], 0.001);
    }

    public function testGetStatModifiersReturnsEmptyWhenNoEffects(): void
    {
        $player = $this->createPlayerMock();

        $this->fightStatusEffectRepo->method('findBy')->willReturn([]);

        $modifiers = $this->manager->getStatModifiers($this->fight, $player);

        $this->assertEmpty($modifiers);
    }

    // --- Tests cleanExpiredEffects ---

    public function testCleanExpiredEffectsRemovesExpired(): void
    {
        $activeEffect = $this->createMock(FightStatusEffect::class);
        $activeEffect->method('isExpired')->willReturn(false);

        $expiredEffect = $this->createMock(FightStatusEffect::class);
        $expiredEffect->method('isExpired')->willReturn(true);

        $this->fightStatusEffectRepo->method('findBy')
            ->with(['fight' => $this->fight])
            ->willReturn([$activeEffect, $expiredEffect]);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($expiredEffect);

        $this->manager->cleanExpiredEffects($this->fight);
    }

    // --- Tests clearAllEffects ---

    public function testClearAllEffectsRemovesEverything(): void
    {
        $effect1 = $this->createMock(FightStatusEffect::class);
        $effect2 = $this->createMock(FightStatusEffect::class);

        $this->fightStatusEffectRepo->method('findBy')
            ->with(['fight' => $this->fight])
            ->willReturn([$effect1, $effect2]);

        $this->entityManager->expects($this->exactly(2))
            ->method('remove');

        $this->manager->clearAllEffects($this->fight);
    }

    // --- Tests persistent effects (PlayerStatusEffect) ---

    public function testApplyPersistentEffectCreatesPlayerStatusEffect(): void
    {
        $player = $this->createPlayerMock();
        $effect = $this->createStatusEffect(
            type: StatusEffect::TYPE_REGENERATION,
            name: 'Food buff',
            category: StatusEffect::CATEGORY_BUFF,
            realTimeDuration: 300,
        );

        $this->playerStatusEffectRepo->method('findBy')->willReturn([]);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist')
            ->with($this->isInstanceOf(PlayerStatusEffect::class));

        $result = $this->manager->applyPersistentEffect($player, $effect);

        $this->assertNotNull($result);
        $this->assertInstanceOf(PlayerStatusEffect::class, $result);
    }

    public function testApplyPersistentEffectReturnsNullWhenNoRealTimeDuration(): void
    {
        $player = $this->createPlayerMock();
        $effect = $this->createStatusEffect(
            type: StatusEffect::TYPE_POISON,
            name: 'Poison',
        );

        $result = $this->manager->applyPersistentEffect($player, $effect);

        $this->assertNull($result);
    }

    public function testApplyPersistentEffectReturnsNullWhenChanceFails(): void
    {
        $player = $this->createPlayerMock();
        $effect = $this->createStatusEffect(
            type: StatusEffect::TYPE_REGENERATION,
            name: 'Buff',
            chance: 0,
            realTimeDuration: 300,
        );

        $result = $this->manager->applyPersistentEffect($player, $effect);

        $this->assertNull($result);
    }

    public function testGetActivePersistentEffectsFiltersExpired(): void
    {
        $player = $this->createPlayerMock();

        $activeEffect = $this->createMock(PlayerStatusEffect::class);
        $activeEffect->method('isExpired')->willReturn(false);

        $expiredEffect = $this->createMock(PlayerStatusEffect::class);
        $expiredEffect->method('isExpired')->willReturn(true);

        $this->playerStatusEffectRepo->method('findBy')
            ->with(['player' => $player])
            ->willReturn([$activeEffect, $expiredEffect]);

        $result = $this->manager->getActivePersistentEffects($player);

        $this->assertCount(1, $result);
        $this->assertSame($activeEffect, $result[0]);
    }

    public function testCleanExpiredPersistentEffects(): void
    {
        $player = $this->createPlayerMock();

        $activeEffect = $this->createMock(PlayerStatusEffect::class);
        $activeEffect->method('isExpired')->willReturn(false);

        $expiredEffect = $this->createMock(PlayerStatusEffect::class);
        $expiredEffect->method('isExpired')->willReturn(true);

        $this->playerStatusEffectRepo->method('findBy')
            ->with(['player' => $player])
            ->willReturn([$activeEffect, $expiredEffect]);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($expiredEffect);

        $this->manager->cleanExpiredPersistentEffects($player);
    }

    public function testGetPersistentStatModifiers(): void
    {
        $player = $this->createPlayerMock();

        $effect = $this->createStatusEffect(
            type: StatusEffect::TYPE_BERSERK,
            name: 'Elixir',
            category: StatusEffect::CATEGORY_BUFF,
        );
        $effect->setStatModifier(['damage' => 0.15]);

        $playerEffect = $this->createMock(PlayerStatusEffect::class);
        $playerEffect->method('isExpired')->willReturn(false);
        $playerEffect->method('getStatusEffect')->willReturn($effect);

        $this->playerStatusEffectRepo->method('findBy')
            ->with(['player' => $player])
            ->willReturn([$playerEffect]);

        $modifiers = $this->manager->getPersistentStatModifiers($player);

        $this->assertArrayHasKey('damage', $modifiers);
        $this->assertEqualsWithDelta(0.15, $modifiers['damage'], 0.001);
    }

    // --- Test integration: food buff loaded into combat ---

    public function testLoadPersistentEffectsIntoFight(): void
    {
        $player = $this->createPlayerMock(id: 5);

        $buffEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_REGENERATION,
            name: 'Repas',
            category: StatusEffect::CATEGORY_BUFF,
            realTimeDuration: 300,
        );
        $buffEffect->setStatModifier(['max_life' => 0.10]);

        $playerEffect = $this->createMock(PlayerStatusEffect::class);
        $playerEffect->method('isExpired')->willReturn(false);
        $playerEffect->method('getStatusEffect')->willReturn($buffEffect);
        $playerEffect->method('getRemainingSeconds')->willReturn(150);
        $playerEffect->method('getAppliedAt')->willReturn(new \DateTime());

        $this->playerStatusEffectRepo->method('findBy')
            ->with(['player' => $player])
            ->willReturn([$playerEffect]);

        $persisted = [];
        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted) {
                $persisted[] = $entity;
            });

        $this->manager->loadPersistentEffectsIntoFight($this->fight, $player);

        // Should have persisted a FightStatusEffect
        $fightEffects = array_filter($persisted, fn ($e) => $e instanceof FightStatusEffect);
        $this->assertNotEmpty($fightEffects);

        $fightEffect = reset($fightEffects);
        $this->assertSame($buffEffect, $fightEffect->getStatusEffect());
        // 150 seconds / 30 = 5 turns
        $this->assertSame(5, $fightEffect->getRemainingTurns());
    }

    public function testLoadPersistentEffectsSkipsDebuffs(): void
    {
        $player = $this->createPlayerMock(id: 5);

        $debuffEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_POISON,
            name: 'Malediction',
            category: StatusEffect::CATEGORY_DEBUFF,
            realTimeDuration: 300,
        );

        $playerEffect = $this->createMock(PlayerStatusEffect::class);
        $playerEffect->method('isExpired')->willReturn(false);
        $playerEffect->method('getStatusEffect')->willReturn($debuffEffect);
        $playerEffect->method('getRemainingSeconds')->willReturn(150);

        $this->playerStatusEffectRepo->method('findBy')
            ->with(['player' => $player])
            ->willReturn([$playerEffect]);

        // Should NOT persist any FightStatusEffect for debuffs
        $this->entityManager->expects($this->never())
            ->method('persist')
            ->with($this->isInstanceOf(FightStatusEffect::class));

        $this->manager->loadPersistentEffectsIntoFight($this->fight, $player);
    }
}
