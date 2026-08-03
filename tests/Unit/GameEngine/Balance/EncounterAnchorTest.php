<?php

namespace App\Tests\Unit\GameEngine\Balance;

use App\Enum\MonsterRank;
use App\GameEngine\Balance\EncounterAnchor;
use PHPUnit\Framework\TestCase;

/**
 * L'ancre d'echelle, et l'ecart qu'elle mesure (ARC-05a).
 *
 * GAME_ARCHETYPES § 6.4 : *les gestes valent 1 a 12 points, les monstres ont 11
 * a 3 200 PV*. Des pourcentages poses sur des nombres sans rapport entre eux ne
 * veulent rien dire — c'est pour cela qu'un levier a +9 % ne retire pas un seul
 * tour de combat.
 *
 * **Ce jalon ne deplace aucune valeur.** Il pose la regle, la rend calculable,
 * et **fige l'ecart mesure** pour qu'ARC-05b ait une cible et un cliquet. On ne
 * recalibre pas ce qu'on ne mesure pas — et le canon (§ 0.2) previent que la
 * recalibration passera par le simulateur d'ARC-17, jamais par une relecture a
 * la main.
 */
class EncounterAnchorTest extends TestCase
{
    /**
     * L'ecart mesure le 2026-08-03, palier par palier.
     *
     * Lu sur le **degat median** des gestes de chaque palier, rapporte a ce que
     * la regle des 25 % exige. Ce sont des **facteurs** : a m4, un geste retire
     * douze fois moins que ce qu'il devrait.
     *
     * La liste vaut constat, pas permission. Le test verifie qu'aucun palier ne
     * **s'eloigne** de sa cible : l'ecart peut se reduire sans toucher au
     * fichier, jamais grandir en silence. ARC-05b le ramene vers 1.
     *
     * @var array<int, float>
     */
    private const MEASURED_SHORTFALL = [1 => 4.0, 2 => 6.0, 3 => 7.6, 4 => 12.5, 5 => 9.4];

    /**
     * Degat median des gestes livres, par palier.
     *
     * @return array<int, int>
     */
    private function medianDamageByTier(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/SpellFixtures.php');

        $damages = [];
        preg_match_all("/'slug' => '([a-z0-9-]+)',/", $source, $slugs, \PREG_OFFSET_CAPTURE);
        foreach ($slugs[1] as $i => [$slug, $offset]) {
            $end = isset($slugs[1][$i + 1]) ? $slugs[1][$i + 1][1] : \strlen($source);
            $block = substr($source, $offset, $end - $offset);

            // Les gestes en pourcentage de la vie ne se comparent pas a une
            // valeur plate : ils sont deja indexes sur leur cible.
            if (preg_match("/'valueType' => 'percent'/", $block) === 1) {
                continue;
            }

            $damage = preg_match("/'damage' => (\d+)/", $block, $m) === 1 ? (int) $m[1] : 0;
            if ($damage <= 0) {
                continue;
            }

            $tier = preg_match("/'level' => (\d+)/", $block, $m) === 1 ? (int) $m[1] : 1;
            $damages[$tier][] = $damage;
        }

        $medians = [];
        foreach ($damages as $tier => $values) {
            sort($values);
            $medians[$tier] = $values[intdiv(\count($values), 2)];
        }

        ksort($medians);

        return $medians;
    }

    /**
     * La cible se calcule depuis le gabarit du bestiaire, jamais d'une table.
     *
     * Recalibrer les PV d'un monstre doit deplacer automatiquement ce qu'un
     * geste doit valoir : deux tables se seraient separees des la premiere
     * revue du bestiaire.
     */
    public function testTheTargetIsDerivedFromTheBestiaryTemplate(): void
    {
        // 25 % de la vie d'un commun de son palier — la regle du canon.
        self::assertSame(8, EncounterAnchor::targetDamageFor(1));
        self::assertSame(18, EncounterAnchor::targetDamageFor(2));
        self::assertSame(38, EncounterAnchor::targetDamageFor(3));
        self::assertSame(75, EncounterAnchor::targetDamageFor(4));
    }

