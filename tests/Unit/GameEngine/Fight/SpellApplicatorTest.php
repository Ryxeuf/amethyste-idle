<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\FightStatusEffect;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\Element;
use App\Event\Fight\MobDeadEvent;
use App\Event\Fight\PlayerDeadEvent;
use App\GameEngine\Fight\Calculator\CriticalCalculator;
use App\GameEngine\Fight\Calculator\DamageCalculator;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Fight\StatusEffectManager;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\GameEngine\World\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SpellApplicatorTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private StatusEffectManager&MockObject $statusEffectManager;
    private CombatLogger&MockObject $combatLogger;
    private PlayerEffectiveStatsCalculator&MockObject $playerEffectiveStatsCalculator;
    private SpellApplicator $spellApplicator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->combatLogger = $this->createMock(CombatLogger::class);
        $this->playerEffectiveStatsCalculator = $this->createMock(PlayerEffectiveStatsCalculator::class);
        $this->playerEffectiveStatsCalculator->method('getEffectiveMaxLife')->willReturnCallback(
            static fn (Player $p) => $p->getMaxLife()
        );

        // Par defaut, pas de status effect actif
        $this->statusEffectManager->method('isCharacterBerserk')->willReturn(false);
        $this->statusEffectManager->method('getActiveEffects')->willReturn([]);

        // Par defaut, EntityManager ne fait rien
        $this->entityManager->method('persist');
        $this->entityManager->method('flush');
        $this->entityManager->method('refresh');

        $this->spellApplicator = new SpellApplicator(
            $this->entityManager,
            $this->eventDispatcher,
            $this->statusEffectManager,
            $this->combatLogger,
            new DamageCalculator(),
            new CriticalCalculator(),
            new WeatherService(),
            $this->playerEffectiveStatsCalculator,
        );
    }

    /**
     * Cree un mock de Spell avec les valeurs fournies.
     */
    private function createSpell(
        int $damage = 0,
        int $heal = 0,
        int $critical = 0,
        int $hit = 100,
        Element $element = Element::None,
        ?string $statusEffectSlug = null,
        string $name = 'Sort de test',
        string $valueType = 'fixed',
    ): Spell&MockObject {
        $spell = $this->createMock(Spell::class);
        $spell->method('getDamage')->willReturn($damage === 0 ? null : $damage);
        $spell->method('getHeal')->willReturn($heal === 0 ? null : $heal);
        $spell->method('getCritical')->willReturn($critical);
        $spell->method('getHit')->willReturn($hit);
        $spell->method('getElement')->willReturn($element);
        $spell->method('getStatusEffectSlug')->willReturn($statusEffectSlug);
        $spell->method('getName')->willReturn($name);
        $spell->method('isPercent')->willReturn($valueType === 'percent');
        $spell->method('getLevel')->willReturn(1);

        return $spell;
    }

    /**
     * Cree un mock de Mob avec vie et vie max.
     */
    private function createMobMock(int $life = 100, int $maxLife = 100, string $name = 'Gobelin'): Mob&MockObject
    {
        $mob = $this->createMock(Mob::class);
        $currentLife = $life;
        $diedAt = null;

        $mob->method('getMaxLife')->willReturn($maxLife);
        $mob->method('getName')->willReturn($name);
        $mob->method('getId')->willReturn(1);

        // Simule getLife/setLife avec un compteur interne via callbacks
        $mob->method('getLife')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife;
        });
        $mob->method('setLife')->willReturnCallback(function (int $l) use (&$currentLife) {
            $currentLife = $l;
        });
        $mob->method('isDead')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife <= 0;
        });
        $mob->method('setDiedAt')->willReturnCallback(function ($dt) use (&$diedAt) {
            $diedAt = $dt;
        });
        $mob->method('isSummoned')->willReturn(false);

        return $mob;
    }

    /**
     * Cree un mock de Player avec vie et vie max.
     */
    private function createPlayerMock(int $life = 100, int $maxLife = 100, string $name = 'Heros'): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $currentLife = $life;
        $diedAt = null;

        $player->method('getMaxLife')->willReturn($maxLife);
        $player->method('getName')->willReturn($name);
        $player->method('getId')->willReturn(1);

        $player->method('getLife')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife;
        });
        $player->method('setLife')->willReturnCallback(function (int $l) use (&$currentLife) {
            $currentLife = $l;
        });
        $player->method('isDead')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife <= 0;
        });
        $player->method('setDiedAt')->willReturnCallback(function ($dt) use (&$diedAt) {
            $diedAt = $dt;
        });

        return $player;
    }

    public function testBasicDamageReducesTargetLife(): void
    {
        $spell = $this->createSpell(damage: 30, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        // Pas de critique (critical = 0 => random_int(0,99) < 0 => toujours faux)
        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        $this->spellApplicator->apply($spell, $sender, $target);

        $this->assertSame(70, $target->getLife());
    }

    public function testBasicHealIncreasesTargetLife(): void
    {
        $spell = $this->createSpell(heal: 25, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createPlayerMock(life: 50, maxLife: 100);

        $this->spellApplicator->apply($spell, $sender, $target);

        $this->assertSame(75, $target->getLife());
    }

    public function testHealCannotExceedMaxLife(): void
    {
        $spell = $this->createSpell(heal: 80, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createPlayerMock(life: 90, maxLife: 100);

        $this->spellApplicator->apply($spell, $sender, $target);

        $this->assertSame(100, $target->getLife());
    }

    public function testLifeNeverGoesBelowZero(): void
    {
        $spell = $this->createSpell(damage: 200, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 50, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        $this->spellApplicator->apply($spell, $sender, $target);

        $this->assertSame(0, $target->getLife());
    }

    public function testElementalResistanceReducesDamageOnMob(): void
    {
        // Resistance de 50% => degats reduits de moitie
        $spell = $this->createSpell(damage: 40, critical: 0, element: Element::Fire);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->with('fire')->willReturn(0.5);
        $target->method('getMonster')->willReturn($monster);

        $messages = $this->spellApplicator->apply($spell, $sender, $target);

        // 40 * (1 - 0.5) = 20 degats => 100 - 20 = 80
        $this->assertSame(80, $target->getLife());
        // Verifie le message de resistance
        $this->assertTrue(
            in_array('Gobelin resiste a fire !', $messages),
            'Un message de resistance devrait etre present'
        );
    }

    public function testElementalWeaknessIncreasesDamageOnMob(): void
    {
        // Resistance negative = faiblesse => degats augmentes
        $spell = $this->createSpell(damage: 40, critical: 0, element: Element::Water);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->with('water')->willReturn(-0.5);
        $target->method('getMonster')->willReturn($monster);

        $messages = $this->spellApplicator->apply($spell, $sender, $target);

        // 40 * (1 - (-0.5)) = 40 * 1.5 = 60 degats => 100 - 60 = 40
        $this->assertSame(40, $target->getLife());
        $this->assertTrue(
            in_array('Gobelin est faible face a water !', $messages),
            'Un message de faiblesse devrait etre present'
        );
    }

    public function testBerserkStatusMultipliesDamage(): void
    {
        $spell = $this->createSpell(damage: 20, critical: 0);
        $fight = $this->createMock(Fight::class);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        // Le sender est berserk
        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->statusEffectManager->method('isCharacterBerserk')
            ->willReturnCallback(function ($f, $char) use ($sender) {
                return $char === $sender;
            });
        $this->statusEffectManager->method('getActiveEffects')->willReturn([]);

        $this->spellApplicator = new SpellApplicator(
            $this->entityManager,
            $this->eventDispatcher,
            $this->statusEffectManager,
            $this->combatLogger,
            new DamageCalculator(),
            new CriticalCalculator(),
            new WeatherService(),
            $this->playerEffectiveStatsCalculator,
        );

        $this->spellApplicator->apply($spell, $sender, $target, ['fight' => $fight]);

        // 20 * 1.5 = 30 degats => 100 - 30 = 70
        $this->assertSame(70, $target->getLife());
    }

    public function testBurnStatusReducesDamage(): void
    {
        $spell = $this->createSpell(damage: 40, critical: 0);
        $fight = $this->createMock(Fight::class);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        // Creer un effet de brulure actif sur le sender
        $burnStatusEffect = $this->createMock(StatusEffect::class);
        $burnStatusEffect->method('getType')->willReturn(StatusEffect::TYPE_BURN);

        $fightBurnEffect = $this->createMock(FightStatusEffect::class);
        $fightBurnEffect->method('getStatusEffect')->willReturn($burnStatusEffect);
        $fightBurnEffect->method('getRemainingTurns')->willReturn(2);

        // getActiveEffects retourne l'effet burn quand on demande pour le sender
        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->statusEffectManager->method('isCharacterBerserk')->willReturn(false);
        $this->statusEffectManager->method('getActiveEffects')
            ->willReturnCallback(function ($f, $char) use ($sender, $fightBurnEffect) {
                if ($char === $sender) {
                    return [$fightBurnEffect];
                }

                return [];
            });

        $this->spellApplicator = new SpellApplicator(
            $this->entityManager,
            $this->eventDispatcher,
            $this->statusEffectManager,
            $this->combatLogger,
            new DamageCalculator(),
            new CriticalCalculator(),
            new WeatherService(),
            $this->playerEffectiveStatsCalculator,
        );

        $this->spellApplicator->apply($spell, $sender, $target, ['fight' => $fight]);

        // 40 * 0.75 = 30 degats => 100 - 30 = 70
        $this->assertSame(70, $target->getLife());
    }

    public function testShieldAbsorptionReducesDamage(): void
    {
        $spell = $this->createSpell(damage: 50, critical: 0);
        $fight = $this->createMock(Fight::class);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        // Creer un effet bouclier sur la cible
        $shieldStatusEffect = $this->createMock(StatusEffect::class);
        $shieldStatusEffect->method('getType')->willReturn(StatusEffect::TYPE_SHIELD);
        $shieldStatusEffect->method('getStatModifier')->willReturn(['shield_absorb' => 20]);

        $fightShieldEffect = $this->createMock(FightStatusEffect::class);
        $fightShieldEffect->method('getStatusEffect')->willReturn($shieldStatusEffect);
        $fightShieldEffect->method('getRemainingTurns')->willReturn(3);

        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->statusEffectManager->method('isCharacterBerserk')->willReturn(false);
        $this->statusEffectManager->method('getActiveEffects')
            ->willReturnCallback(function ($f, $char) use ($target, $fightShieldEffect) {
                if ($char === $target) {
                    return [$fightShieldEffect];
                }

                return [];
            });

        $this->spellApplicator = new SpellApplicator(
            $this->entityManager,
            $this->eventDispatcher,
            $this->statusEffectManager,
            $this->combatLogger,
            new DamageCalculator(),
            new CriticalCalculator(),
            new WeatherService(),
            $this->playerEffectiveStatsCalculator,
        );

        $this->spellApplicator->apply($spell, $sender, $target, ['fight' => $fight]);

        // 50 - 20 absorbes = 30 degats => 100 - 30 = 70
        $this->assertSame(70, $target->getLife());
    }

    public function testShieldAbsorbsAllDamageWhenAbsorbGreaterThanDamage(): void
    {
        $spell = $this->createSpell(damage: 10, critical: 0);
        $fight = $this->createMock(Fight::class);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        $shieldStatusEffect = $this->createMock(StatusEffect::class);
        $shieldStatusEffect->method('getType')->willReturn(StatusEffect::TYPE_SHIELD);
        $shieldStatusEffect->method('getStatModifier')->willReturn(['shield_absorb' => 50]);

        $fightShieldEffect = $this->createMock(FightStatusEffect::class);
        $fightShieldEffect->method('getStatusEffect')->willReturn($shieldStatusEffect);

        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->statusEffectManager->method('isCharacterBerserk')->willReturn(false);
        $this->statusEffectManager->method('getActiveEffects')
            ->willReturnCallback(function ($f, $char) use ($target, $fightShieldEffect) {
                if ($char === $target) {
                    return [$fightShieldEffect];
                }

                return [];
            });

        $this->spellApplicator = new SpellApplicator(
            $this->entityManager,
            $this->eventDispatcher,
            $this->statusEffectManager,
            $this->combatLogger,
            new DamageCalculator(),
            new CriticalCalculator(),
            new WeatherService(),
            $this->playerEffectiveStatsCalculator,
        );

        $this->spellApplicator->apply($spell, $sender, $target, ['fight' => $fight]);

        // Bouclier absorbe tout => 0 degats => vie reste a 100
        $this->assertSame(100, $target->getLife());
    }

    public function testMobDeadEventDispatchedWhenMobDies(): void
    {
        $spell = $this->createSpell(damage: 200, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 50, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(MobDeadEvent::class),
                $this->equalTo(MobDeadEvent::NAME)
            );

        $this->spellApplicator->apply($spell, $sender, $target);
    }

    public function testPlayerDeadEventDispatchedWhenPlayerDies(): void
    {
        $spell = $this->createSpell(damage: 200, critical: 0);
        $sender = $this->createMobMock();
        $target = $this->createPlayerMock(life: 30, maxLife: 100);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(PlayerDeadEvent::class),
                $this->equalTo(PlayerDeadEvent::NAME)
            );

        $this->spellApplicator->apply($spell, $sender, $target);
    }

    public function testNoDeathEventWhenTargetSurvives(): void
    {
        $spell = $this->createSpell(damage: 10, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->spellApplicator->apply($spell, $sender, $target);

        $this->assertSame(90, $target->getLife());
    }

    public function testStatusEffectAppliedWhenSpellHasSlug(): void
    {
        $spell = $this->createSpell(damage: 10, critical: 0, statusEffectSlug: 'poison');
        $fight = $this->createMock(Fight::class);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        // Mock du repository pour trouver le StatusEffect
        $statusEffect = $this->createMock(StatusEffect::class);
        $statusEffect->method('getName')->willReturn('Poison');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')
            ->with(['slug' => 'poison'])
            ->willReturn($statusEffect);

        $this->entityManager->method('getRepository')
            ->with(StatusEffect::class)
            ->willReturn($repository);

        $this->statusEffectManager->expects($this->once())
            ->method('applyStatusEffect')
            ->with($fight, $target, $statusEffect);

        $messages = $this->spellApplicator->apply($spell, $sender, $target, ['fight' => $fight]);

        $this->assertContains('Gobelin est affecte par Poison !', $messages);
    }

    public function testDomainDamageAddedToSpellDamage(): void
    {
        $spell = $this->createSpell(damage: 20, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        $this->spellApplicator->apply($spell, $sender, $target, ['damage' => 10]);

        // 20 (spell) + 10 (domain) = 30 degats => 100 - 30 = 70
        $this->assertSame(70, $target->getLife());
    }

    public function testDomainHealAddedToSpellHeal(): void
    {
        $spell = $this->createSpell(heal: 15, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createPlayerMock(life: 50, maxLife: 100);

        $this->spellApplicator->apply($spell, $sender, $target, ['heal' => 5]);

        // 15 (spell) + 5 (domain) = 20 heal => 50 + 20 = 70
        $this->assertSame(70, $target->getLife());
    }

    public function testNoDamageWhenSpellDamageIsNull(): void
    {
        // Spell sans degats ni heal => vie inchangee
        $spell = $this->createSpell(damage: 0, heal: 0, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createPlayerMock(life: 80, maxLife: 100);

        $this->spellApplicator->apply($spell, $sender, $target);

        $this->assertSame(80, $target->getLife());
    }

    public function testPercentDamageUsesTargetMaxLife(): void
    {
        // Sort avec 10% de degats sur cible avec 200 maxLife => 20 degats
        $spell = $this->createSpell(damage: 10, critical: 0, valueType: 'percent');
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 200);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        $this->spellApplicator->apply($spell, $sender, $target);

        // 10% de 200 = 20 degats => 100 - 20 = 80
        $this->assertSame(80, $target->getLife());
    }

    public function testPercentHealUsesTargetMaxLife(): void
    {
        // Sort avec 10% de soin sur cible avec 200 maxLife => 20 soin
        $spell = $this->createSpell(heal: 10, critical: 0, valueType: 'percent');
        $sender = $this->createPlayerMock();
        $target = $this->createPlayerMock(life: 50, maxLife: 200);

        $this->spellApplicator->apply($spell, $sender, $target);

        // 10% de 200 = 20 soin => 50 + 20 = 70
        $this->assertSame(70, $target->getLife());
    }

    public function testApplyReturnsMessagesArray(): void
    {
        $spell = $this->createSpell(damage: 10, critical: 0);
        $sender = $this->createPlayerMock();
        $target = $this->createMobMock(life: 100, maxLife: 100);

        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $target->method('getMonster')->willReturn($monster);

        $messages = $this->spellApplicator->apply($spell, $sender, $target);

        $this->assertIsArray($messages);
    }
}
