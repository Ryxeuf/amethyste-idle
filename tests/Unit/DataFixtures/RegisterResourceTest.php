<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * Un geste ne facture que la ressource de son registre (ARC-04a).
 *
 * GAME_MATERIA § 2.3 bis, tranche le 2026-08-01 : trois registres qui coutent
 * la meme chose ne sont qu'un registre. Facturer des PM a un guerrier efface la
 * seule difference **structurelle** entre les trois — le reste n'est que des
 * pourcentages.
 *
 * | Ce que le geste coute | m1 | m2 | m3 | m4 | m5 |
 * |---|---|---|---|---|---|
 * | Sorts — PM (`energyCost`)          | 10 | 15 | 20 | 25 | 30 |
 * | Melee — reprise en tours (`cooldown`) | 0 | 1 | 2 | 3 | 4 |
 * | Distance — munitions (`ammoCost`)   | 1 | 2 | 3 | 4 | 5 |
 *
 * **La ligne distance attend ARC-04b** : `ammoCost` n'existe pas encore, et le
 * carquois est une piece d'equipement a ecrire. Les gestes de tir qui coutent
 * quelque chose gardent donc leur cout en PM, ce que ce fichier constate au lieu
 * de le taire — une liste nommee qui ne peut que retrecir, comme celle d'ARC-08.
 */
class RegisterResourceTest extends TestCase
{
    /**
     * La grille de reprise de la melee, par palier de geste.
     *
     * @var array<int, int>
     */
    private const MELEE_COOLDOWNS = [1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4];

    /**
     * Les gestes de tir qui facturent encore des PM, faute de munitions.
     *
     * Elle vaut aveu, pas permission : ARC-04b la vide en creant `ammoCost` et
     * le carquois. Le test verifie qu'elle est **exacte** — une entree n'y
     * survit pas a sa conversion.
     *
     * @var list<string>
     */
    private const AWAITING_ARC_04B = [
        'arrow-rain',
        'critical-shot',
        'piercing-arrow',
        'precise-shot',
    ];

    /**
     * Le seul sort livre qui porte une reprise sous le palier 3.
     *
     * **Constat, pas permission.** GAME_MATERIA § 2.3 bis regle 3 n'autorise un
     * `cooldown` hors melee qu'a partir du palier 3 ; `stone-shield` est de
     * palier 2 et en porte un depuis bien avant ce jalon. Le corriger demande
     * un arbitrage que ce jalon n'a pas a rendre : lui retirer sa reprise le
     * rend spammable (c'est une protection a 5 de soin et un statut
     * `shield`), la lui garder demande de monter son palier — deux facons de
     * changer une valeur de jeu vivante.
     *
     * La liste est donc nommee et **verifiee exacte** : elle empeche qu'un
     * second cas s'ajoute en silence, et elle disparaitra quand l'arbitrage
     * sera rendu.
     *
     * @var list<string>
     */
    private const COOLDOWN_BELOW_TIER_THREE = ['stone-shield'];

    /**
     * Registre, palier, cout en PM et reprise de chaque geste livre.
     *
     * @return array<string, array{register: string, level: int, energy: int, cooldown: int}>
     */
    private function gestures(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/SpellFixtures.php');

        $gestures = [];
        preg_match_all("/'slug' => '([a-z0-9-]+)',/", $source, $slugs, \PREG_OFFSET_CAPTURE);
        foreach ($slugs[1] as $i => [$slug, $offset]) {
            $end = isset($slugs[1][$i + 1]) ? $slugs[1][$i + 1][1] : \strlen($source);
            $block = substr($source, $offset, $end - $offset);

            $gestures[$slug] = [
                'register' => preg_match("/'register' => CombatRegister::(\w+)/", $block, $m) === 1 ? strtolower($m[1]) : 'spell',
                'level' => preg_match("/'level' => (\d+)/", $block, $m) === 1 ? (int) $m[1] : 1,
                'energy' => preg_match("/'energyCost' => (\d+)/", $block, $m) === 1 ? (int) $m[1] : 0,
                'cooldown' => preg_match("/'cooldown' => (\d+)/", $block, $m) === 1 ? (int) $m[1] : 0,
            ];
        }

        self::assertNotEmpty($gestures, 'L\'extraction des gestes a echoue : rien n\'est verifie.');

        return $gestures;
    }

