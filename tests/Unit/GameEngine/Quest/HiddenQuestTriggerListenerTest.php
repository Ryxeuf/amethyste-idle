<?php

namespace App\Tests\Unit\GameEngine\Quest;

use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\Game\Monster;
use App\Entity\Game\Quest;
use App\Event\Fight\MobDeadEvent;
use App\Event\Map\PlayerMovedEvent;
use App\Event\Map\SpotHarvestEvent;
use App\GameEngine\Quest\HiddenQuestTriggerListener;
use App\GameEngine\Quest\QuestTrackingFormater;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HiddenQuestTriggerListenerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private QuestTrackingFormater $questTrackingFormater;
    private HiddenQuestTriggerListener $listener;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->questTrackingFormater = new QuestTrackingFormater();

        $this->listener = new HiddenQuestTriggerListener(
            $this->playerHelper,
            $this->entityManager,
            $this->questTrackingFormater,
        );
    }

    public function testSubscribedEvents(): void
    {
        $events = HiddenQuestTriggerListener::getSubscribedEvents();

        $this->assertArrayHasKey(PlayerMovedEvent::NAME, $events);
        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertArrayHasKey(SpotHarvestEvent::NAME, $events);
    }

    public function testOnMobDeadTriggersHiddenQuest(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('slime');

        $mob = $this->createMock(Mob::class);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('isSummoned')->willReturn(false);

        $quest = new Quest();
        $quest->setName('Hidden quest');
        $quest->setDescription('Test');
        $quest->setIsHidden(true);
        $quest->setTriggerCondition(['type' => 'kill', 'monster_slug' => 'slime']);
        $quest->setRequirements(['monsters' => [['slug' => 'slime', 'name' => 'Slime', 'count' => 5]]]);
        $quest->setRewards([]);

        // Use reflection to set ID
        $ref = new \ReflectionProperty(Quest::class, 'id');
        $ref->setValue($quest, 99);

        $questRepo = $this->createMock(EntityRepository::class);
        $questRepo->method('findBy')
            ->with(['isHidden' => true, 'isDaily' => false])
            ->willReturn([$quest]);

        $playerQuestRepo = $this->createMock(EntityRepository::class);
        $playerQuestRepo->method('findBy')
            ->with(['player' => $player])
            ->willReturn([]);

        $completedQuestRepo = $this->createMock(EntityRepository::class);
        $completedQuestRepo->method('findBy')
            ->with(['player' => $player])
            ->willReturn([]);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Quest::class, $questRepo],
                [PlayerQuest::class, $playerQuestRepo],
                [PlayerQuestCompleted::class, $completedQuestRepo],
            ]);

        $this->entityManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(PlayerQuest::class));
        $this->entityManager->expects($this->once())->method('flush');

        $event = new MobDeadEvent($mob);
        $this->listener->onMobDead($event);
    }

    public function testDoesNotTriggerWhenQuestAlreadyActive(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('slime');

        $mob = $this->createMock(Mob::class);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('isSummoned')->willReturn(false);

        $quest = new Quest();
        $quest->setName('Hidden quest');
        $quest->setDescription('Test');
        $quest->setIsHidden(true);
        $quest->setTriggerCondition(['type' => 'kill', 'monster_slug' => 'slime']);
        $quest->setRequirements(['monsters' => [['slug' => 'slime', 'name' => 'Slime', 'count' => 5]]]);
        $quest->setRewards([]);

        $ref = new \ReflectionProperty(Quest::class, 'id');
        $ref->setValue($quest, 99);

        // Player already has this quest active
        $existingPq = $this->createMock(PlayerQuest::class);
        $existingPq->method('getQuest')->willReturn($quest);

        $questRepo = $this->createMock(EntityRepository::class);
        $questRepo->method('findBy')->willReturn([$quest]);

        $playerQuestRepo = $this->createMock(EntityRepository::class);
        $playerQuestRepo->method('findBy')->willReturn([$existingPq]);

        $completedQuestRepo = $this->createMock(EntityRepository::class);
        $completedQuestRepo->method('findBy')->willReturn([]);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Quest::class, $questRepo],
                [PlayerQuest::class, $playerQuestRepo],
                [PlayerQuestCompleted::class, $completedQuestRepo],
            ]);

        $this->entityManager->expects($this->never())->method('persist');

        $event = new MobDeadEvent($mob);
        $this->listener->onMobDead($event);
    }

    public function testDoesNotTriggerWhenMonsterSlugDoesNotMatch(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('goblin');

        $mob = $this->createMock(Mob::class);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('isSummoned')->willReturn(false);

        $quest = new Quest();
        $quest->setName('Hidden quest');
        $quest->setDescription('Test');
        $quest->setIsHidden(true);
        $quest->setTriggerCondition(['type' => 'kill', 'monster_slug' => 'slime']);
        $quest->setRequirements([]);
        $quest->setRewards([]);

        $ref = new \ReflectionProperty(Quest::class, 'id');
        $ref->setValue($quest, 99);

        $questRepo = $this->createMock(EntityRepository::class);
        $questRepo->method('findBy')->willReturn([$quest]);

        $playerQuestRepo = $this->createMock(EntityRepository::class);
        $playerQuestRepo->method('findBy')->willReturn([]);

        $completedQuestRepo = $this->createMock(EntityRepository::class);
        $completedQuestRepo->method('findBy')->willReturn([]);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Quest::class, $questRepo],
                [PlayerQuest::class, $playerQuestRepo],
                [PlayerQuestCompleted::class, $completedQuestRepo],
            ]);

        $this->entityManager->expects($this->never())->method('persist');

        $event = new MobDeadEvent($mob);
        $this->listener->onMobDead($event);
    }

    public function testSummonedMobsAreIgnored(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(true);

        // Should not even call playerHelper
        $this->playerHelper->expects($this->never())->method('getPlayer');

        $event = new MobDeadEvent($mob);
        $this->listener->onMobDead($event);
    }
}
