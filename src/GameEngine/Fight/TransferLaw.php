<?php

namespace App\GameEngine\Fight;

/**
 * Ce qu'un protecteur prend a la place des siens (ARC-18d).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 3 : *une part des degats des allies vous
 * revient*. Elle repare le defaut le plus structurel des huit — ***notre modele
 * ne peut pas avoir d'aggro***. La rencontre frappe le joueur qui vient
 * d'agir ; il n'y a personne a provoquer, donc l'encaisse perd son geste
 * identitaire et un groupe sans tank se joue exactement comme un groupe avec.
 *
 * Le transfert le lui rend **sans table de menace** : on ne demande pas au
 * monstre qui il vise, on decide qui paie. *L'aggro ne reduit rien, elle
 * deplace* (§ 13.4) — le total des degats d'une rencontre est fixe par sa
 * duree, et tout l'interet est de les concentrer sur celui qui est equipe pour
 * les recevoir.
 *
 * ## Les deux bornes, et pourquoi elles sont deux
 *
 * Le canon les nomme ensemble : **borne en pourcentage et en duree** — *sinon
 * le tank meurt pour les autres, ce qui est un beau geste et un mauvais jeu*.
 * Elles ne disent pas la meme chose, et l'une sans l'autre ne suffit pas :
 *
 * - **la part** empeche qu'un allie devienne invulnerable. Sans elle, la
 *   parade optimale d'un groupe serait un protecteur permanent, et le reste du
 *   groupe cesserait d'avoir une barre de vie ;
 * - **la duree** empeche que le protecteur paie tout le combat. Sans elle, le
 *   transfert ne serait pas un geste mais un etat, et l'encaisse jouerait un
 *   tour au debut de la rencontre puis regarderait.
 *
 * La part est **la moitie**, et elle se derive plutot que de se choisir : le
 * § 13.4 borne deja le deplacement de menace a *« au plus la moitie »*, et le
 * transfert est ce meme deplacement sous un autre nom. Lui donner une autre
 * valeur ferait exister deux bornes pour une seule question.
 *
 * ## L'anti-empilement
 *
 * ***Ce qui est transfere ne peut pas l'etre deux fois.*** Deux protecteurs a
 * 50 % chacun ne retirent pas 100 % des degats a leur allie : la part **totale**
 * prise a un allie reste bornee, et les protecteurs se la partagent. Sans cette
 * regle, la borne en pourcentage serait un plafond par personne et non par
 * coup, donc empiler les protecteurs annulerait les degats — la borne
 * s'annulerait elle-meme des qu'un groupe compte deux encaisses.
 */
final class TransferLaw
{
    /**
     * La part maximale des degats d'un allie qu'un protecteur peut prendre.
     *
     * Derivee du § 13.4 : *un geste de menace en deplace au plus la moitie*.
     */
    public const MAX_SHARE = 0.5;

    /**
     * La duree minimale d'un transfert, en tours de rencontre.
     *
     * La meme que celle d'un depot (`DepositLaw::MIN_DURATION`), et pour la
     * meme raison arithmetique : un transfert qui ne dure que le tour ou il est
     * joue n'a rien depose — il a **reagi** —, et dans un donjon semi-synchrone
     * ou le tour d'un absent se resout tout seul, reagir est precisement ce
     * qu'on ne peut pas faire.
     */
    public const MIN_DURATION = DepositLaw::MIN_DURATION;

    /**
     * La part opposable a un transfert : jamais plus que la moitie.
     */
    public static function shareFor(float $declared): float
    {
        return min(self::MAX_SHARE, max(0.0, $declared));
    }

    /**
     * La duree opposable a un transfert : jamais moins de deux tours.
     */
    public static function durationFor(int $declared): int
    {
        return max(self::MIN_DURATION, $declared);
    }

    /**
     * Ce que les protecteurs prennent, tous ensemble, sur ce coup.
     *
     * La liste des parts est **plafonnee dans son total** avant d'etre
     * appliquee : c'est ici que vit l'anti-empilement, et l'ecrire au moment du
     * coup plutot qu'au moment de la pose est ce qui le rend impossible a
     * contourner — deux gestes poses separement ne peuvent pas savoir l'un de
     * l'autre.
     *
     * @param list<float> $shares les parts des protecteurs actifs
     */
    public static function redirected(int $damage, array $shares): int
    {
        if ($damage <= 0 || $shares === []) {
            return 0;
        }

        $total = 0.0;
        foreach ($shares as $share) {
            $total += self::shareFor($share);
        }

        return (int) floor($damage * min(self::MAX_SHARE, $total));
    }

    /**
     * Ce qui reste a l'allie, une fois les protecteurs servis.
     *
     * Rendu plutot que calcule par soustraction chez l'appelant : la somme des
     * deux **doit** valoir le coup d'origine, et *un total qui se perd en
     * chemin ferait du transfert une reduction de degats* — exactement ce que
     * le canon lui interdit d'etre.
     *
     * @param list<float> $shares
     */
    public static function borneBy(int $damage, array $shares): int
    {
        return max(0, $damage - self::redirected($damage, $shares));
    }

    /**
     * Un protecteur tombe ne protege plus.
     *
     * Ce n'est pas un garde-fou de confort : sans lui, un mort continuerait
     * d'encaisser pour les vivants, ce qui rendrait le groupe **plus** solide
     * apres une perte qu'avant.
     */
    public static function stillProtects(int $protectorLife, int $remainingTurns): bool
    {
        return $protectorLife > 0 && $remainingTurns > 0;
    }
}
