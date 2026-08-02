<?php

namespace App\GameEngine\Fight;

use App\Enum\Element;
use App\Enum\MonsterRank;
use App\Event\Fight\MobDeadEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MateriaXpGranter implements EventSubscriberInterface
{
    private const BASE_XP_PER_KILL = 10;

    /**
     * BES-01 : l'XP suit la case `tier × rank`, plus une echelle 1-40.
     *
     * Recalibrage a magnitude constante : les monstres T1 (ex-niveaux 1-5)
     * rendaient 10-50 XP, les T4 (ex-niveaux 26-40) 260-400. Le rang
     * multiplie — l'elite vaut deux tout-venants, le boss en vaut cinq
     * (l'ancien BOSS_XP_MULTIPLIER, conserve).
     *
     * @var array<int, int>
     */
    private const TIER_XP_FACTOR = [0 => 1, 1 => 3, 2 => 8, 3 => 18, 4 => 32];

    private const ELITE_XP_MULTIPLIER = 2;
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
        $xpGain = self::BASE_XP_PER_KILL * (self::TIER_XP_FACTOR[$monster->getTier()] ?? 1);

        $xpGain *= match ($monster->getRank()) {
            MonsterRank::Boss => self::BOSS_XP_MULTIPLIER,
            MonsterRank::Elite => self::ELITE_XP_MULTIPLIER,
            MonsterRank::Common => 1,
        };

        $xpMultiplier = $this->gameEventBonusProvider->getXpMultiplier($mob->getMap());
        $xpGain = (int) round($xpGain * $xpMultiplier);

        // Dungeon difficulty: scale materia XP
        $dungeonXpMultiplier = (float) ($fight->getMetadataValue('difficulty_xp_multiplier', 1.0) ?? 1.0);
        if ($dungeonXpMultiplier > 1.0) {
            $xpGain = (int) round($xpGain * $dungeonXpMultiplier);
        }

        // Coop: split XP equally between participants (minimum 1)
        $alivePlayers = $fight->getPlayers()->filter(fn ($p) => !$p->isDead());
        if ($fight->isCoopFight() && $alivePlayers->count() > 1) {
            $xpGain = max(1, (int) round($xpGain / $alivePlayers->count()));
        }

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
