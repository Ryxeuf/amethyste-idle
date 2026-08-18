<?php

namespace App\GameEngine\Fight;

/**
 * Le geste qui frappe plus tard (ARC-18f).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 8. ***C'est la seule des huit formes qui
 * exploite l'asynchronie au lieu de la subir.*** Le donjon de groupe est
 * semi-synchrone — un joueur actif a la fois, le tour d'un absent resolu tout
 * seul —, et toutes les autres mecaniques du jeu composent avec cette
 * contrainte ; celle-ci s'en sert : un geste qui se resout deux tours plus tard
 * se resout **pendant le tour de quelqu'un d'autre**, si bien que le joueur qui
 * l'a pose continue d'agir apres etre parti.
 *
 * C'est le cousin offensif du depot (ARC-11b) : *la ou le depot etale un effet
 * sur les allies, le differe etale une **action** sur la rencontre*.
 *
 * ## Le garde-fou du canon : des tours, jamais des secondes
 *
 * Il se resout **en tours de rencontre**, le meme compteur que les depots
 * (§ 7 bis), et jamais en temps reel. La raison est la meme que pour eux : dans
 * un donjon ou un tour peut durer des heures, une echeance en secondes ferait
 * exploser la bombe **avant** que le tour suivant n'ait ete joue, ou trois
 * tours trop tard selon la vitesse de connexion des autres — *le geste
 * dependrait de la ponctualite d'inconnus plutot que du combat*.
 *
 * ## Deux garde-fous que le canon n'ecrit pas
 *
 * **Le delai ne change pas la valeur.** C'est la correction 5 transposee — *la
 * duree etale la valeur, elle ne l'augmente pas* : si differer plus longtemps
 * rapportait plus, poser sa bombe au tour le plus lointain serait toujours
 * correct, et le differe cesserait d'etre un choix pour devenir un calcul. Ce
 * qui fait la valeur de la forme n'est pas d'attendre, c'est **d'agir quand on
 * n'est pas la**.
 *
 * **Il meurt avec la rencontre**, comme la charge (ARC-18e). Le rangement le
 * garantit — la file vit dans les metadonnees du combat —, et sans cela un
 * differe pose puis fui exploserait dans la rencontre suivante, c'est-a-dire
 * sur un monstre qui n'existait pas quand on a vise.
 */
final class DeferredLaw
{
    /**
     * La cle des metadonnees du combat ou vit la file.
     */
    public const METADATA_KEY = 'arc18f_deferred';

    /**
     * Le delai le plus court qu'un differe puisse porter, en tours.
     *
     * Un differe a zero tour se resout dans le tour ou il est joue : ce n'est
     * pas un differe, c'est un geste ordinaire ecrit de facon compliquee. La
     * borne est donc **un**, et non deux : un tour suffit a ce que la
     * resolution tombe pendant le tour d'un autre, qui est tout ce que la forme
     * promet.
     */
    public const MIN_DELAY = 1;

    /**
     * Le delai le plus long, en tours.
     *
     * Le canon ne le nomme pas, et il est necessaire pour la meme raison que le
     * plafond de la charge : sans borne haute, un differe pose au tour 1 pour
     * le tour 30 serait **oublie de tout le monde** — du joueur qui l'a pose
     * comme de celui qui le subit —, et un geste qu'on ne relie pas a sa cause
     * n'est pas une mecanique, c'est du bruit. Trois tours : de quoi enjamber
     * le tour d'un autre sans sortir de la memoire courte d'une rencontre.
     */
    public const MAX_DELAY = 3;

    /**
     * Le delai opposable a un differe.
     */
    public static function delayFor(int $declared): int
    {
        return min(self::MAX_DELAY, max(self::MIN_DELAY, $declared));
    }

    /**
     * Le tour de rencontre auquel ce geste se resoudra.
     */
    public static function resolvesAt(int $currentTurn, int $declaredDelay): int
    {
        return $currentTurn + self::delayFor($declaredDelay);
    }

    /**
     * Ce differe est-il du ?
     *
     * La comparaison est **large** (`>=`) et non stricte, et ce n'est pas une
     * commodite : un tour peut etre saute — un joueur qui fuit, une rencontre
     * qui change d'etape —, et une egalite stricte laisserait alors la bombe
     * dans la file **pour toujours**, a la fois jamais resolue et jamais
     * effacee.
     */
    public static function isDue(int $resolvesAt, int $currentTurn): bool
    {
        return $currentTurn >= $resolvesAt;
    }

    /**
     * Ce que le differe rend a son echeance.
     *
     * ***Le delai ne le multiplie pas.*** La valeur est celle du geste, entiere,
     * quel que soit le nombre de tours attendus : ce qu'on achete en differant
     * n'est pas de la puissance, c'est un tour d'action pendant qu'on est
     * ailleurs.
     */
    public static function payload(int $declaredValue, int $delay): int
    {
        return max(0, $declaredValue);
    }
}
