<?php

namespace App\GameEngine\Item;

use App\Entity\CharacterInterface;
use App\Entity\Game\Item;
use App\Helper\ItemHelper;
use App\Helper\PlayerDomainHelper;

class ItemHitResolver
{
    private const UNKNOWN_DOMAIN_FACTOR = 0.75;

    public function __construct(private readonly PlayerDomainHelper $playerDomainHelper, private readonly ItemHelper $itemHelper)
    {
    }

    public function hasItemHit(Item $item, CharacterInterface $sender, CharacterInterface $target): bool
    {
        if ($item->isObject()) {
            return true;
        }

        return ItemUtils::isActionSuccess($this->getHitChances($item, $sender, $target));
    }

    protected function getHitChances(Item $item, CharacterInterface $sender, CharacterInterface $target): int
    {
        $spell = $this->itemHelper->getItemSpell($item);
        $chances = $spell ? $spell->getHit() : 0;

        $domainExperience = null;
        if ($domain = $item->getDomain()) {
            if ($domainExperience = $this->playerDomainHelper->getDomainExperience($domain, $sender)) {
                $chances += $domainExperience->getHit();
            }
        }

        if (!$domain || !$domainExperience) {
            $chances = round(ItemUtils::DEFAULT_HIT_CHANCES * self::UNKNOWN_DOMAIN_FACTOR);
        }

        return $chances;
    }
}
