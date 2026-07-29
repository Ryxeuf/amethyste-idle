<?php

declare(strict_types=1);

namespace App\Tests\Integration\Quest;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * ONB-15 — le contrat contre la recidive.
 *
 * Depuis le pivot PBBG, une position est une **zone**. `updateExplored()` ne se
 * declenche qu'a l'arrivee dans une zone et resout par `zone_slug` (forme cible)
 * ou par `map_id` (forme heritee, via `Zone::sourceMap`). Une quete ciblee par
 * coordonnees ne tombe donc jamais — et pire, `map_id => 1` designe la « Carte
 * de test », que `MapFixtures` cree en premier et qu'aucune zone ne prend pour
 * origine : ces objectifs visaient le vide.
 *
 * Trois des sept etapes de l'arc `intro` etaient dans ce cas. L'acte I etait
 * bloque des sa premiere etape, sans que rien ne s'en plaigne.
 *
 * Ce test empeche la faute de revenir, en la mesurant la ou elle compte : sur
 * les donnees reellement chargees, pas sur le texte des fixtures.
 */
final class QuestCoordinateContractTest extends AbstractIntegrationTestCase
{
    /**
     * Les seuls verbes qu'un objectif d'exploration peut employer aujourd'hui.
     */
    private const FORBIDDEN_EXPLORE_KEYS = ['map_id', 'coordinates'];

    /**
     * Le cœur du jalon : l'arc d'introduction ne depend d'aucune carte.
     */
    public function testNoIntroQuestDependsOnAMapOrCoordinates(): void
    {
        $offenders = [];

        foreach ($this->em->getRepository(Quest::class)->findBy(['storyArc' => 'intro']) as $quest) {
            foreach ($this->coordinateKeysUsedBy($quest) as $key) {
                $offenders[] = sprintf('%s (%s)', $quest->getName(), $key);
            }
        }

        self::assertSame([], $offenders, sprintf(
            "L'arc d'introduction ne doit dependre ni de `map_id` ni de coordonnees.\nFautifs : %s",
            implode(', ', $offenders),
        ));
    }

    /**
     * Chaque etape de l'arc doit se valider par un geste que le pivot connait.
     */
    public function testEveryIntroStepUsesAPivotObjective(): void
    {
        $pivotVerbs = ['talk_to', 'monsters', 'collect', 'craft', 'deliver', 'explore', 'harvest'];

        foreach ($this->em->getRepository(Quest::class)->findBy(['storyArc' => 'intro']) as $quest) {
            $verbs = array_keys($quest->getRequirements() ?? []);

            self::assertNotEmpty($verbs, sprintf('« %s » n\'a aucun objectif.', $quest->getName()));
            self::assertSame([], array_diff($verbs, $pivotVerbs), sprintf(
                '« %s » emploie un objectif que le pivot ne sait pas valider.',
                $quest->getName(),
            ));
        }
    }

    /**
     * Un `talk_to` dont le `pnj_id` vaut 0 n'a pas ete recale apres flush : il
     * ne peut designer personne, et la quete est aussi morte qu'avant.
     */
    public function testEveryIntroTalkToPointsAtSomeone(): void
    {
        foreach ($this->em->getRepository(Quest::class)->findBy(['storyArc' => 'intro']) as $quest) {
            foreach ($quest->getRequirements()['talk_to'] ?? [] as $entry) {
                self::assertGreaterThan(0, $entry['pnj_id'] ?? 0, sprintf(
                    '« %s » doit designer un PNJ existant (back-patch manquant).',
                    $quest->getName(),
                ));
            }
        }
    }

    /**
     * Un `pnj_id` valide ne suffit pas : depuis ZON-27, l'ecran de zone est le
     * seul endroit d'ou l'on atteint un PNJ, et il liste **par zone**. Un PNJ
     * sans zone n'apparait nulle part, et l'objectif reste injoignable meme
     * quand il designe quelqu'un de reel.
     */
    public function testEveryIntroTalkToTargetIsReachableInAZone(): void
    {
        $pnjRepository = $this->em->getRepository(Pnj::class);

        foreach ($this->em->getRepository(Quest::class)->findBy(['storyArc' => 'intro']) as $quest) {
            foreach ($quest->getRequirements()['talk_to'] ?? [] as $entry) {
                $pnj = $pnjRepository->find($entry['pnj_id'] ?? 0);

                self::assertInstanceOf(Pnj::class, $pnj, sprintf(
                    '« %s » designe un PNJ qui n\'existe pas.',
                    $quest->getName(),
                ));
                self::assertNotNull($pnj->getZone(), sprintf(
                    '« %s » envoie vers %s, qui n\'habite aucune zone : personne ne peut l\'atteindre.',
                    $quest->getName(),
                    $pnj->getName(),
                ));
            }
        }
    }

    /**
     * Le balayage des autres arcs : plus aucune quete, nulle part, ne se valide
     * par une coordonnee.
     */
    public function testNoQuestAnywhereIsTargetedByCoordinates(): void
    {
        $offenders = [];

        foreach ($this->em->getRepository(Quest::class)->findAll() as $quest) {
            foreach ($this->coordinateKeysUsedBy($quest) as $key) {
                $offenders[] = sprintf('%s (%s)', $quest->getName(), $key);
            }
        }

        self::assertSame([], $offenders, sprintf(
            "Aucune quete ne doit se valider par une coordonnee.\nFautifs : %s",
            implode(', ', $offenders),
        ));
    }

    /**
     * Cles fautives portees par une quete, objectifs et condition de
     * declenchement confondus.
     *
     * `defend` est hors perimetre : il porte bien un `map_id`, mais il se resout
     * par `Mob::map` et fonctionne — ce n'est pas la meme faute.
     *
     * @return list<string>
     */
    private function coordinateKeysUsedBy(Quest $quest): array
    {
        $found = [];

        foreach (['explore', 'escort'] as $verb) {
            foreach ($quest->getRequirements()[$verb] ?? [] as $entry) {
                foreach (self::FORBIDDEN_EXPLORE_KEYS as $key) {
                    foreach ([$key, 'destination_' . $key] as $candidate) {
                        if (isset($entry[$candidate])) {
                            $found[] = $verb . '.' . $candidate;
                        }
                    }
                }
            }
        }

        $trigger = $quest->getTriggerCondition() ?? [];
        if (($trigger['type'] ?? null) === 'explore') {
            foreach (self::FORBIDDEN_EXPLORE_KEYS as $key) {
                if (isset($trigger[$key])) {
                    $found[] = 'triggerCondition.' . $key;
                }
            }
        }

        return $found;
    }
}
