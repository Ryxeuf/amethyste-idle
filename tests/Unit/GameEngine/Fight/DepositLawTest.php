<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\SpellIntent;
use App\Enum\SpellScope;
use App\GameEngine\Fight\DepositLaw;
use PHPUnit\Framework\TestCase;

/**
 * La loi du depot (ARC-11b).
 *
 * GAME_ARCHETYPES § 7 bis. Le combat de groupe est semi-synchrone — un seul
 * joueur actif a la fois, et le tour d'un absent resolu tout seul. Un soin
 * **reactif** y est une mecanique morte, et avec elle tout l'archetype
 * d'entretien en groupe. Ces tests tiennent les quatre proprietes qui font que
 * le depot repare ca sans casser autre chose.
 */
class DepositLawTest extends TestCase
{
    /**
     * Tout ce qui touche le groupe se depose — c'est la loi elle-meme.
     */
    public function testEveryGroupScopedGestureDeposits(): void
    {
        foreach (SpellIntent::cases() as $intent) {
            self::assertTrue(
                DepositLaw::deposits($intent, SpellScope::Group),
                sprintf('Un geste `%s` de portee groupe doit se deposer.', $intent->value)
            );
        }
    }

    /**
     * Toute protection se depose, **quelle que soit sa portee**.
     *
     * Correction du § 9 bis : une garde qui coute un tour entier pour couvrir
     * *ce* tour punit l'encaisse de se defendre — il perd en degats exactement
     * ce qu'il gagne en survie, et son tour defensif est toujours un mauvais
     * calcul. C'est ce qui rend l'encaisse jouable **seul**.
     */
    public function testEveryProtectionDepositsWhateverItsScope(): void
    {
        foreach (SpellScope::cases() as $scope) {
            self::assertTrue(
                DepositLaw::deposits(SpellIntent::Protection, $scope),
                sprintf('Une protection de portee `%s` doit se deposer.', $scope->value)
            );
        }
    }

    /**
     * La loi n'interdit pas le soin direct.
     *
     * § 7 bis.2 bis : *le direct est l'urgence, le depot est la provision*. Un
     * guerisseur solo joue surtout le premier — le soin d'allie reste le geste
     * d'entree naturel de l'entretien, et le transformer en depot retirerait
     * au jeu le seul geste qui sauve quelqu'un a 20 PV.
     */
    public function testDirectHealingIsNotTurnedIntoADeposit(): void
    {
        self::assertFalse(DepositLaw::deposits(SpellIntent::Heal, SpellScope::Ally));
        self::assertFalse(DepositLaw::deposits(SpellIntent::Heal, SpellScope::SelfOnly));
        self::assertFalse(DepositLaw::deposits(SpellIntent::Damage, SpellScope::Target));
        self::assertFalse(DepositLaw::deposits(SpellIntent::Hinder, SpellScope::Target));
        self::assertFalse(DepositLaw::deposits(null, null));
    }

    /**
     * Un depot court au moins deux tours.
     *
     * Meme raison qu'`ElementalMark::MIN_DURATION`, et elle est arithmetique :
     * un effet qui ne dure que le tour ou il est joue n'a rien depose — il a
     * **reagi**, ce que le modele semi-synchrone interdit.
     */
    public function testADepositNeverLastsASingleTurn(): void
    {
        self::assertSame(DepositLaw::MIN_DURATION, DepositLaw::durationFor(0));
        self::assertSame(DepositLaw::MIN_DURATION, DepositLaw::durationFor(1));
        self::assertSame(6, DepositLaw::durationFor(6));
        self::assertGreaterThanOrEqual(2, DepositLaw::MIN_DURATION);
    }

