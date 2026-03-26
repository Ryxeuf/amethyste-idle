<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\App\Fight;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\Quest;
use App\Entity\Game\Recipe;
use App\Enum\InfluenceActivityType;
use App\Event\CraftEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\Event\Map\ButcheringEvent;
use App\Event\Map\FishingEvent;
use App\Event\Map\SpotHarvestEvent;
use App\EventListener\InfluenceListener;
use App\GameEngine\Guild\InfluenceManager;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InfluenceListenerTest extends TestCase
{
    private InfluenceManager&MockObject $influenceManager;
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $em;
    private InfluenceListener $listener;

    protected function setUp(): void
    {
        $this->influenceManager = $this->createMock(InfluenceManager::class);
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->listener = new InfluenceListener(
            $this->influenceManager,
            $this->playerHelper,
            $this->em,
        );
    }

    public function testSubscribedEvents(): void
    {
        $events = InfluenceListener::getSubscribedEvents();

        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertArrayHasKey(CraftEvent::NAME, $events);
        $this->assertArrayHasKey(SpotHarvestEvent::NAME, $events);
        $this->assertArrayHasKey(FishingEvent::NAME, $events);
        $this->assertArrayHasKey(ButcheringEvent::NAME, $events);
        $this->assertArrayHasKey(QuestCompletedEvent::NAME, $events);
    }

    public function testOnMobDeadAwardsInfluence(): void
    {
        $region = $this->createRegion();
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('goblin');

        $mob = $this->createMobWithFight($region, $monster, 8, [$player]);

        $this->influenceManager->expects($this->once())
            ->method('awardInfluence')
            ->with(
                $player,
                InfluenceActivityType::MobKill,
                ['mob_level' => 8],
                $region,
                $this->callback(fn (array $d) => $d['monster'] === 'goblin' && $d['level'] === 8),
            );

        $this->em->expects($this->once())->method('flush');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadSkipsSummonedMobs(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(true);

        $this->influenceManager->expects($this->never())->method('awardInfluence');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadSkipsDeadPlayers(): void
    {
        $region = $this->createRegion();
        $deadPlayer = $this->createMock(Player::class);
        $deadPlayer->method('isDead')->willReturn(true);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('zombie');

        $mob = $this->createMobWithFight($region, $monster, 5, [$deadPlayer]);

        $this->influenceManager->expects($this->never())->method('awardInfluence');
        $this->em->expects($this->once())->method('flush');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadSkipsNoRegion(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('rat');

        $mob = $this->createMobWithFight(null, $monster, 1, [$player]);

        $this->influenceManager->expects($this->never())->method('awardInfluence');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnCraftAwardsInfluence(): void
    {
        $player = $this->createMock(Player::class);
        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getRequiredLevel')->willReturn(3);
        $recipe->method('getName')->willReturn('Epee de fer');

        $item = $this->createMock(Item::class);
        $item->method('getSlug')->willReturn('iron_sword');

        $event = new CraftEvent($player, $recipe, $item, 1);

        $this->influenceManager->expects($this->once())
            ->method('awardInfluence')
            ->with(
                $player,
                InfluenceActivityType::Craft,
                ['recipe_level' => 3],
                null,
                $this->callback(fn (array $d) => $d['recipe'] === 'Epee de fer' && $d['item'] === 'iron_sword'),
            );

        $this->em->expects($this->once())->method('flush');

        $this->listener->onCraft($event);
    }

    public function testOnSpotHarvestAwardsInfluence(): void
    {
        $region = $this->createRegion();
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $map = $this->createMock(Map::class);
        $map->method('getRegion')->willReturn($region);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getMap')->willReturn($map);
        $objectLayer->method('getSlug')->willReturn('herb_01');

        $items = [$this->createMock(PlayerItem::class), $this->createMock(PlayerItem::class)];

        $event = new SpotHarvestEvent($objectLayer, $items);

        $this->influenceManager->expects($this->once())
            ->method('awardInfluence')
            ->with(
                $player,
                InfluenceActivityType::Harvest,
                ['item_count' => 2],
                $region,
                $this->callback(fn (array $d) => $d['spot'] === 'herb_01' && $d['items'] === 2),
            );

        $this->em->expects($this->once())->method('flush');

        $this->listener->onSpotHarvest($event);
    }

    public function testOnSpotHarvestSkipsNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $event = new SpotHarvestEvent($objectLayer, []);

        $this->influenceManager->expects($this->never())->method('awardInfluence');

        $this->listener->onSpotHarvest($event);
    }

    public function testOnSpotHarvestSkipsEmptyItems(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $event = new SpotHarvestEvent($objectLayer, []);

        $this->influenceManager->expects($this->never())->method('awardInfluence');

        $this->listener->onSpotHarvest($event);
    }

    public function testOnFishingAwardsInfluence(): void
    {
        $region = $this->createRegion();
        $player = $this->createMock(Player::class);

        $map = $this->createMock(Map::class);
        $map->method('getRegion')->willReturn($region);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getMap')->willReturn($map);
        $objectLayer->method('getSlug')->willReturn('fishing_spot_01');

        $caughtItem = $this->createMock(PlayerItem::class);

        $event = new FishingEvent($player, $objectLayer, $caughtItem);

        $this->influenceManager->expects($this->once())
            ->method('awardInfluence')
            ->with($player, InfluenceActivityType::Fishing, [], $region, $this->isType('array'));

        $this->em->expects($this->once())->method('flush');

        $this->listener->onFishing($event);
    }

    public function testOnFishingSkipsFailure(): void
    {
        $player = $this->createMock(Player::class);
        $objectLayer = $this->createMock(ObjectLayer::class);

        $event = new FishingEvent($player, $objectLayer, null);

        $this->influenceManager->expects($this->never())->method('awardInfluence');

        $this->listener->onFishing($event);
    }

    public function testOnButcheringAwardsInfluence(): void
    {
        $region = $this->createRegion();
        $player = $this->createMock(Player::class);

        $map = $this->createMock(Map::class);
        $map->method('getRegion')->willReturn($region);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('wolf');

        $mob = $this->createMock(Mob::class);
        $mob->method('getMap')->willReturn($map);
        $mob->method('getMonster')->willReturn($monster);

        $items = [$this->createMock(PlayerItem::class)];

        $event = new ButcheringEvent($player, $mob, $items);

        $this->influenceManager->expects($this->once())
            ->method('awardInfluence')
            ->with(
                $player,
                InfluenceActivityType::Butchering,
                ['item_count' => 1],
                $region,
                $this->callback(fn (array $d) => $d['mob'] === 'wolf' && $d['items'] === 1),
            );

        $this->em->expects($this->once())->method('flush');

        $this->listener->onButchering($event);
    }

    public function testOnQuestCompletedAwardsInfluence(): void
    {
        $player = $this->createMock(Player::class);
        $quest = $this->createMock(Quest::class);
        $quest->method('getName')->willReturn('Chasse aux loups');

        $event = new QuestCompletedEvent($player, $quest);

        $this->influenceManager->expects($this->once())
            ->method('awardInfluence')
            ->with(
                $player,
                InfluenceActivityType::Quest,
                ['quest_tier' => 1],
                null,
                $this->callback(fn (array $d) => $d['quest'] === 'Chasse aux loups'),
            );

        $this->em->expects($this->once())->method('flush');

        $this->listener->onQuestCompleted($event);
    }

    private function createRegion(): Region
    {
        $region = new Region();
        $region->setName('Plaines');
        $region->setSlug('plaines');

        return $region;
    }

    /**
     * @param Player[] $players
     */
    private function createMobWithFight(?Region $region, Monster&MockObject $monster, int $level, array $players): Mob&MockObject
    {
        $map = $this->createMock(Map::class);
        $map->method('getRegion')->willReturn($region);

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));

        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(false);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getMap')->willReturn($map);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('getLevel')->willReturn($level);

        return $mob;
    }
}
