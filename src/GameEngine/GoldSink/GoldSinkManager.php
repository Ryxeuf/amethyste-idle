<?php

namespace App\GameEngine\GoldSink;

use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use Doctrine\ORM\EntityManagerInterface;

class GoldSinkManager
{
    public const RENAME_COST = 50;
    public const TRAVEL_BASE_COST = 100;
    public const REPAIR_BASE_COST = 10;

    private const REPAIR_RARITY_MULTIPLIER = [
        'common' => 1,
        'uncommon' => 2,
        'rare' => 4,
        'epic' => 8,
        'legendary' => 16,
        'amethyst' => 32,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    // ── Rename ────────────────────────────────────────────────

    /**
     * @return array{success: bool, message: string}
     */
    public function renameItem(Player $player, PlayerItem $playerItem, string $newName): array
    {
        $newName = trim($newName);

        if ($newName === '' || mb_strlen($newName) > 50) {
            return ['success' => false, 'message' => 'Le nom doit faire entre 1 et 50 caracteres.'];
        }

        if (!preg_match('/^[\p{L}\p{N}\s\'-]+$/u', $newName)) {
            return ['success' => false, 'message' => 'Le nom contient des caracteres invalides.'];
        }

        if (!$player->removeGils(self::RENAME_COST)) {
            return ['success' => false, 'message' => sprintf('Gils insuffisants (%d/%d).', $player->getGils(), self::RENAME_COST)];
        }

        $playerItem->setCustomName($newName);
        $this->entityManager->flush();

        return ['success' => true, 'message' => sprintf('Objet renomme en « %s » pour %d Gils.', $newName, self::RENAME_COST)];
    }

    // ── Fast Travel ───────────────────────────────────────────

    /**
     * @return Region[]
     */
    public function getAvailableDestinations(Player $player): array
    {
        $regions = $this->entityManager->getRepository(Region::class)->findAll();
        $currentRegion = $player->getMap()?->getRegion();

        return array_filter($regions, function (Region $r) use ($currentRegion) {
            return $r->getCapitalMap() !== null && $r !== $currentRegion;
        });
    }

    public function getTravelCost(Player $player, Region $destination): int
    {
        return self::TRAVEL_BASE_COST;
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function fastTravel(Player $player, Region $destination): array
    {
        if ($player->getFight() !== null) {
            return ['success' => false, 'message' => 'Impossible de voyager en combat.'];
        }

        if ($player->isMoving()) {
            return ['success' => false, 'message' => 'Impossible de voyager en deplacement.'];
        }

        $capitalMap = $destination->getCapitalMap();
        if ($capitalMap === null) {
            return ['success' => false, 'message' => 'Cette region n\'a pas de capitale.'];
        }

        $currentRegion = $player->getMap()?->getRegion();
        if ($currentRegion === $destination) {
            return ['success' => false, 'message' => 'Vous etes deja dans cette region.'];
        }

        $cost = $this->getTravelCost($player, $destination);
        if (!$player->removeGils($cost)) {
            return ['success' => false, 'message' => sprintf('Gils insuffisants (%d/%d).', $player->getGils(), $cost)];
        }

        $player->setMap($capitalMap);
        $player->setCoordinates($this->getMapSpawnCoordinates($capitalMap));
        $player->setIsMoving(false);
        $this->entityManager->flush();

        return ['success' => true, 'message' => sprintf('Teleporte a %s pour %d Gils.', $destination->getName(), $cost)];
    }

    // ── Repair ────────────────────────────────────────────────

    public function getRepairCost(PlayerItem $playerItem): int
    {
        $maxDurability = $playerItem->getGenericItem()->getDurability();
        $current = $playerItem->getCurrentDurability();

        if ($maxDurability === null || $current === null || $current >= $maxDurability) {
            return 0;
        }

        $missing = $maxDurability - $current;
        $rarity = $playerItem->getGenericItem()->getRarity() ?? 'common';
        $multiplier = self::REPAIR_RARITY_MULTIPLIER[$rarity] ?? 1;

        return max(1, (int) ceil($missing * self::REPAIR_BASE_COST * $multiplier / $maxDurability));
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function repairItem(Player $player, PlayerItem $playerItem): array
    {
        $maxDurability = $playerItem->getGenericItem()->getDurability();
        $current = $playerItem->getCurrentDurability();

        if ($maxDurability === null || $current === null) {
            return ['success' => false, 'message' => 'Cet objet n\'a pas de durabilite.'];
        }

        if ($current >= $maxDurability) {
            return ['success' => false, 'message' => 'Cet objet est deja en parfait etat.'];
        }

        $cost = $this->getRepairCost($playerItem);
        if (!$player->removeGils($cost)) {
            return ['success' => false, 'message' => sprintf('Gils insuffisants (%d/%d).', $player->getGils(), $cost)];
        }

        $playerItem->setCurrentDurability($maxDurability);
        $this->entityManager->flush();

        return ['success' => true, 'message' => sprintf('Objet repare pour %d Gils.', $cost)];
    }

    /**
     * Degrade la durabilite de tous les items equipes ayant une durabilite.
     * Appele a la mort du joueur.
     */
    public function degradeEquippedItems(Player $player): int
    {
        $degraded = 0;

        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() === 0) {
                    continue;
                }

                $maxDurability = $playerItem->getGenericItem()->getDurability();
                $current = $playerItem->getCurrentDurability();

                if ($maxDurability === null || $current === null || $current <= 0) {
                    continue;
                }

                $loss = max(1, (int) ceil($maxDurability * 0.10));
                $playerItem->setCurrentDurability(max(0, $current - $loss));
                ++$degraded;
            }
        }

        return $degraded;
    }

    private function getMapSpawnCoordinates(Map $map): string
    {
        return (int) floor($map->getAreaWidth() / 2) . '.' . (int) floor($map->getAreaHeight() / 2);
    }
}
