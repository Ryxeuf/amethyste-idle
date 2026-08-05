<?php

namespace App\GameEngine\Balance;

use App\Enum\DomainRole;

/**
 * Le palier des accords suit la fonction (ARC-05b, correction 14).
 *
 * GAME_ARCHETYPES § 9 sexies a mesure ce qu'aucun exercice isole ne pouvait
 * voir : **le guerrier tue aussi vite que l'hydromancien**, survit mieux que
 * tout le monde et ne paie rien. Trois causes ; celle-ci se corrige sans
 * toucher a un seul levier.
 *
 * > **L'assaut ouvre ses gestes de degat au palier plein ; controle, entretien
 * > et encaisse les ouvrent un palier en dessous (≈ −25 %).** Une fois de plus,
 * > la difference passe par les **gestes**, pas par les pourcentages.
 *
 * Mesure du canon, a leviers strictement inchanges :
 *
 * | Build | Avant | Apres |
 * |---|---|---|
 * | Soldat — la Ligne mobile | 9 tours, 78 PV | **11 tours, 55 PV** |
 * | Guerisseur — le Ressac | 11 tours, 76 PV | **14 tours, 47 PV** |
 * | Archer — le Guet | 7 tours, 38 PV | 7 tours, 38 PV |
 * | Pyromancien — l'Eclat | 8 tours, 11 PV | 8 tours, 11 PV |
 *
 * L'ecart devient lisible — 7 tours contre 11 a 14 — et c'est ce qui donne a
 * l'assaut sa raison d'exister, une fois la vitesse dotee d'une valeur par les
 * rencontres a fenetre (§ 9 sexies.4, tranche le 2026-08-02).
 *
 * **Ce que la regle ne fait pas** : elle ne retire aucun geste a personne. Un
 * arbre de controle ouvre les memes accords, au meme endroit de son calendrier
 * — il les ouvre a un palier de materia en dessous. La chaine
 * competence → materia → sort (GAME_MATERIA) reste intacte : c'est le
 * `unlock` qui vise `m2` au lieu de `m3`, rien d'autre.
 *
 * Le premier palier n'est jamais rabote : un arbre dont l'accord d'entree
 * n'ouvrirait rien ne se joue pas au jour 1, ce que la regle du jour 1
 * (GAME_MATERIA § 3) interdit.
 */
final class AccordTierRule
{
    /** Le palier de materia le plus bas qu'un accord puisse ouvrir. */
    public const FLOOR = 1;

    /**
     * De combien de paliers la fonction decale ses accords de degat.
     *
     * Un cran, et un seul : deux crans mettraient un arbre d'encaisse de palier
     * 3 sur des gestes de debut de jeu, ce qui n'est plus « payer autrement »
     * mais ne plus jouer.
     */
    public const STEP_DOWN = 1;

    /**
     * Le palier auquel cette fonction ouvre un accord ecrit pour ce palier
     * d'arbre.
     */
    public static function tierFor(int $treeTier, DomainRole $role): int
    {
        if (self::opensAtFullTier($role)) {
            return max(self::FLOOR, $treeTier);
        }

        return max(self::FLOOR, $treeTier - self::STEP_DOWN);
    }

    /**
     * Seul l'assaut ouvre au palier plein.
     *
     * C'est **sa** contrepartie : il paie en fragilite et en ressource ce que
     * les trois autres paient en tours (§ 9 septies). Rendre le palier plein a
     * une seconde fonction reviendrait a lui donner la vitesse en plus de ce
     * qu'elle a deja.
     */
    public static function opensAtFullTier(DomainRole $role): bool
    {
        return $role === DomainRole::Assault;
    }
}
