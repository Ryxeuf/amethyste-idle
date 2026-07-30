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

    public function testIntroArcHasTenOrderedSteps(): void
    {
        $quests = $this->introQuests();

        // ONB-12b : dix, et non plus sept. Les trois etapes ajoutees sont
        // l'accord, le second mannequin et le choix du metier — c'est-a-dire
        // tout ce qui touche a la materia, dont l'arc ne parlait pas.
        self::assertCount(10, $quests, 'L\'arc intro doit compter 10 etapes.');

        // findByStoryArc trie par arcOrder ASC : la sequence doit etre 1..10, contigue.
        $orders = array_map(static fn (Quest $q): ?int => $q->getArcOrder(), $quests);
        self::assertSame(range(1, 10), $orders, 'Les positions d\'arc doivent etre contigues de 1 a 10.');

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

    /**
     * Les trois tours de la boucle se terminent chacun par un geste.
     *
     * C'est la forme meme de l'acte I (GAME_ONBOARDING § 5.2) : porter l'arme,
     * sertir la materia, recolter. Un tour qui s'arreterait au parchemin
     * n'aurait rien enseigne — c'est ce que faisait la chaine heritee, qui
     * remettait une epee et passait a autre chose.
     */
    public function testEachLoopTurnEndsOnAGesture(): void
    {
        $quests = $this->introQuests();

        foreach ([2 => 'equip_item', 4 => 'socket_materia', 7 => 'gather'] as $step => $gesture) {
            $requirements = $quests[$step - 1]->getRequirements();

            self::assertArrayHasKey('gesture', $requirements, sprintf('L\'etape %d doit se conclure par un geste.', $step));
            self::assertSame(
                $gesture,
                $requirements['gesture'][0]['gesture'] ?? null,
                sprintf('L\'etape %d doit constater « %s ».', $step, $gesture),
            );
        }
    }

    /**
     * Les deux etapes qui coutent du temps reel sont les deux dernieres.
     *
     * Le joueur sort du tunnel avec une journee entiere d'energie. La chaine
     * heritee ouvrait sur le voyage — la seule attente du jeu, placee avant
     * tout le reste.
     */
    public function testTheOnlyWaitsAreTheLastTwoSteps(): void
    {
        $quests = $this->introQuests();

        self::assertSame('travel', $quests[8]->getRequirements()['gesture'][0]['gesture'] ?? null);
        self::assertSame('start_expedition', $quests[9]->getRequirements()['gesture'][0]['gesture'] ?? null);
    }

    /**
     * Les deux etapes qui envoient parler a quelqu'un designent un vrai PNJ.
     *
     * `pnj_id` vaut 0 dans les fixtures — elles sont ecrites avant que les PNJ
     * existent — et `QuestChainFixtures` le recale. Un back-patch oublie ne
     * casse rien au chargement : la quete devient simplement interminable.
     */
    public function testEveryTalkStepPointsAtARealPnj(): void
    {
        $quests = $this->introQuests();

        foreach ([1, 6] as $step) {
            $requirements = $quests[$step - 1]->getRequirements();

            self::assertArrayHasKey('talk_to', $requirements, sprintf('L\'etape %d doit envoyer parler a quelqu\'un.', $step));
            self::assertGreaterThan(
                0,
                $requirements['talk_to'][0]['pnj_id'] ?? 0,
                sprintf('Le `pnj_id` de l\'etape %d n\'a pas ete recale : la quete serait interminable.', $step),
            );
        }
    }

    /**
     * Les deux etapes de choix proposent bien plusieurs options.
     *
     * A chaque tour de boucle, le choix doit etre reel — c'est le principe de
     * l'acte I. Une liste vide ne bloquerait pas la quete : elle la remettrait
     * sans rien demander, et le joueur ne saurait jamais qu'il avait le choix.
     */
    public function testBothChoiceStepsReallyOfferAChoice(): void
    {
        $quests = $this->introQuests();

        self::assertCount(6, $quests[0]->getChoiceOutcome() ?? [], 'L\'etape 1 doit proposer les six armes.');
        self::assertCount(5, $quests[5]->getChoiceOutcome() ?? [], 'L\'etape 6 doit proposer les cinq metiers de recolte.');
    }
}
