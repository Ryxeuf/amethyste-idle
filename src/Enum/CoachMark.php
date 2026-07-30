<?php

namespace App\Enum;

/**
 * Les encarts de coach, un par ecran (ONB-17).
 *
 * **Ferme la dette D10.** Le jeu n'expliquait ses ecrans nulle part : le
 * tutoriel disait quoi faire, jamais ce qu'un ecran contient ni ce qu'un geste
 * coute. Un joueur ouvrait l'inventaire sans savoir que les emplacements de
 * matéria s'y trouvaient, et le catalogue des arbres sans savoir ce qu'un
 * parchemin ouvre.
 *
 * GAME_ONBOARDING § 7 : deux phrases, le geste, **son cout en energie**, une
 * croix. **Ne revient jamais seul.**
 *
 * Trois contraintes portent le sens du mecanisme, et deux d'entre elles sont
 * dans ce fichier :
 *
 * - **C1 — jamais un systeme inutilisable.** Un encart qui explique le marche a
 *   quelqu'un qui n'a rien a vendre, ou la guilde avant la fin de l'acte I,
 *   enseigne une frustration. Les encarts differes portent leur condition.
 * - **C2 — toujours le cout.** Un ecran qui presente un geste sans son prix en
 *   energie apprend au joueur a decouvrir le prix en le payant.
 * - **C3 — a l'arrivee, jamais au temps ecoule** (dans le resolveur) : un encart
 *   qui apparaîtrait apres N secondes se lirait comme une relance.
 */
enum CoachMark: string
{
    case Zone = 'zone';

    /**
     * Le combat, sur le **premier mannequin** — et nulle part ailleurs.
     *
     * C'est le seul combat ou lire ne tue pas : le mannequin inerte ne frappe
     * jamais (ONB-11). Poser cet encart sur une vraie rencontre reviendrait a
     * demander au joueur de lire pendant qu'on le frappe.
     */
    case Combat = 'combat';

    case Inventory = 'inventory';
    case TreeCatalog = 'tree-catalog';
    case Quests = 'quests';
    case Crafting = 'crafting';
    case WorldMap = 'world-map';

    /**
     * Le hub — **apres** l'acte I seulement.
     *
     * Avant, il n'a rien a raconter : ni semaine, ni reprise, ni attente. Un
     * encart qui presenterait un tableau de bord vide serait la meilleure facon
     * d'apprendre au joueur a ne pas y revenir.
     */
    case Hub = 'hub';

    /**
     * Le marche — au premier objet vendable **et** e-mail verifie.
     *
     * Les deux conditions, pas une : l'e-mail non verifie barre le marche
     * (§ 2), et expliquer une porte fermee est le cas d'ecole de C1.
     */
    case Market = 'market';

    /** La guilde — a la fin de l'acte I. */
    case Guild = 'guild';

    /**
     * Les encarts qui attendent la fin de l'acte I.
     *
     * @return list<self>
     */
    public static function afterActOne(): array
    {
        return [self::Hub, self::Guild];
    }

    public function waitsForActOne(): bool
    {
        return \in_array($this, self::afterActOne(), true);
    }

    /**
     * L'encart depend-il d'une condition que l'ecran doit fournir ?
     *
     * Deux cas, et ils se ressemblent : ni « un objet vendable et un e-mail
     * verifie », ni « ce combat est le premier mannequin » ne se deduisent du
     * joueur seul. L'appelant tranche, et `CoachMarkResolver` refuse de decider
     * a sa place — c'est ce qui empeche l'encart de combat d'apparaître sur une
     * vraie rencontre, ou lire coute des points de vie.
     */
    public function needsCallerCondition(): bool
    {
        return \in_array($this, [self::Market, self::Combat], true);
    }

    /**
     * Les deux phrases de l'encart, et le geste.
     */
    public function bodyKey(): string
    {
        return 'game.coach.' . $this->value . '.body';
    }

    public function titleKey(): string
    {
        return 'game.coach.' . $this->value . '.title';
    }

    /**
     * Ce que le geste de cet ecran coute — C2.
     *
     * Toujours une cle, jamais un nombre : le cout d'une action se lit dans les
     * parametres de jeu, et le figer ici en ferait une seconde source qui
     * mentirait au premier reglage.
     */
    public function costKey(): string
    {
        return 'game.coach.' . $this->value . '.cost';
    }
}
