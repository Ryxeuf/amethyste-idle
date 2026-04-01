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

        // Aligner fight_id sur la BDD (source de vérité). Évite l'état « encore en combat » en mémoire
        // alors que la fuite a déjà mis fight_id à NULL (identity map / même instance que User#getPlayer).
        if ($this->entityManager->contains($player)) {
            $fightId = $this->entityManager->getConnection()->fetchOne(
                'SELECT fight_id FROM player WHERE id = ?',
                [$player->getId()],
            );
            if ($fightId === null || $fightId === false || $fightId === '') {
                $player->setFight(null);
            } else {
                $player->setFight($this->entityManager->getReference(Fight::class, (int) $fightId));
            }
        }

        if ($player->getFight()) {
            throw new \LogicException(sprintf('Le joueur #%d est en combat et ne peut pas se déplacer.', $player->getId()));
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

            // World boss déjà en combat : rejoindre le fight existant
            if ($encounterMob->isWorldBoss() && $encounterMob->getFight() !== null) {
                $this->fightHandler->joinWorldBossFight($player, $encounterMob->getFight());
                $this->triggeredFight = $encounterMob->getFight();
            } else {
                $groupMobs = $this->resolveGroupMobs($encounterMob, $player);
                $this->triggeredFight = $this->fightHandler->startGroupFight($player, $groupMobs);
            }
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

    /**
     * Résout le groupe de mobs à engager. Si le mob a un groupTag,
     * tous les mobs du même groupe sur la même map rejoignent le combat.
     *
     * @return Mob[]
     */
    private function resolveGroupMobs(Mob $encounterMob, Player $player): array
    {
        $groupTag = $encounterMob->getGroupTag();
        if ($groupTag === null) {
            return [$encounterMob];
        }

        $groupMobs = $this->entityManager->getRepository(Mob::class)->findBy([
            'groupTag' => $groupTag,
            'map' => $player->getMap(),
        ]);

        // Exclure les mobs déjà en combat
        $groupMobs = array_filter($groupMobs, fn (Mob $m) => $m->getFight() === null);

        if (empty($groupMobs)) {
            return [$encounterMob];
        }

        return array_values($groupMobs);
    }
}