    /**
     * Un geste a la cible tue un commun en quatre tours.
     *
     * C'est l'aller-retour qui verifie que la regle et les bandes disent la
     * meme chose : 25 % par geste, ~4 tours, ce qui tombe au milieu de la bande
     * du commun (3-5). Si l'une des deux bougeait sans l'autre, ce test le
     * dirait.
     */
    public function testAGestureAtTheTargetClearsACommonWithinItsBand(): void
    {
        foreach ([1, 2, 3, 4] as $tier) {
            $turns = EncounterAnchor::turnsToClear(EncounterAnchor::targetDamageFor($tier), $tier, MonsterRank::Common);

            self::assertNotNull($turns);
            self::assertTrue(
                EncounterAnchor::isWithinBand($turns, MonsterRank::Common),
                sprintf('Un geste a la cible met %d tours a nettoyer un commun de palier %d.', $turns, $tier),
            );
        }
    }

    /**
     * Les bandes ordonnent les trois rangs sans se chevaucher.
     *
     * Le rang dit le **format** d'une rencontre : si les bandes se
     * chevauchaient, un elite pourrait durer moins qu'un commun et le rang
     * cesserait de vouloir dire quelque chose.
     */
    public function testTheThreeBandsAreOrderedAndDoNotOverlap(): void
    {
        [$commonMin, $commonMax] = EncounterAnchor::TURN_BANDS['common'];
        [$eliteMin, $eliteMax] = EncounterAnchor::TURN_BANDS['elite'];
        [$bossMin, $bossMax] = EncounterAnchor::TURN_BANDS['boss'];

        self::assertLessThan($commonMax, $commonMin);
        self::assertLessThan($eliteMax, $eliteMin);
        self::assertLessThan($bossMax, $bossMin);
        self::assertGreaterThan($commonMax, $eliteMin);
        self::assertGreaterThan($eliteMax, $bossMin);
    }

    /**
     * Un geste sans degat n'a pas de duree, et n'en invente pas une.
     */
    public function testAGestureWithoutDamageHasNoDuration(): void
    {
        self::assertNull(EncounterAnchor::turnsToClear(0, 2, MonsterRank::Common));
        self::assertSame(\INF, EncounterAnchor::shortfallFor(0, 2));
    }

    /**
     * L'ecart mesure ne grandit pas.
     *
     * **C'est le cliquet du jalon.** Les gestes livres retirent aujourd'hui
     * quatre a douze fois moins que la regle n'exige — l'ecart que le canon
     * decrit, enfin chiffre. Il peut se reduire librement (ARC-05b est
     * exactement ce travail) ; il ne peut pas s'aggraver sans que ce test le
     * dise, et personne ne peut ajouter un geste sous-calibre sans le voir.
     */
    public function testTheMeasuredShortfallNeverWidens(): void
    {
        $widened = [];
        foreach ($this->medianDamageByTier() as $tier => $median) {
            $baseline = self::MEASURED_SHORTFALL[$tier] ?? null;
            if (null === $baseline) {
                continue;
            }

            $shortfall = EncounterAnchor::shortfallFor($median, min(4, $tier));
            if ($shortfall > $baseline + 0.05) {
                $widened[] = sprintf('m%d : x%.1f contre x%.1f mesure', $tier, $shortfall, $baseline);
            }
        }

        self::assertSame(
            [],
            $widened,
            'L\'ecart a l\'ancre s\'est creuse : un geste a ete ajoute ou affaibli sous la courbe. '
            . 'Reduire l\'ecart est libre ; l\'aggraver demande de le dire.',
        );
    }

    /**
     * Le releve couvre tous les paliers qui portent des gestes.
     *
     * Une base de reference incomplete serait pire qu'absente : elle donnerait
     * l'impression d'un cliquet la ou il n'y en a pas.
     */
    public function testTheBaselineCoversEveryTierThatCarriesGestures(): void
    {
        self::assertSame(
            array_keys(self::MEASURED_SHORTFALL),
            array_keys($this->medianDamageByTier()),
        );
    }
}
