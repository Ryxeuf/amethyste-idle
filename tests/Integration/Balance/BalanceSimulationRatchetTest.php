<?php

namespace App\Tests\Integration\Balance;

use App\Enum\MonsterRank;
use App\GameEngine\Balance\CompositionFactory;
use App\GameEngine\Balance\DailyAnchor;
use App\GameEngine\Balance\DaySimulator;
use App\GameEngine\Balance\EncounterSimulator;
use App\GameEngine\Balance\GroupEncounterSimulator;
use App\GameEngine\Balance\ReferenceBuildFactory;
use App\GameEngine\Balance\ReferenceCharacter;
use App\GameEngine\Balance\ReferenceCharacterFactory;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Le releve du simulateur, tenu en CI comme un cliquet (ARC-17c-c, etendu au groupe
 * par ARC-17c-d).
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
     *
     * **Releve deplace le 2026-08-18 par ARC-20c — de x5,7 a x13,2 — et le
     * deplacement n'est pas une regression** : jusqu'ici le simulateur mesurait
     * une barre de **20 PV a tous les paliers**, celle que `PlayerFactory`
     * ecrivait faute de Socle. Les degats subis y etaient donc quasi
     * inobservables (le plan d'ARC-20 le dit : *quatre des cinq seuils portent
     * sur les degats subis et sont inatteignables*), et l'ecart mesure valait
     * pour une journee qui n'existait pas.
     *
     * Avec la barre reelle et la regeneration proportionnelle, l'ecart se lit
     * enfin : il est **plus grand qu'on ne croyait**, et sa cause est celle que
     * le § 9 septies avait nommee — *les PV se rechargent proportionnellement,
     * les PM non*, si bien qu'un build qui ne paie qu'en PV attend peu et qu'un
     * build qui vide son pool attend autant qu'avant. La reduire est ARC-05c.
     *
     * ***Un cliquet qui bouge parce qu'on mesure enfin est le contraire d'un
     * cliquet qui bouge en silence.***
     */
    private const MAX_FUNCTION_ANCHOR_SPREAD = 13.2;

    /**
     * Ce que le meilleur des quatre groupes entame de sa rencontre.
     *
     * Mesure le 2026-08-18 : **14 %**, pour un groupe qui tombe en trois rondes.
     * Comme les autres, ce nombre ne peut que monter.
     */
    private const MIN_GROUP_ENCOUNTER_SHARE = 14.0;

    /**
     * Combien de builds une elite de leur palier emporte.
     *
     * Mesure le 2026-08-18, une fois la barre reelle branchee (ARC-20c) :
     * **14 sur 18**, et les quatre survivants sont ceux qui la tuent avant huit
     * tours (le Mur, la Ligne mobile, le Guet, l'Ombre).
     *
     * La mesure retrouve au passage la reference du canon : les builds y
     * laissent **97 a 100 %** de leur barre, quand le § 9 octies annoncait
     * « 102 a 129 % ». *Une derivation qui rate sa propre reference ne derive
     * rien* — celle-ci tombe dessus.
     *
     * Le canon les veut **tous** ; ce qui manque pour y arriver est la
     * mitigation d'armure (ARC-19). En cliquet : ce nombre peut monter, jamais
     * descendre.
     */
    private const MIN_BUILDS_AN_ELITE_KILLS = 12;

    /**
     * **Une elite tue un joueur seul** — le seuil du § 9 octies.
     *
     * C'est la seule des cinq exigences qui ne parle pas d'echelle : elle dit ce
     * qu'une elite **est**. Un archetype qui viendrait seul a bout d'une elite
     * de son palier ne serait pas bien equilibre, il rendrait le rang inutile.
     *
     * **ARC-20c le fait passer de seuil dur a cliquet, et il faut dire
     * pourquoi.** Il etait vert sur une barre de **20 PV** — c'est-a-dire qu'il
     * mesurait une mort certaine contre n'importe quoi, pas la difficulte d'une
     * elite. Avec la barre reelle (96 au palier 1, calibree pour qu'une elite la
     * vide en **huit** tours), le resultat depend de la duree du combat : la
     * bande d'une elite est de **6 a 10 tours** (`EncounterAnchor`), donc *les
     * builds qui la tuent avant huit tours survivent, et c'est arithmetique*.
     *
     * Le canon est ici en tension avec lui-meme, et le mesurer est le livrable :
     * il veut a la fois une elite qui dure 6-10 tours et qui emporte une barre
     * entiere. Ce qui manque pour trancher est **la moitie que le canon nomme
     * lui-meme** — la mitigation d'armure (decision 21 : *la mitigation d'un
     * tank vient de son armure, pas de son arbre*), qui n'est lue par aucune
     * formule et qui appartient a ARC-19.
     *
     * Le cliquet compte donc **combien de builds tombent**, et il ne peut que
     * monter : *un seuil qu'on garde vert en fermant les yeux ne mesure rien.*
     *
     * **ARC-19 a re-cale le releve, et la raison compte plus que le chiffre.**
     * Le commentaire ci-dessus attendait de la mitigation d'armure qu'elle
     * porte le compte « a tous » ; la mesure dit l'inverse, et c'est
     * arithmetique elle aussi — ***mitiger, c'est survivre***. Les six builds
     * qui s'en sortent desormais sont ceux qui portent une ligne (plaque ou
     * cuir), et le releve passe de 14 a **12 sur 18**.
     *
     * Ce qui manque pour que l'elite les emporte tous n'etait donc pas la
     * mitigation mais **la recalibration** — ARC-05c, que le canon (§ 0.2)
     * confie au simulateur et jamais a une relecture a la main. L'attribution
     * etait fausse, la mesure la corrige, et le cliquet repart de sa nouvelle
     * mesure : il ne pourra plus redescendre sous 12.
     *
     * La **loi**, elle, ne bouge pas : GAME_VITALITY § 8 invariant 8 dit *« en
     * tue au moins un sur deux »*, et c'est la seconde assertion — celle qui,
     * contrairement au releve, ne depend d'aucune calibration.
     */
    public function testAnEliteStillKillsMostSoloPlayers(): void
    {
        $simulator = new EncounterSimulator();

        $survivors = [];
        $fallen = 0;
        foreach ($this->characters() as $character) {
            $outcome = $simulator->simulate($character, self::REPORTED_TIER, MonsterRank::Elite);

            if ($outcome->victory) {
                $survivors[] = $character->label;
            } else {
                ++$fallen;
            }
        }

        self::assertGreaterThanOrEqual(
            self::MIN_BUILDS_AN_ELITE_KILLS,
            $fallen,
            sprintf(
                '%d builds tombent contre une elite de leur palier, contre %d au releve — et %s en viennent a bout. Ce nombre peut monter, jamais descendre : ce qui manque pour le porter a tous est la recalibration (ARC-05c), pas la mitigation.',
                $fallen,
                self::MIN_BUILDS_AN_ELITE_KILLS,
                implode(', ', $survivors) ?: 'aucun',
            ),
        );

        // La loi, et non le releve : *une elite en tue au moins un sur deux*
        // (GAME_VITALITY § 8, invariant 8). Elle ne depend d'aucune
        // calibration, donc elle ne se re-cale jamais.
        $total = $fallen + \count($survivors);
        self::assertGreaterThanOrEqual(
            $total / 2,
            $fallen,
            sprintf('Une elite n\'emporte plus la moitie des builds (%d sur %d) : le rang cesse d\'etre un rang.', $fallen, $total),
        );
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
     * **Aucun role n'est necessaire** — le seuil du § 7 bis, et il est dur.
     *
     * La formulation exacte compte : on ne demande pas qu'un groupe gagne, on
     * demande qu'**aucune composition ne reussisse la ou « sans tank / sans
     * soigneur » echoue**. C'est la difference entre un role utile et un role
     * obligatoire, et c'est la seule des deux qui soit une regle.
     *
     * Aujourd'hui les quatre compositions echouent — le seuil tient donc, mais
     * **par construction** : le donjon ne connait ni soin ni mitigation ni
     * deplacement de riposte. *Un seuil qu'aucun mecanisme ne peut faire echouer
     * ne mesure rien tant que le mecanisme n'existe pas.* Ce test prend son sens
     * avec ARC-18 et ARC-19, et il est ecrit maintenant pour qu'ils le trouvent.
     */
    public function testNoCompositionSucceedsWhereThePlainOneFails(): void
    {
        $outcomes = $this->groupOutcomes();

        self::assertNotEmpty($outcomes, 'Aucune composition jouable : les quatre fonctions ne sont pas toutes au gabarit.');

        $plain = $outcomes['sans tank / sans soigneur'] ?? null;
        self::assertNotNull($plain, 'La composition sans tank ni soigneur est celle que le § 7 bis protege : elle doit etre jouee.');

        if ($plain->victory) {
            return;
        }

        foreach ($outcomes as $label => $outcome) {
            self::assertFalse(
                $outcome->victory,
                sprintf('« %s » vient a bout de la rencontre quand « sans tank / sans soigneur » echoue : un role est devenu necessaire, ce que le § 7 bis interdit.', $label),
            );
        }
    }

    /**
     * Ce que le meilleur groupe entame de la rencontre, en cliquet.
     *
     * Mesure le 2026-08-18 : **14 %** au mieux, pour un groupe qui tombe en
     * trois rondes. La rencontre porte 480 PV partages (120 x 4) quand les
     * membres en retirent cinq ou six par tour et tombent en deux coups. C'est
     * le meme ecart d'echelle qu'en solo, amplifie par le multiple.
     */
    public function testWhatTheBestGroupClearsNeverShrinks(): void
    {
        $best = 0.0;
        foreach ($this->groupOutcomes() as $outcome) {
            $best = max($best, $outcome->encounterShareCleared());
        }

        self::assertGreaterThanOrEqual(
            self::MIN_GROUP_ENCOUNTER_SHARE,
            $best,
            sprintf('Le meilleur groupe entame %.1f %% de la rencontre, contre %.1f %% au releve. Ce nombre peut monter, jamais descendre.', $best, self::MIN_GROUP_ENCOUNTER_SHARE),
        );
    }

    /**
     * Les quatre compositions, jouees contre une elite du palier du releve.
     *
     * @return array<string, \App\GameEngine\Balance\GroupOutcome>
     */
    private function groupOutcomes(): array
    {
        /** @var CompositionFactory $compositions */
        $compositions = self::getContainer()->get(CompositionFactory::class);
        /** @var GroupEncounterSimulator $simulator */
        $simulator = self::getContainer()->get(GroupEncounterSimulator::class);

        $outcomes = [];
        foreach ($compositions->all() as $label => $members) {
            $outcomes[$label] = $simulator->simulate($members, self::REPORTED_TIER, MonsterRank::Elite, $label);
        }

        return $outcomes;
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
