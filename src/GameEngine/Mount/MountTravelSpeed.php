<?php

namespace App\GameEngine\Mount;

use App\Entity\App\Player;

/**
 * Effet de la monture sur le temps de voyage (tache 130).
 *
 * Le pivot PBBG a supprime le deplacement en tuiles, et avec lui le seul
 * consommateur du bonus de monture (`Player::getEffectiveSpeed()`, retire ici).
 * Le bonus est transpose au modele zone : la monture active **reduit le
 * `travel_seconds`** des connexions du graphe, seule dimension de deplacement
 * qui subsiste.
 *
 * `speedBonus` reste une **vitesse**, pas une remise : une monture a +50 % de
 * vitesse parcourt la meme distance en 100/150 du temps, soit -33 %. Traiter le
 * bonus comme un pourcentage de temps retire aurait rendu une future monture a
 * +100 % instantanee.
 */
class MountTravelSpeed
{
    /**
     * Reduction maximale du temps de voyage, en pourcentage.
     *
     * Le temps de voyage est l'un des deux regulateurs de rythme du pivot (avec
     * l'energie). Une monture doit l'alleger, jamais l'annuler : sans plafond,
     * une monture a +400 % ramenerait une liaison de 15 minutes a 3.
     */
    public const MAX_REDUCTION_PERCENT = 50;

    /**
     * Temps de voyage reellement applique au joueur.
     *
     * Une liaison instantanee (interieurs, ruelles) le reste : il n'y a rien a
     * reduire, et monter a cheval pour traverser une piece serait absurde.
     */
    public function effectiveTravelSeconds(Player $player, int $baseSeconds): int
    {
        if ($baseSeconds <= 0) {
            return 0;
        }

        $bonus = $this->speedBonus($player);
        if ($bonus <= 0) {
            return $baseSeconds;
        }

        $reduced = (int) ceil($baseSeconds * 100 / (100 + $bonus));
        $floor = (int) ceil($baseSeconds * (100 - self::MAX_REDUCTION_PERCENT) / 100);

        return max($floor, $reduced);
    }

    /**
     * Reduction effective en pourcentage, pour l'affichage.
     *
     * Calculee a partir du resultat plutot que du bonus brut : c'est le chiffre
     * que le joueur pourra verifier au chronometre, plafond compris.
     */
    public function reductionPercent(Player $player, int $baseSeconds): int
    {
        if ($baseSeconds <= 0) {
            return 0;
        }

        return (int) round(($baseSeconds - $this->effectiveTravelSeconds($player, $baseSeconds)) * 100 / $baseSeconds);
    }

    private function speedBonus(Player $player): int
    {
        return max(0, $player->getActiveMount()?->getSpeedBonus() ?? 0);
    }
}
