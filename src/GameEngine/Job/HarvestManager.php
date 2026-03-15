<?php

namespace App\GameEngine\Job;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Event\Map\SpotHarvestEvent;
use App\GameEngine\Generator\HarvestItemGenerator;
use App\GameEngine\Player\PlayerActionHelper;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HarvestManager
{
    public function __construct(
        private readonly HarvestItemGenerator $harvestItemGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly InventoryHelper $inventoryHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws UnauthorizedHttpException
     */
    public function checkObjectLayer(ObjectLayer $objectLayer): void
    {
        // Check si le spot est disponible (respawn)
        if (!$objectLayer->isAvailable()) {
            throw new UnauthorizedHttpException('Récolte impossible');
        }

        // Check si l'objet est récoltable
        $canBeHarvested = false;
        foreach ($objectLayer->getActions() as $action) {
            $canBeHarvested = $canBeHarvested || $action['action'] === PlayerActionHelper::HARVEST;
        }
        if (!$canBeHarvested) {
            throw new UnauthorizedHttpException('Vous ne pouvez pas récolter ' . $objectLayer->getName());
        }
    }

    /**
     * Vérifie que le joueur possède l'outil requis pour ce spot de récolte.
     * Retourne le PlayerItem de l'outil, ou null si aucun outil n'est requis.
     *
     * @throws UnauthorizedHttpException
     */
    public function checkToolRequirement(Player $player, ObjectLayer $objectLayer): ?PlayerItem
    {
        $requiredToolType = $objectLayer->getRequiredToolType();
        if ($requiredToolType === null) {
            return null;
        }

        $tool = $this->findPlayerTool($player, $requiredToolType);
        if ($tool === null) {
            $toolNames = [
                Item::TOOL_TYPE_PICKAXE => 'une pioche',
                Item::TOOL_TYPE_SICKLE => 'une faucille',
                Item::TOOL_TYPE_FISHING_ROD => 'une canne à pêche',
                Item::TOOL_TYPE_SKINNING_KNIFE => 'un couteau de dépeçage',
            ];
            $toolName = $toolNames[$requiredToolType] ?? 'un outil adapté';
            throw new UnauthorizedHttpException("Vous avez besoin de {$toolName} pour récolter ici.");
        }

        return $tool;
    }

    /**
     * @return array{objectLayer: ObjectLayer, items: PlayerItem[], toolBroken: bool}
     *
     * @throws EntityNotFoundException
     */
    public function harvestResources(ObjectLayer $objectLayer, ?Player $player = null, bool $flush = true): array
    {
        $objectLayer = $this->entityManager->getRepository(ObjectLayer::class)->find($objectLayer->getId());
        $items = $this->harvestItemGenerator->generateHarvestItems($objectLayer);
        $objectLayer->setUsedAt(new \DateTime());

        // Vérifier et réduire la durabilité de l'outil si nécessaire
        $toolBroken = false;
        if ($player !== null) {
            $tool = $this->findPlayerTool($player, $objectLayer->getRequiredToolType());
            if ($tool !== null) {
                $toolBroken = $tool->reduceDurability(1);
                $this->entityManager->persist($tool);
            }
        }

        foreach ($items as $item) {
            $this->inventoryHelper->addItem($item, false);
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        $this->eventDispatcher->dispatch(new SpotHarvestEvent($objectLayer), SpotHarvestEvent::NAME);

        return [
            'objectLayer' => $objectLayer,
            'items' => $items,
            'toolBroken' => $toolBroken,
        ];
    }

    /**
     * Trouve un outil fonctionnel du type demandé dans l'inventaire du joueur.
     */
    private function findPlayerTool(Player $player, ?string $toolType): ?PlayerItem
    {
        if ($toolType === null) {
            return null;
        }

        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }
            foreach ($inventory->getItems() as $playerItem) {
                $genericItem = $playerItem->getGenericItem();
                if ($genericItem->isTool()
                    && $genericItem->getToolType() === $toolType
                    && ($playerItem->getCurrentDurability() === null || $playerItem->getCurrentDurability() > 0)
                ) {
                    return $playerItem;
                }
            }
        }

        return null;
    }
}
