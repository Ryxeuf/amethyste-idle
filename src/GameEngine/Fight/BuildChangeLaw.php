<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;

/**
 * Le build se change hors combat, jamais dedans (ARC-18i).
 *
 * GAME_ARCHETYPES § 13.5, le quatrieme des sept refus : ***le changement d'arme
 * en combat*** (GW2, ESO). Ce n'est pas une preference de style — il contredit
 * DOM-02, et c'est ce qui rend la double borne honnete :
 *
 * > *L'equipement **est** le build* (GAME_DOMAINS § 3). Les passifs d'arbre ne
 * > s'expriment que si le personnage porte ce qui les qualifie, et les passifs
 * > conditionnels d'ARC-12 valent x1,4 **parce que la condition se decide a
 * > l'inventaire**.
 *
 * Autoriser le changement en combat effondre les deux d'un coup : un joueur
 * porterait la dague pour le geste qui aime la dague, puis la hache au tour
 * suivant, et *chaque condition serait vraie tout le temps* — le multiplicateur
 * cesserait d'etre paye et la borne materielle ne bornerait plus rien.
 *
 * ## La loi vit ici, et pas dans cinq controleurs
 *
 * Cinq chemins changent l'equipement — equiper, deequiper, sertir, dessertir,
 * modifier. **Aucun ne verifiait le combat**, quand `UseItemController` le
 * verifiait depuis toujours : la regle etait tenue a un endroit sur six, ce qui
 * est la facon habituelle dont une regle recopiee disparait. Un point de
 * decision unique — la discipline d'`EmailVerificationGate` (ONB-04) — la rend
 * opposable, et le test qui l'accompagne compte les appelants plutot que de
 * relire les fichiers.
 */
final class BuildChangeLaw
{
    /**
     * Ce joueur peut-il changer son build maintenant ?
     */
    public static function isAllowed(?Player $player): bool
    {
        // Sans joueur, il n'y a pas de combat en cours — donc rien a refuser.
        // Rendre `true` plutot que lever laisse chaque appelant traiter
        // l'absence de joueur avec ses propres mots : *une loi de jeu ne doit
        // pas devenir un chemin d'erreur technique.*
        return $player === null || $player->getFight() === null;
    }

    /**
     * Ce qu'on dit au joueur quand il ne peut pas.
     *
     * Le message dit **ou** se change un build plutot que de se contenter de
     * refuser : *un refus qui n'indique pas la sortie se lit comme une panne.*
     */
    public static function refusal(): string
    {
        return 'Vous ne pouvez pas changer votre équipement pendant un combat.';
    }
}
