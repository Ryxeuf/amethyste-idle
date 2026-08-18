<?php

namespace App\Tests\Unit\GameEngine\Balance;

use App\Enum\MonsterRank;
use App\GameEngine\Balance\EncounterAnchor;
use App\GameEngine\Balance\VitalityLaw;
use App\Service\PlayerFactory;
use PHPUnit\Framework\TestCase;

/**
 * La barre de vie se derive du bestiaire (ARC-20a).
 *
 * GAME_VITALITY § 4. Le personnage n'a pas de niveau et **rien ne faisait monter
 * sa barre** : 20 PV livres, 26 a 40 une fois tout appris, contre un contenu qui
 * fait x80 et une elite de palier 4 qui frappe 110.
 *
 * La loi : *la barre d'un joueur de palier n vaut ce qu'une elite de son palier
 * lui prend en une rencontre entiere*.
 */
class VitalityLawTest extends TestCase
{
    /**
     * La duree qui definit la barre **se derive**, elle ne s'ecrit pas.
     *
     * Poser « 8 » en constante ferait diverger la barre de la bande de duree le
     * jour ou l'une des deux bouge — et c'est tout ce que ce jalon cherche a
     * empecher.
     */
    public function testTheEncounterLengthIsTheCentreOfTheEliteBand(): void
    {
        [$min, $max] = EncounterAnchor::TURN_BANDS['elite'];

        self::assertSame((int) round(($min + $max) / 2), VitalityLaw::eliteEncounterTurns());
        self::assertSame(8, VitalityLaw::eliteEncounterTurns());
    }

    /**
     * **Une elite tue un joueur seul**, et elle y met exactement une rencontre.
     *
     * Ce n'est pas un reglage, c'est la definition — mais l'invariant n'est pas
     * pour autant tautologique : il exige que la duree obtenue **retombe dans la
     * bande de son propre rang**, ce qu'un arrondi malheureux ou une grille
     * d'attaque remaniee pourraient perdre en silence.
     */
    public function testAnEliteFellsAPlayerInExactlyOneEncounter(): void
    {
        foreach (range(VitalityLaw::FIRST_TIER, VitalityLaw::LAST_TIER) as $tier) {
            $turns = VitalityLaw::turnsToFall($tier, MonsterRank::Elite);

            self::assertSame(VitalityLaw::eliteEncounterTurns(), $turns, sprintf('T%d', $tier));
            self::assertTrue(
                EncounterAnchor::isWithinBand($turns, MonsterRank::Elite),
                sprintf('T%d : une elite qui tue hors de sa propre bande de duree n\'est plus une elite.', $tier),
            );
        }
    }

    /**
     * La derivation retrouve la reference que le canon avait mesuree a la main.
     *
     * GAME_ARCHETYPES § 9 octies a mesure qu'un commun retire **16 a 26 %** de la
     * barre sur une rencontre. La loi le rend sans qu'on l'ait ecrit — il sort du
     * huitieme de la vie et du rapport de rang. *Une derivation qui rate sa propre
     * reference ne derive rien.*
     */
    public function testACommonTakesTheShareTheCanonMeasured(): void
    {
        [$min, $max] = EncounterAnchor::TURN_BANDS['common'];
        $turns = (int) round(($min + $max) / 2);

        foreach (range(VitalityLaw::FIRST_TIER, VitalityLaw::LAST_TIER) as $tier) {
            $share = VitalityLaw::shareTakenPerTurn($tier, MonsterRank::Common) * $turns;

            self::assertGreaterThanOrEqual(0.16, $share, sprintf('T%d', $tier));
            self::assertLessThanOrEqual(0.26, $share, sprintf('T%d', $tier));
        }
    }

    /**
     * **Le rapport ne depend pas du palier**, et ce n'est pas une coincidence :
     * les deux membres derivent de la meme vie de commun.
     *
     * C'est l'invariant qui dit que monter de palier ne rend ni plus ni moins
     * fragile — il rend les rencontres plus longues et plus cheres. Le perdre,
     * ce serait reproduire sur le joueur la faille du milieu que BES-01 a
     * refermee sur les monstres.
     */
    public function testTheShareDoesNotDependOnTheTier(): void
    {
        // **La tolerance se derive, elle ne se choisit pas.** La grille d'attaque
        // arrondit deux fois — la vie divisee par huit, puis le rapport de rang —,
        // si bien qu'un rang peut frapper un point a cote du rapport exact. Un
        // point pese le plus la ou la barre est la plus petite : c'est le plancher
        // qui fixe la borne. Mesure : l'elite ne derive pas d'un millieme (elle
        // *est* la definition de la barre), le commun de 0,0016, le boss de 0,0096
        // — dont on lit maintenant qu'il vient de l'arrondi et de rien d'autre.
        $tolerance = 1 / VitalityLaw::floor();

        foreach (MonsterRank::cases() as $rank) {
            $reference = VitalityLaw::shareTakenPerTurn(VitalityLaw::FIRST_TIER, $rank);

            foreach (range(VitalityLaw::FIRST_TIER, VitalityLaw::LAST_TIER) as $tier) {
                self::assertEqualsWithDelta(
                    $reference,
                    VitalityLaw::shareTakenPerTurn($tier, $rank),
                    $tolerance,
                    sprintf('%s T%d : la part prise change de palier en palier.', $rank->value, $tier),
                );
            }
        }
    }

