<?php

namespace App\GameEngine\Fight;

use App\Entity\Game\StatusEffect;
use App\Enum\SpellScope;

/**
 * Ce qu'une posture est, et ce qu'elle n'a pas le droit d'etre (ARC-18b).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 7 : *un choix durable qu'on remplace*.
 * Elle repare un defaut nomme au § 6.1 bis — **aucun choix a l'echelle d'une
 * rencontre** : la fourche se decide une fois pour la vie du personnage, les
 * leviers ne bougent jamais, et un combat se joue donc exactement comme le
 * precedent. La posture est *la meme decision que la fourche, ramenee a la
 * rencontre*, et c'est cette phrase qui dicte toute la loi ci-dessous.
 *
 * ## Elle deplace le budget, elle ne l'ajoute pas
 *
 * ***C'est la regle qui empeche la posture d'etre un bouton.*** Le canon borne
 * la forme par « en changer coute le tour », mais **le tour ne suffit pas** :
 * une posture qui donnerait `+9 % de degats` sans rien retirer se paierait un
 * tour et rapporterait dix tours de bonus sur une rencontre ordinaire — le
 * meilleur rapport du jeu, et un geste qu'on ne se pose meme pas la question de
 * jouer. Le tour borne la **frequence** des changements, jamais la **valeur**
 * de ce qu'on prend.
 *
 * La borne de valeur se derive de la phrase du canon plutot que de s'inventer :
 * une **fourche** repartit les 50 points de budget de l'arbre entre deux
 * branches — elle n'en cree aucun. Une posture, qui est la fourche a l'echelle
 * de la rencontre, **repartit donc elle aussi** : ce qu'elle donne sur un
 * levier, elle le retire sur un autre. `isBalanced()` est cette regle, et elle
 * est ce qui fait d'une posture une **decision** — on ne choisit pas entre
 * « rien » et « mieux », on choisit ce qu'on accepte de perdre.
 *
 * ## Elle s'ecrit en leviers, jamais en `statModifier`
 *
 * Le champ `StatusEffect::statModifier` existe et serait le rangement evident.
 * Il est refuse pour la raison d'ARC-16a : son vocabulaire est **ouvert** — les
 * fixtures livrees y ecrivent `damage`, `speed`, `defense`, `shield_absorb`,
 * `max_life`, et aussi quatre noms de leviers —, et *un systeme qui compte
 * soigneusement 50 points de budget et laisse a cote un champ ou l'on ecrit
 * n'importe quel chiffre ne compte rien*. Une posture s'exprime dans la seule
 * unite que le jeu sait equilibrer : `CombatLever`, en points de budget.
 *
 * ## Elle n'a pas de duree
 *
 * Une posture ne se decompte pas. Elle finit de deux facons, et **les deux sont
 * des evenements, jamais un compteur** : on en pose une autre, ou la rencontre
 * s'acheve. Lui donner une duree en ferait une amelioration ordinaire — et
 * surtout, la decision qu'elle porte cesserait d'etre une decision : il
 * suffirait d'attendre.
 *
 * C'est aussi ce qui la distingue du depot (ARC-11b), dont la duree **etale**
 * une valeur totale. Une posture n'etale rien : elle ne vaut pas plus longtemps
 * parce qu'on la garde, elle vaut la meme chose a chaque tour ou elle est la.
 */
final class StanceLaw
{
    /**
     * Ce qu'on inscrit dans `remainingTurns` d'une posture posee.
     *
     * Le moteur lit l'expiration comme `remainingTurns <= 0` ; une posture doit
     * donc rester **strictement positive** pour ne jamais expirer, et
     * `holdsThroughTheTurn()` garantit qu'aucun tour ne la fait descendre. Le
     * chiffre n'est pas une duree : c'est *« elle est la »*.
     */
    public const HELD = 1;

    /**
     * Ce statut est-il une posture ?
     */
    public static function isStance(StatusEffect $effect): bool
    {
        return $effect->getType() === StatusEffect::TYPE_STANCE;
    }

    /**
     * Une posture traverse le tour sans etre decomptee.
     *
     * Rendu ici plutot qu'ecrit dans `StatusEffectManager` pour que la question
     * *« cet effet vieillit-il ? »* ait **une seule** reponse : le jour ou une
     * seconde forme sans duree existe, elle se pose ici et le moteur ne bouge
     * pas.
     */
    public static function holdsThroughTheTurn(StatusEffect $effect): bool
    {
        return self::isStance($effect);
    }

    /**
     * Une posture ne se pose que sur soi.
     *
     * Le canon l'ecrit dans la forme technique (*un depot `scope: soi`*), et ce
     * n'est pas une convenance : une posture posee sur un allie serait une
     * amelioration de groupe, c'est-a-dire un **depot**, qui a deja sa loi et sa
     * borne. Deux formes qui occupent la meme place sont une seule forme sous
     * deux noms — le critere d'admission du § 13.1.
     */
    public static function scopeIsLegal(?SpellScope $scope): bool
    {
        return $scope === SpellScope::SelfOnly;
    }

    /**
     * La posture deplace le budget : elle ne le cree pas.
     *
     * Somme algebrique des points, sur **tous** les leviers qu'elle touche. Un
     * total strictement positif est une posture qui donne sans rien reprendre,
     * c'est-a-dire un bonus permanent achete au prix d'un tour.
     *
     * Le total peut etre **negatif** — une posture qui rend plus qu'elle ne
     * donne reste legale. Ce n'est pas un oubli : elle est alors simplement
     * mauvaise, et un mauvais choix n'a pas besoin d'etre interdit. La regle
     * ferme la porte au bouton gratuit, pas au sacrifice.
     *
     * @param array<string, int> $budgetPoints points de budget par levier
     */
    public static function isBalanced(array $budgetPoints): bool
    {
        return array_sum($budgetPoints) <= 0;
    }

    /**
     * Ce qu'une posture pese, tous leviers confondus.
     *
     * Ce que le joueur **prend**, sans compter ce qu'il rend : c'est ce chiffre
     * qui se compare a un nœud d'arbre, et non la somme algebrique. Une posture
     * a somme nulle qui deplacerait 40 points serait equilibree au sens de
     * `isBalanced()` et pourtant hors d'echelle — elle transformerait un
     * personnage en un autre le temps d'un tour.
     *
     * @param array<string, int> $budgetPoints points de budget par levier
     */
    public static function weightOf(array $budgetPoints): int
    {
        $taken = 0;
        foreach ($budgetPoints as $points) {
            if ($points > 0) {
                $taken += $points;
            }
        }

        return $taken;
    }
}