    /**
     * Aucune technique de melee ne facture de PM.
     *
     * C'est la regle qui fait le travail : un guerrier cesse d'etre « un mage
     * qui tape ». Elle se verifie en une lecture, et elle mord des le premier
     * geste converti.
     */
    public function testNoMeleeTechniqueBillsMana(): void
    {
        $offenders = [];
        foreach ($this->gestures() as $slug => $gesture) {
            if ($gesture['register'] === 'melee' && $gesture['energy'] > 0) {
                $offenders[] = sprintf('%s (%d PM)', $slug, $gesture['energy']);
            }
        }

        self::assertSame([], $offenders, 'Ces techniques de melee facturent des PM : la ressource ne suit plus le registre.');
    }

    /**
     * La reprise d'une technique de melee suit la grille de son palier.
     *
     * Ni plus ni moins : la grille n'a pas ete inventee, elle a ete lue sur le
     * seul geste livre qui portait deja une reprise sans etre un sort de zone
     * (`shadow-dance`, niveau 5, reprise 4).
     */
    public function testEveryMeleeTechniqueFollowsTheRecoveryGrid(): void
    {
        $offenders = [];
        foreach ($this->gestures() as $slug => $gesture) {
            if ($gesture['register'] !== 'melee') {
                continue;
            }

            $expected = self::MELEE_COOLDOWNS[$gesture['level']] ?? null;
            self::assertNotNull($expected, sprintf('Le geste "%s" est de palier %d : la grille ne connait que 1 a 5.', $slug, $gesture['level']));

            if ($gesture['cooldown'] !== $expected) {
                $offenders[] = sprintf('%s (palier %d : %d au lieu de %d)', $slug, $gesture['level'], $gesture['cooldown'], $expected);
            }
        }

        self::assertSame([], $offenders, 'Ces techniques de melee ne paient pas la reprise de leur palier.');
    }

    /**
     * Au-dela du geste d'entree, une technique de melee coute toujours un tour.
     *
     * Le palier 1 est gratuit **par la grille** : c'est le geste qu'on a
     * toujours, celui qui garantit qu'un guerrier a sec n'est jamais bloque.
     * Au-dessus, un geste sans reprise serait le seul geste gratuit du jeu, et
     * donc le seul qu'on jouerait sans reflechir.
     */
    public function testAboveTheEntryTierAMeleeTechniqueAlwaysCostsATurn(): void
    {
        $free = [];
        foreach ($this->gestures() as $slug => $gesture) {
            if ($gesture['register'] === 'melee' && $gesture['level'] > 1 && $gesture['cooldown'] === 0) {
                $free[] = $slug;
            }
        }

        self::assertSame([], $free, 'Ces techniques de melee ne coutent rien du tout.');
    }

    /**
     * La liste d'attente d'ARC-04b est exacte, donc condamnee a retrecir.
     */
    public function testTheRangedWaitingListIsAccurate(): void
    {
        $billing = [];
        foreach ($this->gestures() as $slug => $gesture) {
            if ($gesture['register'] === 'ranged' && $gesture['energy'] > 0) {
                $billing[] = $slug;
            }
        }

        sort($billing);

        self::assertSame(
            self::AWAITING_ARC_04B,
            $billing,
            'La liste d\'attente d\'ARC-04b ne decrit plus la realite : un geste de tir y est reste apres sa conversion, '
            . 'ou un geste converti facture de nouveau des PM.',
        );
    }

    /**
     * Les sorts gardent leur ressource, et elle seule.
     *
     * Un `cooldown` additionnel reste permis hors melee **a partir du palier 3**
     * (§ 2.3 bis, regle 3) : c'est un garde-fou anti-spam sur les gestes de
     * pointe, jamais une seconde facture.
     */
    public function testSpellsKeepManaAndOnlyGetACooldownAtTheTopTiers(): void
    {
        $offenders = [];
        foreach ($this->gestures() as $slug => $gesture) {
            if ($gesture['register'] !== 'spell') {
                continue;
            }

            if ($gesture['cooldown'] > 0 && $gesture['level'] < 3) {
                $offenders[] = $slug;
            }
        }

        sort($offenders);

        self::assertSame(
            self::COOLDOWN_BELOW_TIER_THREE,
            $offenders,
            'Un sort de bas palier porte une reprise sans etre nomme : le garde-fou anti-spam ne vaut qu\'a partir du palier 3.',
        );
    }
}
