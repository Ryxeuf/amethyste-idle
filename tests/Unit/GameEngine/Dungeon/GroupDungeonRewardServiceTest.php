<?php

namespace App\Tests\Unit\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonMember;
use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Dungeon;
use App\GameEngine\Dungeon\GroupDungeonRewardService;
use App\GameEngine\Materia\MateriaLootTable;
use App\Repository\GroupDungeonClearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GroupDungeonRewardServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private GroupDungeonClearRepository&MockObject $clearRepository;
    private GroupDungeonRewardService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->clearRepository = $this->createMock(GroupDungeonClearRepository::class);
        $this->parameterRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $this->parameterRepository],
        ]);

        // MAT-06 : la table de butin est muette (classe finale, non mockable) —
        // son catalogue est vide, donc aucune materia ne tombe : ces tests
        // portent sur les gils et la decroissance, pas sur la materia.
        $emptyRepository = $this->createMock(EntityRepository::class);
        $emptyRepository->method('findBy')->willReturn([]);
        $lootEntityManager = $this->createMock(EntityManagerInterface::class);
        $lootEntityManager->method('getRepository')->willReturn($emptyRepository);
        $materiaLootTable = new MateriaLootTable($lootEntityManager);

        $this->service = new GroupDungeonRewardService($this->entityManager, $this->clearRepository, $materiaLootTable, new EventDispatcher());
    }

    private function buildPlayer(int $id): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->setGils(0);

        return $player;
    }

    private function buildRun(Player $player): GroupDungeonRun
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Caverne');
        $dungeon->setMaxPlayers(4);
        $run = new GroupDungeonRun($dungeon, $player, (new Zone())->setSlug('z')->setName('Z'));
        $run->setStatus(GroupDungeonRun::STATUS_COMPLETED);
        $run->addMember(new GroupDungeonMember($run, $player));

        return $run;
    }

    public function testFirstClearAwardsFullBaseGils(): void
    {
        $player = $this->buildPlayer(1);
        $run = $this->buildRun($player);
        $this->clearRepository->method('countRecentClears')->willReturn(0);
        $this->entityManager->expects($this->once())->method('persist');

        $this->service->award($run);

        // 0 reussite recente => facteur 1 => 150 gils par defaut.
        $this->assertSame(150, $player->getGils());
    }

    public function testSecondClearWithinWindowIsHalved(): void
    {
        $player = $this->buildPlayer(1);
        $run = $this->buildRun($player);
        // 1 reussite recente => facteur decay^1 = 0.5.
        $this->clearRepository->method('countRecentClears')->willReturn(1);

        $this->service->award($run);

        $this->assertSame(75, $player->getGils());
    }

    public function testRewardIsFlooredByMinFactor(): void
    {
        $player = $this->buildPlayer(1);
        $run = $this->buildRun($player);
        // Beaucoup de reussites => 0.5^10 tres petit, borne par le plancher 0.25.
        $this->clearRepository->method('countRecentClears')->willReturn(10);

        $this->service->award($run);

        // 150 * 0.25 = 37.5 => arrondi a 38.
        $this->assertSame(38, $player->getGils());
    }

    /**
     * DON-04 — la decroissance s'etend a la table d'objets : la chance de
     * materia suit decay^n **sans plancher**. Les gils gardent le leur (le
     * prix de la participation), le sommet du catalogue n'en a pas — sans
     * quoi le farm infini garderait un quart de chance de m5 pour toujours.
     */
    public function testMateriaDropChanceDecaysWithoutFloor(): void
    {
        $this->assertSame(100, $this->service->materiaDropChance(0));
        $this->assertSame(50, $this->service->materiaDropChance(1));
        $this->assertSame(25, $this->service->materiaDropChance(2));
        $this->assertSame(0, $this->service->materiaDropChance(10), 'Pas de plancher pour les objets.');
    }
}
