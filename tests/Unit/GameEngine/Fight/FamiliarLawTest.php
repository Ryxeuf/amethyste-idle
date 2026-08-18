<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\GestureForm;
use App\GameEngine\Fight\DepositLaw;
use App\GameEngine\Fight\FamiliarLaw;
use PHPUnit\Framework\TestCase;

/**
 * Ce que vaut un familier (ARC-18h).
 *
 * GAME_ARCHETYPES § 13.1 forme n° 1, telle que l'arbitrage du § 13.3 l'a
 * tranchee : ***c'est un depot offensif, pas un acteur***. Ce fichier tient la
 * correction 21, qui est la seule recalibration chiffree que le canon ait faite
 * sur lui-meme — et elle etait **deja ecrite quand elle n'a pas ete
 * appliquee**.
 */
class FamiliarLawTest extends TestCase
{
    /**
     * **Sa valeur totale vaut un tour d'attaque, exactement.**.
     *
     * La premiere calibration (x2,4 le tour investi) etait cassee en groupe :
     * *le familier agit sur les tours de la RENCONTRE quand son invocateur n'a
     * que les SIENS*, soit un taux de change de 4 pour 1 — mesure, l'invocateur
     * contribuait **+87 %** avec quatre invocations, et plus il invoquait plus
     * il gagnait.
     */
    public function testItIsWorthExactlyOneTurnOfAttack(): void
    {
        self::assertSame(12, FamiliarLaw::totalValue(12));
        self::assertSame(0, FamiliarLaw::totalValue(-3));

        // Etale sur sa duree, jamais multiplie par elle.
        self::assertSame(4, FamiliarLaw::perTurn(12, 3));
        self::assertSame(2, FamiliarLaw::perTurn(12, 6));
    }

    /**
     * **La borne des depots offensifs tient, quelle que soit la duree.**.
     *
     * C'est le garde-fou qui avait ete ecrit puis pas applique : la correction 5
     * — *la duree etale la valeur, elle ne l'augmente pas* — existait deja quand
     * la premiere calibration a ete posee.
     */
    public function testItNeverExceedsTheOffensiveDepositBound(): void
    {
        foreach ([2, 3, 4, 6, 10] as $duration) {
            $perTurn = FamiliarLaw::perTurn(12, $duration);
            self::assertTrue(
                FamiliarLaw::isWithinBound($perTurn, $duration, 12),
                sprintf('Sur %d tours, le familier rend plus qu\'un tour d\'attaque.', $duration)
            );
        }

        // Ce que la premiere calibration faisait — et qu'on refuse desormais.
        self::assertFalse(FamiliarLaw::isWithinBound(5, 6, 12));
    }

    /**
     * Sa duree est celle d'un depot.
     *
     * Un familier d'un seul tour n'a rien depose, il a **reagi** — et reagir est
     * precisement ce qu'un joueur absent ne peut pas faire.
     */
    public function testItLastsAtLeastAsLongAsADeposit(): void
    {
        self::assertSame(DepositLaw::MIN_DURATION, FamiliarLaw::durationFor(1));
        self::assertSame(6, FamiliarLaw::durationFor(6));
    }

    /**
     * ***Il ne vaut rien quand vous jouez. Il vaut tout quand vous ne jouez
     * pas.***.
     *
     * L'affirmation du canon, rendue **calculable** plutot que decrite : présent,
     * l'invocation remplace un tour d'attaque par un tour d'attaque, donc
     * n'ajoute rien ; absent, elle remplace des attaques de base — bien plus
     * faibles — par la meme valeur.
     *
     * C'est ce qui fait du geste une decision de joueur : *je pose mon familier
     * avant de fermer l'onglet*.
     */
    public function testItIsWorthNothingWhenYouPlayAndEverythingWhenYouDoNot(): void
    {
        // Present : aucun tour d'absence, aucune contribution nette.
        self::assertSame(0, FamiliarLaw::contributionOver(12, 1, 0));

        // Six tours d'absence, ou l'on n'aurait porte que des attaques de base
        // a 1 degat : le familier rend 12 la ou il y aurait eu 6.
        self::assertSame(6, FamiliarLaw::contributionOver(12, 1, 6));

        // Un joueur dont l'attaque de repli vaut autant que son geste ne gagne
        // rien a invoquer — et c'est juste : il n'a rien a compenser.
        self::assertSame(0, FamiliarLaw::contributionOver(12, 2, 6));
    }

    /**
     * **Un seul a la fois.**.
     *
     * Sans lui, la correction de valeur ne suffirait pas : on empilerait quatre
     * depots a un tour d'attaque chacun, ce qui est exactement la mesure a
     * +87 % que la correction 21 a fermee.
     */
    public function testOnlyOneAtATime(): void
    {
        self::assertSame(1, FamiliarLaw::MAX_ACTIVE);
    }

    public function testTheFormIsDeclaredReadable(): void
    {
        self::assertTrue(GestureForm::Familiar->isImplemented());
    }
}
