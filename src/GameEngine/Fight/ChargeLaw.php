<?php

namespace App\GameEngine\Fight;

/**
 * La ressource qui se construit dans la rencontre (ARC-18e).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 2 : *une ressource qui se construit dans la
 * rencontre*. Elle repare un defaut mesure : **la melee n'a qu'un temps de
 * reprise, donc une rotation sans recompense** — ses gestes se succedent sans
 * que rien ne s'accumule, et jouer bien ne se distingue pas de jouer au hasard.
 *
 * Surtout, elle **paie les longs combats** : precisement la ou le canon veut
 * que la melee brille (l'elite, le boss — § 9 octies) et la ou elle est
 * aujourd'hui la plus mauvaise. C'est la seule forme dont la valeur *croit*
 * avec la duree de la rencontre, ce qui la met exactement a l'endroit ou la
 * melee perd.
 *
 * ## Le garde-fou du canon : elle meurt avec la rencontre
 *
 * ***Une ressource qui persiste entre les combats double la comptabilite de la
 * journee*** (§ 9 septies) et transforme le jeu en gestion de stock. C'est ce
 * qui decide de son rangement : elle vit dans les **metadonnees du combat**,
 * comme le registre des gestes d'ARC-06b — *le meme endroit que ce qui n'a de
 * sens que le temps d'une rencontre*. Elle n'a besoin d'aucune colonne, et le
 * jour ou la rencontre s'efface, elle s'efface avec.
 *
 * ## Le plafond, et pourquoi il en faut un
 *
 * Le canon ne le nomme pas, et il est pourtant necessaire : sans plafond, la
 * charge **croitrait lineairement avec la duree**, si bien qu'un combat de
 * quarante tours donnerait un geste quarante fois plus fort qu'au premier — ce
 * n'est plus une ressource, c'est une prime a la lenteur, et elle irait a
 * l'exact oppose de ce que la forme repare (*la melee doit aimer les longs
 * combats, pas les provoquer*).
 *
 * Le plafond est **cinq**, et il se derive du reste plutot que de s'inventer :
 * la grille de reprise de la melee compte cinq crans (0 a 4 tours selon le
 * palier, GAME_MATERIA § 2.3 bis), et une charge qui se remplit en cinq gestes
 * tient donc **dans une rotation complete** — l'accumulation se conclut dans la
 * rencontre plutot que de la deborder.
 */
final class ChargeLaw
{
    /**
     * La cle des metadonnees du combat ou vivent les charges.
     */
    public const METADATA_KEY = 'arc18e_charges';

    /**
     * Ce qu'un personnage peut accumuler au plus.
     */
    public const MAX = 5;

    /**
     * Ce que ce geste laisse au compteur.
     *
     * Le plafond est applique **a l'ajout** et non a la lecture : un compteur
     * qui pourrait depasser puis serait rabote a la lecture garderait, entre
     * les deux, un etat que rien n'autorise — et c'est l'etat que le prochain
     * lecteur finirait par croire.
     */
    public static function after(int $current, int $generated): int
    {
        return min(self::MAX, max(0, $current) + max(0, $generated));
    }

    /**
     * Ce geste peut-il etre joue ?
     *
     * ***Un geste qui consomme plus qu'on ne possede ne se joue pas du tout***,
     * il ne se joue pas « en moins fort ». C'est ce qui fait de la charge une
     * decision : la garder ou la depenser. Un geste qui s'adapterait au
     * compteur retirerait le choix — il serait toujours correct de le lancer.
     */
    public static function canSpend(int $current, int $cost): bool
    {
        return $cost <= 0 || $current >= $cost;
    }

    /**
     * Ce qu'il reste apres la depense.
     */
    public static function spend(int $current, int $cost): int
    {
        if (!self::canSpend($current, $cost)) {
            return max(0, $current);
        }

        return max(0, $current - max(0, $cost));
    }

    /**
     * Un geste ne peut pas a la fois generer et consommer.
     *
     * Le refus est structurel plutot que prudent : un geste qui ferait les deux
     * serait **impossible a lire au moment de jouer** — le joueur ne saurait
     * pas s'il monte ou s'il depense —, et les deux moities du geste se
     * neutraliseraient par construction des que le cout egale le gain. *La
     * charge oppose deux gestes, elle n'en decore pas un seul.*
     */
    public static function isLegal(int $generates, int $consumes): bool
    {
        return $generates <= 0 || $consumes <= 0;
    }
}
