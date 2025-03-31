<?php

namespace App\GameEngine\Player;

use App\Helper\CellHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Event\Map\PlayerMovedEvent;
use Doctrine\ORM\EntityManager;

class PlayerMoveUpdater
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHelper $playerHelper,
        private readonly MessageBusInterface $bus,
    ) {}

    public function updatePlayerMove(int $x, int $y): void
    {
        $player = $this->playerHelper->getPlayer();
        $player->setLastCoordinates($player->getCoordinates());
        $player->setCoordinates(CellHelper::stringifyCoordinates($x, $y));

        $this->entityManager->flush();

        $this->bus->dispatch(new PlayerMovedEvent($player));
    }

    public function setPlayerMoving(): void
    {
        $player = $this->playerHelper->getPlayer();
        $player->setIsMoving(true);
        $this->entityManager->flush();
        $this->entityManager->refresh($player);
    }
}
