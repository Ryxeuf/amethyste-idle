<?php

namespace App\Tests\Integration\Quest;

use App\Entity\Game\Quest;
use App\Repository\QuestRepository;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Verifie la coherence de l'arc d'introduction (NAR-03) tel que charge par les fixtures :
 * la chaine `storyArc = 'intro'` est ordonnee, contigue et chainee par `prerequisiteQuests`.
 */
final class IntroArcFixturesTest extends AbstractIntegrationTestCase
{
    private function introQuests(): array
    {
        /** @var QuestRepository $repository */
        $repository = $this->em->getRepository(Quest::class);

        return $repository->findByStoryArc('intro');
    }

    public function testIntroArcHasSevenOrderedSteps(): void
    {
        $quests = $this->introQuests();

        self::assertCount(7, $quests, 'L\'arc intro doit compter 7 etapes.');

        // findByStoryArc trie par arcOrder ASC : la sequence doit etre 1..7, contigue.
        $orders = array_map(static fn (Quest $q): ?int => $q->getArcOrder(), $quests);
        self::assertSame([1, 2, 3, 4, 5, 6, 7], $orders, 'Les positions d\'arc doivent etre contigues de 1 a 7.');

        foreach ($quests as $quest) {
            self::assertSame('intro', $quest->getStoryArc());
        }
    }

    public function testIntroArcIsAContiguousPrerequisiteChain(): void
    {
        $quests = $this->introQuests();

        // La premiere etape n'a aucun prerequis.
        $first = $quests[0];
        self::assertTrue(
            empty($first->getPrerequisiteQuests()),
            'La premiere etape de l\'arc intro ne doit avoir aucun prerequis.'
        );

        // Chaque etape suivante a pour unique prerequis l'etape precedente.
        for ($i = 1, $count = \count($quests); $i < $count; ++$i) {
            $previousId = $quests[$i - 1]->getId();
            $prerequisites = $quests[$i]->getPrerequisiteQuests() ?? [];

            self::assertContains(
                $previousId,
                $prerequisites,
                sprintf(
                    'L\'etape %d (%s) doit dependre de l\'etape precedente.',
                    $quests[$i]->getArcOrder(),
                    $quests[$i]->getName()
                )
            );
        }
    }

    public function testIntroArcTeachesCraftAndGuildSteps(): void
    {
        $quests = $this->introQuests();

        // Etape 6 : craft T1 (premiere potion) — requirement de type `craft`.
        $craftStep = $quests[5];
        self::assertSame(6, $craftStep->getArcOrder());
        self::assertArrayHasKey('craft', $craftStep->getRequirements(), 'L\'etape 6 doit enseigner le craft.');

        // Etape 7 : guilde — requirement `talk_to` avec un pnj_id resolu (> 0) apres back-patch.
        $guildStep = $quests[6];
        self::assertSame(7, $guildStep->getArcOrder());
        $requirements = $guildStep->getRequirements();
        self::assertArrayHasKey('talk_to', $requirements, 'L\'etape 7 doit orienter vers une guilde via talk_to.');
        self::assertGreaterThan(
            0,
            $requirements['talk_to'][0]['pnj_id'] ?? 0,
            'Le pnj_id de l\'etape guilde doit etre resolu (back-patch Claire la Sage).'
        );
    }
}
