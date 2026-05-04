<?php

namespace App\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;

/**
 * Wraps MountAcquisitionService for the "shop purchase" channel:
 * validates the mount has a gilCost, the player has enough gils, deducts
 * them, then grants the mount with source SOURCE_PURCHASE.
 */
class MountPurchaseService
{
    public function __construct(
        private readonly MountAcquisitionService $mountAcquisitionService,
    ) {
    }

    /**
     * @throws MountNotPurchasableException if gilCost is null or <= 0
     * @throws InsufficientGilsException    if the player does not have enough gils
     * @throws MountAlreadyOwnedException   relayed from MountAcquisitionService
     * @throws \DomainException             relayed from MountAcquisitionService (mount disabled)
     */
    public function purchase(Player $player, Mount $mount, bool $flush = true): PlayerMount
    {
        $cost = $mount->getGilCost();
        if ($cost === null || $cost <= 0) {
            throw new MountNotPurchasableException($mount);
        }

        if ($player->getGils() < $cost) {
            throw new InsufficientGilsException($player, $cost);
        }

        $player->setGils($player->getGils() - $cost);

        // Defer the flush to grantMount so that the gils deduction and the
        // PlayerMount creation are persisted atomically in the same flush.
        return $this->mountAcquisitionService->grantMount(
            $player,
            $mount,
            PlayerMount::SOURCE_PURCHASE,
            $flush,
        );
    }
}
