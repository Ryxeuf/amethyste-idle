<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\GameEngine\Fight\ArmorMitigationLaw;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'une ligne d'armure retire (ARC-19).
 *
 * GAME_ARCHETYPES § 2.2 : **plaque 40 %, cuir 20 %, tissu 0 %**, borne haute a
 * 50 %. C'est la moitie que le canon refuse a l'arbre, et il le dit avec des
 * nombres : par l'arbre seul l'ecart tank/tissu est de x1,39, *ce qui n'est pas
 * un ecart, c'est une nuance*.
 */
class ArmorMitigationLawTest extends TestCase
{
    /**
     * Les trois parts du canon, et rien d'autre.
     *
     * Le **bouclier** n'en a pas : c'est une famille de ligne `armor` dans
     * l'echelle de port, mais ce qu'il apporte se joue dans le levier `guard`
     * sous condition `shield` — lui donner une part le compterait deux fois.
     */
    public function testOnlyTheThreeCanonLinesMitigate(): void
    {
        self::assertSame(0.40, ArmorMitigationLaw::shareOfLine('plate'));
        self::assertSame(0.20, ArmorMitigationLaw::shareOfLine('leather'));
        self::assertSame(0.0, ArmorMitigationLaw::shareOfLine('cloth'));

        self::assertSame(0.0, ArmorMitigationLaw::shareOfLine('shield'));
        self::assertSame(0.0, ArmorMitigationLaw::shareOfLine('sword'));
        self::assertSame(0.0, ArmorMitigationLaw::shareOfLine(null));
    }

    /**
     * **La fourchette du canon tient** : la ligne la plus protectrice passe le
     * plancher de l'aggro (28 %) sans atteindre la borne ou le solo casse
     * (50 %).
     *
     * Les deux bornes sortent d'un calcul et non d'un avis : 50 % est le point
     * ou la mitigation du tank annule exactement sa lenteur, 28 % le minimum
     * sous lequel le transfert d'ARC-18d ne passe plus.
     */
    public function testThePlateSitsInsideTheMeasuredBand(): void
    {
        $plate = ArmorMitigationLaw::shareOfLine('plate');

        self::assertGreaterThanOrEqual(ArmorMitigationLaw::AGGRO_FLOOR, $plate);
        self::assertLessThan(ArmorMitigationLaw::MAX_SHARE, $plate);
    }

    /**
     * **Une part s'obtient en portant la ligne, pas en possedant une piece.**.
     *
     * La mitigation se moyenne sur les sept emplacements d'armure : un jeu
     * complet de plaque vaut la part de sa ligne, une seule epauliere en vaut
     * le septieme. Sans cette regle, une piece unique vaudrait une armure.
     */
    public function testTheShareIsAveragedOverTheArmourSlots(): void
    {
        $full = array_fill(0, \count(ArmorMitigationLaw::ARMOR_SLOTS), 'plate');
        self::assertEqualsWithDelta(0.40, ArmorMitigationLaw::shareFor($full), 0.001);

        self::assertEqualsWithDelta(0.40 / 7, ArmorMitigationLaw::shareFor(['plate']), 0.001);
        self::assertSame(0.0, ArmorMitigationLaw::shareFor([]));
    }

    /**
     * Un emplacement occupe par une piece hors ligne compte pour zero — il
     * n'ajoute rien et ne retire rien a ce que les autres apportent.
     */
    public function testAPieceWithoutALineNeitherAddsNorRemoves(): void
    {
        $mixed = ['plate', 'plate', null, null, 'leather', null, null];

        self::assertEqualsWithDelta((0.40 * 2 + 0.20) / 7, ArmorMitigationLaw::shareFor($mixed), 0.001);
    }

    /**
     * **La borne haute est opposable**, quelle que soit la tenue : au-dela de
     * 50 %, le tank encaisse moins que l'archer *tout en survivant mieux*, et
     * redevient le meilleur choix partout.
     */
    public function testNothingEverExceedsTheHighBound(): void
    {
        $absurd = array_fill(0, 40, 'plate');

        self::assertSame(ArmorMitigationLaw::MAX_SHARE, ArmorMitigationLaw::shareFor($absurd));
        self::assertSame(50, ArmorMitigationLaw::mitigated(100, 0.9));
    }

    /**
     * La mitigation retire une part, jamais un plancher : un coup mitige reste
     * un coup, et zero degat reste zero.
     */
    public function testMitigationScalesAndNeverGoesNegative(): void
    {
        self::assertSame(60, ArmorMitigationLaw::mitigated(100, 0.40));
        self::assertSame(80, ArmorMitigationLaw::mitigated(100, 0.20));
        self::assertSame(100, ArmorMitigationLaw::mitigated(100, 0.0));
        self::assertSame(0, ArmorMitigationLaw::mitigated(0, 0.40));
        self::assertSame(0, ArmorMitigationLaw::mitigated(-5, 0.40));
    }
}
