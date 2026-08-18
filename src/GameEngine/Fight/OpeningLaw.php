<?php

namespace App\GameEngine\Fight;

/**
 * Le combat qui commence avant le combat (ARC-18g).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 5. Elle repare un defaut nomme au § 9
 * sexies : ***`tempo` — l'initiative — n'a aucun effet modelise***, et c'est un
 * levier decoratif dans deux palettes sur quatre. Une ouverture le rend
 * concret : un geste pose **depuis l'ecran de zone**, qui s'applique a la
 * rencontre suivante. *Le premier tour est joue avant que l'ennemi n'existe.*
 *
 * ## Le garde-fou du canon : de l'energie d'action, jamais un tour
 *
 * C'est ce qui l'empeche d'etre systematique, et la raison est economique
 * plutot que ludique : un geste qui coute un **tour** se paie dans la rencontre
 * ou on le joue, donc on le joue toujours si son effet depasse celui d'une
 * attaque. Un geste qui coute de l'**energie d'action** se paie sur la
 * **journee**, et il entre alors en concurrence avec *un combat de plus* — la
 * seule monnaie que le § 9 septies reconnaisse.
 *
 * D'ou la derivation du cout plutot que son ecriture : il vaut une **fraction
 * de ce que coute une chasse**, si bien que la question posee au joueur est
 * toujours la meme et toujours lisible — *combien d'ouvertures est-ce que
 * j'echange contre une rencontre ?* Ecrire un chiffre a la main l'aurait
 * decroche du jour ou la chasse changera de prix.
 *
 * ## Ce que le canon n'ecrit pas, et qui la borne vraiment
 *
 * **Une seule ouverture en attente.** Sans cette regle, la journee optimale
 * consisterait a en poser dix avant d'engager, et l'ouverture cesserait d'etre
 * une preparation pour devenir un **stock** — la meme derive que la charge
 * evite en mourant avec la rencontre. Une seconde ouverture **remplace** la
 * premiere plutot que de s'y ajouter : refuser serait plus surprenant
 * qu'utile, et l'energie deja depensee est perdue de toute facon.
 *
 * **La premiere rencontre la consomme, et elle seule.** Une ouverture qui
 * servirait a chaque combat serait un bonus permanent achete une fois.
 *
 * **Elle ne se pose pas en combat.** C'est ce qui la distingue d'un geste
 * ordinaire : posee pendant la rencontre, elle ne couterait plus rien du tout
 * — ni tour, ni presque energie — et deviendrait le geste le moins cher du jeu.
 */
final class OpeningLaw
{
    /**
     * Ce qu'une ouverture coute, rapporte au prix d'une chasse.
     *
     * Un tiers : trois ouvertures valent une rencontre. Assez cher pour que la
     * question se pose a chaque fois, assez peu pour qu'elle ne soit pas
     * toujours perdante.
     */
    public const HUNT_COST_SHARE = 1 / 3;

    /**
     * Le cout d'une ouverture, derive du cout d'une chasse.
     *
     * Plancher a 1 : une ouverture gratuite serait posee avant **chaque**
     * rencontre sans qu'on ait a y penser, ce qui est exactement l'inverse de
     * ce que le garde-fou cherche.
     */
    public static function costFor(int $huntCost): int
    {
        return max(1, (int) round($huntCost * self::HUNT_COST_SHARE));
    }

    /**
     * Combien d'ouvertures valent une rencontre.
     *
     * Rendu pour que la question du joueur soit **calculable** plutot que
     * decrite : c'est le seul arbitrage que la forme lui demande.
     */
    public static function openingsPerHunt(int $huntCost): int
    {
        $cost = self::costFor($huntCost);

        return $cost > 0 ? intdiv($huntCost, $cost) : 0;
    }

    /**
     * Cette ouverture peut-elle etre posee maintenant ?
     *
     * ***Jamais pendant une rencontre.*** Posee en combat, elle ne couterait ni
     * tour ni presque energie, et deviendrait le geste le moins cher du jeu.
     */
    public static function canBePlaced(bool $inFight, int $availableEnergy, int $huntCost): bool
    {
        return !$inFight && $availableEnergy >= self::costFor($huntCost);
    }

    /**
     * Ce qu'une ouverture rend, une fois la rencontre ouverte.
     *
     * La valeur est celle du geste, entiere — comme le differe (ARC-18f), et
     * pour la meme raison : *ce qu'on achete en preparant n'est pas de la
     * puissance, c'est un tour qui n'a coute aucun tour*.
     */
    public static function payload(int $declaredValue): int
    {
        return max(0, $declaredValue);
    }
}
