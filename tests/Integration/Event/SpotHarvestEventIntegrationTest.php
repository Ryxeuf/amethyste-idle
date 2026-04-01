<?php

namespace App\Tests\Integration\Event;

use App\Entity\App\ObjectLayer;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Event\Map\SpotHarvestEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Progression\DomainExperienceEvolver;
use App\GameEngine\Quest\PlayerQuestUpdater;
use App\GameEngine\Quest\QuestCollectTrackingListener;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: SpotHarvestEvent triggers DomainExperienceEvolver
 * and QuestCollectTrackingListener simultaneously.
 */
class SpotHarvestEventIntegrationTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerDomainHelper&MockObject $playerDomainHelper;
    private PlayerQuestUpdater&MockObject $playerQuestUpdater;
    private GameEventBonusProvider&MockObject $gameEventBonusProvider;
    private PlayerHelper&MockObject $playerHelper;

    private DomainExperienceEvolver $domainExperienceEvolver;
    private QuestCollectTrackingListener $questCollectTracker;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->playerDomainHelper = $this->createMock(PlayerDomainHelper::class);
        $this->playerQuestUpdater = $this->createMock(PlayerQuestUpdater::class);
        $this->gameEventBonusProvider = $this->createMock(GameEventBonusProvider::class);
        $this->playerHelper = $this->createMock(PlayerHelper::class);

        $this->gameEventBonusProvider->method('getXpMultiplier')->willReturn(1.0);

        $this->domainExperienceEvolver = new DomainExperienceEvolver(
            $this->playerDomainHelper,
            $this->entityManager,
            $this->gameEventBonusProvider,
            $this->playerHelper,
        );
        $this->questCollectTracker = new QuestCollectTrackingListener(
            $this->playerQuestUpdater,
        );
    }

    public function testBothListenersSubscribeToSpotHarvestEvent(): void
    {
        $this->assertArrayHasKey(SpotHarvestEvent::NAME, DomainExperienceEvolver::getSubscribedEvents());
        $this->assertArrayHasKey(SpotHarvestEvent::NAME, QuestCollectTrackingListener::getSubscribedEvents());
    }

    public function testSpotHarvestTriggersXpAndQuestTracking(): void
    {
        $objectLayer = $this->createObjectLayer('iron_ore_spot');
        $harvestedItem = $this->createHarvestedItem('iron_ore');
        $event = new SpotHarvestEvent($objectLayer, [$harvestedItem]);

        // Domain XP: matching domain found
        $domain = $this->createMock(Domain::class);
        $domainExperience = $this->createMock(\App\Entity\App\DomainExperience::class);
        $domainExperience->method('getTotalExperience')->willReturn(10);
        $domainExperience->expects($this->once())
            ->method('setTotalExperience')
            ->with(11);

        $this->playerDomainHelper->method('getDomainBySkillAction')
            ->with('harvest', ['spot' => 'iron_ore_spot'])
            ->willReturn($domain);
        $this->playerDomainHelper->method('getDomainExperience')
            ->with($domain)
            ->willReturn($domainExperience);

        // Quest tracking: item collected
        $this->playerQuestUpdater->expects($this->once())
            ->method('updateItemCollected')
            ->with('iron_ore');

        // Execute both listeners
        $this->domainExperienceEvolver->experienceFromHarvesting($event);
        $this->questCollectTracker->onSpotHarvest($event);
    }

    public function testSpotHarvestWithNoDomainSkipsXpButTracksQuest(): void
    {
        $objectLayer = $this->createObjectLayer('unknown_spot');
        $harvestedItem = $this->createHarvestedItem('mysterious_herb');
        $event = new SpotHarvestEvent($objectLayer, [$harvestedItem]);

        // No matching domain
        $this->playerDomainHelper->method('getDomainBySkillAction')->willReturn(null);

        // XP should not be granted (no persist/flush)
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        // Quest tracking still works
        $this->playerQuestUpdater->expects($this->once())
            ->method('updateItemCollected')
            ->with('mysterious_herb');

        $this->domainExperienceEvolver->experienceFromHarvesting($event);
        $this->questCollectTracker->onSpotHarvest($event);
    }

    public function testSpotHarvestWithEmptyItemsDoesNotTrackQuest(): void
    {
        $objectLayer = $this->createObjectLayer('empty_spot');
        $event = new SpotHarvestEvent($objectLayer, []);

        // Quest tracking: no items → no calls
        $this->playerQuestUpdater->expects($this->never())
            ->method('updateItemCollected');

        $this->questCollectTracker->onSpotHarvest($event);
    }

    public function testSpotHarvestWithMultipleItemsTracksEach(): void
    {
        $objectLayer = $this->createObjectLayer('herb_spot');
        $item1 = $this->createHarvestedItem('mint');
        $item2 = $this->createHarvestedItem('sage');
        $event = new SpotHarvestEvent($objectLayer, [$item1, $item2]);

        $this->playerQuestUpdater->expects($this->exactly(2))
            ->method('updateItemCollected')
            ->willReturnCallback(function (string $slug): void {
                $this->assertContains($slug, ['mint', 'sage']);
            });

        $this->questCollectTracker->onSpotHarvest($event);
    }

    private function createObjectLayer(string $slug): ObjectLayer&MockObject
    {
        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getSlug')->willReturn($slug);

        return $objectLayer;
    }

    private function createHarvestedItem(string $slug): PlayerItem&MockObject
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('getSlug')->willReturn($slug);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($genericItem);

        return $playerItem;
    }
}
