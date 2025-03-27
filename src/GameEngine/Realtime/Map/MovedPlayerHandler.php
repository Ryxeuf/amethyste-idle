<?php

namespace App\GameEngine\Realtime\Map;

use App\Event\Map\PlayerMovedEvent;
use App\Helper\PlayerHelper;
use App\Transformer\CellModelTransformer;
use App\Transformer\PlayerInfosTransformer;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Entity\App\Player;
use Psr\Log\LoggerInterface;

class MovedPlayerHandler extends MovedHandler
{
    public function __construct(
        HubInterface $publisher,
        private readonly PlayerInfosTransformer $playerInfosTransformer,
        private readonly CellModelTransformer $cellModelTransformer,
        private readonly PlayerHelper $playerHelper,
        private readonly LoggerInterface $logger,
    )
    {
        parent::__construct($publisher, $logger);
    }

    public function __invoke(PlayerMovedEvent $event): void
    {
        $this->playerHelper->setPlayer($event->getPlayer());
        $this->move('player', $event->getPlayer()->getId(), $event->getPlayer()->getCoordinates(),
        [
            'player' => $this->playerInfosTransformer->transform($event->getPlayer()),
//            'actions' => $this->cellModelTransformer->transformCellActions($event->getPlayer()->getCell()),
        ]);
    }

    public function movePlayer(Player $player): void
    {
        $this->playerHelper->setPlayer($player);
        $this->move('player', $player->getId(), $player->getCoordinates(),
        [
            'player' => $this->playerInfosTransformer->transform($player),
        ]);
    }
}