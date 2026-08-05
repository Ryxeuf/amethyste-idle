<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\GameEngine\Progression\SkillCostScale;
use PHPUnit\Framework\TestCase;

/**
 * L'echelle de cout, et ce qu'elle mesure sur les 24 arbres de combat (ARC-06a).
 *
 * GAME_ARCHETYPES § 6.2 : *l'echelle 0 / 10 / 25 / 50 / 100 est derivee d'un
 * calendrier, qui est la vraie decision de design*. Un cout dit un **palier**,
 * jamais un dosage — et les arbres livres portaient 23 valeurs distinctes, dont
 * cinq seulement sur l'echelle.
 *
 * Comme `EncounterAnchorTest` (ARC-05a), ce test lit la fixture a la source :
 * une regle d'equilibrage qui ne se verifie pas sur les vraies donnees ne se
 * verifie pas du tout.
 */
class SkillCostScaleTest extends TestCase
{
    /**
     * Les 24 domaines de combat, par leur fabrique dans `SkillFixtures`.
     *
     * Les arbres de metier n'y sont pas : leur gabarit est le chantier de
     * MET-04 et MET-05, et leur imposer le calendrier du combat serait leur
     * imposer une progression qui n'est pas la leur.
     *
     * @var list<string>
     */
    private const COMBAT_TREES = [
        'Pyromancy', 'Berserker', 'Artificer', 'Hydromancer', 'Healer', 'Tidecaller',
        'Soldier', 'Knight', 'Engineer', 'Geomancer', 'Defender', 'Guardian',
        'Necromancer', 'Druid', 'Hunter', 'Tamer', 'Stormcaller', 'Archer',
        'Wanderer', 'Paladin', 'Priest', 'Inquisitor', 'Assassin', 'Warlock',
    ];

    /**
     * Ce que chaque arbre coute aujourd'hui, dormant exclu (mesure ARC-06a).
     *
     * **C'est la liste d'attente d'ARC-07 et d'ARC-08**, sur le modele de celle
     * d'ARC-02b. L'echelle est posee ; ce qui manque aux 23 autres arbres, ce
     * ne sont pas des couts mais des **nœuds** — le gabarit du § 6.1 en ecrit
     * 18 et en laisse apprendre 15, quand les arbres livres en portent 13 a 18.
     * Un arbre a 240 points n'est pas mal chiffre : il lui manque cinq nœuds.
     *
     * Le test verifie qu'aucun arbre ne **s'eloigne** de 390. Reduire l'ecart
     * est libre — c'est exactement le travail d'ARC-07/08 ; l'aggraver demande
     * de le dire.
     *
     * @var array<string, int>
     */
    private const MEASURED_TREE_COST = [
        'Pyromancy' => 390, 'Berserker' => 275, 'Artificer' => 275, 'Hydromancer' => 350,
        'Healer' => 525, 'Tidecaller' => 240, 'Soldier' => 435, 'Knight' => 375,
        'Engineer' => 240, 'Geomancer' => 250, 'Defender' => 525, 'Guardian' => 240,
        'Necromancer' => 525, 'Druid' => 525, 'Hunter' => 250, 'Tamer' => 340,
        'Stormcaller' => 350, 'Archer' => 275, 'Wanderer' => 240, 'Paladin' => 285,
        'Priest' => 525, 'Inquisitor' => 240, 'Assassin' => 285, 'Warlock' => 340,
    ];

    /**
     * Les couts declares par chaque arbre de combat, lus dans la fixture.
     *
     * @return array<string, list<int>>
     */
    private function costsByCombatTree(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/Game/SkillFixtures.php');

        preg_match_all(
            '/    private function get(\w+)Skills\(\): array\s*\{.*?\n    \}\n/s',
            $source,
            $matches,
            \PREG_SET_ORDER,
        );

        $costs = [];
        foreach ($matches as [$body, $tree]) {
            if (!\in_array($tree, self::COMBAT_TREES, true)) {
                continue;
            }

            preg_match_all("/'requiredPoints' => (\d+)/", $body, $found);
            $costs[$tree] = array_map('intval', $found[1]);
        }

        return $costs;
    }

    /**
     * Le total d'un arbre : ce qu'un personnage paie, dormant exclu.
     *
     * @param list<int> $costs
     */
    private function learnableTotal(array $costs): int
    {
        return array_sum(array_filter($costs, static fn (int $cost): bool => SkillCostScale::DORMANT !== $cost));
    }

    /**
     * Le total du gabarit se calcule, il ne se recopie pas.
     *
     * `4x10 + 4x25 + 3x50 + 100` — quatre nœuds au palier 1, quatre au palier 2,
     * **trois** au palier 3 (la fourche en ecrit six et n'en laisse prendre que
     * trois, § 6.1 bis), un capstone. Si le gabarit bougeait, cette somme
     * devrait bouger avec lui.
     */
    public function testTheCompleteTreeCostFollowsTheTemplateArithmetic(): void
    {
        $expected = 4 * SkillCostScale::TIER_1
            + 4 * SkillCostScale::TIER_2
            + 3 * SkillCostScale::TIER_3
            + SkillCostScale::CAPSTONE;

        self::assertSame($expected, SkillCostScale::COMPLETE_TREE);
        self::assertSame(390, SkillCostScale::COMPLETE_TREE);
    }

