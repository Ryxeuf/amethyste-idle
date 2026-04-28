<?php

namespace App\GameEngine\Generator;

use App\Entity\App\ObjectLayer;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class HarvestItemGenerator
{
    public function __construct(
        protected PlayerItemGenerator $playerItemGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param float $quantityMultiplier multiplicateur applique au count tire (>=1.0).
     *                                  Utilise par les events `gathering_bonus` actifs.
     *
     * @return PlayerItem[]
     *
     * @throws EntityNotFoundException
     */
    public function generateHarvestItems(ObjectLayer $objectLayer, float $quantityMultiplier = 1.0): array
    {
        if (null === $objectLayer->getItems()) {
            return [];
        }

        $items = [];
        foreach ($objectLayer->getItems() as $itemData) {
            try {
                $count = random_int($itemData['min'] ?? 1, $itemData['max'] ?? 1);
            } catch (\Exception) {
                $count = 1;
            }

            if ($quantityMultiplier !== 1.0) {
                $count = max(1, (int) round($count * $quantityMultiplier));
            }

            for ($i = 0; $i < $count; ++$i) {
                if (isset($itemData['id'])) {
                    $items[] = $this->playerItemGenerator->generateFromItemId($itemData['id']);
                } elseif (isset($itemData['slug'])) {
                    $items[] = $this->generateFromSlug($itemData['slug']);
                }
            }
        }

        return $items;
    }

    private function generateFromSlug(string $slug): PlayerItem
    {
        $item = $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $slug]);
        if ($item === null) {
            throw new EntityNotFoundException("Item with slug '{$slug}' not found");
        }

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($item);
        $playerItem->setNbUsages($item->getNbUsages());

        return $playerItem;
    }
}
