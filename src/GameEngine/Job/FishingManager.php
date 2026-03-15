<?php

namespace App\GameEngine\Job;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Event\Map\FishingEvent;
use App\GameEngine\Generator\HarvestItemGenerator;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FishingManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HarvestItemGenerator $harvestItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly PlayerHelper $playerHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Vérifie si le joueur peut pêcher à ce spot.
     */
    public function canFish(Player $player, ObjectLayer $spot): bool
    {
        if (!$spot->isHarvestSpot()) {
            return false;
        }

        if ($spot->getRequiredToolType() !== Item::TOOL_TYPE_FISHING_ROD) {
            return false;
        }

        if (!$spot->isAvailable()) {
            return false;
        }

        return $this->getPlayerFishingRod($player) !== null;
    }

    /**
     * Démarre la pêche et retourne les données de tension pour le mini-jeu.
     *
     * @return array{spot_id?: int, spot_name?: string, difficulty?: int, started: bool, error?: string}
     */
    public function startFishing(Player $player, ObjectLayer $spot): array
    {
        if (!$this->canFish($player, $spot)) {
            return ['started' => false, 'error' => 'Impossible de pêcher ici.'];
        }

        // La difficulté dépend du spot
        $items = $spot->getItems() ?? [];
        $difficulty = 50;
        foreach ($items as $item) {
            if (isset($item['difficulty'])) {
                $difficulty = (int) $item['difficulty'];
                break;
            }
        }

        return [
            'spot_id' => $spot->getId(),
            'spot_name' => $spot->getName(),
            'difficulty' => $difficulty,
            'started' => true,
        ];
    }

    /**
     * Termine la pêche en fonction de la valeur de tension (0-100).
     * 30-70 = succès, <30 = trop faible, >70 = trop fort (perte de durabilité).
     *
     * @return array{success: bool, item: ?array, message: string}
     */
    public function completeFishing(Player $player, ObjectLayer $spot, int $tension): array
    {
        $fishingRod = $this->getPlayerFishingRod($player);
        if ($fishingRod === null) {
            return ['success' => false, 'item' => null, 'message' => 'Vous n\'avez pas de canne à pêche.'];
        }

        if ($tension < 30) {
            $this->eventDispatcher->dispatch(
                new FishingEvent($player, $spot),
                FishingEvent::NAME
            );

            return ['success' => false, 'item' => null, 'message' => 'Trop faible ! Le poisson s\'échappe.'];
        }

        if ($tension > 70) {
            $broken = $fishingRod->reduceDurability(2);
            $this->entityManager->persist($fishingRod);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(
                new FishingEvent($player, $spot),
                FishingEvent::NAME
            );

            $message = 'Trop fort ! La ligne casse.';
            if ($broken) {
                $message .= ' Votre canne à pêche est brisée !';
            }

            return ['success' => false, 'item' => null, 'message' => $message];
        }

        // Succès (30-70)
        $items = $this->harvestItemGenerator->generateHarvestItems($spot);
        $caughtItem = $items[0] ?? null;

        if ($caughtItem !== null) {
            $this->inventoryHelper->addItem($caughtItem, false);
        }

        // Réduire la durabilité normalement
        $fishingRod->reduceDurability(1);
        $this->entityManager->persist($fishingRod);

        // Marquer le spot comme utilisé
        $spot->setUsedAt(new \DateTime());
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new FishingEvent($player, $spot, $caughtItem),
            FishingEvent::NAME
        );

        return [
            'success' => true,
            'item' => $caughtItem !== null ? [
                'name' => $caughtItem->getGenericItem()->getName(),
                'slug' => $caughtItem->getGenericItem()->getSlug(),
            ] : null,
            'message' => $caughtItem !== null
                ? 'Vous avez pêché : ' . $caughtItem->getGenericItem()->getName()
                : 'Aucune prise cette fois.',
        ];
    }

    /**
     * Récupère la canne à pêche équipée ou dans l'inventaire du joueur.
     */
    private function getPlayerFishingRod(Player $player): ?PlayerItem
    {
        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }
            foreach ($inventory->getItems() as $playerItem) {
                $genericItem = $playerItem->getGenericItem();
                if ($genericItem->isTool()
                    && $genericItem->getToolType() === Item::TOOL_TYPE_FISHING_ROD
                    && ($playerItem->getCurrentDurability() === null || $playerItem->getCurrentDurability() > 0)
                ) {
                    return $playerItem;
                }
            }
        }

        return null;
    }
}
