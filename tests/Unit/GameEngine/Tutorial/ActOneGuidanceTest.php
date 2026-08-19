<?php

namespace App\Tests\Unit\GameEngine\Tutorial;

use App\GameEngine\Tutorial\TutorialGuide;
use PHPUnit\Framework\TestCase;

/**
 * Aucune etape de l'acte I ne doit rester sans chemin.
 *
 * **La forme du defaut repare.** Le bandeau savait dire *quoi faire* et jamais
 * *ou aller* — il envoyait « chez la maitresse d'armes » sans nommer sa zone,
 * sous un lien qui rechargeait la page courante. Le guide corrige les dix
 * etapes, mais rien n'empecherait qu'une onzieme soit ecrite demain avec un
 * geste que la table d'ecrans ne connait pas : elle retomberait alors sur la
 * route large de son etape, c'est-a-dire sur le defaut d'origine, sans bruit.
 *
 * Ce test lit la chaine reelle et exige que chaque exigence ait un chemin
 * **propre**. Il lit la source des fixtures plutot que la base, comme
 * `ActOneChainTest` : la chaine est une donnee d'auteur, elle se verifie la ou
 * elle s'ecrit.
 */
class ActOneGuidanceTest extends TestCase
{
    public function testEveryStepOfTheArcHasItsOwnDestination(): void
    {
        $orphans = [];

        foreach ($this->arcRequirements() as $reference => $requirement) {
            if ($this->isCovered($requirement)) {
                continue;
            }

            $orphans[] = sprintf('%s (%s)', $reference, $this->summarize($requirement));
        }

        self::assertSame([], $orphans, sprintf(
            "Ces etapes de l'acte I n'ont pas de destination propre et retomberaient sur la route large de leur etape :\n- %s",
            implode("\n- ", $orphans),
        ));
    }

    /**
     * Tout geste connu de la table d'ecrans doit viser une route que le guide
     * declare : les deux listes sont derivees de la meme constante, et ce test
     * refuse qu'on en desynchronise une en ajoutant une branche a la main.
     */
    public function testTheDeclaredRoutesCoverTheScreenTable(): void
    {
        $declared = TutorialGuide::emittableRoutes();

        foreach (['app_game_quests', 'app_game_pnj_talk', 'app_game_world_map', 'app_game_zone', 'app_game_craft'] as $route) {
            self::assertContains($route, $declared, sprintf('« %s » est emise par le guide sans etre declaree.', $route));
        }
    }

    /**
     * De quoi lire l'echec sans deverser le bloc entier dans le message.
     */
    private function summarize(?string $requirement): string
    {
        if (null === $requirement) {
            return 'aucune exigence';
        }

        return preg_match("/'gesture' => '([a-z_]+)'/", $requirement, $matches)
            ? 'geste ' . $matches[1]
            : 'exigence non reconnue';
    }

    /**
     * Une exigence est couverte quand le guide sait en tirer un ecran.
     */
    private function isCovered(?string $requirement): bool
    {
        if (null === $requirement) {
            return false;
        }

        // « Parler a » porte toujours son PNJ, donc sa zone : couvert par
        // construction.
        if (str_contains($requirement, "'talk_to'")) {
            return true;
        }

        if (str_contains($requirement, 'training_dummy')) {
            return true;
        }

        if (!preg_match("/'gesture' => '([a-z_]+)'/", $requirement, $matches)) {
            return false;
        }

        return \in_array($matches[1], $this->knownGestures(), true);
    }

    /**
     * @return list<string>
     */
    private function knownGestures(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Tutorial/TutorialGuide.php');

        $table = substr($source, (int) strpos($source, 'GESTURE_SCREENS = ['));
        $table = substr($table, 0, (int) strpos($table, '];'));

        preg_match_all("/'([a-z_]+)' => \['app_game/", $table, $matches);

        return $matches[1];
    }

    /**
     * Le bloc `requirements` de chaque etape de l'arc, dans l'ordre du fichier.
     *
     * @return array<string, ?string>
     */
    private function arcRequirements(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/QuestFixtures.php');

        $found = [];
        foreach ([
            'quest_acte1_reveil',
            'quest_acte1_premiers_pas',
            'quest_acte1_bapteme_du_feu',
            'quest_acte1_accord',
            'quest_acte1_second_mannequin',
            'quest_acte1_metier',
            'quest_acte1_recolte',
            'quest_acte1_premiere_potion',
            'quest_acte1_cristal',
            'quest_acte1_guilde',
        ] as $reference) {
            $start = strpos($source, sprintf("            '%s' => [", $reference));
            self::assertNotFalse($start, sprintf('L\'etape « %s » a disparu de l\'arc.', $reference));

            $end = strpos($source, "\n            ],\n", $start);
            self::assertNotFalse($end, sprintf('Le bloc de « %s » n\'est pas referme comme attendu.', $reference));

            $block = substr($source, $start, $end - $start);

            $requirementsAt = strpos($block, "'requirements' => [");
            $found[$reference] = false === $requirementsAt ? null : substr($block, $requirementsAt);
        }

        return $found;
    }
}
