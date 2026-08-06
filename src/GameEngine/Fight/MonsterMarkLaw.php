<?php

namespace App\GameEngine\Fight;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;

/**
 * La marque, du cote du monstre (ARC-13b-b).
 *
 * GAME_ARCHETYPES § 1.1, correction du § 9 ter : *les marques se portent des
 * **deux** cotes.* ARC-13b-a a branche le cote joueur — un accord d'entree par
 * arbre applique la marque de son element. Le cote monstre restait vide, et
 * cela laissait **deux mecaniques sans objet** : `ward` (« resistance a
 * l'application d'un statut subi ») figure dans deux palettes sur quatre et
 * n'avait rien a quoi resister ; l'accord de dissipation du Guerisseur n'avait
 * rien a dissiper. *Une marque qui n'existe que dans un sens est un levier mort
 * pour la moitie des fonctions.*
 *
 * > **Un joueur porte son element dans ses gestes ; un monstre le porte dans sa
 * > peau.**
 *
 * L'asymetrie n'est pas un raccourci, c'est la seule lecture possible. Un
 * joueur tient son element de la materia qu'il sertit, donc du geste : la
 * marque s'ecrit sur l'accord, et ARC-13b-a l'y a ecrite. Un monstre tient son
 * element de **lui-meme** (`Monster::element`, livre par BES-01) et ses gestes
 * sont **partages** — `none_attack_1` sert des dizaines d'especes de sept
 * elements differents. Ecrire la marque sur le geste obligerait donc a
 * dupliquer chaque attaque par element, ou a mentir.
 *
 * **Et cette lecture est immunisee, par construction, au defaut du cote
 * joueur.** ARC-13b-a a trouve trois gestes qui appliquent la Brulure sans etre
 * du feu (`holy-fire`, `dark-forge-blast`, `amethyst-shatter`) et allument donc
 * le capstone d'un Pyromancien : le capstone d'un arbre s'allume sur le geste
 * d'un autre. Ici, la marque **est** l'element du monstre : elle ne peut pas en
 * designer un autre.
 *
 * Cette classe ne fait qu'**enoncer la loi**. Ce qui l'applique est
 * `MobActionHandler` ; ce qui la pose est `StatusEffectManager`.
 */
final class MonsterMarkLaw
{
    /**
     * La marque que ce geste de ce monstre applique, ou `null`.
     *
     * Trois refus, dans cet ordre :
     *
     *  1. **Un geste qui ne blesse pas ne marque pas** (§ 1.1). C'est la meme
     *     loi que le cote joueur, et elle est arithmetique : une entrave qui
     *     coute un tour plein pour un tour vole est nulle en duel (§ 9
     *     quinquies). Une marque doit voyager avec un coup.
     *  2. **Un mannequin ne marque jamais** (ONB-11). Ils sont deja d'element
     *     neutre, donc le refus suivant suffirait — mais la clemence des
     *     mannequins se pose a chaque chemin plutot qu'a un seul, sans quoi il
     *     suffit d'un chemin oublie pour abimer un debutant.
     *  3. **`None` n'a pas de marque.** Ce n'est pas un element, c'est son
     *     absence (§ 9 quater) : lui en donner une creerait une neuvieme case
     *     qui ne correspond a aucun domaine.
     */
    public static function markFor(Monster $monster, Spell $gesture): ?string
    {
        if ((int) ($gesture->getDamage() ?? 0) <= 0) {
            return null;
        }

        if ($monster->isTrainingDummy()) {
            return null;
        }

        $mark = ElementalMark::forElement($monster->getElement());
        if ($mark === null) {
            return null;
        }

        // Le geste porte deja cette marque : la poser une seconde fois par
        // l'autre chemin ne ferait que rafraichir une duree qui vient d'etre
        // posee, et gaspillerait un jet de `ward` que la cible a le droit de
        // ne subir qu'une fois par geste.
        if ($gesture->getStatusEffectSlug() === $mark) {
            return null;
        }

        return $mark;
    }
}
