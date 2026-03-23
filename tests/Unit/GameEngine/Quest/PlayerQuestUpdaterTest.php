<?php

namespace App\Tests\Unit\GameEngine\Quest;

use App\Entity\App\Mob;
use App\Entity\App\PlayerQuest;
use App\Entity\Game\Monster;
use App\GameEngine\Quest\PlayerQuestHelper;
use App\GameEngine\Quest\PlayerQuestUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayerQuestUpdaterTest extends TestCase
{
    private PlayerQuestHelper&MockObject $playerQuestHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerQuestUpdater $updater;

    protected function setUp(): void
    {
        $this->playerQuestHelper = $this->createMock(PlayerQuestHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->updater = new PlayerQuestUpdater(
            $this->playerQuestHelper,
            $this->entityManager,
        );
    }

    public function testUpdateMobKilledIncrementsMatchingMonster(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'monsters' => [
                ['slug' => 'goblin', 'count' => 2, 'necessary' => 5],
                ['slug' => 'slime', 'count' => 0, 'necessary' => 3],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('goblin');

        $mob = $this->createMock(Mob::class);
        $mob->method('getMonster')->willReturn($monster);

        $this->entityManager->expects($this->once())->method('flush');

        $this->updater->updateMobKilled($mob);

        $tracking = $quest->getTracking();
        $this->assertEquals(3, $tracking['monsters'][0]['count']);
        $this->assertEquals(0, $tracking['monsters'][1]['count']);
    }

    public function testUpdateMobKilledSkipsCompletedQuests(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'monsters' => [
                ['slug' => 'goblin', 'count' => 5, 'necessary' => 5],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(true);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('goblin');

        $mob = $this->createMock(Mob::class);
        $mob->method('getMonster')->willReturn($monster);

        $this->updater->updateMobKilled($mob);

        // Count should remain unchanged
        $tracking = $quest->getTracking();
        $this->assertEquals(5, $tracking['monsters'][0]['count']);
    }

    public function testUpdateItemCollectedIncrementsCount(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'collect' => [
                ['slug' => 'iron-ore', 'count' => 3, 'necessary' => 10],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->once())->method('flush');

        $this->updater->updateItemCollected('iron-ore', 2);

        $tracking = $quest->getTracking();
        $this->assertEquals(5, $tracking['collect'][0]['count']);
    }

    public function testUpdateItemCollectedCapsAtNecessary(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'collect' => [
                ['slug' => 'iron-ore', 'count' => 9, 'necessary' => 10],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->updater->updateItemCollected('iron-ore', 5);

        $tracking = $quest->getTracking();
        $this->assertEquals(10, $tracking['collect'][0]['count']);
    }

    public function testUpdateItemCraftedIncrementsCount(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'craft' => [
                ['slug' => 'iron-sword', 'count' => 0, 'necessary' => 2],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->once())->method('flush');

        $this->updater->updateItemCrafted('iron-sword', 1);

        $tracking = $quest->getTracking();
        $this->assertEquals(1, $tracking['craft'][0]['count']);
    }

    public function testUpdateItemCollectedNoMatchNoFlush(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'collect' => [
                ['slug' => 'iron-ore', 'count' => 0, 'necessary' => 5],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->never())->method('flush');

        $this->updater->updateItemCollected('gold-ore', 1);

        // iron-ore count should remain 0
        $tracking = $quest->getTracking();
        $this->assertEquals(0, $tracking['collect'][0]['count']);
    }

    public function testUpdateMobKilledMultipleQuestsTrackSameMob(): void
    {
        $quest1 = new PlayerQuest();
        $quest1->setTracking([
            'monsters' => [
                ['slug' => 'goblin', 'count' => 1, 'necessary' => 5],
            ],
        ]);

        $quest2 = new PlayerQuest();
        $quest2->setTracking([
            'monsters' => [
                ['slug' => 'goblin', 'count' => 0, 'necessary' => 3],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest1, $quest2]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn('goblin');

        $mob = $this->createMock(Mob::class);
        $mob->method('getMonster')->willReturn($monster);

        $this->updater->updateMobKilled($mob);

        $this->assertEquals(2, $quest1->getTracking()['monsters'][0]['count']);
        $this->assertEquals(1, $quest2->getTracking()['monsters'][0]['count']);
    }

    public function testUpdateDeliveredIncrementsMatchingEntry(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'deliver' => [
                ['item_slug' => 'mushroom', 'pnj_id' => 5, 'count' => 1, 'necessary' => 3, 'name' => 'Champignon'],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->once())->method('flush');

        $this->updater->updateDelivered('mushroom', 5, 2);

        $tracking = $quest->getTracking();
        $this->assertEquals(3, $tracking['deliver'][0]['count']);
    }

    public function testUpdateDeliveredWrongPnjNoChange(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'deliver' => [
                ['item_slug' => 'mushroom', 'pnj_id' => 5, 'count' => 0, 'necessary' => 3, 'name' => 'Champignon'],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->never())->method('flush');

        $this->updater->updateDelivered('mushroom', 99, 1);

        $this->assertEquals(0, $quest->getTracking()['deliver'][0]['count']);
    }

    public function testUpdateExploredMatchesCoordinates(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'explore' => [
                ['map_id' => 1, 'coordinates' => '15.20', 'count' => 0, 'necessary' => 1, 'name' => 'Clairière'],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->once())->method('flush');

        $this->updater->updateExplored(1, '15.20');

        $this->assertEquals(1, $quest->getTracking()['explore'][0]['count']);
    }

    public function testUpdateExploredWrongCoordinatesNoChange(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'explore' => [
                ['map_id' => 1, 'coordinates' => '15.20', 'count' => 0, 'necessary' => 1, 'name' => 'Clairière'],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->never())->method('flush');

        $this->updater->updateExplored(1, '10.10');

        $this->assertEquals(0, $quest->getTracking()['explore'][0]['count']);
    }

    public function testUpdateExploredNullCoordinatesMatchesAnyOnMap(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'explore' => [
                ['map_id' => 1, 'coordinates' => null, 'count' => 0, 'necessary' => 1, 'name' => 'Carte entière'],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->once())->method('flush');

        $this->updater->updateExplored(1, '5.5');

        $this->assertEquals(1, $quest->getTracking()['explore'][0]['count']);
    }

    public function testUpdateExploredWrongMapNoChange(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'explore' => [
                ['map_id' => 1, 'coordinates' => null, 'count' => 0, 'necessary' => 1, 'name' => 'Carte 1'],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->never())->method('flush');

        $this->updater->updateExplored(2, '5.5');

        $this->assertEquals(0, $quest->getTracking()['explore'][0]['count']);
    }

    public function testUpdateItemCollectedSkipsQuestWithoutCollectType(): void
    {
        $quest = new PlayerQuest();
        $quest->setTracking([
            'monsters' => [
                ['slug' => 'goblin', 'count' => 0, 'necessary' => 5],
            ],
        ]);

        $this->playerQuestHelper->method('getCurrentQuests')->willReturn([$quest]);
        $this->playerQuestHelper->method('isPlayerQuestCompleted')->willReturn(false);

        $this->entityManager->expects($this->never())->method('flush');

        $this->updater->updateItemCollected('iron-ore', 1);

        // Tracking should be unchanged
        $this->assertArrayNotHasKey('collect', $quest->getTracking());
    }
}