    /**
     * L'echelle est fermee, et le dormant n'est pas un palier.
     */
    public function testTheScaleIsClosedAndTheDormantRungIsNotLearnable(): void
    {
        foreach (SkillCostScale::RUNGS as $rung) {
            self::assertTrue(SkillCostScale::isOnScale($rung));
        }

        foreach ([5, 15, 20, 30, 35, 40, 45, 60, 70, 80, 85, 90, 110, 120, 200] as $offScale) {
            self::assertFalse(
                SkillCostScale::isOnScale($offScale),
                sprintf('%d n\'est pas un palier : c\'est un dosage.', $offScale),
            );
        }

        self::assertNotContains(SkillCostScale::DORMANT, SkillCostScale::learnableRungs());
        self::assertContains(SkillCostScale::DORMANT, SkillCostScale::RUNGS);
    }

    /**
     * Aucun nœud d'arbre de combat ne coute hors echelle.
     *
     * C'est l'invariant du jalon. Avant lui, les 24 arbres portaient 23 valeurs
     * distinctes : deux nœuds a 30 et 35 points ne disaient pas deux paliers,
     * ils disaient qu'on avait dose a la main — et un dosage ne se calibre pas.
     */
    public function testEveryCombatNodeSitsOnARung(): void
    {
        $offScale = [];
        foreach ($this->costsByCombatTree() as $tree => $costs) {
            foreach ($costs as $cost) {
                if (!SkillCostScale::isOnScale($cost)) {
                    $offScale[] = sprintf('%s : %d', $tree, $cost);
                }
            }
        }

        self::assertSame([], $offScale, 'Un cout hors echelle est un palier qui n\'existe pas.');
    }

    /**
     * Le relevé couvre les 24 arbres, et eux seuls.
     *
     * Un arbre de combat ajoute sans entrer dans la mesure echapperait a
     * l'echelle sans que rien ne le dise.
     */
    public function testTheMeasureCoversTheTwentyFourCombatTrees(): void
    {
        $found = array_keys($this->costsByCombatTree());
        sort($found);

        $expected = self::COMBAT_TREES;
        sort($expected);

        self::assertSame($expected, $found);
        self::assertCount(24, $expected);
    }

    /**
     * L'arbre de reference du canon tombe pile sur la cible.
     *
     * Le § 6.3 deroule le Pyromancien fini comme patron du gabarit, et c'est
     * lui que le § 0.1 chiffrait a 465 points. Sur l'echelle, il vaut
     * exactement **390** — la cible, sans qu'on ait touche a un seul de ses
     * nœuds. C'est ce qui montre que l'echelle n'a pas ete taillee pour le
     * resultat : elle etait deja la, sous les dosages.
     */
    public function testTheCanonReferenceTreeLandsExactlyOnTarget(): void
    {
        $costs = $this->costsByCombatTree()['Pyromancy'];

        self::assertSame(SkillCostScale::COMPLETE_TREE, $this->learnableTotal($costs));
    }

    /**
     * Les nœuds **generes** sont sur l'echelle eux aussi.
     *
     * Deux fabriques ecrivent un cout hors des arbres : les echelons superieurs
     * de port d'armure (ONB-20b, un nœud par famille x par arbre qui l'enseigne)
     * et l'accord dormant de DOM-07. Ils appartiennent aux memes arbres et se
     * paient de la meme bourse — les lire seulement dans le corps des arbres
     * aurait laisse un angle mort, et un angle mort dans un invariant vaut
     * moins que pas d'invariant.
     */
    public function testTheGeneratedNodesSitOnTheScaleToo(): void
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/Game/SkillFixtures.php');

        preg_match_all("/'points' => (\d+)/", $source, $ports);
        self::assertNotEmpty($ports[1], 'Les echelons de port ne declarent plus leur cout ici.');

        foreach ($ports[1] as $points) {
            self::assertTrue(
                SkillCostScale::isOnScale((int) $points),
                sprintf('Un echelon de port coute %s points, hors echelle.', $points),
            );
        }

        preg_match(
            "/'requiredPoints' => (\d+),\s*\n\s*'domain' => \\\$domain,\s*\n\s*'dormant' => true,/",
            $source,
            $dormant,
        );
        self::assertSame((string) SkillCostScale::DORMANT, $dormant[1] ?? null);
    }

    /**
     * Aucun arbre ne s'eloigne de la cible.
     *
     * **C'est le cliquet du jalon.** Les 23 autres arbres n'y sont pas encore,
     * et ce n'est pas un probleme de couts : il leur manque des nœuds, ce
     * qu'ARC-07 (les quatre patrons) et ARC-08 (les vingt autres) livreront.
     * D'ici la, l'ecart peut se reduire librement ; il ne peut plus grandir en
     * silence, et personne ne peut ajouter un nœud hors gabarit sans le voir.
     */
    public function testNoTreeDriftsFurtherFromTheTarget(): void
    {
        $drifted = [];
        foreach ($this->costsByCombatTree() as $tree => $costs) {
            $baseline = self::MEASURED_TREE_COST[$tree] ?? null;
            if (null === $baseline) {
                continue;
            }

            $now = abs($this->learnableTotal($costs) - SkillCostScale::COMPLETE_TREE);
            $was = abs($baseline - SkillCostScale::COMPLETE_TREE);

            if ($now > $was) {
                $drifted[] = sprintf('%s : ecart %d contre %d mesure', $tree, $now, $was);
            }
        }

        self::assertSame(
            [],
            $drifted,
            'Un arbre s\'est eloigne des 390 points. S\'en rapprocher est libre ; s\'en eloigner demande de le dire.',
        );
    }
}
