<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\GestureForm;
use App\GameEngine\Fight\ChargeLaw;
use PHPUnit\Framework\TestCase;

/**
 * La ressource qui se construit dans la rencontre (ARC-18e).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 2. Elle repare un defaut mesure — *la melee
 * n'a qu'un temps de reprise, donc une rotation sans recompense* — et elle est
 * la seule des huit dont la valeur **croit avec la duree du combat**, ce qui la
 * met exactement la ou la melee perd (§ 9 octies).
 */
class ChargeLawTest extends TestCase
{
    /**
     * **Le plafond, et pourquoi il en faut un.**.
     *
     * Le canon ne le nomme pas ; sans lui, la charge croitrait lineairement
     * avec la duree, si bien qu'un combat de quarante tours donnerait un geste
     * quarante fois plus fort qu'au premier. Ce n'est plus une ressource, c'est
     * une prime a la lenteur — l'exact oppose de ce que la forme repare : *la
     * melee doit aimer les longs combats, pas les provoquer*.
     */
    public function testItNeverGrowsPastItsCeiling(): void
    {
        self::assertSame(5, ChargeLaw::MAX);

        self::assertSame(1, ChargeLaw::after(0, 1));
        self::assertSame(5, ChargeLaw::after(3, 2));
        self::assertSame(5, ChargeLaw::after(4, 9));
        self::assertSame(5, ChargeLaw::after(5, 1));

        // Le plafond mord a l'ajout, jamais a la lecture : un compteur qui
        // pourrait depasser puis serait rabote garderait, entre les deux, un
        // etat que rien n'autorise.
        self::assertSame(ChargeLaw::MAX, ChargeLaw::after(ChargeLaw::MAX, 100));
    }

    /**
     * **Un geste qu'on ne peut pas payer ne se joue pas du tout.**.
     *
     * Il ne se joue pas « en moins fort » : c'est ce qui fait de la charge une
     * decision — la garder ou la depenser. Un geste qui s'adapterait au
     * compteur retirerait le choix, puisqu'il serait toujours correct de le
     * lancer.
     */
    public function testAGestureOneCannotAffordIsNotPlayedAtAll(): void
    {
        self::assertTrue(ChargeLaw::canSpend(5, 3));
        self::assertTrue(ChargeLaw::canSpend(3, 3));
        self::assertFalse(ChargeLaw::canSpend(2, 3));

        // Un geste sans cout se joue toujours : l'attaque de base ne depend
        // jamais d'une ressource (regle 10).
        self::assertTrue(ChargeLaw::canSpend(0, 0));

        self::assertSame(2, ChargeLaw::spend(5, 3));
        self::assertSame(0, ChargeLaw::spend(3, 3));

        // Refuse, donc rien n'est preleve — un geste refuse ne coute rien.
        self::assertSame(2, ChargeLaw::spend(2, 3));
    }

    /**
     * **Un geste ne peut pas a la fois generer et consommer.**.
     *
     * Le refus est structurel : un tel geste serait impossible a lire au moment
     * de jouer — le joueur ne saurait pas s'il monte ou s'il depense — et ses
     * deux moities se neutraliseraient par construction des que le cout egale
     * le gain. *La charge oppose deux gestes, elle n'en decore pas un seul.*
     */
    public function testAGestureEitherBuildsOrSpends(): void
    {
        self::assertTrue(ChargeLaw::isLegal(1, 0));
        self::assertTrue(ChargeLaw::isLegal(0, 3));
        self::assertTrue(ChargeLaw::isLegal(0, 0));
        self::assertFalse(ChargeLaw::isLegal(1, 3));
    }

    public function testTheFormIsDeclaredReadable(): void
    {
        self::assertTrue(GestureForm::Charge->isImplemented());
    }
}
