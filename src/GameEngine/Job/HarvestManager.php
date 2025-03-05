<?php

namespace App\GameEngine\Job;

use App\Entity\App\ObjectLayer;
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
    )
    {
    }

    /**
     * @throws UnauthorizedHttpException
     */
    public function checkObjectLayer(ObjectLayer $objectLayer)
    {
        // Check si l'objet est déjà utilisé ou pas
        if ($objectLayer->getUsedAt() !== null) {
            throw new UnauthorizedHttpException("Récolte impossible");
        }

        // Check si l'objet est récoltable
        $canBeHarvested = false;
        foreach ($objectLayer->getActions() as $action) {
            $canBeHarvested = $canBeHarvested || $action['action'] === PlayerActionHelper::HARVEST;
        }
        if (!$canBeHarvested) {
            throw new UnauthorizedHttpException("Vous ne pouvez pas récolter " . $objectLayer->getName());
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    public function harvestResources(ObjectLayer $objectLayer, bool $flush = true): ObjectLayer
    {
        $objectLayer = $this->entityManager->getRepository(ObjectLayer::class)->find($objectLayer->getId());
        $items = $this->harvestItemGenerator->generateHarvestItems($objectLayer);
        $objectLayer->setUsedAt(new \DateTime());

        foreach ($items as $item) {
            $this->inventoryHelper->addItem($item, false);
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        $this->eventDispatcher->dispatch(new SpotHarvestEvent($objectLayer), SpotHarvestEvent::NAME);

        return $objectLayer;
    }
}