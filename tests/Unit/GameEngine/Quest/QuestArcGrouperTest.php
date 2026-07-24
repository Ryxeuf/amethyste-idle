<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Quest;

use App\Entity\App\PlayerQuest;
use App\Entity\Game\Quest;
use App\GameEngine\Quest\QuestArcGrouper;
use App\Repository\QuestRepository;
use PHPUnit\Framework\TestCase;

final class QuestArcGrouperTest extends TestCase
{
    public function testGroupsQuestsByArcSortedByArcOrder(): void
    {
        $q1 = $this->quest(1, 'intro', 1);
        $q2 = $this->quest(2, 'intro', 2);
        $q3 = $this->quest(3, 'intro', 3);

        $grouper = $this->grouperWithArcs(['intro' => [$q1, $q2, $q3]]);

        // Items volontairement desordonnes en entree.
        $result = $grouper->group([$q3, $q1, $q2], self::identity());

        $this->assertCount(1, $result['arcs']);
        $this->assertSame([], $result['isolated']);

        $arc = $result['arcs'][0];
        $this->assertSame('intro', $arc['slug']);
        $this->assertSame([1, 2, 3], array_map(
            static fn (Quest $q): int => $q->getId(),
            $arc['items'],
        ));
    }

    public function testIsolatedQuestsWithoutArcGoToIsolatedBucket(): void
    {
        $arcQuest = $this->quest(1, 'intro', 1);
        $loose1 = $this->quest(10, null, null);
        $loose2 = $this->quest(11, null, null);

        $grouper = $this->grouperWithArcs(['intro' => [$arcQuest]]);

        $result = $grouper->group([$loose1, $arcQuest, $loose2], self::identity());

        $this->assertCount(1, $result['arcs']);
        $this->assertSame('intro', $result['arcs'][0]['slug']);
        $this->assertSame([10, 11], array_map(
            static fn (Quest $q): int => $q->getId(),
            $result['isolated'],
        ));
    }

    public function testArcProgressionIsPartialWhenSomeQuestsCompleted(): void
    {
        $q1 = $this->quest(1, 'intro', 1);
        $q2 = $this->quest(2, 'intro', 2);
        $q3 = $this->quest(3, 'intro', 3);

        $grouper = $this->grouperWithArcs(['intro' => [$q1, $q2, $q3]]);

        // Le joueur n'a en cours que q3, mais a deja termine q1 et q2.
        $result = $grouper->group([$q3], self::identity(), [1, 2]);

        $arc = $result['arcs'][0];
        $this->assertSame(2, $arc['completed']);
        $this->assertSame(3, $arc['total']);
    }

    public function testArcProgressionIsCompleteWhenAllQuestsCompleted(): void
    {
        $q1 = $this->quest(1, 'intro', 1);
        $q2 = $this->quest(2, 'intro', 2);

        $grouper = $this->grouperWithArcs(['intro' => [$q1, $q2]]);

        $result = $grouper->group([$q1], self::identity(), [1, 2]);

        $arc = $result['arcs'][0];
        $this->assertSame($arc['total'], $arc['completed']);
        $this->assertSame(2, $arc['completed']);
    }

    public function testProgressionIgnoresCompletedIdsOutsideTheArc(): void
    {
        $q1 = $this->quest(1, 'intro', 1);

        $grouper = $this->grouperWithArcs(['intro' => [$q1]]);

        // 99 est une quete completee d'un autre arc : ne doit pas compter ici.
        $result = $grouper->group([$q1], self::identity(), [99]);

        $this->assertSame(0, $result['arcs'][0]['completed']);
        $this->assertSame(1, $result['arcs'][0]['total']);
    }

    public function testArcsAreOrderedAlphabeticallyBySlug(): void
    {
        $intro = $this->quest(1, 'intro', 1);
        $season = $this->quest(2, 'season_summer', 1);
        $ancients = $this->quest(3, 'ancients', 1);

        $grouper = $this->grouperWithArcs([
            'intro' => [$intro],
            'season_summer' => [$season],
            'ancients' => [$ancients],
        ]);

        $result = $grouper->group([$intro, $season, $ancients], self::identity());

        $this->assertSame(['ancients', 'intro', 'season_summer'], array_map(
            static fn (array $arc): string => $arc['slug'],
            $result['arcs'],
        ));
    }

    public function testExtractsQuestViaCallableFromWrapperItems(): void
    {
        $quest = $this->quest(1, 'intro', 1);
        $playerQuest = $this->createMock(PlayerQuest::class);
        $playerQuest->method('getQuest')->willReturn($quest);

        $grouper = $this->grouperWithArcs(['intro' => [$quest]]);

        $result = $grouper->group(
            [$playerQuest],
            static fn (PlayerQuest $pq): Quest => $pq->getQuest(),
            [1],
        );

        $this->assertSame('intro', $result['arcs'][0]['slug']);
        $this->assertSame([$playerQuest], $result['arcs'][0]['items']);
        $this->assertSame(1, $result['arcs'][0]['completed']);
    }

    public function testEmptyInputProducesEmptyGroups(): void
    {
        $grouper = $this->grouperWithArcs([]);

        $result = $grouper->group([], self::identity());

        $this->assertSame([], $result['arcs']);
        $this->assertSame([], $result['isolated']);
    }

    /**
     * @return callable(Quest): Quest
     */
    private static function identity(): callable
    {
        return static fn (Quest $quest): Quest => $quest;
    }

    private function quest(int $id, ?string $arc, ?int $order): Quest
    {
        $quest = new Quest();
        $quest->setId($id);
        $quest->setName('quest-' . $id);
        $quest->setStoryArc($arc);
        $quest->setArcOrder($order);

        return $quest;
    }

    /**
     * @param array<string, Quest[]> $arcs slug d'arc -> quetes canoniques de l'arc
     */
    private function grouperWithArcs(array $arcs): QuestArcGrouper
    {
        $repository = $this->createMock(QuestRepository::class);
        $repository->method('findByStoryArc')->willReturnCallback(
            static fn (string $slug): array => $arcs[$slug] ?? [],
        );

        return new QuestArcGrouper($repository);
    }
}
