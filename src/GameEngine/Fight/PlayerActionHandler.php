<?php

namespace App\GameEngine\Fight;

use App\ApiResource\FightResource;
use App\Entity\App\Player;
use App\Event\Fight\ActionEvent;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PlayerActionHandler
{

    public function __construct( private readonly iterable $handlers, private readonly PlayerHelper $playerHelper, private readonly FightChecker $fightChecker, private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function doAction(FightResource $fightResource, string $context): FightResource
    {
        // Récupère le joueur initiateur de l'action
        $player = $this->playerHelper->getPlayer();

        // Vérifie le combat : existe, a le droit, etc.
        $fight = $this->fightChecker->checkFight($player->getFight());
        $fightResource->id = $fight->getId();
        $fightResource->step = $fight->getStep();

        $this->applyAction($fightResource, $context, $player);

        $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);

        return $fightResource;
    }

    /**
     *
     * @throws Exception
     */
    protected function applyAction(FightResource $fight, string $context, Player $player): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($fight, $context)) {
                return $handler->applyAction($fight, $player);
            }
        }

        throw new Exception("No spell available for this action");
    }
}
