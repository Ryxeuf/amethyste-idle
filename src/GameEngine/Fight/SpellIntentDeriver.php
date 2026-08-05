<?php

namespace App\GameEngine\Fight;

use App\Enum\SpellIntent;
use App\Enum\SpellScope;

/**
 * L'intention et la portee d'un geste, derivees de ce qu'il fait deja (ARC-11a).
 *
 * GAME_ARCHETYPES § 3.1 ajoute deux etiquettes au geste. Elles pourraient
 * s'ecrire 253 fois a la main ; ce serait 253 occasions de se tromper, et le
 * depot fait deja l'inverse partout ailleurs — la materia se **derive** du sort
 * qu'elle porte (MAT-02), les stats d'un monstre de sa case `tier x rank`
 * (BES-02), la cible d'un geste du gabarit du bestiaire (ARC-05a).
 *
 * **Ce qui rend la derivation possible ici** : les huit types de `StatusEffect`
 * se rangent sans reste dans les cinq intentions (`SpellIntent::from
 * StatusEffectType()`), et `damage` / `heal` / `aoeTargets` disent le reste. On
 * ne devine pas — on lit ce que la donnee declare deja.
 *
 * **Ce qui reste declaratif** : la portee `Group`. Aucun geste livre ne la
 * porte, et aucune colonne ne pourrait la faire apparaitre — c'est une decision
 * d'auteur, pas une propriete des chiffres. ARC-11b l'introduit avec la duree
 * qui l'accompagne. La derivation ne l'invente donc jamais : elle rend au plus
 * `Ally`, et un auteur qui veut le groupe l'ecrit.
 *
 * Classe **pure** : elle prend des valeurs, pas des entites, pour que le type
 * de l'effet de statut soit resolu par l'appelant et que la regle se teste sans
 * base de donnees.
 */
final class SpellIntentDeriver
{
    /**
     * Ce que le geste fait.
     *
     * L'ordre des questions est la regle, et il compte :
     *
     *  1. **Le degat d'abord.** Un geste qui blesse *et* applique une marque
     *     reste un geste de degat — le canon (§ 1.1, correction du § 9
     *     quinquies) veut precisement qu'une marque soit portee par un geste de
     *     degat, sans quoi une entrave d'un tour serait nulle en duel.
     *  2. **Puis le soin**, qui ne se confond avec rien.
     *  3. **Puis l'effet de statut**, qui dit le reste — protection,
     *     amelioration, entrave.
     *
     * Un geste qui ne fait aucun des trois rend `null` : il n'a pas
     * d'intention lisible, et lui en attribuer une par defaut ferait entrer un
     * geste muet dans une palette.
     */
    public static function deriveIntent(?int $damage, ?int $heal, ?string $statusEffectType): ?SpellIntent
    {
        if (null !== $damage && $damage > 0) {
            return SpellIntent::Damage;
        }

        if (null !== $heal && $heal > 0) {
            return SpellIntent::Heal;
        }

        if (null !== $statusEffectType) {
            return SpellIntent::fromStatusEffectType($statusEffectType);
        }

        return null;
    }

    /**
     * Qui le geste touche.
     *
     * La portee se lit de l'intention et du nombre de cibles : une intention
     * hostile vise un adversaire — un seul, ou plusieurs si le geste declare
     * une aire —, une intention amicale vise un allie. **`Group` n'est jamais
     * derive** (voir l'en-tete) : c'est une decision d'auteur.
     *
     * `Ally` plutot que `SelfOnly` pour les gestes amicaux : un soin qui ne
     * pourrait viser que son lanceur serait un cas particulier, quand l'inverse
     * — viser un allie, soi compris — est le cas general.
     */
    public static function deriveScope(?SpellIntent $intent, int $aoeTargets): ?SpellScope
    {
        if (null === $intent) {
            return null;
        }

        if (!$intent->isHostile()) {
            return SpellScope::Ally;
        }

        return $aoeTargets > 1 ? SpellScope::Targets : SpellScope::Target;
    }
}
