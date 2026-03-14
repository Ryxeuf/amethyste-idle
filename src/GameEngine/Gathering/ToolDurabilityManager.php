<?php

namespace App\GameEngine\Gathering;

use App\Entity\App\PlayerItem;
use Doctrine\ORM\EntityManagerInterface;

class ToolDurabilityManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Returns remaining durability uses for a tool.
     * Returns -1 if the tool has unlimited durability.
     */
    public function checkDurability(PlayerItem $tool): int
    {
        $currentDurability = $tool->getCurrentDurability();

        if ($currentDurability === null) {
            return -1; // Unlimited durability
        }

        return $currentDurability;
    }

    /**
     * Repairs a tool by restoring $amount durability points.
     * Cannot exceed the max durability defined on the generic item.
     */
    public function repair(PlayerItem $tool, int $amount): void
    {
        $currentDurability = $tool->getCurrentDurability();

        if ($currentDurability === null) {
            return; // Tool has no durability system
        }

        $maxDurability = $tool->getGenericItem()->getDurability();
        if ($maxDurability === null) {
            return;
        }

        $newDurability = min($maxDurability, $currentDurability + $amount);
        $tool->setCurrentDurability($newDurability);

        $this->em->persist($tool);
        $this->em->flush();
    }

    /**
     * Checks if a tool is broken (0 durability remaining).
     * Tools with null durability (unlimited) are never broken.
     */
    public function isToolBroken(PlayerItem $tool): bool
    {
        $currentDurability = $tool->getCurrentDurability();

        if ($currentDurability === null) {
            return false; // Unlimited durability
        }

        return $currentDurability <= 0;
    }

    /**
     * Reduces tool durability by $amount.
     * Returns true if the tool is now broken.
     */
    public function reduceDurability(PlayerItem $tool, int $amount = 1): bool
    {
        return $tool->reduceDurability($amount);
    }
}
