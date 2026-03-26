<?php

namespace App\EventListener;

use App\Entity\App\Player;
use App\Entity\App\PlayerResourceCatalog;
use App\Entity\Game\Item;
use App\Event\GatheringEvent;
use App\Event\Map\SpotHarvestEvent;
use App\Helper\PlayerHelper;
use App\Repository\PlayerResourceCatalogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResourceCatalogListener implements EventSubscriberInterface
{
    private const RESOURCE_TYPES = ['herb', 'ore', 'plant', 'resource'];

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerResourceCatalogRepository $catalogRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SpotHarvestEvent::NAME => 'onSpotHarvest',
            GatheringEvent::NAME => 'onGathering',
        ];
    }

    public function onSpotHarvest(SpotHarvestEvent $event): void
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return;
        }

        foreach ($event->getHarvestedItems() as $playerItem) {
            $item = $playerItem->getGenericItem();
            if (!$this->isTrackableResource($item->getType())) {
                continue;
            }

            $this->trackResource($player, $item);
        }

        $this->entityManager->flush();
    }

    public function onGathering(GatheringEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if (!$this->isTrackableResource($item->getType())) {
            return;
        }

        $this->trackResource($player, $item, $event->getQuantity());
        $this->entityManager->flush();
    }

    private function isTrackableResource(string $type): bool
    {
        return \in_array($type, self::RESOURCE_TYPES, true);
    }

    private function trackResource(Player $player, Item $item, int $quantity = 1): void
    {
        $entry = $this->catalogRepository->findOneByPlayerAndItem($player, $item);

        if ($entry === null) {
            $entry = new PlayerResourceCatalog($player, $item, $quantity);
            $this->entityManager->persist($entry);
        } else {
            $entry->incrementCollectCount($quantity);
        }
    }
}
