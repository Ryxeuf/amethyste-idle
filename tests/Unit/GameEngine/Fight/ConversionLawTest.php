<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\GestureForm;
use App\GameEngine\Fight\ConversionLaw;
use App\GameEngine\Zone\LifeRegenManager;
use App\GameEngine\Zone\ManaRegenManager;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'un point de vie achete (ARC-18c).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 6. Le garde-fou du canon tient en une
 * phrase — *on perd a convertir, sinon convertir est toujours correct et ce
 * n'est plus une decision* —, et tout ce fichier verifie qu'on ne peut pas
 * l'annuler par distraction.
 */
class ConversionLawTest extends TestCase
{
    /**
     * **Le taux se derive des curseurs, il ne s'ecrit pas.**.
     *
     * ARC-05b a etabli que le temps d'attente est la seule monnaie commune aux
     * quatre fonctions. Les deux ressources ont chacune leur curseur de
     * regeneration, et leur rapport dit ce qu'un PV vaut en PM sans qu'on ait
     * a en decider : sur les curseurs livres — 12 s par PV, 6 s par PM —, un
     * point de vie coute le temps de deux points de magie.
     *
     * *Une table ecrite a la main aurait diverge de ses curseurs a la premiere
     * recalibration.*
     */
    public function testTheFairRateComesFromTheTwoCursors(): void
    {
        self::assertSame(
            2.0,
            ConversionLaw::fairRate(LifeRegenManager::DEFAULT_REGEN_SECONDS, ManaRegenManager::DEFAULT_REGEN_SECONDS)
        );

        // Deplacer un curseur deplace le taux : c'est le rapport qui est fige,
        // jamais le chiffre.
        self::assertSame(4.0, ConversionLaw::fairRate(24, 6));
        self::assertSame(1.0, ConversionLaw::fairRate(6, 6));
    }

    /**
     * **On perd a convertir**, et c'est verifie plutot que relu.
     *
     * Le jour ou quelqu'un porte la penalite a 1,0 « pour que la forme serve
     * enfin », la conversion cesse d'etre une decision et devient un bouton —
     * c'est un test qui doit le dire, pas une revue.
     */
    public function testConvertingAlwaysLoses(): void
    {
        self::assertTrue(ConversionLaw::isUnfavourable(LifeRegenManager::DEFAULT_REGEN_SECONDS, ManaRegenManager::DEFAULT_REGEN_SECONDS));
        self::assertLessThan(1.0, ConversionLaw::PENALTY);

        self::assertLessThan(
            ConversionLaw::fairRate(12, 6),
            ConversionLaw::rate(12, 6),
        );
    }

    /**
     * L'arrondi va toujours contre celui qui convertit.
     *
     * Un arrondi au plus proche rendrait certaines conversions gagnantes par la
     * seule grace de l'arithmetique, et *une mecanique dont la rentabilite
     * depend de la parite d'un nombre n'est pas une decision*.
     */
    public function testTheRoundingAlwaysGoesAgainstTheConverter(): void
    {
        // 12 s / 6 s = x2, penalite de moitie = x1 : 5 PV rendent 5 PM.
        self::assertSame(5, ConversionLaw::manaFor(5, 12, 6));

        // Un taux qui tombe sur une demie : 3 PV a x0,5 rendent 1 PM, pas 2.
        self::assertSame(1, ConversionLaw::manaFor(3, 6, 6));

        self::assertSame(0, ConversionLaw::manaFor(0, 12, 6));
        self::assertSame(0, ConversionLaw::manaFor(-4, 12, 6));
    }

    /**
     * **La conversion ne tue jamais.**.
     *
     * Le canon ne l'ecrit pas parce qu'il n'y pense pas — mais sans plancher,
     * un geste qui coute des points de vie peut en couter le dernier, et le
     * joueur meurt **en lancant un sort**, d'une facon qu'aucun ecran ne lui
     * aura annoncee.
     *
     * Le plancher est **un** point de vie et pas davantage : elle peut vous
     * laisser a un coup de la mort, et c'est un pari qu'on laisse au joueur.
     */
    public function testItNeverKillsButItMayLeaveYouOneBlowAway(): void
    {
        self::assertSame(9, ConversionLaw::affordableLife(20, 9));

        // Le plancher mord : on demande 19, il en reste 10, on en depense 9.
        self::assertSame(9, ConversionLaw::affordableLife(10, 19));

        // A un point de vie, il n'y a plus rien a echanger.
        self::assertSame(0, ConversionLaw::affordableLife(1, 5));
        self::assertSame(0, ConversionLaw::affordableLife(0, 5));
        self::assertSame(0, ConversionLaw::affordableLife(20, 0));
    }

    /**
     * La forme est declaree lisible par le moteur.
     *
     * `implemented()` n'est utile que si elle dit vrai : une forme inscrite
     * sans lecteur serait un mensonge d'interface — le defaut qu'ARC-16a a
     * nomme sur les accointances.
     */
    public function testTheFormIsDeclaredReadable(): void
    {
        self::assertTrue(GestureForm::Conversion->isImplemented());
    }
}
