<?php

namespace App\Tests\Unit\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonMember;
use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Dungeon;
use App\GameEngine\Dungeon\GroupDungeonCombatService;
use App\GameEngine\Dungeon\GroupDungeonException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupDungeonCombatServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private EntityRepository&MockObject $playerRepository;
    private GroupDungeonCombatService $service;
    public \DateTimeImmutable $now;

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

        $test = $this;
        $this->service = new class($this->entityManager, $test) extends GroupDungeonCombatService {
            public function __construct(EntityManagerInterface $em, private $test)
            {
                parent::__construct($em);
            }

            protected function now(): \DateTimeImmutable
            {
                return $this->test->now;
            }
        };
    }

    private function buildPlayer(int $id, int $hit = 100): Player
    {
        $player = new Player();
        $player->setHit($hit);
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
        // 2 membres * 200 PV/membre = 400 PV de rencontre.
        $this->assertSame(400, $state['encounterHpMax']);
        $this->assertSame(400, $state['encounterHpCurrent']);
        $this->assertSame(1, $state['activePlayerId']);
        $this->assertSame(45, $state['turnRemainingSeconds']);
    }

    public function testActReducesEncounterAndAdvancesTurn(): void
    {
        $p1 = $this->buildPlayer(1, 100);
        $p2 = $this->buildPlayer(2, 100);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // init

        $state = $this->service->act($p1, $run);

        $this->assertSame(300, $state['encounterHpCurrent']); // 400 - 100
        $this->assertSame(2, $state['activePlayerId']); // tour avance vers p2
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
        $p1 = $this->buildPlayer(1, 100);
        $p2 = $this->buildPlayer(2, 100);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // init, deadline = now + 45s, tour = p1

        // Le repository renvoie le joueur actif pour la resolution auto.
        $this->playerRepository->method('find')->willReturnCallback(
            fn ($id): ?Player => 1 === $id ? $p1 : (2 === $id ? $p2 : null)
        );

        // On avance le temps au-dela de l'echeance : le tour de p1 rate -> attaque auto.
        $this->now = $this->now->modify('+50 seconds');
        $state = $this->service->state($run);

        $this->assertSame(300, $state['encounterHpCurrent']); // p1 a inflige 100 par defaut
        $this->assertSame(2, $state['activePlayerId']); // tour avance vers p2
    }

    public function testEncounterDefeatCompletesRun(): void
    {
        // 1 membre, HP 200, hit 100 : 2 attaques suffisent.
        $p1 = $this->buildPlayer(1, 100);
        $run = $this->buildRun([$p1]);
        $this->service->state($run);

        $this->service->act($p1, $run); // 200 -> 100
        $state = $this->service->act($p1, $run); // 100 -> 0 => complete

        $this->assertSame(GroupDungeonRun::STATUS_COMPLETED, $state['status']);
        $this->assertSame(0, $state['encounterHpCurrent']);
        $this->assertNull($run->getTurnDeadline());
    }
}
