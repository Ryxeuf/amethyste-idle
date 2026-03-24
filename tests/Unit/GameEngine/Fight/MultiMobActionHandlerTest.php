<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Fight\StatusEffectManager;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MultiMobActionHandlerTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private SpellApplicator&MockObject $spellApplicator;
    private LoggerInterface&MockObject $logger;
    private StatusEffectManager&MockObject $statusEffectManager;
    private CombatLogger&MockObject $combatLogger;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->spellApplicator = $this->createMock(SpellApplicator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->combatLogger = $this->createMock(CombatLogger::class);

        $this->statusEffectManager->method('processStartOfTurn')->willReturn([]);
        $this->statusEffectManager->method('isCharacterParalyzed')->willReturn(false);
        $this->statusEffectManager->method('isCharacterFrozen')->willReturn(false);
    }

    private function createHandler(): MobActionHandler
    {
        return new MobActionHandler(
            [],
            $this->eventDispatcher,
            $this->spellApplicator,
            $this->logger,
            $this->statusEffectManager,
            $this->combatLogger,
        );
    }

    private function createMobMock(
        bool $isDead = false,
        int $life = 100,
        int $maxLife = 100,
        ?array $aiPattern = null,
        array $spells = [],
        int $hit = 100,
        string $name = 'Squelette',
        int $id = 1,
    ): Mob&MockObject {
        $monster = $this->createMock(Monster::class);
        $monster->method('getAiPattern')->willReturn($aiPattern);
        $monster->method('getSpells')->willReturn(new ArrayCollection($spells));
        $monster->method('isBoss')->willReturn(false);
        $monster->method('getBossPhases')->willReturn(null);
        $monster->method('getHit')->willReturn($hit);
        $monster->method('getCurrentBossPhase')->willReturn(null);

        $basicAttack = $this->createMock(Spell::class);
        $basicAttack->method('getName')->willReturn('Attaque');
        $basicAttack->method('getHeal')->willReturn(null);

        $mob = $this->createMock(Mob::class);
        $mob->method('isDead')->willReturn($isDead);
        $mob->method('getLife')->willReturn($life);
        $mob->method('getMaxLife')->willReturn($maxLife);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('getName')->willReturn($name);
        $mob->method('getId')->willReturn($id);
        $mob->method('getAttack')->willReturn($basicAttack);

        return $mob;
    }

    private function createFightMock(array $mobs, ?Player $player = null): Fight&MockObject
    {
        if ($player === null) {
            $player = $this->createMock(Player::class);
            $player->method('isDead')->willReturn(false);
            $player->method('getName')->willReturn('Heros');
        }

        $fight = $this->createMock(Fight::class);
        $fight->method('getMobs')->willReturn(new ArrayCollection($mobs));
        $fight->method('getPlayers')->willReturn(new ArrayCollection([$player]));
        $fight->method('getId')->willReturn(42);
        $fight->method('getStep')->willReturn(0);
        $fight->method('isTerminated')->willReturn(false);

        return $fight;
    }

    public function testAllAliveMobsAct(): void
    {
        $mob1 = $this->createMobMock(name: 'Squelette 1', id: 1, hit: 100);
        $mob2 = $this->createMobMock(name: 'Squelette 2', id: 2, hit: 100);

        $fight = $this->createFightMock([$mob1, $mob2]);
        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        // Les deux mobs doivent avoir agi (un message par mob)
        $mob1Acted = false;
        $mob2Acted = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'Squelette 1')) {
                $mob1Acted = true;
            }
            if (str_contains($msg, 'Squelette 2')) {
                $mob2Acted = true;
            }
        }
        $this->assertTrue($mob1Acted, 'Mob 1 devrait avoir agi');
        $this->assertTrue($mob2Acted, 'Mob 2 devrait avoir agi');
    }

    public function testDeadMobsSkipped(): void
    {
        $aliveMob = $this->createMobMock(name: 'Squelette vivant', id: 1, hit: 100);
        $deadMob = $this->createMobMock(isDead: true, name: 'Squelette mort', id: 2);

        $fight = $this->createFightMock([$aliveMob, $deadMob]);
        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        // Seul le mob vivant doit agir
        $deadMobActed = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'Squelette mort')) {
                $deadMobActed = true;
            }
        }
        $this->assertFalse($deadMobActed, 'Le mob mort ne devrait pas agir');
    }

    public function testHealerTargetsMostWoundedAlly(): void
    {
        $healSpell = $this->createMock(Spell::class);
        $healSpell->method('getName')->willReturn('Guerison naturelle');
        $healSpell->method('getHeal')->willReturn(4);

        // Mob soigneur (Nécromancien) avec rôle healer
        $healer = $this->createMobMock(
            name: 'Necromancien',
            id: 3,
            hit: 100,
            aiPattern: ['role' => 'healer', 'spell_chance' => 40],
            spells: [$healSpell],
        );

        // Squelette blessé (30/100 PV = 30%)
        $woundedMob = $this->createMobMock(
            life: 30,
            maxLife: 100,
            name: 'Squelette blesse',
            id: 1,
            hit: 100,
        );

        // Squelette pleine vie
        $healthyMob = $this->createMobMock(
            name: 'Squelette sain',
            id: 2,
            hit: 100,
        );

        $fight = $this->createFightMock([$woundedMob, $healthyMob, $healer]);

        // Vérifie que SpellApplicator est appelé avec le mob blessé comme cible
        $this->spellApplicator->expects($this->atLeastOnce())
            ->method('apply')
            ->willReturnCallback(function (Spell $spell, $sender, $target) use ($woundedMob, $healSpell) {
                // Quand le sort de soin est utilisé, la cible doit être le mob blessé
                if ($spell === $healSpell) {
                    $this->assertSame($woundedMob, $target, 'Le soigneur devrait cibler le mob le plus blesse');
                }

                return [];
            });

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        // Vérifie que le soin apparaît dans les messages
        $healUsed = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'Guerison naturelle')) {
                $healUsed = true;
                break;
            }
        }
        $this->assertTrue($healUsed, 'Le necromancien devrait utiliser son sort de soin');
    }

    public function testHealerAttacksWhenAllAlliesHealthy(): void
    {
        $healSpell = $this->createMock(Spell::class);
        $healSpell->method('getName')->willReturn('Guerison naturelle');
        $healSpell->method('getHeal')->willReturn(4);

        // Mob soigneur — tous les alliés ont 100% PV
        $healer = $this->createMobMock(
            name: 'Necromancien',
            id: 2,
            hit: 100,
            aiPattern: ['role' => 'healer', 'spell_chance' => 0],
            spells: [$healSpell],
        );

        $healthyMob = $this->createMobMock(
            name: 'Squelette',
            id: 1,
            hit: 100,
        );

        $fight = $this->createFightMock([$healthyMob, $healer]);
        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        // Le nécromancien ne devrait PAS utiliser le soin (tous en pleine vie)
        $healUsed = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'Guerison naturelle')) {
                $healUsed = true;
            }
        }
        $this->assertFalse($healUsed, 'Le necromancien ne devrait pas soigner si tous les allies sont en pleine vie');
    }

    public function testHealerTargetsSelfWhenSelfIsWounded(): void
    {
        $healSpell = $this->createMock(Spell::class);
        $healSpell->method('getName')->willReturn('Guerison naturelle');
        $healSpell->method('getHeal')->willReturn(4);

        // Nécromancien blessé (40/100), unique mob → se soigne lui-même
        $healer = $this->createMobMock(
            life: 40,
            maxLife: 100,
            name: 'Necromancien',
            id: 1,
            hit: 100,
            aiPattern: ['role' => 'healer', 'spell_chance' => 0],
            spells: [$healSpell],
        );

        $fight = $this->createFightMock([$healer]);

        $this->spellApplicator->expects($this->atLeastOnce())
            ->method('apply')
            ->willReturnCallback(function (Spell $spell, $sender, $target) use ($healer, $healSpell) {
                if ($spell === $healSpell) {
                    $this->assertSame($healer, $target, 'Le soigneur devrait se soigner lui-meme');
                }

                return [];
            });

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $healUsed = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'Guerison naturelle')) {
                $healUsed = true;
            }
        }
        $this->assertTrue($healUsed, 'Le necromancien blesse devrait se soigner');
    }
}
