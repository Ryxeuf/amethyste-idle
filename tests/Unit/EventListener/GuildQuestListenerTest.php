<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\App\Fight;
use App\Entity\App\Guild;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\Recipe;
use App\Enum\GuildQuestType;
use App\Event\CraftEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Map\SpotHarvestEvent;
use App\EventListener\GuildQuestListener;
use App\GameEngine\Guild\GuildQuestManager;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GuildQuestListenerTest extends TestCase
{
    private GuildQuestManager&MockObject $questManager;
    private PlayerHelper&MockObject $playerHelper;
    private GuildQuestListener $listener;

    protected function setUp(): void
    {
        $this->questManager = $this->createMock(GuildQuestManager::class);
        $this->playerHelper = $this->createMock(PlayerHelper::class);

        $this->listener = new GuildQuestListener(
            $this->questManager,
            $this->playerHelper,
        );
    }

    public function testSubscribedEvents(): void
    {
        $events = GuildQuestListener::getSubscribedEvents();

        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertArrayHasKey(SpotHarvestEvent::NAME, $events);
        $this->assertArrayHasKey(CraftEvent::NAME, $events);
    }

    public function testOnMobDeadTracksKillProgress(): void
    {
        $guild = $this->createMock(Guild::class);
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('goblin');

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection([$player]));

        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(false);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getMonster')->willReturn($monster);

        $this->questManager->method('getPlayerGuild')
            ->with($player)
            ->willReturn($guild);

        $this->questManager->expects($this->once())
            ->method('trackProgress')
            ->with($guild, GuildQuestType::Kill, 'goblin', 1);

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadSkipsSummonedMobs(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(true);

        $this->questManager->expects($this->never())->method('trackProgress');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadSkipsPlayerWithNoGuild(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('goblin');

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection([$player]));

        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(false);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getMonster')->willReturn($monster);

        $this->questManager->method('getPlayerGuild')->willReturn(null);
        $this->questManager->expects($this->never())->method('trackProgress');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnCraftTracksProgress(): void
    {
        $guild = $this->createMock(Guild::class);
        $player = $this->createMock(Player::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getSlug')->willReturn('iron-sword');

        $item = $this->createMock(Item::class);

        $this->questManager->method('getPlayerGuild')
            ->with($player)
            ->willReturn($guild);

        $this->questManager->expects($this->once())
            ->method('trackProgress')
            ->with($guild, GuildQuestType::Craft, 'iron-sword', 2);

        $this->listener->onCraft(new CraftEvent($player, $recipe, $item, 2));
    }

    public function testOnCraftSkipsPlayerWithNoGuild(): void
    {
        $player = $this->createMock(Player::class);
        $recipe = $this->createMock(Recipe::class);
        $item = $this->createMock(Item::class);

        $this->questManager->method('getPlayerGuild')->willReturn(null);
        $this->questManager->expects($this->never())->method('trackProgress');

        $this->listener->onCraft(new CraftEvent($player, $recipe, $item, 1));
    }
}
