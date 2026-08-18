<?php

namespace App\Tests\Integration\Balance;

use App\Enum\MonsterRank;
use App\GameEngine\Balance\DailyAnchor;
use App\GameEngine\Balance\DaySimulator;
use App\GameEngine\Balance\EncounterSimulator;
use App\GameEngine\Balance\ReferenceBuildFactory;
use App\GameEngine\Balance\ReferenceCharacter;
use App\GameEngine\Balance\ReferenceCharacterFactory;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Le releve du simulateur, tenu en CI comme un cliquet (ARC-17c-c).
 *
 * ## Pourquoi un cliquet et pas les cinq seuils du canon
 *
 * Le plan demande cinq seuils en CI (§ 9 octies). **Quatre d'entre eux ne
 * peuvent pas passer aujourd'hui**, et ce n'est pas un defaut du simulateur :
 * c'est ce qu'il mesure. L'ecart d'ARC-05a — les gestes retirent quatre a douze
 * fois moins que la regle des 25 % n'exige — n'a pas encore ete resorbe, et le
 * resorber est le travail d'ARC-05c.
 *
 * Poser un seuil qu'on sait rouge produirait l'un des deux pires resultats
 * possibles : une CI durablement rouge, que tout le monde apprend a ignorer, ou
 * un seuil desserre jusqu'a passer, c'est-a-dire un seuil qui ne mesure plus
 * rien. **Le cliquet est la troisieme voie**, et c'est celle qu'ARC-05a a deja
 * employee sur le meme ecart : *le releve peut s'ameliorer librement, il ne peut
 * plus se degrader en silence*. Le jour ou ARC-05c ramene l'ecart vers 1, ces
 * bornes deviennent des seuils secs — le changement sera d'un chiffre.
 *
 * **Un seuil du canon est deja tenu, et il est dur** : *une elite tue un joueur
 * seul*. Il ne se relache pas, parce qu'il ne dit rien de l'echelle : il dit ce
 * qu'une elite doit etre.
 *
 * ## Ce que le releve du jour dit
 *
 * L'echelle **tient au palier 1 et casse au palier 2** — c'est le resultat de
 * cette passe, et aucun exercice isole ne pouvait le voir. Huit builds sur dix
 * viennent a bout d'un commun de palier 1, six dans leur bande ; au palier 2,
 * **les dix tombent**. Ce n'est donc pas « les gestes sont faibles » : c'est que
 * la courbe des gestes et celle du bestiaire divergent d'un palier a l'autre.
 */
class BalanceSimulationRatchetTest extends AbstractIntegrationTestCase
{
    /**
     * Le palier sur lequel le releve se lit.
     *
     * Le palier 1 et pas le 2, parce que c'est le seul ou quelque chose se
     * mesure : au palier 2 tous les builds tombent, et un releve ou tout le
     * monde meurt ne distingue rien. Le palier 2 a sa propre borne ci-dessous,
     * et elle est faite pour **bouger** — c'est meme tout ce qu'on lui demande.
     */
    private const REPORTED_TIER = 1;

    /**
     * Combien de builds viennent a bout d'un commun de palier 1, et combien
     * dans leur bande de duree (3-5 tours).
     *
     * **Ces nombres ne peuvent que monter.** Les faire descendre demanderait
     * soit d'affaiblir des gestes, soit de renforcer le bestiaire — deux choses
     * qui se decident, jamais qui s'observent.
     */
    private const MIN_BUILDS_CLEARING_A_COMMON = 8;
    private const MIN_BUILDS_WITHIN_THEIR_BAND = 6;

    /**
     * L'ecart d'attente entre les builds qui menent leur journee a terme.
     *
     * Mesure le 2026-08-18 : **x5,62** pour une borne canonique de **x2,0**
     * (`DailyAnchor::MAX_REST_SPREAD`). Le Pyromancien paie 164 minutes quand
     * l'Archer en paie 29 — l'ecart vient des PM, la seule ressource qui se
     * reporte d'une rencontre a la suivante.
     *
     * La borne du cliquet est posee juste au-dessus de la mesure : elle laisse
     * l'ecart se reduire sans limite et refuse qu'il s'aggrave.
     */
    private const MAX_FUNCTION_ANCHOR_SPREAD = 5.7;