    /**
     * **Un boss n'est pas un contenu solo, et la barre le dit** sans qu'on ait eu
     * a l'interdire.
     *
     * Il tue en cinq a six tours quand sa bande en demande douze a vingt : tenir
     * la fenetre demande la mitigation d'un tank, les depots d'un guerisseur et
     * un groupe. C'est ce que GAME_DUNGEONS suppose deja.
     */
    public function testABossOutrunsItsOwnEncounterWindow(): void
    {
        [$minimumBossTurns] = EncounterAnchor::TURN_BANDS['boss'];

        foreach (range(VitalityLaw::FIRST_TIER, VitalityLaw::LAST_TIER) as $tier) {
            self::assertLessThan(
                $minimumBossTurns,
                VitalityLaw::turnsToFall($tier, MonsterRank::Boss),
                sprintf('T%d : un boss qu\'un joueur seul peut tenir jusqu\'a sa fenetre n\'est plus un boss.', $tier),
            );
        }
    }

    /**
     * La barre monte avec les paliers, et le plancher est le premier d'entre eux.
     */
    public function testTheBarClimbsAndTheFloorIsTheFirstTier(): void
    {
        $previous = 0;

        foreach (range(VitalityLaw::FIRST_TIER, VitalityLaw::LAST_TIER) as $tier) {
            $bar = VitalityLaw::barFor($tier);
            self::assertGreaterThan($previous, $bar, sprintf('T%d', $tier));
            $previous = $bar;
        }

        self::assertSame(VitalityLaw::barFor(VitalityLaw::FIRST_TIER), VitalityLaw::floor());
    }

    /**
     * Hors des paliers, la loi **borne** au lieu d'extrapoler.
     *
     * Le palier 0 du bestiaire ne sert qu'aux mannequins d'entrainement, qui ne
     * frappent pas : lui donner une barre propre creerait un cinquieme palier ne
     * correspondant a aucun contenu.
     */
    public function testTheLawClampsOutsideTheTiers(): void
    {
        self::assertSame(VitalityLaw::floor(), VitalityLaw::barFor(0));
        self::assertSame(VitalityLaw::floor(), VitalityLaw::barFor(-3));
        self::assertSame(VitalityLaw::barFor(VitalityLaw::LAST_TIER), VitalityLaw::barFor(9));
    }

    /**
     * **L'ecart avec ce qui est livre, en cliquet.**.
     *
     * `PlayerFactory::BASE_LIFE` vaut 20 quand le plancher de la loi en demande
     * 96 : c'est l'ecart qu'ARC-20b refermera en portant le plancher sur la loi.
     * Ecrit comme un cliquet — il peut se reduire, plus s'aggraver en silence.
     */
    public function testTheDeliveredBaseNeverExceedsTheFloor(): void
    {
        self::assertLessThanOrEqual(
            VitalityLaw::floor(),
            PlayerFactory::BASE_LIFE,
            'La base livree depasse le plancher de la loi : la barre ne se derive plus de rien.',
        );
    }

    /**
     * **Aucune formule ne lit encore la loi, et c'est voulu.**.
     *
     * Comme `EncounterAnchor`, `DailyAnchor` et `MonsterStatTemplate::attackFor()`
     * avant elle, cette sous-phase livre un **instrument de mesure** et ne
     * deplace aucune valeur de jeu. Ce test documente la transition : le jour ou
     * ARC-20b et ARC-20c branchent la loi, il sera **retourne et pas supprime**,
     * comme celui d'ARC-17a l'a ete par ARC-17b.
     */
    public function testNothingReadsTheLawYet(): void
    {
        $root = \dirname(__DIR__, 4);

        foreach ([
            '/src/Service/PlayerFactory.php' => 'la creation du personnage',
            '/src/GameEngine/Player/PlayerEffectiveStatsCalculator.php' => 'les statistiques effectives',
            '/src/GameEngine/Zone/LifeRegenManager.php' => 'la regeneration hors combat',
        ] as $path => $what) {
            $source = file_get_contents($root . $path);
            self::assertIsString($source, $path);

            self::assertStringNotContainsString(
                'VitalityLaw',
                $source,
                sprintf('%s lit deja la loi : ARC-20a ne devait deplacer aucune valeur de jeu.', $what),
            );
        }
    }
}
