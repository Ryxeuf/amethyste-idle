<?php

namespace App\GameEngine\Generator;

use App\Entity\App\ObjectLayer;
use App\Entity\App\PlayerItem;
use Doctrine\ORM\EntityNotFoundException;

class HarvestItemGenerator
{
    public function __construct(protected PlayerItemGenerator $playerItemGenerator)
    {
    }

    /**
     * @return PlayerItem[]
     * @throws EntityNotFoundException
     */
    public function generateHarvestItems(ObjectLayer $objectLayer): array
    {
        if (null === $objectLayer->getItems()) {
            return [];
        }

        $items = [];
        foreach ($objectLayer->getItems() as $item) {
            // TODO : proba de génération
            try {
                $count = random_int($item['min'] ?? 1, $item['max'] ?? 1);
            }catch (\Exception) {
                $count = 1;
            }

            for ($i = 0; $i < $count; $i++) {
                $items[] = $this->playerItemGenerator->generateFromItemId($item['id']);
            }
        }

        return $items;
    }
}