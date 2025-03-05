<?php

namespace App\GameEngine\Realtime\Map;

use App\Event\Map\PlayerMovedEvent;
use App\Helper\PlayerHelper;
use App\Transformer\CellModelTransformer;
use App\Transformer\PlayerInfosTransformer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;

class MovedPlayerHandler extends MovedHandler implements EventSubscriberInterface
{
    public function __construct(
        HubInterface $publisher,
        private readonly PlayerInfosTransformer $playerInfosTransformer,
        private readonly CellModelTransformer $cellModelTransformer,
        private readonly PlayerHelper $playerHelper,
    )
    {
        parent::__construct($publisher);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerMovedEvent::NAME => 'movePlayer'
        ];
    }

    public function movePlayer(PlayerMovedEvent $event): void
    {
        $this->playerHelper->setPlayer($event->getPlayer());
        $this->move('player', $event->getPlayer()->getId(), $event->getPlayer()->getCoordinates(),
        [
            'player' => $this->playerInfosTransformer->transform($event->getPlayer()),
//            'actions' => $this->cellModelTransformer->transformCellActions($event->getPlayer()->getCell()),
        ]);
    }
}