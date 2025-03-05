<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\Game\Spell;
use App\Event\Fight\ActionEvent;
use App\Event\Fight\MobActionHitEvent;
use App\Event\Fight\MobActionMissEvent;
use App\GameEngine\Fight\Handler\MobActionHandlerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MobActionHandler
{
    /**
     * @var iterable|MobActionHandlerInterface[]
     */
    protected $handlers;

    /**
     * MobActionHandler constructor.
     *
     * @param MobActionHandlerInterface[]|iterable $handlers
     * @param EventDispatcherInterface             $eventDispatcher
     * @param SpellApplicator                      $spellApplicator
     */
    public function __construct(iterable $handlers, private readonly EventDispatcherInterface $eventDispatcher, private readonly SpellApplicator $spellApplicator, private readonly LoggerInterface $logger)
    {
        $this->handlers = $handlers;
    }

    public function doAction(Fight $fight)
    {
        if ($fight->getMob()->isDead()) {
            $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);
            return;
        }

        $action = $this->generateAction($fight);
        $spell = $this->getSpell($action, $fight->getMob());

        $this->logger->debug(sprintf('[MobActionHandler] Spell %s used by mob #%d', $spell->getName(), $fight->getMob()->getId()));

        if (FightCalculator::hasAttackHit($fight->getMob()->getMonster()->getHit())) {
            // Applique le sort sur la cible
            $this->spellApplicator->apply($spell, $fight->getMob(), $fight->getPlayers()->first());
            $this->eventDispatcher->dispatch(new MobActionHitEvent($spell->getName()), MobActionHitEvent::NAME);
        } else {
            $this->eventDispatcher->dispatch(new MobActionMissEvent($spell->getName()), MobActionMissEvent::NAME);
        }

        $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);
    }

    private function getSpell($action, Mob $mob): Spell
    {
        foreach ($this->handlers as $handler) {
            if($handler->supports($action)) {
                return $handler->getSpell($mob);
            }
        }

        throw new Exception("No spell available for this action");
    }

    private function generateAction(Fight $fight)
    {
        return 'attack';
    }
}
