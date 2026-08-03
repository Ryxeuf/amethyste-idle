<?php

namespace App\Enum;

/**
 * Le vocabulaire ferme des leviers de combat (ARC-03).
 *
 * GAME_ARCHETYPES § 4 : les cinq statistiques plates (`damage`, `heal`, `hit`,
 * `critical`, `life`) sont ineequilibrables — *`damage: +1` vaut +50 % sur un
 * geste a 2 degats et +8 % sur un geste a 12*. Elles cedent la place a quinze
 * leviers en **pourcentage**, chacun paye en points de budget.
 *
 * L'ensemble est **ferme** : ajouter un levier est une decision de moteur — une
 * place de plus dans une formule —, jamais une decision de contenu. Un arbre qui
 * « aurait besoin » d'un seizieme levier est un arbre mal concu.
 *
 * **Le critere d'admission**, et c'est lui qui fait tenir la liste : *un levier
 * occupe une place dans la formule qu'aucun autre n'occupe.* `dodge` (eviter
 * entierement, avant tout calcul) et `guard` (reduire, apres resistance) ne sont
 * pas deux dosages de la meme chose : l'un est binaire et volatil, l'autre
 * continu et fiable. C'est ce qui les rend tous deux necessaires — et ce qui
 * distingue une armure de cuir d'une armure de plaque autrement que par un
 * chiffre.
 *
 * **Aucun taux ni plafond ici.** Ils vivent dans `config/game/combat_levers.yaml`
 * : GAME_ARCHETYPES § 0.2 previent qu'aucun nombre du canon n'est une valeur de
 * jeu definitive, et le simulateur d'ARC-17 les rejouera. L'enum ne dit que
 * **quels leviers existent**.
 */
enum CombatLever: string
{
    /** Degats du geste. */
    case Power = 'power';

    /** Soin rendu. */
    case Mending = 'mending';

    /** Taux de critique. */
    case Critical = 'critical';

    /** Degats d'un critique. */
    case CriticalPower = 'critical_power';

    /** Precision. */
    case Hit = 'hit';

    /** Resistance elementaire ignoree. */
    case Pierce = 'pierce';

    /** Cout du geste en sa ressource. */
    case Thrift = 'thrift';

    /** Ressource rendue par tour. */
    case Wind = 'wind';

    /** Degats subis. */
    case Guard = 'guard';

    /** Chance d'eviter entierement. */
    case Dodge = 'dodge';

    /** PV maximum. */
    case Life = 'life';

    /** PV rendus en fin de tour. */
    case Recovery = 'recovery';

    /** Duree et intensite des statuts appliques. */
    case Grip = 'grip';

    /** Resistance a l'application d'un statut subi. */
    case Ward = 'ward';

    /** Initiative et ordre du tour. */
    case Tempo = 'tempo';

    /**
     * Libelle d'auteur, jamais affiche au joueur.
     *
     * Comme la fonction d'un domaine (ARC-01), un levier est une contrainte de
     * conception : l'ecran montre ce que le nœud fait, pas la mecanique qui le
     * borne. Pas de cle de traduction, donc, tant que rien ne l'affiche.
     */
    public function label(): string
    {
        return match ($this) {
            self::Power => 'Puissance',
            self::Mending => 'Guerison',
            self::Critical => 'Critique',
            self::CriticalPower => 'Force du critique',
            self::Hit => 'Precision',
            self::Pierce => 'Penetration',
            self::Thrift => 'Economie',
            self::Wind => 'Souffle',
            self::Guard => 'Garde',
            self::Dodge => 'Esquive',
            self::Life => 'Vie',
            self::Recovery => 'Regeneration',
            self::Grip => 'Emprise',
            self::Ward => 'Sauvegarde',
            self::Tempo => 'Tempo',
        };
    }
}
