<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * La chaine de l'acte I (ONB-12b).
 *
 * GAME_ONBOARDING § 5.2 : dix etapes, trois tours de la meme boucle —
 * **parchemin → arbre → geste** — sur l'arme, la materia et la recolte.
 *
 * Ce que la chaine heritee faisait a la place : sept etapes, un depart par le
 * voyage — le seul geste time-gate du jeu, place en premier — et **aucune
 * mention de la materia**, c'est-a-dire ni la seule source d'actions de combat
 * (regle 10), ni le build du personnage. Un joueur pouvait terminer le tutoriel
 * sans jamais savoir que le systeme existait.
 *
 * Les lois ci-dessous portent sur la **forme** de la chaine, pas sur son texte :
 * NAR-20 reecrira les libelles du Fanal, et il ne doit pas pouvoir casser
 * l'enchainement en le faisant.
 */
class ActOneChainTest extends TestCase
{
    /**
     * Les dix etapes, dans l'ordre du canon.
     *
     * @var list<string>
     */
    private const STEPS = [
        'quest_acte1_reveil',           // 1 — le maitre d'armes
        'quest_acte1_premiers_pas',     // 2 — apprendre        (tour 1)
        'quest_acte1_bapteme_du_feu',   // 3 — le mannequin
        'quest_acte1_accord',           // 4 — l'accord         (tour 2)
        'quest_acte1_second_mannequin', // 5 — le second mannequin
        'quest_acte1_metier',           // 6 — le metier        (tour 3)
        'quest_acte1_recolte',          // 7 — la recolte
        'quest_acte1_premiere_potion',  // 8 — l'atelier
        'quest_acte1_cristal',          // 9 — le depart
        'quest_acte1_guilde',           // 10 — l'expedition
    ];

    /**
     * Une etape = **une** quete de l'arc `intro`, et elles sont dix.
     */
    public function testTenSteps(): void
    {
        $blocks = $this->stepBlocks();

        self::assertSame(self::STEPS, array_keys($blocks), 'La chaine de l\'acte I n\'a plus ses dix etapes, ou plus dans cet ordre.');

        foreach ($blocks as $reference => $source) {
            self::assertStringContainsString("'storyArc' => 'intro'", $source, sprintf('« %s » a quitte l\'arc `intro`.', $reference));
        }
    }

    /**
     * Chaque etape porte son rang, et chaque rang est tenu une fois.
     */
    public function testEachStepCarriesItsOwnRank(): void
    {
        $ranks = [];
        foreach ($this->stepBlocks() as $reference => $source) {
            self::assertSame(1, preg_match("/'arcOrder' => (\d+)/", $source, $match), sprintf('« %s » n\'a pas de rang.', $reference));
            $ranks[] = (int) $match[1];
        }

        self::assertSame(range(1, 10), $ranks, 'Les rangs de l\'arc `intro` ne sont plus 1 a 10 dans l\'ordre : le journal afficherait la chaine melangee.');
    }

    /**
     * Aucune etape avant la 9e n'est time-gatee.
     *
     * Le joueur sort du tunnel avec une journee entiere d'energie et n'a aucun
     * budget a menager. La premiere attente reelle est l'etape 9 — et elle est
     * annoncee. Une attente placee plus tot, comme dans la chaine heritee ou le
     * voyage ouvrait le tutoriel, met un mur la ou il faut une pente.
     */
    public function testNothingBeforeTheNinthStepCostsRealTime(): void
    {
        $timeGated = ['travel', 'start_expedition'];
        $early = [];

        foreach (array_slice($this->stepBlocks(), 0, 8, true) as $reference => $source) {
            foreach ($timeGated as $gesture) {
                if (str_contains($source, sprintf("'gesture' => '%s'", $gesture))) {
                    $early[] = $reference . '/' . $gesture;
                }
            }
            if (str_contains($source, "'explore' => [")) {
                $early[] = $reference . '/explore';
            }
        }

        self::assertSame([], $early, sprintf(
            "Ces etapes font attendre avant la 9e : %s.\nLe voyage et l'expedition sont les deux lecons de la fin ; les avancer met un mur la ou il faut une pente.",
            implode(', ', $early),
        ));
    }

    /**
     * La materia est **garantie, accordee et lancee**.
     *
     * Les trois dans cet ordre, et par trois etapes distinctes : la recevoir
     * sans l'accorder ne se distingue pas d'un objet decoratif, et l'accorder
     * sans la lancer n'apprend pas qu'elle sert en combat.
     */
    public function testTheMateriaIsGrantedAttunedAndCast(): void
    {
        $blocks = $this->stepBlocks();

        self::assertStringContainsString(
            "'act_one_materia' => true",
            $blocks['quest_acte1_bapteme_du_feu'],
            'L\'etape 3 ne remet plus de materia : le reste de la chaine tourne a vide.',
        );
        self::assertStringContainsString(
            "'gesture' => 'socket_materia'",
            $blocks['quest_acte1_accord'],
            'L\'etape 4 ne demande plus de sertir : la materia resterait un objet d\'inventaire.',
        );
        self::assertStringContainsString(
            "'gesture' => 'cast_spell'",
            $blocks['quest_acte1_second_mannequin'],
            'L\'etape 5 ne demande plus de lancer le sort : rien n\'apprendrait a quoi sert la materia.',
        );
    }

    /**
     * La materia n'est **jamais** un objet fixe.
     *
     * Elle derive de l'arbre ouvert a l'etape 1. Nommer un slug rendrait le
     * choix de l'etape 1 decoratif, et remettrait a un berserker une materia
     * debloquee par l'arbre du pyromancien — donc inutilisable, et sans message.
     */
    public function testTheMateriaIsNeverAFixedItem(): void
    {
        self::assertSame(
            0,
            preg_match("/'genericItemSlug' => 'm1-/", $this->stepBlocks()['quest_acte1_bapteme_du_feu']),
            'L\'etape 3 nomme une materia : le choix de l\'etape 1 deviendrait decoratif.',
        );
    }

