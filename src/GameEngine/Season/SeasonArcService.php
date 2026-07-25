<?php

namespace App\GameEngine\Season;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use App\Repository\GameEventRepository;

/**
 * Arc narratif d'une saison (NAR-08) : le `theme` de la saison est materialise
 * par 4 beats (amorce / montee / climax / resolution), chacun un `GameEvent`
 * rattache a la saison avec sa fenetre temporelle. Ce service lit l'arc de
 * facon ordonnee et resout le beat actif a un instant donne.
 */
class SeasonArcService
{
    public function __construct(
        private readonly GameEventRepository $gameEventRepository,
    ) {
    }

    /**
     * Beats de la saison, ordonnes (amorce -> resolution).
     *
     * @return GameEvent[]
     */
    public function getBeats(InfluenceSeason $season): array
    {
        return $this->gameEventRepository->findBySeasonOrdered($season);
    }

    /**
     * Beat actif a l'instant donne (premiere fenetre couvrant `now`), ou null si
     * aucun beat n'est en cours.
     */
    public function getActiveBeat(InfluenceSeason $season, \DateTimeInterface $now): ?GameEvent
    {
        foreach ($this->getBeats($season) as $beat) {
            if ($beat->isActiveAt($now)) {
                return $beat;
            }
        }

        return null;
    }
}
