<?php

namespace App\GameEngine\Fight;

use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Enum\Element;
use Doctrine\ORM\EntityManagerInterface;

class MateriaFusionManager
{
    /**
     * Fusion rules for same-element combinations.
     * Maps element => resulting item slug at next level.
     */
    private const SAME_ELEMENT_UPGRADES = [
        'fire' => 'materia_fire_%d',
        'water' => 'materia_water_%d',
        'earth' => 'materia_earth_%d',
        'air' => 'materia_air_%d',
        'light' => 'materia_light_%d',
        'dark' => 'materia_dark_%d',
        'metal' => 'materia_metal_%d',
        'beast' => 'materia_beast_%d',
    ];

    /**
     * Cross-element fusion results.
     * Keys are sorted alphabetically: "element1+element2".
     */
    private const CROSS_ELEMENT_FUSIONS = [
        'air+fire' => 'materia_inferno',
        'air+water' => 'materia_blizzard',
        'dark+light' => 'materia_eclipse',
        'earth+fire' => 'materia_magma',
        'earth+water' => 'materia_nature',
        'air+earth' => 'materia_sandstorm',
        'dark+fire' => 'materia_shadow_flame',
        'light+water' => 'materia_holy_water',
        'air+light' => 'materia_lightning',
        'dark+earth' => 'materia_void',
        'fire+metal' => 'materia_forge',
        'light+metal' => 'materia_holy_blade',
        'beast+earth' => 'materia_primal',
        'beast+dark' => 'materia_venom',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Check if two materia can be fused together.
     */
    public function canFuse(PlayerItem $materia1, PlayerItem $materia2): bool
    {
        // Both must be materia
        if (!$materia1->isMateria() || !$materia2->isMateria()) {
            return false;
        }

        // Cannot fuse the same item with itself
        if ($materia1->getId() === $materia2->getId()) {
            return false;
        }

        // Both must not be equipped (in a slot)
        if ($materia1->getSlotSet() !== null || $materia2->getSlotSet() !== null) {
            return false;
        }

        $element1 = $materia1->getGenericItem()->getElement();
        $element2 = $materia2->getGenericItem()->getElement();

        // Same element: check if upgrade path exists and level is not maxed
        if ($element1 === $element2) {
            $level1 = $materia1->getMateriaLevel();
            $level2 = $materia2->getMateriaLevel();

            // Max level is 5 — cannot fuse if both are already at max
            if (max($level1, $level2) >= 5) {
                return false;
            }

            $newLevel = min(5, max($level1, $level2) + 1);

            $resultSlug = $this->getSameElementResultSlug($element1->value, $newLevel);

            return $resultSlug !== null && $this->findItemBySlug($resultSlug) !== null;
        }

        // Different elements: check if combination exists
        $comboKey = $this->getCrossElementKey($element1->value, $element2->value);
        if (!isset(self::CROSS_ELEMENT_FUSIONS[$comboKey])) {
            return false;
        }

        $resultSlug = self::CROSS_ELEMENT_FUSIONS[$comboKey];

        return $this->findItemBySlug($resultSlug) !== null;
    }

    /**
     * Fuse two materia into a new one.
     * The two source materia are consumed (removed).
     */
    public function fuse(PlayerItem $materia1, PlayerItem $materia2): PlayerItem
    {
        if (!$this->canFuse($materia1, $materia2)) {
            throw new \LogicException('Ces deux materia ne peuvent pas être fusionnées.');
        }

        $element1 = $materia1->getGenericItem()->getElement();
        $element2 = $materia2->getGenericItem()->getElement();
        $inventory = $materia1->getInventory();

        // Determine result item
        if ($element1 === $element2) {
            $level1 = $materia1->getMateriaLevel();
            $level2 = $materia2->getMateriaLevel();
            $newLevel = min(5, max($level1, $level2) + 1);
            $resultSlug = $this->getSameElementResultSlug($element1->value, $newLevel);
        } else {
            $comboKey = $this->getCrossElementKey($element1->value, $element2->value);
            $resultSlug = self::CROSS_ELEMENT_FUSIONS[$comboKey];
        }

        $resultItem = $this->findItemBySlug($resultSlug);
        if ($resultItem === null) {
            throw new \LogicException(sprintf('Item résultant introuvable : %s', $resultSlug));
        }

        // Combine XP from both materia
        $combinedXp = $materia1->getExperience() + $materia2->getExperience();

        // Create new PlayerItem
        $newPlayerItem = new PlayerItem();
        $newPlayerItem->setGenericItem($resultItem);
        $newPlayerItem->setInventory($inventory);
        $newPlayerItem->setExperience($combinedXp);

        // Remove old materia
        $this->entityManager->remove($materia1);
        $this->entityManager->remove($materia2);

        // Persist new materia
        $this->entityManager->persist($newPlayerItem);
        $this->entityManager->flush();

        return $newPlayerItem;
    }

    /**
     * Get the result slug for same-element fusion.
     */
    private function getSameElementResultSlug(string $element, int $level): ?string
    {
        if (!isset(self::SAME_ELEMENT_UPGRADES[$element])) {
            return null;
        }

        return sprintf(self::SAME_ELEMENT_UPGRADES[$element], $level);
    }

    /**
     * Get the sorted key for cross-element fusion lookup.
     */
    private function getCrossElementKey(string $element1, string $element2): string
    {
        $elements = [$element1, $element2];
        sort($elements);

        return implode('+', $elements);
    }

    /**
     * Find an Item entity by its slug.
     */
    private function findItemBySlug(string $slug): ?Item
    {
        return $this->entityManager->getRepository(Item::class)->findOneBy([
            'slug' => $slug,
        ]);
    }
}
