<?php

namespace App\GameEngine\Fight;

/**
 * Ce qu'un point de vie achete en points de magie (ARC-18c).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 6 : *echanger une ressource contre une
 * autre*. Elle repare un defaut mesure au § 9 octies.4 — **le pyromancien paie
 * deux fois** : il est le seul archetype dont la fragilite n'est pas compensee
 * par sa vitesse, parce qu'il paie **aussi** le pool. Sur la journee de communs
 * du canon, il paie 99 minutes d'attente en PV *et* 64 en PM, quand le soldat
 * n'en paie que 74 au total.
 *
 * ## Ce qu'elle repare vraiment, et ce qu'elle ne repare pas
 *
 * ***Elle ne reduit pas la facture, elle la rend jouable.*** Le releve du § 9
 * sexies mesure un pyromancien qui **tombe en panne de PM au tour 8** alors
 * qu'il lui reste des points de vie : la rencontre continue, et il la finit a
 * mains nues. La conversion lui rend un choix a ce moment-la — payer en PV ce
 * qu'il ne peut plus payer en PM — sans rien lui rendre sur la journee.
 *
 * C'est important pour ne pas se tromper de correction : *si la conversion
 * reduisait la facture, elle serait un rabais et pas une decision*, et le § 9
 * octies.4 demande explicitement l'inverse — ce sont le cout en PM ou la barre
 * de vie du pyromancien qui doivent bouger, pas un bouton qui efface la
 * difference.
 *
 * ## Le taux se derive, il ne se pose pas
 *
 * ARC-05b a etabli que **le temps d'attente est la seule monnaie commune aux
 * quatre fonctions**. Les deux ressources ont chacune leur curseur de
 * regeneration hors combat (`zone.life.regen_seconds`,
 * `zone.mana.regen_seconds`), et leur rapport **dit ce qu'un PV vaut en PM**
 * sans qu'on ait a en decider : avec les curseurs livres — 12 s par PV, 6 s par
 * PM —, un point de vie coute exactement le temps de deux points de magie.
 *
 * C'est ce rapport, et non un chiffre, qui est fige : deplacer un curseur
 * deplace le taux, comme deplacer un curseur deplace le calendrier d'ARC-06a.
 * *Une table ecrite a la main aurait diverge de ses curseurs a la premiere
 * recalibration.*
 *
 * ## Le taux est defavorable, et de moitie
 *
 * Le garde-fou du canon : **on perd a convertir, sinon convertir est toujours
 * correct et ce n'est plus une decision.** La penalite est un facteur nomme
 * plutot qu'un second chiffre — la moitie —, si bien que *convertir rend la
 * moitie de ce que le temps rendrait* : un point de vie donne un point de
 * magie la ou l'attente en donnerait deux.
 *
 * La moitie n'est pas prise au hasard : c'est la penalite la plus lourde qui
 * laisse la forme utile. Au-dela, le geste ne se joue plus jamais — et une
 * mecanique qu'on ne joue pas ne repare rien.
 */
final class ConversionLaw
{
    /**
     * Ce que la penalite retire au taux equitable.
     *
     * Un facteur, pas un taux : le taux se derive des curseurs, la penalite dit
     * seulement de combien on perd. Les separer est ce qui permet de
     * recalibrer les curseurs sans reecrire la regle.
     */
    public const PENALTY = 0.5;

    /**
     * Ce qu'un point de vie vaudrait en points de magie **au temps**.
     *
     * Le rapport des deux curseurs, et rien d'autre. C'est la valeur d'echange
     * juste — celle a laquelle convertir ne coute ni ne rapporte rien.
     */
    public static function fairRate(int $lifeRegenSeconds, int $manaRegenSeconds): float
    {
        if ($manaRegenSeconds <= 0) {
            return 0.0;
        }

        return $lifeRegenSeconds / $manaRegenSeconds;
    }

    /**
     * Ce qu'un point de vie achete reellement, penalite comprise.
     */
    public static function rate(int $lifeRegenSeconds, int $manaRegenSeconds): float
    {
        return self::fairRate($lifeRegenSeconds, $manaRegenSeconds) * self::PENALTY;
    }

    /**
     * Ce que cette conversion rend, en points de magie.
     *
     * Arrondi **vers le bas** : la perte est du cote de celui qui convertit, ce
     * que le garde-fou demande. Un arrondi au plus proche rendrait certaines
     * conversions gagnantes par la seule grace de l'arithmetique, et une
     * mecanique dont la rentabilite depend de la parite d'un nombre n'est pas
     * une decision.
     */
    public static function manaFor(int $lifeSpent, int $lifeRegenSeconds, int $manaRegenSeconds): int
    {
        if ($lifeSpent <= 0) {
            return 0;
        }

        return (int) floor($lifeSpent * self::rate($lifeRegenSeconds, $manaRegenSeconds));
    }

    /**
     * Ce qu'on peut reellement depenser, sans jamais mourir de l'avoir fait.
     *
     * ***La conversion ne tue jamais.*** Le canon ne l'ecrit pas parce qu'il
     * n'y pense pas — mais sans plancher, un geste qui coute des points de vie
     * peut en couter le dernier, et le joueur meurt **en lancant un sort**,
     * c'est-a-dire d'une facon qu'aucun ecran ne lui aura annoncee.
     *
     * Le plancher est **un** point de vie et pas davantage : elle ne tue pas,
     * mais elle peut vous laisser a un coup de la mort — c'est un pari, et le
     * canon aime les paris. Le borner plus haut reviendrait a decider a la
     * place du joueur combien de risque il a le droit de prendre.
     */
    public static function affordableLife(int $currentLife, int $asked): int
    {
        if ($asked <= 0 || $currentLife <= 1) {
            return 0;
        }

        return min($asked, $currentLife - 1);
    }

    /**
     * Le taux est-il bien defavorable ?
     *
     * Le garde-fou, rendu interrogeable plutot que confie a la relecture : le
     * jour ou quelqu'un porte `PENALTY` a 1,0 « pour que la forme serve enfin »,
     * la conversion cesse d'etre une decision et devient un bouton — et c'est
     * un test qui doit le dire, pas une revue.
     */
    public static function isUnfavourable(int $lifeRegenSeconds, int $manaRegenSeconds): bool
    {
        return self::rate($lifeRegenSeconds, $manaRegenSeconds) < self::fairRate($lifeRegenSeconds, $manaRegenSeconds);
    }
}
