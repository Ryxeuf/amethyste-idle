<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\GameEvent;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\PlayerZoneEventParticipation;
use App\Entity\App\Zone;
use App\Entity\App\ZoneBoss;
use App\Entity\Game\Monster;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\ZoneActionException;
use App\GameEngine\Zone\ZoneBossService;
use App\Helper\InventoryHelper;
use App\Repository\PlayerZoneEventParticipationRepository;
use App\Repository\ZoneBossRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;

class ZoneBossServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private ZoneBossRepository&MockObject $bossRepository;
    private PlayerZoneEventParticipationRepository&MockObject $participationRepository;
    private ActionEnergyManager&MockObject $actionEnergyManager;
    private PlayerItemGenerator&MockObject $playerItemGenerator;
    private InventoryHelper&MockObject $inventoryHelper;
    private ZoneBossService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $this->parameterRepository],
        ]);

        $this->bossRepository = $this->createMock(ZoneBossRepository::class);
        $this->participationRepository = $this->createMock(PlayerZoneEventParticipationRepository::class);
        $this->actionEnergyManager = $this->createMock(ActionEnergyManager::class);
        $this->playerItemGenerator = $this->createMock(PlayerItemGenerator::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);

        $this->service = new class($this->entityManager, $this->bossRepository, $this->participationRepository, $this->actionEnergyManager, $this->playerItemGenerator, $this->inventoryHelper, $this->createMock(HubInterface::class), $this->createMock(LoggerInterface::class)) extends ZoneBossService {
            /** @var list<int> */
            public array $rolls = [21]; // variance 0 par defaut
            private int $rollIndex = 0;

            protected function roll(int $max): int
            {
                return $this->rolls[$this->rollIndex++] ?? 21;
            }

            protected function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-07-25 12:00:00');
            }
        };
    }

    private function buildZone(int $id = 1): Zone
    {
        $zone = (new Zone())->setSlug('foret')->setName('Foret');
        (new \ReflectionProperty(Zone::class, 'id'))->setValue($zone, $id);

        return $zone;
    }

    private function buildEvent(Zone $zone): GameEvent
    {
        $event = new GameEvent();
        $event->setName('Reveil du colosse');
        $event->setStatus(GameEvent::STATUS_ACTIVE);
        $event->setStartsAt(new \DateTime('2026-07-25 10:00:00'));
        $event->setEndsAt(new \DateTime('2026-07-25 14:00:00'));
        $event->setZone($zone);

        return $event;
    }

    private function buildMonster(): Monster
    {
        $monster = new Monster();
        $monster->setName('Colosse');
        $monster->setSlug('colossus');

        return $monster;
    }

    private function buildPlayer(Zone $zone, int $hit = 50): Player
    {
        $player = new Player();
        $player->setCurrentZone($zone);
        $player->setHit($hit);
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, 1);

        return $player;
    }

    public function testAssaultDealsDamageAndRecordsContribution(): void
    {
        $zone = $this->buildZone();
        $event = $this->buildEvent($zone);
        $boss = new ZoneBoss($event, $this->buildMonster(), 1000);
        $player = $this->buildPlayer($zone, 50);

        $this->bossRepository->method('findOneByGameEvent')->willReturn($boss);
        $this->participationRepository->method('findOneForPlayerAndEvent')->willReturn(null);
        $this->actionEnergyManager->expects($this->once())->method('spend');

        $result = $this->service->assault($player, $event);

        // hit 50, facteur 100 %, variance 0 => 50 degats.
        $this->assertSame(50, $result->damageDealt);
        $this->assertSame(950, $result->hpCurrent);
        $this->assertSame(1000, $result->hpMax);
        $this->assertSame(50, $result->totalContribution);
        $this->assertFalse($result->defeated);
    }

    public function testAssaultAccumulatesContributionOnExistingParticipation(): void
    {
        $zone = $this->buildZone();
        $event = $this->buildEvent($zone);
        $boss = new ZoneBoss($event, $this->buildMonster(), 1000);
        $player = $this->buildPlayer($zone, 30);

        $participation = new PlayerZoneEventParticipation($player, $event);
        $participation->addContribution(100);
        $this->bossRepository->method('findOneByGameEvent')->willReturn($boss);
        $this->participationRepository->method('findOneForPlayerAndEvent')->willReturn($participation);

        $result = $this->service->assault($player, $event);

        $this->assertSame(30, $result->damageDealt);
        $this->assertSame(130, $result->totalContribution);
    }

    public function testAssaultDefeatsBossAndDistributesLoot(): void
    {
        $zone = $this->buildZone();
        $event = $this->buildEvent($zone);
        $boss = new ZoneBoss($event, $this->buildMonster(), 40);
        $player = $this->buildPlayer($zone, 50); // 50 degats > 40 PV => defaite

        $this->bossRepository->method('findOneByGameEvent')->willReturn($boss);
        $this->participationRepository->method('findOneForPlayerAndEvent')->willReturn(null);

        $participation = new PlayerZoneEventParticipation($player, $event);
        $participation->addContribution(40);
        $this->participationRepository->method('findByEventOrderedByContribution')->willReturn([$participation]);
        $this->playerItemGenerator->method('generateFromItemId')->willReturn(new PlayerItem());

        $result = $this->service->assault($player, $event);

        $this->assertTrue($result->defeated);
        $this->assertSame(0, $result->hpCurrent);
        $this->assertSame(40, $result->damageDealt); // borne aux PV restants
        $this->assertTrue($boss->isDefeated());
    }

    public function testAssaultRejectsWhenNoBoss(): void
    {
        $zone = $this->buildZone();
        $event = $this->buildEvent($zone);
        $this->bossRepository->method('findOneByGameEvent')->willReturn(null);

        $this->expectException(ZoneActionException::class);
        $this->service->assault($this->buildPlayer($zone), $event);
    }

    public function testAssaultRejectsWhenNotPresent(): void
    {
        $eventZone = $this->buildZone(1);
        $otherZone = $this->buildZone(2);
        $event = $this->buildEvent($eventZone);
        $boss = new ZoneBoss($event, $this->buildMonster(), 100);
        $this->bossRepository->method('findOneByGameEvent')->willReturn($boss);

        $this->expectException(ZoneActionException::class);
        $this->service->assault($this->buildPlayer($otherZone), $event);
    }

    public function testAssaultRejectsWhenAlreadyDefeated(): void
    {
        $zone = $this->buildZone();
        $event = $this->buildEvent($zone);
        $boss = new ZoneBoss($event, $this->buildMonster(), 10);
        $boss->applyDamage(10); // deja vaincu
        $this->bossRepository->method('findOneByGameEvent')->willReturn($boss);

        $this->expectException(ZoneActionException::class);
        $this->service->assault($this->buildPlayer($zone), $event);
    }

    public function testGetAssaultCostReadsParameterOverride(): void
    {
        $parameter = new Parameter();
        $parameter->setName(ZoneBossService::PARAM_ASSAULT_COST);
        $parameter->setValue('15');
        $this->parameterRepository->method('findOneBy')->willReturn($parameter);

        $this->assertSame(15, $this->service->getAssaultCost());
    }
}
