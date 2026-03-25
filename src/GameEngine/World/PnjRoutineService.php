<?php

namespace App\GameEngine\World;

use App\Entity\App\Pnj;
use App\Entity\App\PnjSchedule;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Déplace les PNJ selon leurs horaires de routine (PnjSchedule).
 *
 * À chaque tick (toutes les 5 min réelles), calcule l'heure in-game courante,
 * recherche les PnjSchedule correspondant à cette heure, et déplace les PNJ
 * dont la position diffère de celle prévue par le schedule.
 */
class PnjRoutineService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameTimeService $gameTimeService,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Exécute un tick de routine : déplace les PNJ vers leur position horaire.
     *
     * @return array{moved: int, total: int}
     */
    public function tick(): array
    {
        $currentHour = $this->gameTimeService->getHour();

        $schedules = $this->entityManager->getRepository(PnjSchedule::class)
            ->findBy(['hour' => $currentHour]);

        $moved = 0;

        foreach ($schedules as $schedule) {
            $pnj = $schedule->getPnj();
            $targetCoordinates = $schedule->getCoordinates();
            $targetMap = $schedule->getMap();

            // Pas besoin de bouger si déjà à la bonne position et sur la bonne carte
            if ($pnj->getCoordinates() === $targetCoordinates
                && $pnj->getMap()?->getId() === $targetMap->getId()) {
                continue;
            }

            $oldCoordinates = $pnj->getCoordinates();
            $pnj->setCoordinates($targetCoordinates);
            $pnj->setMap($targetMap);

            $this->publishPnjMove($pnj, $oldCoordinates, $targetCoordinates);

            ++$moved;
        }

        if ($moved > 0) {
            $this->entityManager->flush();
        }

        $this->logger->info('PNJ routine tick: {moved}/{total} PNJ déplacés (heure in-game: {hour})', [
            'moved' => $moved,
            'total' => count($schedules),
            'hour' => $currentHour,
        ]);

        return ['moved' => $moved, 'total' => count($schedules)];
    }

    private function publishPnjMove(Pnj $pnj, string $oldCoordinates, string $newCoordinates): void
    {
        [$newX, $newY] = explode('.', $newCoordinates);
        [$oldX, $oldY] = explode('.', $oldCoordinates);

        $update = new Update(
            'map/move',
            json_encode([
                'topic' => 'map/move',
                'type' => 'pnj',
                'object' => $pnj->getId(),
                'x' => (int) $newX,
                'y' => (int) $newY,
                'coordinates' => $newCoordinates,
                'oldX' => (int) $oldX,
                'oldY' => (int) $oldY,
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}
