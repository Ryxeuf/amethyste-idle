<?php

namespace App\GameEngine\Job;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Event\Map\SpotHarvestEvent;
use App\GameEngine\Generator\HarvestItemGenerator;
use App\GameEngine\Player\PlayerActionHelper;
use App\Helper\GearHelper;
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
        private readonly GearHelper $gearHelper,
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

        $toolName = Item::TOOL_TYPE_LABELS[$requiredToolType] ?? 'un outil adapté';

        // Vérifier que l'emplacement d'outil est débloqué
        if (!$player->hasToolSlot($requiredToolType)) {
            throw new UnauthorizedHttpException("Vous devez débloquer l'emplacement de {$toolName} via l'arbre de compétences correspondant.");
        }

        // Vérifier qu'un outil est équipé dans le slot
        $tool = $this->gearHelper->getEquippedToolByType($requiredToolType);
        if ($tool === null) {
            throw new UnauthorizedHttpException("Équipez {$toolName} dans votre emplacement d'outil pour récolter ici.");
        }

        // Vérifier la durabilité
        if ($tool->getCurrentDurability() !== null && $tool->getCurrentDurability() <= 0) {
            throw new UnauthorizedHttpException("Votre outil est cassé. Réparez-le avant de continuer.");
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
        if ($player !== null && $objectLayer->getRequiredToolType() !== null) {
            $tool = $this->gearHelper->getEquippedToolByType($objectLayer->getRequiredToolType());
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

        $this->eventDispatcher->dispatch(new SpotHarvestEvent($objectLayer, $items), SpotHarvestEvent::NAME);

        return [
            'objectLayer' => $objectLayer,
            'items' => $items,
            'toolBroken' => $toolBroken,
        ];
    }
}
