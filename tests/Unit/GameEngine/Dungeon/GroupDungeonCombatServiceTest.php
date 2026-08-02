<?php

namespace App\Tests\Unit\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonMember;
use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Dungeon;
use App\GameEngine\Dungeon\DungeonActionResolver;
use App\GameEngine\Dungeon\GroupDungeonCombatService;
use App\GameEngine\Dungeon\GroupDungeonException;
use App\GameEngine\Dungeon\GroupDungeonRewardService;
use App\GameEngine\Realtime\Dungeon\GroupDungeonCombatPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * DON-02 : la boucle semi-synchrone reste (tour, echeance, action par
 * defaut), mais l'action est celle du build (via `DungeonActionResolver`,
 * mocke ici — son contrat propre a son test), la rencontre riposte sur le
 * membre qui agit, et un donjon peut etre perdu.
 */
class GroupDungeonCombatServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private EntityRepository&MockObject $playerRepository;
    private DungeonActionResolver&MockObject $actionResolver;
    private GroupDungeonCombatService $service;
    public \DateTimeImmutable $now;

    /** @var array<int, int> degat par id de joueur (defaut 100) */
    public array $damageByPlayer = [];

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->playerRepository = $this->createMock(EntityRepository::class);
        $this->parameterRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $this->parameterRepository],
            [Player::class, $this->playerRepository],
        ]);
        $this->now = new \DateTimeImmutable('2026-07-25 12:00:00');

        $this->actionResolver = $this->createMock(DungeonActionResolver::class);
        $this->actionResolver->method('resolve')->willReturnCallback(
            fn (Player $player, ?string $spellSlug = null): array => [
                'damage' => $this->damageByPlayer[$player->getId()] ?? 100,
                'spellSlug' => $spellSlug,
            ]
        );

        $publisher = $this->createMock(GroupDungeonCombatPublisher::class);
        $rewardService = $this->createMock(GroupDungeonRewardService::class);
        $test = $this;
        $this->service = new class($this->entityManager, $publisher, $rewardService, $this->actionResolver, $test) extends GroupDungeonCombatService {
            public function __construct(EntityManagerInterface $em, GroupDungeonCombatPublisher $publisher, GroupDungeonRewardService $rewardService, DungeonActionResolver $actionResolver, private $test)
            {
                parent::__construct($em, $publisher, $rewardService, $actionResolver);
            }

            protected function now(): \DateTimeImmutable
            {
                return $this->test->now;
            }
        };
    }

    private function buildPlayer(int $id, int $life = 50): Player
    {
        $player = new Player();
        $player->setLife($life);
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);

        return $player;
    }

    /** @param list<Player> $players */
    private function buildRun(array $players): GroupDungeonRun
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Caverne');
        $dungeon->setMaxPlayers(4);
        $run = new GroupDungeonRun($dungeon, $players[0], (new Zone())->setSlug('z')->setName('Z'));
        $run->setStatus(GroupDungeonRun::STATUS_IN_PROGRESS);
        foreach ($players as $p) {
            $run->addMember(new GroupDungeonMember($run, $p));
        }

        return $run;
    }

    public function testStateInitializesCombat(): void
    {
        $run = $this->buildRun([$this->buildPlayer(1), $this->buildPlayer(2)]);

        $state = $this->service->state($run);

        $this->assertTrue($run->isCombatInitialized());
        // 2 membres * 120 PV/membre = 240 PV de rencontre (recalibre DON-02 :
        // 200 etait le reglage d'une rencontre sans riposte).
        $this->assertSame(240, $state['encounterHpMax']);
        $this->assertSame(240, $state['encounterHpCurrent']);
        $this->assertSame(1, $state['activePlayerId']);
        $this->assertSame(45, $state['turnRemainingSeconds']);
    }

    public function testActReducesEncounterAndAdvancesTurn(): void
    {
        $p1 = $this->buildPlayer(1);
        $p2 = $this->buildPlayer(2);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // init

        $state = $this->service->act($p1, $run);

        $this->assertSame(140, $state['encounterHpCurrent']); // 240 - 100
        $this->assertSame(2, $state['activePlayerId']); // tour avance vers p2
    }

    /**
     * DON-02 — le build modifie le degat : deux joueurs aux actions
     * differentes n'entament pas la rencontre pareil.
     */
    public function testTheBuildChangesTheDamage(): void
    {
        $p1 = $this->buildPlayer(1);
        $p2 = $this->buildPlayer(2);
        $this->damageByPlayer = [1 => 30, 2 => 90];
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run);

        $this->service->act($p1, $run);
        $state = $this->service->act($p2, $run);

        $this->assertSame(120, $state['encounterHpCurrent']); // 240 - 30 - 90
    }

    /**
     * DON-02 — la riposte : la rencontre frappe le membre qui vient d'agir
     * (10 par defaut), et agir a enfin un cout.
     */
    public function testTheEncounterStrikesBack(): void
    {
        $p1 = $this->buildPlayer(1, 50);
        $p2 = $this->buildPlayer(2, 50);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run);

        $this->service->act($p1, $run);

        $this->assertSame(40, $p1->getLife());
        $this->assertSame(50, $p2->getLife());
    }

    /**
     * DON-02 — l'echec est atteignable : quand plus un membre ne tient
     * debout, le run passe FAILED et rien n'est distribue.
     */
    public function testTheRunFailsWhenEveryMemberIsDown(): void
    {
        $p1 = $this->buildPlayer(1, 10); // la riposte de son propre tour le couche
        $run = $this->buildRun([$p1]);
        $this->service->state($run);

        $state = $this->service->act($p1, $run);

        $this->assertSame(GroupDungeonRun::STATUS_FAILED, $state['status']);
        $this->assertTrue($p1->isDead());
        $this->assertNull($run->getTurnDeadline());
    }

    /**
     * Un membre a terre ne joue plus : le tour le saute apres chaque action,
     * et s'il est tombe hors du donjon en etant actif, son action volontaire
     * est refusee.
     */
    public function testADownedMemberIsSkipped(): void
    {
        $p1 = $this->buildPlayer(1, 10);
        $p2 = $this->buildPlayer(2, 50);
        $this->damageByPlayer = [1 => 5, 2 => 5];
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run);

        $this->service->act($p1, $run); // riposte : p1 tombe a 0
        $this->assertTrue($p1->isDead());
        $this->assertSame(2, $run->getActivePlayerId());

        $this->service->act($p2, $run); // p2 agit, le tour saute p1 et lui revient
        $this->assertSame(2, $run->getActivePlayerId());
    }

    /**
     * Un membre tombe hors du donjon en etant le joueur actif ne peut pas
     * agir : son action est refusee, le tour passera aux survivants.
     */
    public function testAMemberDownedWhileActiveCannotAct(): void
    {
        $p1 = $this->buildPlayer(1, 50);
        $p2 = $this->buildPlayer(2, 50);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // tour actif = p1

        // p1 meurt hors du donjon (un combat de zone, par exemple).
        $p1->setLife(0);
        $p1->setDiedAt(new \DateTime());

        $this->expectException(GroupDungeonException::class);
        $this->expectExceptionMessage('game.zone.dungeon.error.member_down');
        $this->service->act($p1, $run);
    }

    public function testActRejectsWhenNotYourTurn(): void
    {
        $p1 = $this->buildPlayer(1);
        $p2 = $this->buildPlayer(2);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // tour actif = p1

        $this->expectException(GroupDungeonException::class);
        $this->service->act($p2, $run);
    }

    public function testOverdueTurnResolvesWithDefaultAction(): void
    {
        $p1 = $this->buildPlayer(1);
        $p2 = $this->buildPlayer(2);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // init, deadline = now + 45s, tour = p1

        // Le repository renvoie le joueur actif pour la resolution auto.
        $this->playerRepository->method('find')->willReturnCallback(
            fn ($id): ?Player => 1 === $id ? $p1 : (2 === $id ? $p2 : null)
        );

        // On avance le temps au-dela de l'echeance : le tour de p1 rate -> action par defaut.
        $this->now = $this->now->modify('+50 seconds');
        $state = $this->service->state($run);

        $this->assertSame(140, $state['encounterHpCurrent']); // p1 a inflige 100 par defaut
        $this->assertSame(40, $p1->getLife()); // et la riposte l'a touche
        $this->assertSame(2, $state['activePlayerId']); // tour avance vers p2
    }

    public function testEncounterDefeatCompletesRun(): void
    {
        // 1 membre, HP 120, degat 100 : 2 attaques suffisent — et la
        // rencontre qui tombe ne riposte pas.
        $p1 = $this->buildPlayer(1, 50);
        $run = $this->buildRun([$p1]);
        $this->service->state($run);

        $this->service->act($p1, $run); // 120 -> 20, riposte : 50 -> 40
        $state = $this->service->act($p1, $run); // 20 -> 0 => complete, pas de riposte

        $this->assertSame(GroupDungeonRun::STATUS_COMPLETED, $state['status']);
        $this->assertSame(0, $state['encounterHpCurrent']);
        $this->assertSame(40, $p1->getLife());
        $this->assertNull($run->getTurnDeadline());
    }
}
