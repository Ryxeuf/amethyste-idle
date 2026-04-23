<?php

namespace App\GameEngine\Job;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Event\Map\FishingEvent;
use App\GameEngine\Generator\HarvestItemGenerator;
use App\Helper\GearHelper;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FishingManager
{
    public const SUCCESS_MIN = 30;
    public const SUCCESS_MAX = 70;
    public const PERFECT_MIN = 45;
    public const PERFECT_MAX = 55;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HarvestItemGenerator $harvestItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly GearHelper $gearHelper,
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

        $rod = $this->getPlayerFishingRod($player);

        return $rod !== null && ($rod->getCurrentDurability() === null || $rod->getCurrentDurability() > 0);
    }

    /**
     * Démarre la pêche et retourne les données de tension pour le mini-jeu.
     *
     * @return array{spot_id?: int, spot_name?: string, difficulty?: int, started: bool, error?: string}
     */
    public function startFishing(Player $player, ObjectLayer $spot): array
    {
        if (!$this->canFish($player, $spot)) {
            // Donner un message d'erreur précis
            $rod = $this->getPlayerFishingRod($player);
            if ($rod === null) {
                if (!$player->hasToolSlot(Item::TOOL_TYPE_FISHING_ROD)) {
                    return ['started' => false, 'error' => 'Vous devez débloquer l\'emplacement de canne à pêche via l\'arbre de compétences.'];
                }

                return ['started' => false, 'error' => 'Équipez une canne à pêche dans votre emplacement d\'outil.'];
            }
            if ($rod->getCurrentDurability() !== null && $rod->getCurrentDurability() <= 0) {
                return ['started' => false, 'error' => 'Votre canne à pêche est cassée. Réparez-la avant de continuer.'];
            }

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
     * 30-70 = succès, 45-55 = zone parfaite (pas d'usure), <30 = trop faible, >70 = trop fort.
     *
     * @return array{success: bool, perfect: bool, item: ?array, message: string}
     */
    public function completeFishing(Player $player, ObjectLayer $spot, int $tension): array
    {
        $fishingRod = $this->getPlayerFishingRod($player);
        if ($fishingRod === null) {
            return ['success' => false, 'perfect' => false, 'item' => null, 'message' => 'Vous n\'avez pas de canne à pêche.'];
        }

        if ($tension < self::SUCCESS_MIN) {
            $this->eventDispatcher->dispatch(
                new FishingEvent($player, $spot),
                FishingEvent::NAME
            );

            return ['success' => false, 'perfect' => false, 'item' => null, 'message' => 'Trop faible ! Le poisson s\'échappe.'];
        }

        if ($tension > self::SUCCESS_MAX) {
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

            return ['success' => false, 'perfect' => false, 'item' => null, 'message' => $message];
        }

        // Succès (30-70) — zone parfaite (45-55) preserve la durabilite de la canne
        $perfect = $tension >= self::PERFECT_MIN && $tension <= self::PERFECT_MAX;

        $items = $this->harvestItemGenerator->generateHarvestItems($spot);
        $caughtItem = $items[0] ?? null;

        if ($caughtItem !== null) {
            $this->inventoryHelper->addItem($caughtItem, false);
        }

        if (!$perfect) {
            $fishingRod->reduceDurability(1);
            $this->entityManager->persist($fishingRod);
        }

        // Marquer le spot comme utilisé
        $spot->setUsedAt(new \DateTime());
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new FishingEvent($player, $spot, $caughtItem, $perfect),
            FishingEvent::NAME
        );

        $message = $caughtItem !== null
            ? 'Vous avez pêché : ' . $caughtItem->getGenericItem()->getName()
            : 'Aucune prise cette fois.';

        if ($perfect && $caughtItem !== null) {
            $message = 'Parfait ! ' . $message;
        }

        return [
            'success' => true,
            'perfect' => $perfect,
            'item' => $caughtItem !== null ? [
                'name' => $caughtItem->getGenericItem()->getName(),
                'slug' => $caughtItem->getGenericItem()->getSlug(),
            ] : null,
            'message' => $message,
        ];
    }

    /**
     * Récupère la canne à pêche équipée dans l'emplacement d'outil.
     */
    private function getPlayerFishingRod(Player $player): ?PlayerItem
    {
        return $this->gearHelper->getEquippedToolByType(Item::TOOL_TYPE_FISHING_ROD);
    }
}
