<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\Map;
use App\Entity\App\Player;

class RegionBonusProvider
{
    /** Discount rate for members of the controlling guild. */
    private const MEMBER_DISCOUNT = 0.10;

    /** Extra shop discount per upgrade level. */
    private const UPGRADE_SHOP_DISCOUNT_PER_LEVEL = 0.05;

    /** Gathering bonus per upgrade level. */
    private const UPGRADE_GATHERING_BONUS_PER_LEVEL = 0.10;

    /** XP bonus per upgrade level. */
    private const UPGRADE_XP_BONUS_PER_LEVEL = 0.05;

    public function __construct(
        private readonly GuildManager $guildManager,
        private readonly TownControlManager $townControlManager,
        private readonly RegionUpgradeManager $regionUpgradeManager,
    ) {
    }

    /**
     * Returns the shop discount rate for a player on a given map.
     * 10% base discount + up to 15% from shop_discount upgrade.
     */
    public function getShopDiscount(Player $player, ?Map $map): float
    {
        if (!$this->isPlayerInControllingGuild($player, $map)) {
            return 0.0;
        }

        $region = $map->getRegion();
        $upgradeLevel = $this->regionUpgradeManager->getUpgradeLevel($region, 'shop_discount');

        return self::MEMBER_DISCOUNT + ($upgradeLevel * self::UPGRADE_SHOP_DISCOUNT_PER_LEVEL);
    }

    /**
     * Returns the gathering bonus rate for a player on a given map.
     * 0% base, up to 30% from gathering_bonus upgrade.
     */
    public function getGatheringBonus(Player $player, ?Map $map): float
    {
        if (!$this->isPlayerInControllingGuild($player, $map)) {
            return 0.0;
        }

        $region = $map->getRegion();
        $upgradeLevel = $this->regionUpgradeManager->getUpgradeLevel($region, 'gathering_bonus');

        return $upgradeLevel * self::UPGRADE_GATHERING_BONUS_PER_LEVEL;
    }

    /**
     * Returns the XP bonus rate for a player on a given map.
     * 0% base, up to 10% from xp_bonus upgrade.
     */
    public function getXpBonus(Player $player, ?Map $map): float
    {
        if (!$this->isPlayerInControllingGuild($player, $map)) {
            return 0.0;
        }

        $region = $map->getRegion();
        $upgradeLevel = $this->regionUpgradeManager->getUpgradeLevel($region, 'xp_bonus');

        return $upgradeLevel * self::UPGRADE_XP_BONUS_PER_LEVEL;
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

    /**
     * Checks if a player belongs to the guild controlling the region of a map.
     */
    private function isPlayerInControllingGuild(Player $player, ?Map $map): bool
    {
        if ($map === null) {
            return false;
        }

        $region = $map->getRegion();
        if ($region === null) {
            return false;
        }

        $controllingGuild = $this->townControlManager->getControllingGuild($region);
        if ($controllingGuild === null) {
            return false;
        }

        $playerGuild = $this->guildManager->getPlayerGuild($player);

        return $playerGuild !== null && $playerGuild->getId() === $controllingGuild->getId();
    }
}
