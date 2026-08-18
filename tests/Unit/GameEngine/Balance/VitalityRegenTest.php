<?php

namespace App\Tests\Unit\GameEngine\Balance;

use App\GameEngine\Balance\VitalityLaw;
use App\GameEngine\Balance\VitalityRegen;
use App\GameEngine\Zone\LifeRegenManager;
use PHPUnit\Framework\TestCase;

/**
 * Le temps qu'il faut pour se remettre (ARC-20c).
 *
 * GAME_VITALITY § 5, invariant 9. La regeneration valait **12 secondes par
 * point, en absolu** — exact tant que la barre valait 20 PV et que rien ne la
 * faisait monter. Le Socle la porte a 880 au palier 4, et le retour a pleine vie
 * serait passe de 19 minutes a **2 h 56**.
 */
class VitalityRegenTest extends TestCase
{
    /**
     * **Le temps de retour a plein ne depend pas du palier.**.
     *
     * L'invariant du jalon, et il n'est pas seulement confortable : l'ancre de
     * journee d'ARC-05b convertit toute la progression dans une seule monnaie —
     * *le temps d'attente* —, et une attente multipliee par neuf entre le
     * premier palier et le dernier fausserait la comparaison entre fonctions
     * qu'elle sert a tenir. Elle n'allongerait pas le jeu, elle le mesurerait
     * mal.
     */
    public function testFullRecoveryTakesTheSameTimeAtEveryTier(): void
    {
        $cursor = LifeRegenManager::DEFAULT_REGEN_SECONDS;
        $expected = VitalityRegen::fullRecoverySeconds($cursor);

        foreach ([1, 2, 3, 4] as $tier) {
            $bar = VitalityLaw::barFor($tier);

            self::assertSame(
                $bar,
                VitalityRegen::pointsFor($expected, $bar, $cursor),
                sprintf('Palier %d : la barre ne se remplit pas dans le temps de reference.', $tier)
            );
        }
    }

    /**
     * **Le sens du curseur change, sa valeur non.**.
     *
     * Au palier 1, la regeneration est rigoureusement celle d'avant : douze
     * secondes par point. C'est ce qui permet de ne rien recalibrer — *un
     * changement de modele qui deplace aussi les valeurs rend impossible de
     * savoir lequel des deux a casse quelque chose*.
     */
    public function testTheFloorKeepsExactlyTheOldCadence(): void
    {
        $cursor = LifeRegenManager::DEFAULT_REGEN_SECONDS;

        self::assertSame($cursor, VitalityRegen::secondsPerPoint(VitalityLaw::floor(), $cursor));
        self::assertSame(1, VitalityRegen::pointsFor($cursor, VitalityLaw::floor(), $cursor));
    }

    /**
     * On compte les **points depuis le temps**, jamais l'inverse.
     *
     * L'evidence serait de diviser le curseur par la barre : 12 x 96 / 880 =
     * 1,3 seconde par point au palier 4. Mais un temps par point est un
     * **entier de secondes**, et l'arrondi ferait deriver le total — un quart
     * plus vite pour le seul palier le plus haut. ***Un invariant qui tombe a
     * cause d'un arrondi n'est pas un invariant.***
     */
    public function testTheTotalNeverDriftsThroughRounding(): void
    {
        $cursor = LifeRegenManager::DEFAULT_REGEN_SECONDS;
        $bar = VitalityLaw::barFor(4);

        // La division naive : 1 seconde par point, donc la barre en 880 s.
        $naive = max(1, (int) round($cursor * VitalityLaw::floor() / $bar)) * $bar;
        self::assertNotSame(VitalityRegen::fullRecoverySeconds($cursor), $naive, 'La division naive tombe juste : le test ne prouve plus rien.');

        // La derivation : exactement le temps de reference.
        self::assertSame($bar, VitalityRegen::pointsFor(VitalityRegen::fullRecoverySeconds($cursor), $bar, $cursor));
    }

    /**
     * Le temps rendu et les points credites restent coherents.
     *
     * `secondsFor()` arrondit **au-dessus** : crediter un point pour un temps
     * qu'on n'a pas encore atteint le ferait recompter au passage suivant, et
     * un joueur gagnerait de la vie a rafraichir sa page.
     */
    public function testTheAnchorNeverCreditsTimeThatHasNotPassed(): void
    {
        $cursor = LifeRegenManager::DEFAULT_REGEN_SECONDS;
        $bar = VitalityLaw::barFor(3);

        foreach ([1, 7, 42, 300] as $points) {
            $seconds = VitalityRegen::secondsFor($points, $bar, $cursor);

            self::assertGreaterThanOrEqual(
                $points,
                VitalityRegen::pointsFor($seconds, $bar, $cursor),
                sprintf('%d points sont credites pour un temps qui n\'en rend pas autant.', $points)
            );
        }
    }
}
