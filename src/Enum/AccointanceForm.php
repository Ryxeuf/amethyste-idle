<?php

namespace App\Enum;

/**
 * Ce qu'une accointance a le droit de faire (ARC-16).
 *
 * GAME_ARCHETYPES § 9.7, decision 15. Les synergies inter-domaines livrees
 * donnaient des **statistiques plates** — « Forge ardente » rendait `damage
 * +10`, « Purification » `heal +15` —, ajoutees dans `CombatSkillResolver`
 * **hors de tout arbre** : hors des 50 points de budget, hors des plafonds par
 * levier, hors des palettes de fonction.
 *
 * > *Un systeme qui compte soigneusement 50 points et laisse une porte de
 * > service a +10 ne compte rien.*
 *
 * D'ou la decision que cette enumeration rend opposable :
 *
 * > **Une accointance ne donne jamais de puissance. Elle donne de la souplesse.**
 *
 * ## Pourquoi une liste fermee
 *
 * La meme raison que `CombatLever` et `SkillCostScale` : une forme qu'on peut
 * inventer au fil des donnees finit par contenir « et aussi un petit bonus ».
 * Les quatre formes ci-dessous sont celles du canon, et le chargeur **refuse**
 * ce qui n'en est pas — un refus a la lecture vaut mieux qu'une revue de code.
 *
 * ## Ce qui distingue les quatre
 *
 * Toutes **elargissent ce qui compte** ou **reduisent ce qu'on paie** ; aucune
 * n'augmente ce qu'on obtient. C'est le critere d'admission, et il se verifie
 * en une phrase : *si l'effet se mesure en degats, en soin, en PV ou en points
 * de budget, ce n'est pas une accointance.*
 */
enum AccointanceForm: string
{
    /**
     * Elargir ce qui satisfait une condition de passif.
     *
     * *Soldat + Vagabond — « Pied sur » : les passifs conditionnes « en cuir »
     * sont aussi satisfaits par la maille.*
     *
     * **Sans lecteur a ce jour** : `SkillCondition` est analysee, valorisee et
     * affichee, mais **jamais confrontee a un equipement reel** — aucun service
     * ne repond « ce joueur porte-t-il une dague ? ». Elargir ce que personne
     * n'evalue n'aurait aucun effet ; la forme est declaree, son branchement
     * attend qu'il existe quelque chose a elargir.
     */
    case ConditionWidening = 'condition_widening';

    /**
     * Elargir ce qui exprime un domaine (DOM-02).
     *
     * *Pyromancien + Artificier — « Poudre » : une munition de feu compte comme
     * source du domaine de pyromancie — le pyromancien s'exprime en tirant.*
     *
     * C'est la seule forme dont le lecteur existe (`BuildDomainResolver`), et
     * c'est aussi la plus generale : **ce qu'on porte pour l'un parle aussi
     * pour l'autre**. Elle ne rend pas un passif plus fort, elle lui permet de
     * s'exprimer dans une tenue qui, sans elle, l'aurait tu.
     */
    case DomainExpression = 'domain_expression';

    /**
     * Elargir ce qu'un emplacement de materia accepte.
     *
     * *Guerisseur + Pretre — « Liturgie » : un emplacement de sort accepte une
     * materia de l'element voisin.*
     */
    case SlotAcceptance = 'slot_acceptance';

    /**
     * Reduire un cout d'acces — jamais un cout de combat.
     *
     * *Archer + Charpentier — « Fut droit » : l'echelon 3 de port de l'arc
     * coute un palier de moins.*
     *
     * La borne qui la garde honnete : elle porte sur un **acces** (un echelon,
     * une porte), jamais sur une ressource de combat. Reduire un cout en PM ou
     * une reprise serait rendre de la puissance par la bande, et le levier
     * `thrift` existe pour cela — dans un arbre, sous plafond.
     */
    case AccessDiscount = 'access_discount';

    /**
     * Le lecteur de cette forme existe-t-il ?
     *
     * Ce que ce drapeau evite : declarer en donnees une accointance qui ne
     * ferait **rien** sans que rien ne le dise. Une accointance inerte n'est
     * pas fausse — le canon veut qu'aucune ne soit necessaire —, mais elle est
     * un mensonge d'interface si on la laisse s'ecrire sans le savoir.
     */
    public function hasReader(): bool
    {
        return $this === self::DomainExpression;
    }
}
