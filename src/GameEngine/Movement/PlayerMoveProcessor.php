<?php

namespace App\GameEngine\Movement;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Event\Map\PlayerMovedEvent;
use App\GameEngine\Fight\Handler\FightHandler;
use App\GameEngine\Map\PortalDetector;
use App\GameEngine\Realtime\Map\MovedPlayerHandler;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PlayerMoveProcessor
{
    private ?ObjectLayer $triggeredPortal = null;
    private ?Fight $triggeredFight = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly MovedPlayerHandler $movedPlayerHandler,
        private readonly FightHandler $fightHandler,
        private readonly PortalDetector $portalDetector,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param array<array{x: int, y: int}> $cells
     *
     * @return array<array{x: int, y: int}> The actually traversed path (truncated at mob if encountered)
     */
    public function processMove(Player $player, array $cells): array
    {
        $this->triggeredPortal = null;
        $this->triggeredFight = null;

        if ($player->getFight()) {
            $this->logger->info('Player {player} is in a fight, move ignored', ['player' => $player->getId()]);

            return [];
        }

        if (empty($cells)) {
            return [];
        }

        $pathCoords = array_map(
            fn (array $cell) => CellHelper::stringifyCoordinates($cell['x'], $cell['y']),
            $cells
        );

        $mobsByCoords = [];
        foreach ($this->entityManager->getRepository(Mob::class)->findBy(
            ['coordinates' => $pathCoords, 'map' => $player->getMap()]
        ) as $mob) {
            $mobsByCoords[$mob->getCoordinates()] = $mob;
        }

        $traversedPath = [];
        $encounterMob = null;
        foreach ($cells as $cell) {
            $coords = CellHelper::stringifyCoordinates($cell['x'], $cell['y']);
            $traversedPath[] = $cell;
            if (isset($mobsByCoords[$coords])) {
                $encounterMob = $mobsByCoords[$coords];
                break;
            }
        }

        $lastCell = end($traversedPath);
        $player->setLastCoordinates($player->getCoordinates());
        $player->setCoordinates(CellHelper::stringifyCoordinates($lastCell['x'], $lastCell['y']));

        if (!$encounterMob) {
            $player->setIsMoving(false);
        }

        $this->entityManager->flush();

        $this->movedPlayerHandler->movePlayerPath($player, $traversedPath);
        $this->eventDispatcher->dispatch(new PlayerMovedEvent($player), PlayerMovedEvent::NAME);

        if ($encounterMob) {
            $this->logger->info('Mob found at {cell}, starting fight', ['cell' => $encounterMob->getCoordinates()]);
            $this->triggeredFight = $this->fightHandler->startFight($player, $encounterMob);
        } else {
            // Check for portal at final position
            $finalCoords = CellHelper::stringifyCoordinates($lastCell['x'], $lastCell['y']);
            $portal = $this->portalDetector->detectPortal($player, $finalCoords);
            if ($portal) {
                $this->triggeredPortal = $portal;
                $this->logger->info('Portal found at {cell}, destination map {mapId}', [
                    'cell' => $finalCoords,
                    'mapId' => $portal->getDestinationMapId(),
                ]);
            } else {
                $this->logger->info('Player {player} completed path of {count} cells', [
                    'player' => $player->getId(),
                    'count' => count($traversedPath),
                ]);
            }
        }

        return $traversedPath;
    }

    public function getTriggeredPortal(): ?ObjectLayer
    {
        return $this->triggeredPortal;
    }

    public function getTriggeredFight(): ?Fight
    {
        return $this->triggeredFight;
    }
}
