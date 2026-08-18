<?php

namespace App\GameEngine\Balance;

/**
 * Le temps qu'il faut pour se remettre (ARC-20c).
 *
 * GAME_VITALITY § 5. `LifeRegenManager` regenerait **12 secondes par point, en
 * absolu** — une valeur posee quand la barre valait 20 PV et que personne ne la
 * faisait monter. Le Socle (ARC-20b) la porte a 96, 208, 440 puis 880 PV, et la
 * regeneration absolue devient alors une punition : le retour a pleine vie
 * passe de **19 minutes** au palier 1 a **2 h 56** au palier 4.
 *
 * Ce n'est pas seulement long, c'est **faux** : l'ancre de journee d'ARC-05b
 * convertit toute la progression dans une seule monnaie — *le temps d'attente*
 * —, et une attente qui se multiplie par neuf entre le premier palier et le
 * dernier ferait exploser la comparaison entre fonctions qu'elle sert a tenir.
 *
 * > **L'invariant : *le temps de retour a plein ne depend pas du palier*.**
 *
 * ## Ce que le curseur dit desormais
 *
 * `zone.life.regen_seconds` cesse d'etre « des secondes par point » pour
 * devenir **le temps de retour a plein, exprime au palier 1** : douze secondes
 * par point sur une barre de 96 font 19,2 minutes, et ce sont ces 19,2 minutes
 * qui sont figees. Un joueur de palier 4 recupere 880 PV dans le meme temps.
 *
 * **Le sens du curseur change, sa valeur non** — c'est ce qui permet de ne rien
 * recalibrer : au palier 1, la regeneration est rigoureusement celle d'avant.
 *
 * ## Pourquoi on ne convertit pas en « secondes par point » par palier
 *
 * L'evidence serait de diviser : 12 s x 96 / 880 = 1,3 s par point au palier 4.
 * Mais un temps par point est un **entier de secondes**, et l'arrondi ferait
 * deriver le total — 1 s par point rendrait la barre en 14,7 minutes au lieu de
 * 19,2, soit un quart de plus vite pour le seul palier le plus haut. *Un
 * invariant qui tombe a cause d'un arrondi n'est pas un invariant.*
 *
 * On compte donc les **points gagnes depuis le temps ecoule**, jamais l'inverse.
 */
final class VitalityRegen
{
    /**
     * Le temps de retour a plein, en secondes — constant, quel que soit le
     * palier.
     */
    public static function fullRecoverySeconds(int $regenSeconds): int
    {
        return max(1, $regenSeconds) * VitalityLaw::floor();
    }

    /**
     * Les points rendus par ce temps ecoule, sur cette barre.
     *
     * C'est **la** fonction : tout le reste s'en derive, et c'est elle qui rend
     * le total constant sans arrondi intermediaire.
     */
    public static function pointsFor(int $elapsedSeconds, int $bar, int $regenSeconds): int
    {
        if ($elapsedSeconds <= 0 || $bar <= 0) {
            return 0;
        }

        return intdiv($elapsedSeconds * $bar, self::fullRecoverySeconds($regenSeconds));
    }

    /**
     * Le temps qu'il faut pour gagner ce nombre de points.
     *
     * Arrondi **au-dessus**, et c'est ce qui garde l'ancre coherente avec
     * `pointsFor()` : crediter un point pour un temps qu'on n'a pas encore
     * atteint le ferait recompter au passage suivant.
     */
    public static function secondsFor(int $points, int $bar, int $regenSeconds): int
    {
        if ($points <= 0 || $bar <= 0) {
            return 0;
        }

        return (int) ceil($points * self::fullRecoverySeconds($regenSeconds) / $bar);
    }

    /**
     * Le temps jusqu'au prochain point, sur cette barre.
     */
    public static function secondsPerPoint(int $bar, int $regenSeconds): int
    {
        return max(1, self::secondsFor(1, $bar, $regenSeconds));
    }
}