    /**
     * **Une elite tue un joueur seul** — le seuil du § 9 octies, et il est dur.
     *
     * C'est la seule des cinq exigences qui ne parle pas d'echelle : elle dit ce
     * qu'une elite **est**. Un archetype qui viendrait seul a bout d'une elite de
     * son palier ne serait pas bien equilibre, il rendrait le rang inutile.
     */
    public function testAnEliteStillKillsASoloPlayer(): void
    {
        $simulator = new EncounterSimulator();

        foreach ($this->characters() as $character) {
            $outcome = $simulator->simulate($character, self::REPORTED_TIER, MonsterRank::Elite);

            self::assertFalse(
                $outcome->victory,
                sprintf('%s vient seul a bout d\'une elite de son palier : le rang cesse de vouloir dire quelque chose.', $character->label),
            );
        }
    }

    /**
     * Le nombre de builds qui concluent un commun, en cliquet.
     */
    public function testTheNumberOfBuildsClearingACommonNeverShrinks(): void
    {
        $simulator = new EncounterSimulator();
        $cleared = 0;
        $inBand = 0;

        foreach ($this->characters() as $character) {
            $outcome = $simulator->simulate($character, self::REPORTED_TIER, MonsterRank::Common);

            if ($outcome->victory) {
                ++$cleared;
            }
            if ($outcome->isWithinBand()) {
                ++$inBand;
            }
        }

        self::assertGreaterThanOrEqual(
            self::MIN_BUILDS_CLEARING_A_COMMON,
            $cleared,
            sprintf('%d builds viennent a bout d\'un commun de palier %d, contre %d au releve. Ce nombre peut monter, jamais descendre.', $cleared, self::REPORTED_TIER, self::MIN_BUILDS_CLEARING_A_COMMON),
        );

        self::assertGreaterThanOrEqual(
            self::MIN_BUILDS_WITHIN_THEIR_BAND,
            $inBand,
            sprintf('%d builds tiennent la bande 3-5 tours, contre %d au releve. Ce nombre peut monter, jamais descendre.', $inBand, self::MIN_BUILDS_WITHIN_THEIR_BAND),
        );
    }

    /**
     * L'ancre de fonction, en cliquet — et elle n'est pas tenue.
     *
     * Le canon la borne a x2,0 ; le releve mesure x5,62. Ce test **ne pretend
     * pas** que l'ancre tient : il empeche l'ecart de s'aggraver pendant qu'on
     * travaille a la reduire.
     */
    public function testTheFunctionAnchorSpreadNeverWorsens(): void
    {
        $spread = DailyAnchor::restSpread($this->restSecondsOfCompletedDays());

        self::assertLessThanOrEqual(
            self::MAX_FUNCTION_ANCHOR_SPREAD,
            $spread,
            sprintf('L\'ecart d\'attente vaut x%.2f pour un releve a x%.2f. Il peut se reduire librement — la borne canonique reste x%.1f.', $spread, self::MAX_FUNCTION_ANCHOR_SPREAD, DailyAnchor::MAX_REST_SPREAD),
        );
    }

    /**
     * **L'ancre ne se lit que sur des journees menees a terme.**.
     *
     * Ce test protege la lecture elle-meme, et pas une valeur : sans lui, un
     * build qui se met a mourir au premier combat *ameliorerait* l'ecart, parce
     * qu'une journee interrompue coute peu. Une mesure qu'une regression peut
     * rendre verte n'est pas une mesure.
     */
    public function testTheAnchorReadsOnlyDaysCarriedToTheirEnd(): void
    {
        $completed = $this->restSecondsOfCompletedDays();

        self::assertGreaterThanOrEqual(
            2,
            \count($completed),
            'Moins de deux journees menees a terme : un ecart se mesure entre deux, et l\'ancre cesserait d\'etre calculable.',
        );
    }

    /**
     * L'attente d'une journee, pour les seuls builds qui la mènent a terme.
     *
     * @return array<string, int>
     */
    private function restSecondsOfCompletedDays(): array
    {
        /** @var DaySimulator $days */
        $days = self::getContainer()->get(DaySimulator::class);

        $rest = [];
        foreach ($this->characters() as $character) {
            $outcome = $days->simulate($character, self::REPORTED_TIER);

            if ($outcome->clearedItsCommons()) {
                $rest[$outcome->buildLabel] = $outcome->restSeconds;
            }
        }

        return $rest;
    }

    /**
     * @return list<ReferenceCharacter>
     */
    private function characters(): array
    {
        /** @var ReferenceBuildFactory $builds */
        $builds = self::getContainer()->get(ReferenceBuildFactory::class);
        /** @var ReferenceCharacterFactory $characters */
        $characters = self::getContainer()->get(ReferenceCharacterFactory::class);

        return array_map(fn ($build) => $characters->of($build), $builds->all());
    }
}