    /**
     * **La durée étale la valeur, elle ne l'augmente pas.**.
     *
     * Correction du § 9 ter, et le garde-fou le plus important du jalon : sans
     * lui, allonger un depot serait le levier le moins cher du jeu. Mesure, un
     * depot de 10 tours sur quatre allies vaudrait 14,7 tours d'attaque, et un
     * groupe sans entretien cesserait d'etre « plus lent » pour devenir **non
     * viable**.
     */
    public function testDurationSpreadsTheValueInsteadOfMultiplyingIt(): void
    {
        // Le meme total, etale sur des durees differentes : le rendu total ne
        // grandit jamais avec la duree.
        $total = 48;
        foreach ([2, 3, 4, 6, 8, 12] as $duration) {
            $delivered = DepositLaw::spreadPerTurn($total, $duration) * $duration;

            self::assertLessThanOrEqual(
                $total,
                $delivered,
                sprintf('Un depot de %d tours rend plus que son total.', $duration)
            );
        }
    }

    /**
     * Doubler la duree ne double pas ce qu'on recoit.
     *
     * La formulation directe du garde-fou : c'est une provision etalee, pas
     * une puissance achetee.
     */
    public function testDoublingTheDurationDoesNotDoubleTheYield(): void
    {
        $short = DepositLaw::spreadPerTurn(48, 4) * 4;
        $long = DepositLaw::spreadPerTurn(48, 8) * 8;

        self::assertSame(48, $short);
        self::assertSame(48, $long);
    }

    /**
     * Un depot qui vaut quelque chose rend quelque chose a chaque tour.
     *
     * Un depot dont chaque tour rend zero n'est pas un depot etale, c'est un
     * depot supprime — et il se lirait comme un choix de build.
     */
    public function testADepositWorthSomethingNeverTicksForNothing(): void
    {
        self::assertGreaterThan(0, DepositLaw::spreadPerTurn(3, 10));
        self::assertGreaterThan(0, DepositLaw::spreadPerTurn(1, 12));
        self::assertSame(0, DepositLaw::spreadPerTurn(0, 6));
    }

    /**
     * L'asymetrie du donjon semi-synchrone, ecrite noir sur blanc.
     *
     * > **Ce qui agit sur un etat se multiplie par le nombre d'allies. Ce qui
     * > agit sur une action ne se multiplie pas.**
     *
     * Parce qu'un seul joueur agit par tour : un soin touche quatre corps a
     * chaque tour (×4), une amelioration de degats ne touche que l'action du
     * tour (×1). C'est structurel, pas un defaut a corriger — et c'est
     * pourquoi **un archetype de barde n'est pas un archetype de groupe** dans
     * ce modele, ni le controle un soutien.
     */
    public function testStateMultipliesWithAlliesButActionDoesNot(): void
    {
        self::assertTrue(DepositLaw::multipliesWithAllies(SpellIntent::Heal));
        self::assertTrue(DepositLaw::multipliesWithAllies(SpellIntent::Protection));

        self::assertFalse(DepositLaw::multipliesWithAllies(SpellIntent::Buff));
        self::assertFalse(DepositLaw::multipliesWithAllies(SpellIntent::Damage));
        self::assertFalse(DepositLaw::multipliesWithAllies(SpellIntent::Hinder));
    }

    /**
     * Le garde-fou : aucun role n'est necessaire.
     *
     * Un groupe sans entretien met plus de tours et perd plus de PV ; il ne
     * rencontre pas un mur. La propriete qui le garantit est que **la valeur
     * par cible reste modeste** — c'est la multiplication par les corps qui
     * fait la valeur d'un depot, jamais le chiffre affiche. Ce test le tient
     * par la seule chose qui soit vraie a toute echelle : un tour depose ne
     * rend jamais, **par cible**, plus qu'un tour direct.
     */
    public function testNoRoleIsRequiredBecauseThePerTargetValueStaysModest(): void
    {
        $directHeal = 40;

        // Le meme geste depose : sa valeur totale ne change pas, elle s'etale.
        foreach ([2, 4, 6, 10] as $duration) {
            $perTurn = DepositLaw::spreadPerTurn($directHeal, $duration);

            self::assertLessThanOrEqual(
                $directHeal,
                $perTurn * $duration,
                'Un depot rend, par cible, plus qu\'un soin direct du meme geste.'
            );
        }
    }
}
