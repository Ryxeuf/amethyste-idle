<?php

namespace App\GameEngine\Movement\Handler;

use App\GameEngine\Movement\Message\PlayerMoveMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Entity\App\Player;
use App\Entity\App\Mob;
use App\GameEngine\Realtime\Map\MovedPlayerHandler;
use App\GameEngine\Fight\Handler\FightHandler;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class PlayerMoveMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly MovedPlayerHandler $movedPlayerHandler,
        private readonly FightHandler $fightHandler,
    ) {}

    public function __invoke(PlayerMoveMessage $message): void
    {
        $content = json_decode($message->content, true);
        /** @var Player $player */
        $player = $this->entityManager->getRepository(Player::class)->find($content['player']);
        if (!$player) {
            $this->logger->error("Player {player} not found", ['player' => $content['player']]);
            return;
        }
        if ($player->getFight()) {
            $this->logger->info("Player {player} is in a fight", ['player' => $player->getId()]);
            return;
        }

        $cells = $content['cells'];
        $traversedPath = [];

        // Une seule requête pour tous les mobs sur les coordonnées du path (évite N findOneBy).
        $pathCoords = array_map(
            fn(array $cell) => CellHelper::stringifyCoordinates($cell['x'], $cell['y']),
            $cells
        );
        $mobsByCoords = [];
        foreach ($this->entityManager->getRepository(Mob::class)->findBy(
            ['coordinates' => $pathCoords, 'map' => $player->getMap()]
        ) as $mob) {
            $mobsByCoords[$mob->getCoordinates()] = $mob;
        }

        foreach ($cells as $cell) {
            $player->setLastCoordinates($player->getCoordinates());
            $coords = CellHelper::stringifyCoordinates($cell['x'], $cell['y']);
            $player->setCoordinates($coords);
            $traversedPath[] = $cell;

            $mob = $mobsByCoords[$coords] ?? null;
            if ($mob) {
                $this->logger->info("Mob found at {cell}, stopping movement", ['cell' => $coords]);
                $this->entityManager->flush();
                $this->movedPlayerHandler->movePlayerPath($player, $traversedPath);
                $this->fightHandler->startFight($player, $mob);
                return;
            }
        }

        $player->setIsMoving(false);
        $this->entityManager->flush();

        $this->movedPlayerHandler->movePlayerPath($player, $traversedPath);
        $this->logger->info("Player {player} completed path of {count} cells", [
            'player' => $player->getId(),
            'count' => count($traversedPath),
        ]);
    }
}
