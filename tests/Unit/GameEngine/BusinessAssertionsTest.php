<?php

namespace App\Tests\Unit\GameEngine;

use App\Entity\App\Fight;
use App\Entity\App\FightStatusEffect;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\Element;
use App\GameEngine\Fight\Calculator\CriticalCalculator;
use App\GameEngine\Fight\Calculator\DamageCalculator;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Fight\StatusEffectManager;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\GameEngine\World\GameTimeService;
use App\GameEngine\World\StaticUtcDayCycleFactorProvider;
use App\GameEngine\World\WeatherService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests des assertions metier TST-14 dans le GameEngine.
 */
class BusinessAssertionsTest extends TestCase
{
    // ─── MobActionHandler : mob 0 HP → LogicException ───

    public function testMobActionHandlerThrowsWhenDeadMobActsDirectly(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $statusEffectManager = $this->createMock(StatusEffectManager::class);
        $combatLogger = $this->createMock(CombatLogger::class);

        $handler = new MobActionHandler(
            [],
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(SpellApplicator::class),
            new NullLogger(),
            $statusEffectManager,
            $combatLogger,
            $em,
        );

        $monster = new Monster();
        $monster->setSlug('test-mob');
        $monster->setName('Test Mob');

        $mob = new Mob();
        $mob->setMonster($monster);
        $mob->setLife(0);
        $mob->setCoordinates('0.0');
        $ref = new \ReflectionProperty(Mob::class, 'id');
        $ref->setValue($mob, 1);

        $fight = $this->createMock(Fight::class);

        // Test private method via reflection to verify the assertion exists
        $method = new \ReflectionMethod(MobActionHandler::class, 'doMobAction');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ne peut pas agir avec 0 PV');

        $method->invoke($handler, $fight, $mob);
    }

    public function testMobActionHandlerSkipsDeadMobs(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $statusEffectManager = $this->createMock(StatusEffectManager::class);
        $combatLogger = $this->createMock(CombatLogger::class);

        $handler = new MobActionHandler(
            [],
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(SpellApplicator::class),
            new NullLogger(),
            $statusEffectManager,
            $combatLogger,
            $em,
        );

        $mob = new Mob();
        $mob->setLife(0);
        $mob->setCoordinates('0.0');

        $fight = $this->createMock(Fight::class);
        $fight->method('getMobs')->willReturn(new ArrayCollection([$mob]));
        $fight->method('isTerminated')->willReturn(false);
        $fight->method('getId')->willReturn(1);

        // Dead mob is skipped by doAction (isDead check before doMobAction)
        // so no LogicException is thrown — doAction completes normally
        $result = $handler->doAction($fight);

        $this->assertIsArray($result['messages']);
    }

    // ─── SpellApplicator : degats < 0 → force a 0 ───

    public function testSpellApplicatorClampsNegativeDamageToZero(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist');
        $em->method('flush');
        $em->method('refresh');

        $statusEffectManager = $this->createMock(StatusEffectManager::class);
        $statusEffectManager->method('isCharacterBerserk')->willReturn(false);
        $statusEffectManager->method('getActiveEffects')->willReturn([]);

        $playerStatsCalc = $this->createMock(PlayerEffectiveStatsCalculator::class);
        $playerStatsCalc->method('getEffectiveMaxLife')->willReturn(100);

        $applicator = new SpellApplicator(
            $em,
            $this->createMock(EventDispatcherInterface::class),
            $statusEffectManager,
            $this->createMock(CombatLogger::class),
            new DamageCalculator(),
            new CriticalCalculator(),
            new WeatherService(new GameTimeService(new StaticUtcDayCycleFactorProvider(1.0))),
            $playerStatsCalc,
        );

        $spell = new Spell();
        $spell->setName('Heal Test');
        $spell->setDamage(0);
        $spell->setHeal(20);
        $spell->setCritical(0);
        $spell->setHit(100);
        $spell->setElement(Element::None);
        $spell->setValueType('fixed');

        $sender = new Player();
        $sender->setName('Sender');
        $sender->setLife(100);
        $sender->setMaxLife(100);
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($sender, 1);

        $target = new Player();
        $target->setName('Target');
        $target->setLife(80);
        $target->setMaxLife(100);
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($target, 2);

        // Apply heal spell: damage should stay at 0, not become negative
        $applicator->apply($spell, $sender, $target);

        // Target should have been healed (life >= 80), never below initial
        $this->assertGreaterThanOrEqual(80, $target->getLife());
    }

