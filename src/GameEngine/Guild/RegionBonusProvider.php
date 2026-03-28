<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\Map;
use App\Entity\App\Player;

class RegionBonusProvider
{
    /** Discount rate for members of the controlling guild. */
    private const MEMBER_DISCOUNT = 0.10;

    public function __construct(
        private readonly GuildManager $guildManager,
        private readonly TownControlManager $townControlManager,
    ) {
    }

    /**
     * Returns the shop discount rate for a player on a given map.
     * 10% discount if the player belongs to the guild controlling the region.
     */
    public function getShopDiscount(Player $player, ?Map $map): float
    {
        if ($map === null) {
            return 0.0;
        }

        $region = $map->getRegion();
        if ($region === null) {
            return 0.0;
        }

        $controllingGuild = $this->townControlManager->getControllingGuild($region);
        if ($controllingGuild === null) {
            return 0.0;
        }

        $playerGuild = $this->guildManager->getPlayerGuild($player);
        if ($playerGuild === null || $playerGuild->getId() !== $controllingGuild->getId()) {
            return 0.0;
        }

        return self::MEMBER_DISCOUNT;
    }

    /**
     * Returns the tax amount collected by the controlling guild for a purchase.
     * Based on the region's tax rate (default 5%).
     */
    public function getTaxAmount(int $basePrice, ?Map $map): int
    {
        if ($basePrice <= 0 || $map === null) {
            return 0;
        }

        $region = $map->getRegion();
        if ($region === null) {
            return 0;
        }

        $controllingGuild = $this->townControlManager->getControllingGuild($region);
        if ($controllingGuild === null) {
            return 0;
        }

        return (int) floor($basePrice * $region->getTaxRateFloat());
    }

    /**
     * Returns the guild controlling the region of the given map, or null.
     */
    public function getControllingGuild(?Map $map): ?Guild
    {
        if ($map === null) {
            return null;
        }

        $region = $map->getRegion();
        if ($region === null) {
            return null;
        }

        return $this->townControlManager->getControllingGuild($region);
    }
}
