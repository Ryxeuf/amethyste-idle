<?php

namespace App\GameEngine\Fight;

use App\ApiResource\FightResource;
use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Event\Fight\ActionEvent;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use App\GameEngine\Fight\Handler\PlayerActionHandlerInterface;

class PlayerActionHandler
{

    public function __construct(
        #[AutowireIterator(tag: PlayerActionHandlerInterface::class)]
        private readonly iterable $handlers,
        private readonly PlayerHelper $playerHelper,
        private readonly FightChecker $fightChecker,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function doAction(Fight $fight, string $context, int $targetId, string $targetType): Fight
    {
        // Récupère le joueur initiateur de l'action
        $player = $this->playerHelper->getPlayer();

        // Vérifie le combat : existe, a le droit, etc.
        $fight = $this->fightChecker->checkFight($player->getFight(), $targetId, $targetType);

        $this->applyAction($fight, $context, $player);

        $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);

        return $fight;
    }

    /**
     *
     * @throws Exception
     */
    protected function applyAction(Fight $fight, string $context, Player $player): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($fight, $context)) {
                return $handler->applyAction($fight, $player);
            }
        }

        throw new Exception("No action available for this action");
    }
}