    /**
     * Les six armes sont reellement proposees, chacune avec sa voie.
     *
     * En separer les deux laisserait un joueur avec une arme qu'il ne peut pas
     * porter — le refus d'ONB-20b, sans le remede.
     */
    public function testTheSixWeaponsAreOfferedWithTheirTree(): void
    {
        $weapons = [
            'short-sword' => 'soldier',
            't1-axe' => 'berserker',
            't1-bow' => 'archer',
            't1-dagger' => 'assassin',
            't1-lance' => 'knight',
            't1-staff' => 'paladin',
        ];

        $source = $this->stepBlocks()['quest_acte1_reveil'];

        foreach ($weapons as $weapon => $tree) {
            self::assertStringContainsString(
                sprintf("['%s' => 1, '%s-domain-parchment' => 1]", $weapon, $tree),
                $source,
                sprintf('L\'option « %s » ne remet plus l\'arme **et** la voie qui l\'autorise.', $weapon),
            );
        }

        self::assertSame(6, substr_count($source, "'bonusRewards' =>"), 'L\'etape 1 ne propose plus exactement six armes.');
    }

    /**
     * Les cinq metiers de recolte sont reellement proposes.
     *
     * Un choix parmi cinq qui deboucherait sur une seule recolte possible serait
     * un faux choix — c'est la dette D11, fermee par ONB-10 du cote des filons.
     * Ici on verifie l'autre moitie : que les cinq sont bien offerts.
     */
    public function testTheFiveGatheringTradesAreOffered(): void
    {
        $source = $this->stepBlocks()['quest_acte1_metier'];

        foreach (['herbalist', 'miner', 'fisherman', 'lumberjack', 'skinner'] as $trade) {
            self::assertStringContainsString(
                sprintf("['%s-domain-parchment' => 1]", $trade),
                $source,
                sprintf('Le metier « %s » n\'est plus propose a l\'etape 6.', $trade),
            );
        }

        self::assertSame(5, substr_count($source, "'bonusRewards' =>"), 'L\'etape 6 ne propose plus exactement cinq metiers.');
    }

    /**
     * Les etapes 7 et 8 ne nomment jamais ce qu'elles ne peuvent pas savoir.
     *
     * Le metier est choisi a l'etape 6. Une cible `collect` ou `craft` designe un
     * objet, donc elle choisirait le metier a la place du joueur — et bloquerait
     * les quatre autres.
     */
    public function testTheHarvestAndTheWorkbenchNameNoObject(): void
    {
        $blocks = $this->stepBlocks();

        foreach (['quest_acte1_recolte' => 'collect', 'quest_acte1_premiere_potion' => 'craft'] as $reference => $forbidden) {
            self::assertStringNotContainsString(
                sprintf("'%s' => [", $forbidden),
                $blocks[$reference],
                sprintf('« %s » nomme un objet : elle choisirait le metier a la place du joueur.', $reference),
            );
        }

        self::assertStringContainsString("'gesture' => 'gather'", $blocks['quest_acte1_recolte']);
        self::assertStringContainsString("'gesture' => 'craft_item'", $blocks['quest_acte1_premiere_potion']);
    }

    /**
     * Les deux mannequins sont ceux d'ONB-11, et dans cet ordre.
     *
     * Le premier ne frappe pas, le second ne tue pas. Les intervertir ferait
     * decouvrir le combat par un adversaire qui riposte.
     */
    public function testBothDummiesAreFoughtInOrder(): void
    {
        $blocks = $this->stepBlocks();

        self::assertStringContainsString("'slug' => 'training_dummy_still'", $blocks['quest_acte1_bapteme_du_feu']);
        self::assertStringContainsString("'slug' => 'training_dummy_sparring'", $blocks['quest_acte1_second_mannequin']);
    }

    /**
     * L'etape 9 reste la porte de l'acte 2.
     *
     * Quatre fixtures de dialogue et `PnjFixtures` designent `quest_acte1_cristal`
     * par cette reference. La renommer aurait casse quatre chaines d'acte 2 sans
     * qu'aucun test ne parle d'elles.
     */
    public function testTheActTwoGateKeepsItsReference(): void
    {
        $chain = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/QuestChainFixtures.php');

        self::assertStringContainsString('$acte1Cristal = $acteOne[8];', $chain);
        self::assertStringContainsString("'quest_acte1_cristal'", $chain);
    }

    /**
     * Les blocs de quete de l'arc `intro`, indexes par reference.
     *
     * @return array<string, string>
     */
    private function stepBlocks(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/QuestFixtures.php');

        $found = [];
        foreach (self::STEPS as $reference) {
            $needle = sprintf("            '%s' => [", $reference);
            $start = strpos($source, $needle);
            self::assertNotFalse($start, sprintf('L\'etape « %s » a disparu de l\'arc.', $reference));

            $end = strpos($source, "\n            ],\n", $start);
            self::assertNotFalse($end, sprintf('Le bloc de « %s » n\'est pas referme comme attendu.', $reference));

            $found[$reference] = ['at' => $start, 'source' => substr($source, $start, $end - $start)];
        }

        // Trie par position dans le fichier, et non selon la constante : sans
        // cela, l'ordre attendu serait celui du test, et une etape deplacee
        // passerait inapercue.
        uasort($found, static fn (array $a, array $b): int => $a['at'] <=> $b['at']);

        return array_map(static fn (array $entry): string => $entry['source'], $found);
    }
}
