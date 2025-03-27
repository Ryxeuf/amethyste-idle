<?php

namespace App\GameEngine\Movement\Handler;

use App\GameEngine\Movement\Message\PlayerMoveMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Event\Map\PlayerMovedEvent;
use App\Entity\App\Player;
use App\GameEngine\Realtime\Map\MovedPlayerHandler;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class PlayerMoveMessageHandler
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly MovedPlayerHandler $movedPlayerHandler,
    )
    {
    }

    public function __invoke(PlayerMoveMessage $message): void
    {
        $content = json_decode($message->content, true);
        $cells = $content['cells'];
        $this->logger->info("Player {player} is moving to {cell}", ['player' => $content['player'], 'cell' => $cells[0]]);
        /** @var Player $player */
        $player = $this->entityManager->getRepository(Player::class)->find($content['player']);
        $cell = array_shift($cells);

        $player->setLastCoordinates($player->getCoordinates());
        $player->setCoordinates(CellHelper::stringifyCoordinates($cell['x'], $cell['y']));
        $this->entityManager->flush();

        $this->logger->info("Player {player} moved to {cell}", ['player' => $player->getId(), 'cell' => $player->getCoordinates()]);

        // $this->eventDispatcher->dispatch(new PlayerMovedEvent($player));
        $this->movedPlayerHandler->movePlayer($player);

        if (!empty($cells)) {
            $this->logger->info("Player {player} has more moves to do", ['player' => $player->getId()]);
            $content['cells'] = $cells;

//            Stamp doesn't work under 1s
//            $envelope = new Envelope(new PlayerMoveMessage(json_encode($content)), [new DelayStamp(500)]);
//            $this->bus->dispatch($envelope);

            usleep(250_000);
            $this->logger->info("Player {player} is dispatching next move", ['player' => $player->getId()]);
            $this->bus->dispatch(new PlayerMoveMessage(json_encode($content)));

        }
    }
}
