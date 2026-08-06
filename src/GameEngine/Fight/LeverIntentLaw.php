<?php

namespace App\GameEngine\Fight;

use App\Enum\CombatLever;
use App\Enum\SpellIntent;

/**
 * Quels leviers un geste qualifie (ARC-11b-b).
 *
 * GAME_ARCHETYPES § 3.1 : *« avec 15 leviers, il faut dire a quoi chacun
 * s'applique. `intent` le dit, **une fois, sur le geste** — pas quinze fois
 * dans quinze formules. »* `mending` ne touche que le soin, `grip` que
 * l'entrave.
 *
 * **La loi existait deja, mais elle n'etait ecrite nulle part.** Le moteur la
 * portait *implicitement*, eparpillee dans quinze positions de formule :
 * `applyPower()` ne lit que les degats, `applyMending()` que le soin,
 * `applyGuard()` n'est appele que sur un coup recu. Tant que la borne est un
 * effet de bord de l'endroit ou le code appelle, elle n'est pas opposable — et
 * elle **fuit** des qu'un chemin nouveau lit un levier ailleurs. C'est
 * exactement ce qui est arrive a `grip` : il porte la duree de **tout** statut
 * applique, y compris un bouclier ou une regeneration, alors que le canon le
 * reserve a l'entrave. Un arbre d'entretien pouvait donc acheter 10 pb de
 * `grip` en teinte et rallonger ses propres protections — le levier principal
 * du controle, revendu a une autre fonction.
 *
 * **Le critere est celui d'ARC-03, lu dans l'autre sens.** Un levier occupe une
 * place dans la formule qu'aucun autre n'occupe ; il qualifie donc un geste
 * exactement quand ce geste **exerce cette place**. Un soin n'exerce ni la
 * resistance elementaire (`pierce`), ni la garde de sa cible (`guard`) : ces
 * leviers n'ont rien a y faire, et le dire ici plutot que dans huit fichiers
 * fait qu'un neuvieme appelant ne pourra pas l'oublier.
 *
 * **Et quand la formule exerce une place que le canon n'autorise pas, le canon
 * gagne.** Deux cas, tous deux anterieurs a ce jalon : le moteur applique
 * `grip` a **tout** statut, la ou le § 3.1 le reserve a l'entrave ; et il tire
 * un jet de touche sur un soin comme sur un coup, la ou la precision n'a de
 * sens que face a quelqu'un qui ne veut pas de ce qu'on lui envoie. Suivre la
 * formule reviendrait a laisser une habitude de code decider d'un axe de
 * conception. Le troisieme cas n'en est pas un : le critique porte sur le soin
 * comme sur le degat dans le moteur **et** dans le canon, qui n'en restreint
 * pas la cible — on ne le separe donc pas.
 *
 * **Cinq leviers ne sont pas des proprietes du geste** et qualifient donc tout
 * : `life` et `recovery` (le canon les declare deja hors double borne, § 4.2 —
 * une barre de vie ne change pas selon le geste qu'on choisit), `tempo`
 * (l'ordre du tour precede le geste), `wind` (la ressource revient par tour,
 * pas par intention) et `thrift` (**tout** geste coute la ressource de son
 * registre, et le canon ne connait aucune exception par intention —
 * GAME_MATERIA § 2.3 bis).
 *
 * La table est un `match` sur l'enum plutot qu'un tableau : les quinze leviers
 * y sont exhaustifs par construction, et un seizieme ne pourrait pas naitre
 * sans que sa place dans la loi soit decidee.
 */
final class LeverIntentLaw
{
    /**
     * Ce geste qualifie-t-il ce levier ?
     *
     * **Une intention inconnue ne borne rien.** Un geste qui ne dit pas ce
     * qu'il fait ne peut pas viser : on le laisse a la borne qu'il avait avant
     * ce jalon — sa place dans la formule. Refuser par defaut rendrait un
     * passif silencieusement inactif, et un bonus mort se lit comme un choix de
     * build (le defaut qu'ARC-12b a corrige sur les conditions).
     *
     * Ce repli est **mesure plutot que suppose** : aucun des 253 gestes livres
     * n'a d'intention illisible, et un test l'ecrit noir sur blanc. La branche
     * existe pour un geste a venir, pas pour ceux qui sont la.
     */
    public static function qualifies(CombatLever $lever, ?SpellIntent $intent): bool
    {
        if ($intent === null) {
            return true;
        }

        $qualified = self::qualifiedIntents($lever);

        return $qualified === null || \in_array($intent, $qualified, true);
    }

    /**
     * Ce levier depend-il de ce que le geste fait ?
     *
     * Sert aux tests et aux ecrans : un levier qui ne vise pas se lit sur la
     * fiche du personnage, un levier qui vise se lit sur le geste.
     */
    public static function aims(CombatLever $lever): bool
    {
        return self::qualifiedIntents($lever) !== null;
    }

    /**
     * Les intentions qu'un levier qualifie, ou toutes s'il ne vise pas.
     *
     * @return list<SpellIntent>
     */
    public static function intentsOf(CombatLever $lever): array
    {
        return self::qualifiedIntents($lever) ?? SpellIntent::cases();
    }

    /**
     * `null` veut dire « toutes les intentions » — pas « aucune ».
     *
     * @return list<SpellIntent>|null
     */
    private static function qualifiedIntents(CombatLever $lever): ?array
    {
        return match ($lever) {
            // La valeur de base du geste : chacun sur la sienne.
            CombatLever::Power => [SpellIntent::Damage],
            CombatLever::Mending => [SpellIntent::Heal],

            // Le critique se joue sur les deux, parce que le moteur le tire sur
            // les deux : `CriticalCalculator` multiplie le soin comme le degat.
            // Les separer ferait dire a `critical` et `critical_power` deux
            // choses differentes de ce qu'est un critique — le genre d'ecart
            // qui donne un chiffre plausible et faux.
            CombatLever::Critical,
            CombatLever::CriticalPower => [SpellIntent::Damage, SpellIntent::Heal],

            // On ne rate pas ce qu'on offre : le jet de touche n'existe que face
            // a quelqu'un qui ne veut pas de ce qu'on lui envoie.
            CombatLever::Hit => [SpellIntent::Damage, SpellIntent::Hinder],

            // La resistance elementaire et les deux reductions de la cible ne se
            // rencontrent que sur un coup.
            CombatLever::Pierce,
            CombatLever::Guard,
            CombatLever::Dodge => [SpellIntent::Damage],

            // Le jet d'application, et la lettre du § 3.1 : `grip` que
            // l'entrave. `ward` lui repond de l'autre cote du meme jet — lui
            // laisser resister a un soin ou a un bouclier ferait d'un levier
            // defensif une punition.
            CombatLever::Grip,
            CombatLever::Ward => [SpellIntent::Hinder],

            // Les cinq qui ne sont pas des proprietes du geste.
            CombatLever::Thrift,
            CombatLever::Wind,
            CombatLever::Life,
            CombatLever::Recovery,
            CombatLever::Tempo => null,
        };
    }
}
