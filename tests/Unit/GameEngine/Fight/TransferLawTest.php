<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\GestureForm;
use App\GameEngine\Fight\DepositLaw;
use App\GameEngine\Fight\TransferLaw;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'un protecteur prend a la place des siens (ARC-18d).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 3. Trois choses sont verifiees ici, et la
 * premiere est celle qu'on rate : **le transfert deplace, il ne reduit pas**.
 */
class TransferLawTest extends TestCase
{
    /**
     * **Le total du coup ne bouge jamais.**.
     *
     * *L'aggro ne reduit rien, elle deplace* (§ 13.4) : le total des degats
     * d'une rencontre est fixe par sa duree, et tout l'interet du transfert est
     * de les concentrer sur celui qui est equipe pour les recevoir. Une
     * implementation qui perdrait des points en chemin ferait du transfert une
     * reduction de degats deguisee — la faute la plus facile a commettre sur
     * cette forme, et la seule que le canon lui interdise explicitement.
     */
    public function testItMovesDamageWithoutEverReducingIt(): void
    {
        foreach ([1, 7, 26, 100, 999] as $damage) {
            foreach ([[], [0.5], [0.25, 0.25], [0.5, 0.5, 0.5]] as $shares) {
                self::assertSame(
                    $damage,
                    TransferLaw::redirected($damage, $shares) + TransferLaw::borneBy($damage, $shares),
                    sprintf('%d degats se perdent en chemin.', $damage)
                );
            }
        }
    }

    /**
     * **Ce qui est transfere ne peut pas l'etre deux fois.**.
     *
     * Sans l'anti-empilement, la borne en pourcentage serait un plafond **par
     * personne** et non par coup : deux protecteurs a 50 % annuleraient les
     * degats, et la borne s'annulerait elle-meme des qu'un groupe compte deux
     * encaisses.
     */
    public function testTwoProtectorsNeverCancelTheBlow(): void
    {
        self::assertSame(50, TransferLaw::redirected(100, [0.5, 0.5]));
        self::assertSame(50, TransferLaw::borneBy(100, [0.5, 0.5]));

        self::assertSame(50, TransferLaw::redirected(100, [0.5, 0.5, 0.5, 0.5]));
        self::assertGreaterThan(0, TransferLaw::borneBy(100, [0.5, 0.5, 0.5, 0.5]), 'Un allie est devenu invulnerable.');
    }

    /**
     * La part est **la moitie**, et elle se derive plutot que de se choisir.
     *
     * Le § 13.4 borne deja le deplacement de menace a « au plus la moitie », et
     * le transfert est ce meme deplacement sous un autre nom : lui donner une
     * autre valeur ferait exister deux bornes pour une seule question.
     */
    public function testAShareIsNeverMoreThanHalf(): void
    {
        self::assertSame(0.5, TransferLaw::MAX_SHARE);
        self::assertSame(0.5, TransferLaw::shareFor(0.9));
        self::assertSame(0.3, TransferLaw::shareFor(0.3));
        self::assertSame(0.0, TransferLaw::shareFor(-1.0));

        self::assertSame(50, TransferLaw::redirected(100, [1.0]));
    }

    /**
     * La duree est la seconde borne, et c'est la meme que celle d'un depot.
     *
     * Un transfert d'un tour n'a rien depose — il a **reagi** —, et dans un
     * donjon semi-synchrone ou le tour d'un absent se resout tout seul, reagir
     * est precisement ce qu'on ne peut pas faire.
     */
    public function testATransferLastsAtLeastAsLongAsADeposit(): void
    {
        self::assertSame(DepositLaw::MIN_DURATION, TransferLaw::MIN_DURATION);
        self::assertSame(2, TransferLaw::durationFor(1));
        self::assertSame(2, TransferLaw::durationFor(0));
        self::assertSame(5, TransferLaw::durationFor(5));
    }

    /**
     * **Un protecteur tombe ne protege plus.**.
     *
     * Sans cette regle, un mort continuerait d'encaisser pour les vivants, ce
     * qui rendrait le groupe **plus** solide apres une perte qu'avant.
     */
    public function testTheFallenProtectNobody(): void
    {
        self::assertTrue(TransferLaw::stillProtects(12, 3));
        self::assertFalse(TransferLaw::stillProtects(0, 3));
        self::assertFalse(TransferLaw::stillProtects(12, 0));
    }

    public function testTheFormIsDeclaredReadable(): void
    {
        self::assertTrue(GestureForm::Transfer->isImplemented());
    }
}
