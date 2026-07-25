<?php

namespace App\Tests\Integration\Quest;

use App\Entity\Game\Quest;
use App\Repository\QuestRepository;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Quetes d'evenement de la Saison 1 (NAR-09) : rattachees a un beat via
 * `Quest.gameEvent`, elles ne sont actives que dans la fenetre de leur beat.
 */
final class SeasonQuestFixturesTest extends AbstractIntegrationTestCase
{
    /**
     * @return array<int, Quest> quetes de l'arc de saison, indexees par arcOrder
     */
    private function seasonQuestsByOrder(): array
    {
        /** @var QuestRepository $repository */
        $repository = $this->em->getRepository(Quest::class);

        $byOrder = [];
        foreach ($repository->findByStoryArc('season_saison-1') as $quest) {
            $byOrder[$quest->getArcOrder()] = $quest;
        }

        return $byOrder;
    }

    public function testSeasonArcHasFourOrderedEventQuests(): void
    {
        $quests = $this->seasonQuestsByOrder();

        self::assertCount(4, $quests);
        self::assertSame([1, 2, 3, 4], array_keys($quests));

        // Chaque quete de saison est rattachee a un GameEvent (beat).
        foreach ($quests as $quest) {
            self::assertTrue($quest->isEventQuest(), sprintf('La quete « %s » doit etre rattachee a un beat.', $quest->getName()));
        }
    }

    public function testMonteeQuestIsActiveAndClimaxQuestIsGatedByWindow(): void
    {
        $quests = $this->seasonQuestsByOrder();

        // La montee (beat en cours) est active ; le climax (beat a venir) est
        // inactif tant que sa fenetre n'est pas atteinte.
        self::assertTrue($quests[2]->isEventActive(), 'La quete de montee doit etre active (beat en cours).');
        self::assertFalse($quests[3]->isEventActive(), 'La quete de climax ne doit pas etre active avant sa fenetre.');
    }
}
