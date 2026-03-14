<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\FightStatusEffect;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\Entity\Game\StatusEffect;
use App\GameEngine\Fight\StatusEffectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusEffectManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $fightStatusEffectRepo;
    private StatusEffectManager $manager;
    private Fight&MockObject $fight;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->fightStatusEffectRepo = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')
            ->with(FightStatusEffect::class)
            ->willReturn($this->fightStatusEffectRepo);

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $this->fight = $this->createMock(Fight::class);

        $this->manager = new StatusEffectManager($this->entityManager);
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
    ): StatusEffect {
        $effect = new StatusEffect();
        $effect->setType($type);
        $effect->setDuration($duration);
        $effect->setChance($chance);
        $effect->setDamagePerTurn($damagePerTurn);
        $effect->setHealPerTurn($healPerTurn);
        $effect->setName($name);
        $effect->setSlug(strtolower($name));

        return $effect;
    }

    private function createPlayerMock(int $id = 1, int $life = 100, int $maxLife = 100, string $name = 'Heros'): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $currentLife = $life;

        $player->method('getId')->willReturn($id);
        $player->method('getMaxLife')->willReturn($maxLife);
        $player->method('getName')->willReturn($name);
        $player->method('getLife')->willReturnCallback(fn() => $currentLife);
        $player->method('setLife')->willReturnCallback(function (int $l) use (&$currentLife) {
            $currentLife = $l;
        });
        $player->method('isDead')->willReturnCallback(fn() => $currentLife <= 0);
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
        $mob->method('getLife')->willReturnCallback(fn() => $currentLife);
        $mob->method('setLife')->willReturnCallback(function (int $l) use (&$currentLife) {
            $currentLife = $l;
        });
        $mob->method('isDead')->willReturnCallback(fn() => $currentLife <= 0);
        $mob->method('setDiedAt');

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

        $poisonEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_POISON,
            damagePerTurn: 15,
            name: 'Poison',
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($poisonEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->expects($this->once())->method('decrementTurn');

        $this->fightStatusEffectRepo->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($fightStatusEffect) {
                // getActiveEffects appelle findBy avec fight, targetType, targetId
                if (isset($criteria['targetType'])) {
                    return [$fightStatusEffect];
                }
                // cleanExpiredEffects appelle findBy avec juste fight
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

        $regenEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_REGENERATION,
            healPerTurn: 10,
            name: 'Regeneration',
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($regenEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->expects($this->once())->method('decrementTurn');

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

        $regenEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_REGENERATION,
            healPerTurn: 20,
            name: 'Regen',
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($regenEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->method('decrementTurn');

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

        $poisonEffect = $this->createStatusEffect(
            type: StatusEffect::TYPE_POISON,
            damagePerTurn: 20,
            name: 'Poison',
        );

        $fightStatusEffect = $this->createMock(FightStatusEffect::class);
        $fightStatusEffect->method('getStatusEffect')->willReturn($poisonEffect);
        $fightStatusEffect->method('isExpired')->willReturn(false);
        $fightStatusEffect->method('decrementTurn');

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

        $this->fightStatusEffectRepo->method('findBy')->willReturn([]);

        $messages = $this->manager->processStartOfTurn($this->fight, $player);

        $this->assertEmpty($messages);
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
}
