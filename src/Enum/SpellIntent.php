<?php

namespace App\Enum;

use App\Entity\Game\StatusEffect;

/**
 * Ce qu'un geste **fait** — la premiere des deux etiquettes d'ARC-11.
 *
 * GAME_ARCHETYPES § 3.1 : *le registre dit **comment** on agit. Il ne dit ni
 * **ce qu'on fait**, ni **a qui**.* L'intention decide deux choses, et aucune
 * n'est cosmetique :
 *
 *  1. **Quels leviers qualifient le geste** — `mending` ne touche que le soin,
 *     `grip` que l'entrave. Dit **une fois sur le geste**, plutot que quinze
 *     fois dans quinze formules.
 *  2. **Quelle fonction a le droit de l'ouvrir** (§ 5.1) — la palette
 *     d'intentions ferme la boucle « arbre x materia » que le § 0 affirme
 *     depuis le debut sans que rien ne l'impose : un arbre d'entretien qui
 *     n'ouvrirait que des gestes de degat devient un arbre qui echoue au test.
 *
 * > **`intent` n'est pas `register`, et ne se deduit pas de lui.** Une
 * > protection peut etre un sort (bouclier d'eau), une technique de melee
 * > (garde haute) ou une piece d'equipement. Un degat peut etre les trois.
 * > Croiser les deux etiquettes est precisement ce qui donne 3 x 5 facons de
 * > faire une chose plutot que 3.
 */
enum SpellIntent: string
{
    /** Retirer des points de vie. */
    case Damage = 'damage';

    /** En rendre. */
    case Heal = 'heal';

    /** Empecher d'en perdre — bouclier, garde, absorption. */
    case Protection = 'protection';

    /** Rendre un allie meilleur a ce qu'il fait deja. */
    case Buff = 'buff';

    /** Retirer a l'adversaire un tour, une option, ou une resistance. */
    case Hinder = 'hinder';

    /**
     * L'intention que porte un effet de statut, par son type.
     *
     * **La table existe deja, elle n'etait simplement pas lue comme une
     * intention** : les types de `StatusEffect` se rangent sans reste, ce qui
     * est le signe qu'on nomme une distinction reelle plutot qu'on n'en invente
     * une. Un type inconnu rend `null` — on ne devine pas l'intention d'un
     * effet qu'on ne connait pas, on refuse de repondre.
     *
     * **`TYPE_MARK` ajoute le 2026-08-06 (ARC-11b-b).** ARC-11a avait mesure
     * *huit* types sans reste ; ARC-13a en a ajoute un **neuvieme** trois
     * jalons plus tard, et le reste etait la — une marque pure ne se rangeait
     * nulle part. C'est le genre d'ecart qui ne fait rien du tout jusqu'au jour
     * ou il fait tout : depuis ARC-11b-b, un geste dont l'intention est
     * illisible **ne borne aucun levier**, si bien qu'un geste de marque pure
     * aurait ete le seul du jeu a qualifier les quinze. Une marque est une
     * `Hinder` a la lettre de ce que cette intention dit — *retirer a
     * l'adversaire un tour, une option, ou une resistance* : la Trempe fait
     * agir plus tard, l'Entaille ouvre la garde, l'Aveugle fait viser moins
     * juste.
     */
    public static function fromStatusEffectType(string $type): ?self
    {
        return match ($type) {
            StatusEffect::TYPE_POISON,
            StatusEffect::TYPE_PARALYSIS,
            StatusEffect::TYPE_BURN,
            StatusEffect::TYPE_FREEZE,
            StatusEffect::TYPE_SILENCE,
            StatusEffect::TYPE_MARK => self::Hinder,
            StatusEffect::TYPE_REGENERATION => self::Heal,
            StatusEffect::TYPE_SHIELD => self::Protection,
            // Les trois ameliorations, et elles se rangent ensemble sans
            // effort : le berserk augmente ce qu'on rend, la riposte ajoute une
            // reponse a ce qu'on encaisse, la posture deplace le budget de
            // leviers. Aucune ne blesse, aucune ne protege au sens ou une
            // absorption protege — **elles changent le porteur**, et c'est la
            // definition de l'amelioration (ARC-11a).
            StatusEffect::TYPE_BERSERK,
            StatusEffect::TYPE_RIPOSTE,
            StatusEffect::TYPE_STANCE => self::Buff,
            // Le transfert **protege**, et c'est le seul rangement qui tienne :
            // il n'ameliore pas son porteur (il le rend plus fragile) et il ne
            // blesse personne (le total de la rencontre est inchange). Ce qu'il
            // fait, c'est mettre quelqu'un entre le coup et son allie.
            StatusEffect::TYPE_TRANSFER => self::Protection,
            default => null,
        };
    }

    /**
     * Ce geste vise-t-il un adversaire ?
     *
     * Le partage qui compte pour le § 7 bis : ce qui se pose sur un **allie**
     * se multiplie par le nombre d'allies, ce qui se pose sur l'**ennemi** ne
     * se multiplie pas — un seul joueur agit par tour dans un donjon
     * semi-synchrone. Entretien et encaisse gagnent au groupe ; assaut et
     * controle n'y gagnent rien.
     */
    public function isHostile(): bool
    {
        return match ($this) {
            self::Damage, self::Hinder => true,
            self::Heal, self::Protection, self::Buff => false,
        };
    }

    /**
     * Le libelle d'auteur, pour les messages de test et les outils.
     */
    public function label(): string
    {
        return match ($this) {
            self::Damage => 'Degat',
            self::Heal => 'Soin',
            self::Protection => 'Protection',
            self::Buff => 'Amelioration',
            self::Hinder => 'Entrave',
        };
    }
}
