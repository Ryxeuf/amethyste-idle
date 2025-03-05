<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\Entity\Game\Spell;
use App\Event\Fight\MobDeadEvent;
use App\Event\Fight\PlayerDeadEvent;
use App\GameEngine\Item\ItemUtils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SpellApplicator
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function apply(Spell $spell, CharacterInterface $sender, CharacterInterface $target, array $options = [])
    {
        $domainHeal = $options['heal'] ?? 0;
        $domainDamage = $options['damage'] ?? 0;
        $domainCritical = $options['critical'] ?? 0;

        $heal = $spell->getHeal() !== 0 ? $domainHeal + $spell->getHeal() : 0;
        $damage = $spell->getDamage() !== 0 ? $domainDamage + $spell->getDamage() : 0;
        if (ItemUtils::isActionCritical($spell->getCritical() + $domainCritical)) {
            $heal = ItemUtils::getCriticalModified($heal);
            $damage = ItemUtils::getCriticalModified($damage);
        }
        $life = $target->getLife() - $damage + $heal;

        $life = min($target->getMaxLife(), $life);
        $life = max(0, $life);

        $target->setLife($life);

        if ($target->getLife() > 0) {
            $target->setDiedAt(null);
        } else {
            $target->setDiedAt(new DateTime());
        }

        $this->entityManager->persist($target);
        $this->entityManager->flush();
        $this->entityManager->refresh($target);

        if ($target->isDead()) {
            if ($target instanceof Mob) {
                $this->eventDispatcher->dispatch(new MobDeadEvent($target), MobDeadEvent::NAME);
            }
            if ($target instanceof Player) {
                $this->eventDispatcher->dispatch(new PlayerDeadEvent($target), PlayerDeadEvent::NAME);
            }
        }
    }
}