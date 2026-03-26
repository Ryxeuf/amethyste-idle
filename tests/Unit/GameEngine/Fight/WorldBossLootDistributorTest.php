<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\MonsterItem;
use App\Event\Fight\MobDeadEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Fight\WorldBossLootDistributor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;

class WorldBossLootDistributorTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private GameEventBonusProvider&MockObject $bonusProvider;
    private HubInterface&MockObject $hub;
    private WorldBossLootDistributor $distributor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->bonusProvider = $this->createMock(GameEventBonusProvider::class);
        $this->hub = $this->createMock(HubInterface::class);

        $this->distributor = new WorldBossLootDistributor(
            $this->em,
            $this->bonusProvider,
            $this->hub,
            new NullLogger(),
        );
    }

    public function testIgnoresNonWorldBossMob(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('isWorldBoss')->willReturn(false);

        $event = new MobDeadEvent($mob);

        $this->em->expects($this->never())->method('persist');

        $this->distributor->onMobDead($event);

        $this->assertFalse($event->isPropagationStopped());
    }

    public function testIgnoresWorldBossWithoutFight(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('isWorldBoss')->willReturn(true);
        $mob->method('getFight')->willReturn(null);

        $event = new MobDeadEvent($mob);

        $this->em->expects($this->never())->method('persist');

        $this->distributor->onMobDead($event);

        $this->assertFalse($event->isPropagationStopped());
    }

    public function testStopsPropagationForWorldBoss(): void
    {
        $mob = $this->createWorldBossMob([], []);

        $event = new MobDeadEvent($mob);

        $this->bonusProvider->method('getDropMultiplier')->willReturn(1.0);
        $this->hub->method('publish');

        $this->distributor->onMobDead($event);

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testTopContributorsReceiveGuaranteedLoot(): void
    {
        $guaranteedItem = $this->createItem('Epee legendaire');
        $guaranteedMonsterItem = $this->createMonsterItem($guaranteedItem, 0, true);

        $player1 = $this->createPlayer(1);
        $player2 = $this->createPlayer(2);
        $player3 = $this->createPlayer(3);
        $player4 = $this->createPlayer(4);

        $fight = new Fight();
        $fight->addPlayer($player1);
        $fight->addPlayer($player2);
        $fight->addPlayer($player3);
        $fight->addPlayer($player4);

        // Player 1 top damage, player 4 least
        $fight->addContribution(1, 500);
        $fight->addContribution(2, 300);
        $fight->addContribution(3, 200);
        $fight->addContribution(4, 50);

        $mob = $this->createWorldBossMob([$guaranteedMonsterItem], [$player1, $player2, $player3, $player4], $fight);

        $this->bonusProvider->method('getDropMultiplier')->willReturn(1.0);
        $this->hub->method('publish');

        // Top 3 players should get guaranteed loot = 3 persists
        $persistedItems = [];
        $this->em->expects($this->exactly(3))->method('persist')
            ->with($this->callback(function ($item) use (&$persistedItems): bool {
                $persistedItems[] = $item;

                return true;
            }));
        $this->em->expects($this->once())->method('flush');

        $event = new MobDeadEvent($mob);
        $this->distributor->onMobDead($event);
    }

    public function testNonTopContributorsDoNotReceiveGuaranteedLoot(): void
    {
        $guaranteedItem = $this->createItem('Epee legendaire');
        $guaranteedMonsterItem = $this->createMonsterItem($guaranteedItem, 0, true);

        // Only 1 player (rank 4+ = not top 3)
        $player1 = $this->createPlayer(1);
        $player2 = $this->createPlayer(2);
        $player3 = $this->createPlayer(3);
        $player4 = $this->createPlayer(4);

        $fight = new Fight();
        $fight->addPlayer($player1);
        $fight->addPlayer($player2);
        $fight->addPlayer($player3);
        $fight->addPlayer($player4);

        // Player 4 is rank 4 with least damage
        $fight->addContribution(1, 500);
        $fight->addContribution(2, 400);
        $fight->addContribution(3, 300);
        $fight->addContribution(4, 10);

        $mob = $this->createWorldBossMob([$guaranteedMonsterItem], [$player1, $player2, $player3, $player4], $fight);

        $this->bonusProvider->method('getDropMultiplier')->willReturn(1.0);
        $this->hub->method('publish');

        // Only top 3 get guaranteed, player 4 does NOT
        $persistCount = 0;
        $this->em->method('persist')->willReturnCallback(function () use (&$persistCount): void {
            ++$persistCount;
        });

        $event = new MobDeadEvent($mob);
        $this->distributor->onMobDead($event);

        // 3 guaranteed items for top 3 contributors
        $this->assertSame(3, $persistCount);
    }

    public function testPublishesWorldBossDefeatedEvent(): void
    {
        $mob = $this->createWorldBossMob([], []);

        $this->bonusProvider->method('getDropMultiplier')->willReturn(1.0);
        $this->hub->expects($this->once())->method('publish');

        $event = new MobDeadEvent($mob);
        $this->distributor->onMobDead($event);
    }

    public function testSubscribedEventsRegistersWithHighPriority(): void
    {
        $events = WorldBossLootDistributor::getSubscribedEvents();

        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertSame(['onMobDead', 10], $events[MobDeadEvent::NAME]);
    }

    private function createPlayer(int $id): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);

        return $player;
    }

    private function createItem(string $name): Item
    {
        $item = new Item();
        $ref = new \ReflectionProperty(Item::class, 'name');
        $ref->setValue($item, $name);

        return $item;
    }

    private function createMonsterItem(Item $item, float $probability, bool $guaranteed, ?int $minDifficulty = null): MonsterItem
    {
        $monsterItem = new MonsterItem();
        $monsterItem->setItem($item);
        $monsterItem->setProbability($probability);
        $monsterItem->setGuaranteed($guaranteed);
        $monsterItem->setMinDifficulty($minDifficulty);

        return $monsterItem;
    }

    /**
     * @param MonsterItem[] $monsterItems
     * @param Player[]      $players
     */
    private function createWorldBossMob(array $monsterItems, array $players, ?Fight $fight = null): Mob&MockObject
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getMonsterItems')->willReturn($monsterItems);
        $monster->method('getDifficulty')->willReturn(1);

        $map = $this->createMock(Map::class);
        $map->method('getId')->willReturn(1);

        if ($fight === null) {
            $fight = new Fight();
            foreach ($players as $player) {
                $fight->addPlayer($player);
            }
        }

        $mob = $this->createMock(Mob::class);
        $mob->method('isWorldBoss')->willReturn(true);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('getMap')->willReturn($map);
        $mob->method('getName')->willReturn('World Boss Test');
        $mob->method('getCoordinates')->willReturn('10.10');
        $mob->method('getId')->willReturn(1);
        $mob->method('addItem')->willReturnCallback(function (): void {});

        return $mob;
    }
}
