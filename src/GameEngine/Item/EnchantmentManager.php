<?php

namespace App\GameEngine\Item;

use App\Entity\App\Enchantment;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Enum\Element;
use App\Helper\GearHelper;
use App\Repository\EnchantmentRepository;
use Doctrine\ORM\EntityManagerInterface;

class EnchantmentManager
{
    public const DEFINITIONS = [
        'fire_edge' => [
            'name' => 'Tranchant de feu',
            'stat' => 'damage',
            'value' => 5,
            'element' => 'fire',
            'duration' => 3600,
            'ingredients' => [
                ['slug' => 'plant-dragonleaf', 'quantity' => 1],
                ['slug' => 'ore-ruby', 'quantity' => 1],
            ],
            'required_skill' => 'alchi-buff-pot',
            'gear_types' => ['main_weapon', 'side_weapon'],
        ],
        'ice_ward' => [
            'name' => 'Protection de glace',
            'stat' => 'protection',
            'value' => 3,
            'element' => 'water',
            'duration' => 1800,
            'ingredients' => [
                ['slug' => 'plant-frostcap', 'quantity' => 1],
                ['slug' => 'ore-silver', 'quantity' => 1],
            ],
            'required_skill' => 'alchi-buff-pot',
            'gear_types' => ['chest', 'head', 'leg', 'shoulder'],
        ],
        'thunder_strike' => [
            'name' => 'Frappe de foudre',
            'stat' => 'critical',
            'value' => 3,
            'element' => 'air',
            'duration' => 2700,
            'ingredients' => [
                ['slug' => 'plant-thunderroot', 'quantity' => 1],
                ['slug' => 'ore-copper', 'quantity' => 2],
            ],
            'required_skill' => 'alchi-buff-pot',
            'gear_types' => ['main_weapon', 'side_weapon'],
        ],
        'nature_blessing' => [
            'name' => 'Benediction naturelle',
            'stat' => 'hit',
            'value' => 4,
            'element' => 'earth',
            'duration' => 3600,
            'ingredients' => [
                ['slug' => 'plant-moonflower', 'quantity' => 1],
                ['slug' => 'plant-sage', 'quantity' => 2],
            ],
            'required_skill' => 'alchi-buff-pot',
            'gear_types' => ['main_weapon', 'side_weapon', 'hand', 'ring_1', 'ring_2'],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EnchantmentRepository $enchantmentRepository,
        private readonly GearHelper $gearHelper,
    ) {
    }

    /**
     * Apply an enchantment to a PlayerItem.
     */
    public function apply(PlayerItem $playerItem, string $enchantType): Enchantment
    {
        if (!isset(self::DEFINITIONS[$enchantType])) {
            throw new \InvalidArgumentException(sprintf('Unknown enchantment type: %s', $enchantType));
        }

        $def = self::DEFINITIONS[$enchantType];

        // Remove any existing enchantment on this item
        $existing = $this->enchantmentRepository->findActiveByPlayerItem($playerItem);
        if ($existing !== null) {
            $this->entityManager->remove($existing);
        }

        $enchantment = new Enchantment();
        $enchantment->setPlayerItem($playerItem);
        $enchantment->setType($enchantType);
        $enchantment->setName($def['name']);
        $enchantment->setStat($def['stat']);
        $enchantment->setValue($def['value']);
        $enchantment->setElement(Element::from($def['element']));
        $enchantment->setExpiresAt(new \DateTime(sprintf('+%d seconds', $def['duration'])));

        $this->entityManager->persist($enchantment);

        return $enchantment;
    }

    /**
     * Remove expired enchantments from the database.
     */
    public function removeExpired(): int
    {
        return $this->enchantmentRepository->removeExpired();
    }

    /**
     * Remove a specific enchantment.
     */
    public function remove(Enchantment $enchantment): void
    {
        $this->entityManager->remove($enchantment);
    }

    /**
     * Get the active enchantment on a PlayerItem, or null.
     */
    public function getActiveEnchantment(PlayerItem $playerItem): ?Enchantment
    {
        return $this->enchantmentRepository->findActiveByPlayerItem($playerItem);
    }

    /**
     * Get combat stat bonuses from enchantments on equipped items.
     *
     * @return array{damage: int, heal: int, hit: int, critical: int, life: int, protection: int}
     */
    public function getEquippedEnchantmentBonuses(Player $player): array
    {
        $bonuses = [
            'damage' => 0,
            'heal' => 0,
            'hit' => 0,
            'critical' => 0,
            'life' => 0,
            'protection' => 0,
        ];

        $enchantments = $this->enchantmentRepository->findActiveByPlayer($player);

        foreach ($enchantments as $enchantment) {
            $playerItem = $enchantment->getPlayerItem();
            if (!$this->gearHelper->isEquipped($playerItem)) {
                continue;
            }

            $stat = $enchantment->getStat();
            if (isset($bonuses[$stat])) {
                $bonuses[$stat] += $enchantment->getValue();
            }
        }

        return $bonuses;
    }

    /**
     * Check if a player has the required ingredients for an enchantment.
     *
     * @return array{possible: bool, missing: array<array{slug: string, need: int, have: int}>}
     */
    public function canApply(Player $player, string $enchantType): array
    {
        if (!isset(self::DEFINITIONS[$enchantType])) {
            return ['possible' => false, 'missing' => []];
        }

        $def = self::DEFINITIONS[$enchantType];
        $bagItems = $this->getBagItemsBySlug($player);
        $missing = [];

        foreach ($def['ingredients'] as $ingredient) {
            $slug = $ingredient['slug'];
            $requiredQty = $ingredient['quantity'];
            $available = $bagItems[$slug] ?? 0;

            if ($available < $requiredQty) {
                $missing[] = [
                    'slug' => $slug,
                    'need' => $requiredQty,
                    'have' => $available,
                ];
            }
        }

        return [
            'possible' => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Check if a PlayerItem is a valid target for a given enchantment type.
     */
    public function isValidTarget(PlayerItem $playerItem, string $enchantType): bool
    {
        if (!isset(self::DEFINITIONS[$enchantType])) {
            return false;
        }

        if (!$playerItem->isGear()) {
            return false;
        }

        $gearLocation = $playerItem->getGenericItem()->getGearLocation();
        $allowedTypes = self::DEFINITIONS[$enchantType]['gear_types'];

        return in_array($gearLocation, $allowedTypes, true);
    }

    /**
     * Check if a player has the required skill for an enchantment.
     */
    public function hasRequiredSkill(Player $player, string $enchantType): bool
    {
        if (!isset(self::DEFINITIONS[$enchantType])) {
            return false;
        }

        $requiredSkillSlug = self::DEFINITIONS[$enchantType]['required_skill'];

        foreach ($player->getSkills() as $skill) {
            if ($skill->getSlug() === $requiredSkillSlug) {
                return true;
            }
        }

        return false;
    }

    /**
     * Consume ingredients from the player's bag.
     */
    public function consumeIngredients(Player $player, string $enchantType): void
    {
        if (!isset(self::DEFINITIONS[$enchantType])) {
            throw new \InvalidArgumentException(sprintf('Unknown enchantment type: %s', $enchantType));
        }

        $def = self::DEFINITIONS[$enchantType];

        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }

            foreach ($def['ingredients'] as $ingredient) {
                $slug = $ingredient['slug'];
                $remainingToRemove = $ingredient['quantity'];

                foreach ($inventory->getItems()->toArray() as $playerItem) {
                    if ($remainingToRemove <= 0) {
                        break;
                    }

                    if ($playerItem->getGenericItem()->getSlug() === $slug) {
                        $inventory->removeItem($playerItem);
                        $playerItem->setInventory(null);
                        $this->entityManager->remove($playerItem);
                        --$remainingToRemove;
                    }
                }
            }

            break;
        }
    }

    /**
     * @return array<string, int>
     */
    private function getBagItemsBySlug(Player $player): array
    {
        $counts = [];

        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }
            foreach ($inventory->getItems() as $playerItem) {
                $slug = $playerItem->getGenericItem()->getSlug();
                $counts[$slug] = ($counts[$slug] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getDefinitions(): array
    {
        return self::DEFINITIONS;
    }
}
