<?php

namespace App\GameEngine\Enchantment;

use App\Entity\App\Enchantment;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\EnchantmentDefinition;
use App\GameEngine\Crafting\CraftingManager;
use Doctrine\ORM\EntityManagerInterface;

class EnchantmentManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CraftingManager $craftingManager,
    ) {
    }

    /**
     * Retourne les enchantements disponibles pour le joueur (selon son niveau alchimiste).
     *
     * @return EnchantmentDefinition[]
     */
    public function getAvailableDefinitions(Player $player): array
    {
        $definitions = $this->entityManager->getRepository(EnchantmentDefinition::class)->findAll();
        $alchemyLevel = $this->craftingManager->getCraftingLevel($player, 'alchimiste');

        return array_filter($definitions, fn (EnchantmentDefinition $d) => $alchemyLevel >= $d->getRequiredLevel());
    }

    /**
     * Verifie si un joueur peut appliquer un enchantement sur un item.
     *
     * @return array{possible: bool, reason: ?string}
     */
    public function canEnchant(Player $player, PlayerItem $playerItem, EnchantmentDefinition $definition): array
    {
        // Verifier que l'item est un equipement
        if (!$playerItem->isGear()) {
            return ['possible' => false, 'reason' => 'Seuls les equipements peuvent etre enchantes.'];
        }

        // Verifier que l'item est equipe
        if ($playerItem->getGear() === 0) {
            return ['possible' => false, 'reason' => 'L\'objet doit etre equipe pour etre enchante.'];
        }

        // Verifier le niveau alchimiste
        $alchemyLevel = $this->craftingManager->getCraftingLevel($player, 'alchimiste');
        if ($alchemyLevel < $definition->getRequiredLevel()) {
            return ['possible' => false, 'reason' => sprintf('Niveau alchimiste insuffisant (%d/%d).', $alchemyLevel, $definition->getRequiredLevel())];
        }

        // Verifier que l'item n'a pas deja un enchantement actif
        $existing = $this->getActiveEnchantment($playerItem);
        if ($existing !== null) {
            return ['possible' => false, 'reason' => 'Cet objet possede deja un enchantement actif.'];
        }

        // Verifier les ingredients
        $bagItems = $this->getBagItemsBySlug($player);
        foreach ($definition->getIngredients() as $ingredient) {
            $slug = $ingredient['slug'];
            $required = $ingredient['quantity'] ?? 1;
            $available = $bagItems[$slug] ?? 0;
            if ($available < $required) {
                return ['possible' => false, 'reason' => sprintf('Ingredient manquant : %s (%d/%d).', $slug, $available, $required)];
            }
        }

        // Verifier le cout en gils
        if ($definition->getCost() > 0 && $player->getGils() < $definition->getCost()) {
            return ['possible' => false, 'reason' => sprintf('Gils insuffisants (%d/%d).', $player->getGils(), $definition->getCost())];
        }

        return ['possible' => true, 'reason' => null];
    }

    /**
     * Applique un enchantement sur un item equipe.
     *
     * @return array{success: bool, message: string, enchantment: ?Enchantment}
     */
    public function apply(Player $player, PlayerItem $playerItem, EnchantmentDefinition $definition): array
    {
        $check = $this->canEnchant($player, $playerItem, $definition);
        if (!$check['possible']) {
            return ['success' => false, 'message' => $check['reason'], 'enchantment' => null];
        }

        // Consommer les ingredients
        $this->removeIngredients($player, $definition);

        // Consommer les gils
        if ($definition->getCost() > 0) {
            $player->removeGils($definition->getCost());
        }

        // Creer l'enchantement
        $enchantment = new Enchantment();
        $enchantment->setPlayerItem($playerItem);
        $enchantment->setDefinition($definition);
        $enchantment->setAppliedAt(new \DateTime());

        $expiresAt = new \DateTime();
        $expiresAt->modify(sprintf('+%d seconds', $definition->getDuration()));
        $enchantment->setExpiresAt($expiresAt);

        $this->entityManager->persist($enchantment);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => sprintf(
                'Enchantement « %s » applique sur %s pour %s.',
                $definition->getName(),
                $playerItem->getGenericItem()->getName(),
                $this->formatDuration($definition->getDuration())
            ),
            'enchantment' => $enchantment,
        ];
    }

    /**
     * Retire un enchantement (expiration forcee).
     */
    public function remove(Enchantment $enchantment): void
    {
        $this->entityManager->remove($enchantment);
        $this->entityManager->flush();
    }

    /**
     * Nettoie tous les enchantements expires.
     */
    public function cleanExpired(): int
    {
        $allEnchantments = $this->entityManager->getRepository(Enchantment::class)->findAll();
        $removed = 0;

        foreach ($allEnchantments as $enchantment) {
            if ($enchantment->isExpired()) {
                $this->entityManager->remove($enchantment);
                ++$removed;
            }
        }

        if ($removed > 0) {
            $this->entityManager->flush();
        }

        return $removed;
    }

    /**
     * Nettoie les enchantements expires pour un joueur specifique.
     */
    public function cleanExpiredForPlayer(Player $player): int
    {
        $enchantments = $this->getActiveEnchantmentsForPlayer($player);
        $removed = 0;

        foreach ($enchantments as $enchantment) {
            if ($enchantment->isExpired()) {
                $this->entityManager->remove($enchantment);
                ++$removed;
            }
        }

        if ($removed > 0) {
            $this->entityManager->flush();
        }

        return $removed;
    }

    /**
     * Retourne l'enchantement actif sur un PlayerItem (ou null).
     */
    public function getActiveEnchantment(PlayerItem $playerItem): ?Enchantment
    {
        $enchantments = $this->entityManager->getRepository(Enchantment::class)->findBy([
            'playerItem' => $playerItem,
        ]);

        foreach ($enchantments as $enchantment) {
            if (!$enchantment->isExpired()) {
                return $enchantment;
            }
        }

        return null;
    }

    /**
     * Retourne tous les enchantements actifs sur les items equipes d'un joueur.
     *
     * @return Enchantment[]
     */
    public function getActiveEnchantmentsForPlayer(Player $player): array
    {
        $enchantments = [];

        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() === 0) {
                    continue;
                }

                $enchantment = $this->getActiveEnchantment($playerItem);
                if ($enchantment !== null) {
                    $enchantments[] = $enchantment;
                }
            }
        }

        return $enchantments;
    }

    /**
     * Calcule les bonus de stats agrégés de tous les enchantements actifs sur les items equipes.
     *
     * @return array<string, float|int>
     */
    public function getEnchantmentBonuses(Player $player): array
    {
        $bonuses = [];
        $enchantments = $this->getActiveEnchantmentsForPlayer($player);

        foreach ($enchantments as $enchantment) {
            foreach ($enchantment->getDefinition()->getStatBonuses() as $stat => $value) {
                if (!isset($bonuses[$stat])) {
                    $bonuses[$stat] = 0;
                }
                $bonuses[$stat] += $value;
            }
        }

        return $bonuses;
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

    private function removeIngredients(Player $player, EnchantmentDefinition $definition): void
    {
        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }

            foreach ($definition->getIngredients() as $ingredient) {
                $slug = $ingredient['slug'];
                $remainingToRemove = $ingredient['quantity'] ?? 1;

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
        }
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds >= 3600) {
            $hours = (int) floor($seconds / 3600);

            return $hours . 'h';
        }

        $minutes = (int) floor($seconds / 60);

        return $minutes . 'min';
    }
}
