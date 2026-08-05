<?php

namespace App\Enum;

/**
 * Qui un geste **touche** — la seconde des deux etiquettes d'ARC-11.
 *
 * GAME_ARCHETYPES § 3.1 : la portee decide le jeu de groupe (§ 7 bis) et la
 * lecture d'ecran — *un geste dit ce qu'il vise avant d'etre lance*.
 *
 * **`Group` est ce sur quoi repose toute la loi du depot.** Le combat de groupe
 * est semi-synchrone (un joueur actif a la fois, le tour d'un absent resolu
 * tout seul) : un soin **reactif** y est une mecanique morte. Un geste de
 * portee `Group` ne reagit donc pas, il **depose une duree** qui court que le
 * lanceur soit connecte ou non. Le soigneur ne soigne pas : il provisionne.
 *
 * Aucun geste livre ne porte encore cette portee — c'est exactement ce
 * qu'ARC-11b introduit, avec la duree qui l'accompagne.
 */
enum SpellScope: string
{
    /** Le lanceur, et lui seul. */
    case SelfOnly = 'self';

    /** Un allie designe. */
    case Ally = 'ally';

    /** Tout le groupe — la portee qui **depose**, jamais qui reagit. */
    case Group = 'group';

    /** Un adversaire. */
    case Target = 'target';

    /** Plusieurs adversaires. */
    case Targets = 'targets';

    /**
     * Cette portee se multiplie-t-elle par la taille du groupe ?
     *
     * L'asymetrie mesuree au § 9 quinquies, et la raison pour laquelle on
     * n'equilibre pas le controle comme un soutien : un effet pose sur les
     * **allies** vaut x8,8 a quatre, un effet pose sur l'**ennemi** vaut x0,9.
     * Un seul joueur agit par tour — ce qui touche l'adversaire ne profite
     * qu'a ce tour-la, ce qui touche les allies court sur tous les leurs.
     */
    public function multipliesWithGroupSize(): bool
    {
        return self::Group === $this;
    }

    /**
     * Cette portee vise-t-elle un allie (ou soi) plutot qu'un adversaire ?
     */
    public function isFriendly(): bool
    {
        return match ($this) {
            self::SelfOnly, self::Ally, self::Group => true,
            self::Target, self::Targets => false,
        };
    }

    /**
     * Le libelle d'auteur, pour les messages de test et les outils.
     */
    public function label(): string
    {
        return match ($this) {
            self::SelfOnly => 'Soi',
            self::Ally => 'Un allie',
            self::Group => 'Le groupe',
            self::Target => 'Une cible',
            self::Targets => 'Plusieurs cibles',
        };
    }
}
