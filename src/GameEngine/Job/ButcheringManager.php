<?php

namespace App\GameEngine\Job;

use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Event\Map\ButcheringEvent;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ButcheringManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Vérifie si le joueur possède un couteau de dépeçage fonctionnel.
     */
    public function canButcher(Player $player): bool
    {
        return $this->getPlayerSkinningKnife($player) !== null;
    }

    /**
     * Dépèce un mob vaincu et retourne les matériaux obtenus.
     *
     * @return array{success: bool, items: array, message: string}
     */
    public function butcher(Player $player, Mob $mob): array
    {
        $knife = $this->getPlayerSkinningKnife($player);
        if ($knife === null) {
            return [
                'success' => false,
                'items' => [],
                'message' => 'Vous n\'avez pas de couteau de dépeçage.',
            ];
        }

        $monster = $mob->getMonster();
        $monsterItems = $monster->getMonsterItems();

        /** @var PlayerItem[] $harvestedItems */
        $harvestedItems = [];

        // Calculer le nombre de matériaux basé sur le tier de l'outil et le niveau du monstre
        $toolTier = $knife->getGenericItem()->getToolTier() ?? 1;
        $baseDropCount = 1;
        $bonusChance = min(80, $toolTier * 15); // 15-60% de chance de drop bonus

        foreach ($monsterItems as $monsterItem) {
            $dropChance = (int) ($monsterItem->getProbability() * 100);
            $roll = random_int(1, 100);

            // L'outil de meilleur tier améliore les chances
            $adjustedChance = min(100, $dropChance + ($toolTier * 5));

            if ($roll <= $adjustedChance) {
                try {
                    $playerItem = $this->playerItemGenerator->generateFromItemId(
                        $monsterItem->getItem()->getId()
                    );
                    $this->inventoryHelper->addItem($playerItem, false);
                    $harvestedItems[] = $playerItem;
                } catch (\Exception) {
                    // Item non trouvé, on continue
                }
            }
        }

        // Réduire la durabilité du couteau
        $broken = $knife->reduceDurability(1);
        $this->entityManager->persist($knife);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new ButcheringEvent($player, $mob, $harvestedItems),
            ButcheringEvent::NAME
        );

        $itemNames = array_map(
            fn(PlayerItem $pi) => $pi->getGenericItem()->getName(),
            $harvestedItems
        );

        $message = count($harvestedItems) > 0
            ? 'Vous avez obtenu : ' . implode(', ', $itemNames)
            : 'Aucun matériau obtenu.';

        if ($broken) {
            $message .= ' Votre couteau de dépeçage est brisé !';
        }

        return [
            'success' => count($harvestedItems) > 0,
            'items' => array_map(fn(PlayerItem $pi) => [
                'name' => $pi->getGenericItem()->getName(),
                'slug' => $pi->getGenericItem()->getSlug(),
            ], $harvestedItems),
            'message' => $message,
        ];
    }

    /**
     * Récupère le couteau de dépeçage du joueur.
     */
    private function getPlayerSkinningKnife(Player $player): ?PlayerItem
    {
        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }
            foreach ($inventory->getItems() as $playerItem) {
                $genericItem = $playerItem->getGenericItem();
                if ($genericItem->isTool()
                    && $genericItem->getToolType() === Item::TOOL_TYPE_SKINNING_KNIFE
                    && ($playerItem->getCurrentDurability() === null || $playerItem->getCurrentDurability() > 0)
                ) {
                    return $playerItem;
                }
            }
        }

        return null;
    }
}
