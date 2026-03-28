<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\Region;
use App\Entity\App\RegionControl;
use App\Entity\App\RegionUpgrade;
use Doctrine\ORM\EntityManagerInterface;

class RegionUpgradeManager
{
    /**
     * Upgrade definitions: slug => [label, maxLevel, costs per level].
     *
     * @var array<string, array{label: string, description: string, maxLevel: int, costs: list<int>, effects: list<string>}>
     */
    public const UPGRADES = [
        'shop_discount' => [
            'label' => 'Reduction boutique',
            'description' => 'Reduit les prix des boutiques PNJ dans la region pour les membres de la guilde.',
            'maxLevel' => 3,
            'costs' => [500, 1500, 4000],
            'effects' => ['-5% supplementaire', '-10% supplementaire', '-15% supplementaire'],
        ],
        'gathering_bonus' => [
            'label' => 'Bonus de recolte',
            'description' => 'Augmente les quantites recoltees dans la region pour les membres de la guilde.',
            'maxLevel' => 3,
            'costs' => [600, 1800, 5000],
            'effects' => ['+10% quantite', '+20% quantite', '+30% quantite'],
        ],
        'xp_bonus' => [
            'label' => 'Bonus d\'experience',
            'description' => 'Augmente l\'experience gagnee dans la region pour les membres de la guilde.',
            'maxLevel' => 2,
            'costs' => [1000, 3000],
            'effects' => ['+5% XP', '+10% XP'],
        ],
        'monument' => [
            'label' => 'Monument de guilde',
            'description' => 'Erige un monument au nom de la guilde dans la capitale de la region.',
            'maxLevel' => 1,
            'costs' => [10000],
            'effects' => ['Monument visible sur la carte'],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TownControlManager $townControlManager,
    ) {
    }

    /**
     * Returns all upgrades for the active control of a region.
     *
     * @return array<string, RegionUpgrade>
     */
    public function getUpgradesForRegion(Region $region): array
    {
        $control = $this->townControlManager->getActiveControl($region);
        if ($control === null) {
            return [];
        }

        return $this->getUpgradesForControl($control);
    }

    /**
     * @return array<string, RegionUpgrade>
     */
    public function getUpgradesForControl(RegionControl $control): array
    {
        $upgrades = $this->entityManager->getRepository(RegionUpgrade::class)->findBy([
            'regionControl' => $control,
        ]);

        $indexed = [];
        foreach ($upgrades as $upgrade) {
            $indexed[$upgrade->getUpgradeSlug()] = $upgrade;
        }

        return $indexed;
    }

    /**
     * Returns the effective level of an upgrade for a region (0 if none).
     */
    public function getUpgradeLevel(Region $region, string $upgradeSlug): int
    {
        $upgrades = $this->getUpgradesForRegion($region);

        return isset($upgrades[$upgradeSlug]) ? $upgrades[$upgradeSlug]->getLevel() : 0;
    }

    /**
     * Checks if a guild can purchase/upgrade a specific upgrade.
     */
    public function canPurchase(Guild $guild, Region $region, string $upgradeSlug): bool
    {
        if (!isset(self::UPGRADES[$upgradeSlug])) {
            return false;
        }

        $control = $this->townControlManager->getActiveControl($region);
        if ($control === null || $control->getGuild() === null) {
            return false;
        }

        if ($control->getGuild()->getId() !== $guild->getId()) {
            return false;
        }

        $currentLevel = $this->getUpgradeLevel($region, $upgradeSlug);
        $def = self::UPGRADES[$upgradeSlug];

        if ($currentLevel >= $def['maxLevel']) {
            return false;
        }

        $cost = $def['costs'][$currentLevel];

        return $guild->getGilsTreasury() >= $cost;
    }

    /**
     * Purchases or upgrades a region upgrade. Deducts cost from guild treasury.
     *
     * @throws \InvalidArgumentException if purchase is not possible
     */
    public function purchase(Guild $guild, Region $region, string $upgradeSlug): RegionUpgrade
    {
        if (!isset(self::UPGRADES[$upgradeSlug])) {
            throw new \InvalidArgumentException('Amelioration inconnue.');
        }

        $control = $this->townControlManager->getActiveControl($region);
        if ($control === null || $control->getGuild() === null) {
            throw new \InvalidArgumentException('Aucune guilde ne controle cette region.');
        }

        if ($control->getGuild()->getId() !== $guild->getId()) {
            throw new \InvalidArgumentException('Votre guilde ne controle pas cette region.');
        }

        $def = self::UPGRADES[$upgradeSlug];
        $upgrades = $this->getUpgradesForControl($control);
        $existing = $upgrades[$upgradeSlug] ?? null;
        $currentLevel = $existing?->getLevel() ?? 0;

        if ($currentLevel >= $def['maxLevel']) {
            throw new \InvalidArgumentException('Amelioration deja au niveau maximum.');
        }

        $cost = $def['costs'][$currentLevel];

        if ($guild->getGilsTreasury() < $cost) {
            throw new \InvalidArgumentException(sprintf('Tresor insuffisant (%d / %d Gils).', $guild->getGilsTreasury(), $cost));
        }

        $guild->addGilsTreasury(-$cost);

        if ($existing !== null) {
            $existing->setLevel($currentLevel + 1);
            $existing->setCostGils($existing->getCostGils() + $cost);
            $existing->setActivatedAt(new \DateTime());

            $this->entityManager->flush();

            return $existing;
        }

        $upgrade = new RegionUpgrade();
        $upgrade->setRegionControl($control);
        $upgrade->setUpgradeSlug($upgradeSlug);
        $upgrade->setLevel(1);
        $upgrade->setCostGils($cost);
        $upgrade->setActivatedAt(new \DateTime());
        $upgrade->setCreatedAt(new \DateTime());
        $upgrade->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($upgrade);
        $this->entityManager->flush();

        return $upgrade;
    }

    /**
     * Returns a summary of all upgrades for display, with current levels and next costs.
     *
     * @return list<array{slug: string, label: string, description: string, currentLevel: int, maxLevel: int, nextCost: int|null, effect: string|null, nextEffect: string|null}>
     */
    public function getUpgradeSummary(Region $region): array
    {
        $upgrades = $this->getUpgradesForRegion($region);
        $summary = [];

        foreach (self::UPGRADES as $slug => $def) {
            $currentLevel = isset($upgrades[$slug]) ? $upgrades[$slug]->getLevel() : 0;
            $nextCost = $currentLevel < $def['maxLevel'] ? $def['costs'][$currentLevel] : null;
            $currentEffect = $currentLevel > 0 ? $def['effects'][$currentLevel - 1] : null;
            $nextEffect = $currentLevel < $def['maxLevel'] ? $def['effects'][$currentLevel] : null;

            $summary[] = [
                'slug' => $slug,
                'label' => $def['label'],
                'description' => $def['description'],
                'currentLevel' => $currentLevel,
                'maxLevel' => $def['maxLevel'],
                'nextCost' => $nextCost,
                'effect' => $currentEffect,
                'nextEffect' => $nextEffect,
            ];
        }

        return $summary;
    }
}
