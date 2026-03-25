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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SummonMobActionHandlerTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private SpellApplicator&MockObject $spellApplicator;
    private LoggerInterface&MockObject $logger;
    private StatusEffectManager&MockObject $statusEffectManager;
    private CombatLogger&MockObject $combatLogger;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->spellApplicator = $this->createMock(SpellApplicator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->combatLogger = $this->createMock(CombatLogger::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

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
            $this->entityManager,
        );
    }

    private function createSummonerMob(int $summonChance = 100, int $count = 1, int $cooldown = 3): Mob&MockObject
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getAiPattern')->willReturn([
            'spell_chance' => 30,
            'summon' => [
                'monster_slug' => 'skeleton',
                'chance' => $summonChance,
                'count' => $count,
                'cooldown' => $cooldown,
            ],
        ]);
        $monster->method('getSpells')->willReturn(new ArrayCollection());
        $monster->method('isBoss')->willReturn(false);
        $monster->method('getBossPhases')->willReturn(null);
        $monster->method('getHit')->willReturn(80);
        $monster->method('getCurrentBossPhase')->willReturn(null);

        $basicAttack = $this->createMock(Spell::class);
        $basicAttack->method('getName')->willReturn('Toucher necrotique');

        $mob = $this->createMock(Mob::class);
        $mob->method('isDead')->willReturn(false);
        $mob->method('getLife')->willReturn(40);
        $mob->method('getMaxLife')->willReturn(40);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('getName')->willReturn('Necromancien');
        $mob->method('getId')->willReturn(10);
        $mob->method('getAttack')->willReturn($basicAttack);
        $mob->method('isSummoned')->willReturn(false);
        $mob->method('getLevel')->willReturn(3);

        return $mob;
    }

    private function createSkeletonMonster(): Monster&MockObject
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getName')->willReturn('Squelette');
        $monster->method('getLife')->willReturn(35);
        $monster->method('getLevel')->willReturn(2);
        $monster->method('getSlug')->willReturn('skeleton');

        return $monster;
    }

    /**
     * Cree un mob invoque mock avec un Monster minimal (pour traverser doMobAction).
     */
    private function createSummonedMobMock(int $id = 99): Mob&MockObject
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getAiPattern')->willReturn(null);
        $monster->method('getSpells')->willReturn(new ArrayCollection());
        $monster->method('isBoss')->willReturn(false);
        $monster->method('getBossPhases')->willReturn(null);
        $monster->method('getHit')->willReturn(80);
        $monster->method('getCurrentBossPhase')->willReturn(null);

        $basicAttack = $this->createMock(Spell::class);
        $basicAttack->method('getName')->willReturn('Attaque');

        $mob = $this->createMock(Mob::class);
        $mob->method('isDead')->willReturn(false);
        $mob->method('isSummoned')->willReturn(true);
        $mob->method('getLife')->willReturn(35);
        $mob->method('getMaxLife')->willReturn(35);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('getName')->willReturn('Squelette');
        $mob->method('getId')->willReturn($id);
        $mob->method('getAttack')->willReturn($basicAttack);

        return $mob;
    }

    private function createFightWithMobs(array $mobs, bool $summonOnCooldown = false): Fight&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);
        $player->method('getName')->willReturn('Heros');

        $mobCollection = new ArrayCollection($mobs);

        $fight = $this->createMock(Fight::class);
        $fight->method('getMobs')->willReturn($mobCollection);
        $fight->method('getPlayers')->willReturn(new ArrayCollection([$player]));
        $fight->method('getId')->willReturn(42);
        $fight->method('getStep')->willReturn(0);
        $fight->method('isTerminated')->willReturn(false);
        $fight->method('isSpellOnCooldown')->willReturn($summonOnCooldown);

        return $fight;
    }

    public function testSummonerInvokesNewMob(): void
    {
        $summoner = $this->createSummonerMob(100);
        $fight = $this->createFightWithMobs([$summoner]);

        $skeletonMonster = $this->createSkeletonMonster();

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['slug' => 'skeleton'])->willReturn($skeletonMonster);
        $this->entityManager->method('getRepository')->with(Monster::class)->willReturn($repo);

        $this->entityManager->expects($this->once())->method('persist')
            ->with($this->callback(function ($mob) {
                return $mob instanceof Mob && $mob->isSummoned();
            }));

        $fight->expects($this->once())->method('addMob')
            ->with($this->isInstanceOf(Mob::class));

        $fight->expects($this->once())->method('setSpellCooldown')
            ->with('mob_10', '__summon', 3);

        $this->combatLogger->expects($this->once())->method('logSummon')
            ->with($fight, $summoner, 'Squelette', 1);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $summonMessage = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'invoque') && str_contains($msg, 'Squelette')) {
                $summonMessage = true;
            }
        }
        $this->assertTrue($summonMessage, 'Le message d\'invocation devrait apparaitre');
    }

    public function testSummonLimitReached(): void
    {
        $summoner = $this->createSummonerMob(100);

        // 2 mobs invoques deja presents (vivants) — mock complet pour doMobAction
        $summoned1 = $this->createSummonedMobMock(id: 20);
        $summoned2 = $this->createSummonedMobMock(id: 21);

        $fight = $this->createFightWithMobs([$summoner, $summoned1, $summoned2]);

        // Le summoner ne devrait PAS invoquer, il devrait attaquer a la place
        $this->spellApplicator->method('apply')->willReturn([]);
        $this->entityManager->expects($this->never())->method('persist');

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $summonMessage = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'invoque')) {
                $summonMessage = true;
            }
        }
        $this->assertFalse($summonMessage, 'Pas d\'invocation quand la limite est atteinte');
    }

    public function testSummonedMobHasCorrectProperties(): void
    {
        $summoner = $this->createSummonerMob(100);
        $fight = $this->createFightWithMobs([$summoner]);

        $skeletonMonster = $this->createSkeletonMonster();

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['slug' => 'skeleton'])->willReturn($skeletonMonster);
        $this->entityManager->method('getRepository')->with(Monster::class)->willReturn($repo);

        $persistedMob = null;
        $this->entityManager->expects($this->once())->method('persist')
            ->with($this->callback(function ($mob) use (&$persistedMob) {
                $persistedMob = $mob;

                return $mob instanceof Mob;
            }));

        $handler = $this->createHandler();
        $handler->doAction($fight);

        $this->assertInstanceOf(Mob::class, $persistedMob);
        $this->assertTrue($persistedMob->isSummoned());
        $this->assertSame(35, $persistedMob->getLife());
        // Level = max(1, summoner.level + level_offset) = max(1, 3 + 0) = 3
        $this->assertSame(3, $persistedMob->getLevel());
    }

    public function testSummonWithZeroChanceNeverSummons(): void
    {
        $summoner = $this->createSummonerMob(0);
        $fight = $this->createFightWithMobs([$summoner]);

        $this->spellApplicator->method('apply')->willReturn([]);
        $this->entityManager->expects($this->never())->method('persist');

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $summonMessage = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'invoque')) {
                $summonMessage = true;
            }
        }
        $this->assertFalse($summonMessage, 'Avec 0%% de chance, pas d\'invocation');
    }

    public function testSummonWithUnknownSlugDoesNotCrash(): void
    {
        $summoner = $this->createSummonerMob(100);
        $fight = $this->createFightWithMobs([$summoner]);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['slug' => 'skeleton'])->willReturn(null);
        $this->entityManager->method('getRepository')->with(Monster::class)->willReturn($repo);

        $this->entityManager->expects($this->never())->method('persist');

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        // Pas de crash, pas de message d'invocation
        $this->assertIsArray($result['messages']);
    }

    public function testSummonOnCooldownSkips(): void
    {
        $summoner = $this->createSummonerMob(100);
        $fight = $this->createFightWithMobs([$summoner], summonOnCooldown: true);

        $this->spellApplicator->method('apply')->willReturn([]);
        $this->entityManager->expects($this->never())->method('persist');

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $summonMessage = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'invoque')) {
                $summonMessage = true;
            }
        }
        $this->assertFalse($summonMessage, 'Pas d\'invocation quand le cooldown est actif');
    }
}
