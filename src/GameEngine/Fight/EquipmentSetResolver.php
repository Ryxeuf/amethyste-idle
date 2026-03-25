<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\Game\EquipmentSet;
use App\Entity\Game\EquipmentSetBonus;
use App\Helper\GearHelper;

class EquipmentSetResolver
{
    public function __construct(
        private readonly GearHelper $gearHelper,
    ) {
    }

    /**
     * Detect active equipment sets from the player's equipped gear.
     *
     * @return array<string, array{set: EquipmentSet, equippedCount: int, totalPieces: int, activeBonuses: list<EquipmentSetBonus>, inactiveBonuses: list<EquipmentSetBonus>}>
     */
    public function getActiveSets(Player $player): array
    {
        $setCounts = $this->countEquippedPiecesPerSet($player);
        $result = [];

        foreach ($setCounts as $slug => $data) {
            $set = $data['set'];
            $count = $data['count'];

            $activeBonuses = [];
            $inactiveBonuses = [];

            foreach ($set->getBonuses() as $bonus) {
                if ($count >= $bonus->getRequiredPieces()) {
                    $activeBonuses[] = $bonus;
                } else {
                    $inactiveBonuses[] = $bonus;
                }
            }

            $result[$slug] = [
                'set' => $set,
                'equippedCount' => $count,
                'totalPieces' => $set->getItems()->count(),
                'activeBonuses' => $activeBonuses,
                'inactiveBonuses' => $inactiveBonuses,
            ];
        }

        return $result;
    }

    /**
     * Get combat stat bonuses from all active equipment sets.
     *
     * @return array{damage: int, heal: int, hit: int, critical: int, life: int, protection: int}
     */
    public function getSetBonuses(Player $player): array
    {
        $bonuses = [
            'damage' => 0,
            'heal' => 0,
            'hit' => 0,
            'critical' => 0,
            'life' => 0,
            'protection' => 0,
        ];

        foreach ($this->getActiveSets($player) as $data) {
            foreach ($data['activeBonuses'] as $bonus) {
                $type = $bonus->getBonusType();
                if (isset($bonuses[$type])) {
                    $bonuses[$type] += $bonus->getBonusValue();
                }
            }
        }

        return $bonuses;
    }

    /**
     * @return array<string, array{set: EquipmentSet, count: int}>
     */
    private function countEquippedPiecesPerSet(Player $player): array
    {
        $setCounts = [];

        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }

            foreach ($inventory->getItems() as $playerItem) {
                if (!$this->gearHelper->isEquipped($playerItem)) {
                    continue;
                }

                $genericItem = $playerItem->getGenericItem();
                $set = $genericItem->getEquipmentSet();
                if ($set === null) {
                    continue;
                }

                $slug = $set->getSlug();
                if (!isset($setCounts[$slug])) {
                    $setCounts[$slug] = ['set' => $set, 'count' => 0];
                }
                ++$setCounts[$slug]['count'];
            }
        }

        return $setCounts;
    }
}
