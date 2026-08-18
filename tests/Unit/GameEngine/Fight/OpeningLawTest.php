<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Player;
use App\Enum\GestureForm;
use App\GameEngine\Fight\OpeningLaw;
use PHPUnit\Framework\TestCase;

/**
 * Le combat qui commence avant le combat (ARC-18g).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 5. Elle repare un defaut nomme au § 9
 * sexies : **`tempo` — l'initiative — n'a aucun effet modelise**, et c'est un
 * levier decoratif dans deux palettes sur quatre.
 */
class OpeningLawTest extends TestCase
{
    /**
     * **Le cout se derive de celui d'une chasse.**.
     *
     * Le garde-fou du canon est economique plutot que ludique : un geste qui
     * coute un **tour** se paie dans la rencontre ou on le joue, donc on le
     * joue toujours si son effet depasse celui d'une attaque. Un geste qui
     * coute de l'**energie d'action** se paie sur la journee, et il entre alors
     * en concurrence avec *un combat de plus* — la seule monnaie que le § 9
     * septies reconnaisse.
     *
     * D'ou la derivation : ecrire un chiffre a la main l'aurait decroche du
     * jour ou la chasse changera de prix.
     */
    public function testItsPriceIsAFractionOfAHunt(): void
    {
        // Le curseur livre : une chasse coute 5 points d'energie d'action.
        self::assertSame(2, OpeningLaw::costFor(5));
        self::assertSame(2, OpeningLaw::openingsPerHunt(5));

        // Deplacer le prix de la chasse deplace celui de l'ouverture.
        self::assertSame(4, OpeningLaw::costFor(12));
        self::assertSame(3, OpeningLaw::openingsPerHunt(12));
    }

    /**
     * Une ouverture n'est jamais gratuite.
     *
     * Elle serait alors posee avant **chaque** rencontre sans qu'on ait a y
     * penser, ce qui est exactement l'inverse de ce que le garde-fou cherche.
     */
    public function testItIsNeverFree(): void
    {
        self::assertSame(1, OpeningLaw::costFor(1));
        self::assertSame(1, OpeningLaw::costFor(0));
    }

    /**
     * **Elle ne se pose jamais pendant une rencontre.**.
     *
     * C'est ce qui la distingue d'un geste ordinaire : posee en combat, elle ne
     * couterait ni tour ni presque energie, et deviendrait le geste le moins
     * cher du jeu.
     */
    public function testItIsNeverPlacedInTheMiddleOfAFight(): void
    {
        self::assertTrue(OpeningLaw::canBePlaced(inFight: false, availableEnergy: 10, huntCost: 5));
        self::assertFalse(OpeningLaw::canBePlaced(inFight: true, availableEnergy: 10, huntCost: 5));
        self::assertFalse(OpeningLaw::canBePlaced(inFight: false, availableEnergy: 1, huntCost: 5));
    }

    /**
     * **Une seule ouverture en attente, et la premiere rencontre la consomme.**.
     *
     * Sans la premiere regle, la journee optimale consisterait a en poser dix
     * avant d'engager, et l'ouverture cesserait d'etre une preparation pour
     * devenir un **stock**. Sans la seconde, elle servirait a chaque combat —
     * un bonus permanent achete une fois.
     */
    public function testOnlyOneWaitsAndTheFirstEncounterEatsIt(): void
    {
        $player = new Player();

        $player->prepareOpening(12);
        $player->prepareOpening(7);
        self::assertSame(7, $player->getPendingOpening(), 'Les ouvertures s\'empilent : elles sont devenues un stock.');

        self::assertSame(7, $player->consumeOpening());
        self::assertSame(0, $player->getPendingOpening());
        self::assertSame(0, $player->consumeOpening(), 'La preparation a resservi : c\'est un bonus permanent.');
    }

    /**
     * Preparer ne multiplie pas la valeur.
     *
     * Comme le differe, et pour la meme raison : *ce qu'on achete en preparant
     * n'est pas de la puissance, c'est un tour qui n'a coute aucun tour*.
     */
    public function testPreparingNeverMultipliesTheValue(): void
    {
        self::assertSame(12, OpeningLaw::payload(12));
        self::assertSame(0, OpeningLaw::payload(-3));
    }

    public function testTheFormIsDeclaredReadable(): void
    {
        self::assertTrue(GestureForm::Opening->isImplemented());
    }
}
