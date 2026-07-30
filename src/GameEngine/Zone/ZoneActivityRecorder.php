<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Player;
use App\Entity\App\PlayerZoneActivity;
use App\Entity\App\Zone;
use App\Repository\PlayerZoneActivityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Compte les actes de travail d'un joueur, par zone (ONB-13).
 *
 * Un seul endroit sait ce qui compte comme du travail, et c'est voulu : la
 * regle « voyager n'est pas travailler » se defait des qu'elle est repetee a
 * deux endroits. Les appelants annoncent ce qu'ils viennent de faire ; c'est
 * ici qu'on decide si cela compte.
 */
class ZoneActivityRecorder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerZoneActivityRepository $repository,
    ) {
    }

    /**
     * Enregistre un acte de travail, sans flush.
     *
     * Pas de flush : l'appelant est au milieu d'une action de zone qui a la
     * sienne, et deux ecritures la ou une suffit se paient a chaque coup de
     * pioche — c'est la boucle la plus jouee du modele.
     */
    public function record(Player $player, Zone $zone, int $acts = 1): void
    {
        $activity = $this->repository->findOneFor($player, $zone);

        if (null === $activity) {
            $activity = new PlayerZoneActivity($player, $zone);
            $this->entityManager->persist($activity);
        }

        $activity->record($acts);
    }
}
