<?php

namespace App\GameEngine\Fight;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\GameEngine\Bestiary\MonsterStatTemplate;

/**
 * Ce qu'un monstre retire de vie, et d'ou ce nombre vient (ARC-17b).
 *
 * ARC-17a a derive **ce qu'un monstre frappe** de sa case `tier x rank`, et
 * s'est arrete la : un test verifiait que la formule de combat ne lisait pas
 * encore la derivation, parce que la brancher deplace de vraies valeurs. C'est
 * ce jalon qui la branche.
 *
 * > **Un joueur porte ses degats dans son geste ; un monstre les porte dans sa
 * > case.**
 *
 * La symetrie avec `MonsterMarkLaw` n'est pas une coquetterie, c'est la meme
 * lecture pour la meme raison. Les gestes des monstres sont **partages** —
 * `none_attack_1` sert 38 des 65 especes, de sept elements et des quatre
 * paliers. Ce qu'un geste partage declare ne peut donc rien dire de l'espece
 * qui le porte : mesure faite, les gestes d'attaque du bestiaire declarent
 * **1, 2, 3 ou 5** degats quand la vie va de 30 a 2 400. *Monter de palier
 * rendait les combats plus longs sans les rendre plus dangereux.*
 *
 * ## Ce qui est remplace, et ce qui ne l'est pas
 *
 * Le geste garde **tout ce qui fait son identite** — son nom, son element, son
 * aire, le statut qu'il applique, sa place dans le schema d'IA. Seul le nombre
 * change, parce que seul le nombre etait faux.
 *
 * **On ne garde pas non plus le rapport entre gestes**, et c'est une mesure et
 * non un gout : sur les 126 couples (sort du pool, attaque de base) du
 * bestiaire, le rapport va de 0,33 a 7,0 pour une mediane de 3,0. Ces rapports
 * ne disent pas « ce sort frappe sept fois plus fort que mon attaque » — ils
 * disent que l'attaque de base est le geste le plus faible d'une echelle plate
 * ou tout tient entre 1 et 7. Le conserver reviendrait a **conserver l'artefact
 * et a le multiplier par la derivation** : un boss de palier 4 y gagnerait un
 * sort a 1 200 degats. *Ce qui ne portait pas d'intention n'en gagne pas en
 * etant mis a l'echelle.*
 *
 * Ce que le geste perd en nombre, il le garde en effet : ce qui separe deux
 * gestes d'un meme monstre, c'est l'aire, le statut, la duree — jamais le
 * chiffre. C'est la these du canon appliquee au bestiaire (§ 0.2 : *l'identite
 * est dans les gestes, pas dans les pourcentages*).
 *
 * ## Le second chemin, et le defaut qu'il cachait
 *
 * Un monstre retire de la vie **par deux chemins**, et un seul passait par un
 * geste. Le donjon de groupe resout sa riposte tout seul (DON-02), et il lisait
 * `Monster::hit` — c'est-a-dire la **precision**. La meme valeur servait donc de
 * *probabilite de toucher* dans le combat de zone (`FightCalculator::
 * hasAttackHit`) et de *degats* dans le donjon.
 *
 * Le commentaire de DON-02 disait deja ce qu'il voulait — « le coup est celui
 * du monstre de l'etape : une elite frappe plus fort qu'un commun, sans reglage
 * special » — et il ne pouvait pas l'obtenir : **aucun nombre de degats
 * n'existait sur un monstre** avant ARC-17a. La precision etait le seul entier
 * disponible, et elle progresse de 75 a 95 sur toute la grille : un facteur
 * **1,27** la ou le canon demande 2,9 entre deux rangs voisins. La riposte
 * d'une elite de palier 1 valait donc 80 points de vie, et celle d'un boss de
 * palier 4 en valait 95.
 *
 * Brancher un seul des deux chemins aurait laisse le simulateur d'ARC-17c
 * mesurer **deux lois differentes** selon qu'il joue une zone ou un donjon.
 */
final class MonsterDamageLaw
{
    /**
     * Ce que ce monstre retire par coup, de par sa seule case.
     *
     * C'est la reponse quand il n'y a pas de geste a lire — la riposte d'une
     * rencontre de donjon, qui est le monstre et rien d'autre.
     */
    public static function strikeFor(Monster $monster): int
    {
        return MonsterStatTemplate::attackFor($monster->getTier(), $monster->getRank());
    }

    /**
     * Ce geste derive-t-il ses degats de la case de celui qui le porte ?
     *
     * Deux refus, et ils disent la meme chose dans deux directions :
     *
     *  1. **Un geste qui ne blesse pas ne se met pas a blesser.** La derivation
     *     remplace un nombre, elle n'en cree pas : un soin de monstre, une
     *     entrave pure, un cri de terreur restent ce qu'ils sont. C'est l'ordre
     *     des questions d'ARC-11a lu ici — *le degat d'abord, et seulement s'il
     *     y en a un.* Sans ce refus, chaque geste de soin du bestiaire se
     *     mettrait a frapper au palier de son porteur.
     *  2. **Un geste en pourcentage est deja relatif.** Il se mesure sur la vie
     *     de sa cible, donc il porte deja une echelle ; lui en imposer une
     *     seconde reviendrait a le mettre a l'echelle deux fois.
     */
    public static function derives(Spell $gesture): bool
    {
        if ((int) ($gesture->getDamage() ?? 0) <= 0) {
            return false;
        }

        return !$gesture->isPercent();
    }

    /**
     * Les degats de base de ce geste porte par ce monstre.
     *
     * Rend `$declared` inchange quand la loi refuse : un appelant n'a jamais a
     * savoir *si* la derivation s'applique, seulement a passer par ici. C'est ce
     * qui fait tenir la borne le jour ou un troisieme chemin apparaitra.
     */
    public static function damageFor(Monster $monster, Spell $gesture, int $declared): int
    {
        if (!self::derives($gesture)) {
            return $declared;
        }

        return self::strikeFor($monster);
    }
}
