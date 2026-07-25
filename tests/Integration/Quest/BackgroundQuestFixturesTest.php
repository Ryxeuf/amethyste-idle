<?php

namespace App\Tests\Integration\Quest;

use App\Entity\Game\Quest;
use App\Repository\QuestRepository;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Contenu de fond (NAR-13) : la chaine de zone « Foret des Murmures » est
 * ordonnee, chainee, et gatee par la decouverte puis la renommee — donc
 * jamais bloquante pour la progression systeme.
 */
final class BackgroundQuestFixturesTest extends AbstractIntegrationTestCase
{
    /**
     * @return array<int, Quest> quetes de l'arc de zone, indexees par arcOrder
     */
    private function chainByOrder(): array
    {
        /** @var QuestRepository $repository */
        $repository = $this->em->getRepository(Quest::class);

        $byOrder = [];
        foreach ($repository->findByStoryArc('zone_foret-des-murmures') as $quest) {
            $byOrder[$quest->getArcOrder()] = $quest;
        }

        return $byOrder;
    }

    public function testBackgroundChainIsOrderedAndLinked(): void
    {
        $quests = $this->chainByOrder();

        self::assertCount(3, $quests);
        self::assertSame([1, 2, 3], array_keys($quests));

        // Chaine de prerequis : meute ← rumeurs, cœur ← meute.
        self::assertContains($quests[1]->getId(), $quests[2]->getPrerequisiteQuests() ?? []);
        self::assertContains($quests[2]->getId(), $quests[3]->getPrerequisiteQuests() ?? []);
    }

    public function testChainIsGatedByDiscoveryThenRenown(): void
    {
        $quests = $this->chainByOrder();

        // Etape 1 : gate par la decouverte (quete cachee).
        self::assertTrue($quests[1]->isHidden(), 'La premiere etape de fond doit etre une decouverte.');

        // Etapes 2 et 3 : gate par la renommee (contenu reserve, croissant).
        self::assertTrue($quests[2]->hasRenownRequirement());
        self::assertTrue($quests[3]->hasRenownRequirement());
        self::assertGreaterThan(
            $quests[2]->getMinRenownScore(),
            $quests[3]->getMinRenownScore(),
            'La renommee requise doit croitre le long de la chaine.'
        );
    }

    public function testBackgroundChainIsNeverBlockingForSystemProgression(): void
    {
        $quests = $this->chainByOrder();
        $backgroundIds = array_map(static fn (Quest $q): int => $q->getId(), $quests);

        // Aucune quete HORS de l'arc de fond ne depend d'une quete de fond :
        // le contenu de fond n'est jamais un prerequis de la progression systeme.
        /** @var QuestRepository $repository */
        $repository = $this->em->getRepository(Quest::class);
        $allQuests = $repository->findAll();

        foreach ($allQuests as $quest) {
            if ($quest->getStoryArc() === 'zone_foret-des-murmures') {
                continue;
            }
            foreach ($quest->getPrerequisiteQuests() ?? [] as $prereqId) {
                self::assertNotContains(
                    $prereqId,
                    $backgroundIds,
                    sprintf('La quete « %s » ne doit pas dependre d\'une quete de fond.', $quest->getName())
                );
            }
        }
    }
}