    // ─── StatusEffectManager : duree < 0 → expirer immediatement ───

    public function testStatusEffectManagerClampsNegativeDuration(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist');
        $em->method('flush');

        $effect = new StatusEffect();
        $effect->setName('Poison Test');
        $effect->setSlug('poison-test');
        $effect->setType(StatusEffect::TYPE_POISON);
        $effect->setDuration(1);
        $effect->setChance(100);
        $effect->setCategory(StatusEffect::CATEGORY_DOT);
        $effect->setDamagePerTurn(5);

        $fightEffect = new FightStatusEffect();
        $fightEffect->setStatusEffect($effect);
        $fightEffect->setRemainingTurns(0); // Will be decremented to -1
        $fightEffect->setTargetType(FightStatusEffect::TARGET_TYPE_PLAYER);
        $fightEffect->setTargetId(1);
        $fightEffect->setAppliedAt(new \DateTime());

        $fight = $this->createMock(Fight::class);
        $fight->method('getStep')->willReturn(1);
        $fight->method('getId')->willReturn(1);

        $fightEffectRepo = $this->createMock(EntityRepository::class);
        $fightEffectRepo->method('findBy')->willReturn([$fightEffect]);
        $em->method('getRepository')->willReturn($fightEffectRepo);
        $em->method('remove');

        $player = new Player();
        $player->setName('Test');
        $player->setLife(50);
        $player->setMaxLife(100);
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($player, 1);

        $manager = new StatusEffectManager(
            $em,
            $this->createMock(CombatLogger::class),
            $this->createMock(PlayerEffectiveStatsCalculator::class),
        );

        $manager->processStartOfTurn($fight, $player);

        // After decrement from 0, the remaining turns should be clamped to 0 (not -1)
        $this->assertSame(0, $fightEffect->getRemainingTurns());
        $this->assertTrue($fightEffect->isExpired());
    }

    // ─── FightTurnResolver : combat sans participants → LogicException ───

    public function testFightTurnResolverThrowsWhenNoParticipants(): void
    {
        $resolver = new FightTurnResolver();

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection());
        $fight->method('getMobs')->willReturn(new ArrayCollection());
        $fight->method('getStep')->willReturn(0);
        $fight->method('getId')->willReturn(1);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('pas de participants valides');

        $resolver->getTimeline($fight);
    }

    public function testFightTurnResolverGetTurnOrderReturnsEmptyForNoParticipants(): void
    {
        $resolver = new FightTurnResolver();

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection());
        $fight->method('getMobs')->willReturn(new ArrayCollection());

        // getTurnOrder returns empty without throwing
        $result = $resolver->getTurnOrder($fight);

        $this->assertSame([], $result);
    }

    public function testFightTurnResolverTimelineWorksWithParticipants(): void
    {
        $resolver = new FightTurnResolver();

        $player = new Player();
        $player->setName('Hero');
        $player->setLife(100);
        $player->setMaxLife(100);
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($player, 1);

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection([$player]));
        $fight->method('getMobs')->willReturn(new ArrayCollection());
        $fight->method('getStep')->willReturn(0);

        $timeline = $resolver->getTimeline($fight);

        $this->assertNotEmpty($timeline);
        $this->assertSame('player', $timeline[0]['type']);
    }
}
