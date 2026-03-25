<?php

namespace App\GameEngine\Fight;

use App\Enum\Element;
use App\Event\Fight\MobDeadEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MateriaXpGranter implements EventSubscriberInterface
{
    private const BASE_XP_PER_KILL = 10;
    private const BOSS_XP_MULTIPLIER = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly GameEventBonusProvider $gameEventBonusProvider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();

        // Les mobs invoqués ne donnent pas d'XP materia (anti-exploit)
        if ($mob->isSummoned()) {
            return;
        }

        $fight = $mob->getFight();
        if (!$fight) {
            return;
        }

        $monster = $mob->getMonster();
        $monsterLevel = $monster->getLevel() ?? 1;
        $xpGain = self::BASE_XP_PER_KILL * $monsterLevel;

        if ($monster->isBoss()) {
            $xpGain *= self::BOSS_XP_MULTIPLIER;
        }

        $xpMultiplier = $this->gameEventBonusProvider->getXpMultiplier($mob->getMap());
        $xpGain = (int) round($xpGain * $xpMultiplier);

        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }

            // Find all socketed materia across all inventories
            foreach ($player->getInventories() as $inventory) {
                foreach ($inventory->getItems() as $playerItem) {
                    // Check each slot on equipment items for socketed materia
                    foreach ($playerItem->getSlots() as $slot) {
                        $materia = $slot->getItemSet();
                        if ($materia !== null && $materia->isMateria()) {
                            // Apply element match XP bonus (+25%)
                            $materiaXp = $xpGain;
                            $slotElement = $slot->getElement();
                            $materiaElement = $materia->getGenericItem()->getElement();
                            if ($slotElement !== null && $slotElement !== Element::None
                                && $materiaElement !== Element::None
                                && $slotElement === $materiaElement) {
                                $materiaXp = (int) round($materiaXp * (1.0 + CombatCapacityResolver::ELEMENT_MATCH_XP_BONUS));
                            }

                            $materia->addExperience($materiaXp);
                            $this->entityManager->persist($materia);

                            $this->logger->debug(sprintf(
                                '[MateriaXpGranter] Materia %s gained %d XP (now %d, level %d)%s',
                                $materia->getGenericItem()->getName(),
                                $materiaXp,
                                $materia->getExperience(),
                                $materia->getMateriaLevel(),
                                $materiaXp > $xpGain ? ' [element match bonus]' : '',
                            ));
                        }
                    }
                }
            }
        }

        $this->entityManager->flush();
    }
}
