<?php

namespace App\GameEngine\Housing;

use App\Entity\App\GardenPlot;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\Game\Item;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\InventoryHelper;
use App\Repository\GardenPlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Jardin d'une demeure (tache 129, HOU-02).
 *
 * Le **pilier PBBG** du housing : une production qui avance quand le joueur
 * n'est pas la. La recolte de zone coute de l'energie et exige sa presence ; le
 * jardin ne demande que d'etre revenu.
 *
 * On plante **la plante elle-meme** — le jeu n'a pas d'objet « graine » — et la
 * parcelle en rend plusieurs. Le jardin multiplie lentement ce qu'on possede
 * deja : auto-limitant, puisqu'il faut d'abord recolter dehors.
 */
class GardenService
{
    /**
     * Prefixe des objets plantables.
     *
     * Le catalogue d'herboristerie porte tous ses slugs sous cette forme
     * (`plant-chamomile`, `plant-sage`...). Un prefixe plutot qu'une liste
     * figee : une plante ajoutee au monde devient cultivable sans qu'on ait a y
     * penser, ce qui est le bon defaut pour de la matiere premiere.
     */
    public const CROP_SLUG_PREFIX = 'plant-';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GardenPlotRepository $plotRepository,
        private readonly InventoryHelper $inventoryHelper,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isPlantable(Item $item): bool
    {
        return str_starts_with($item->getSlug(), self::CROP_SLUG_PREFIX);
    }

    /**
     * Les parcelles du jardin, creees a la demande.
     *
     * Les creer ici plutot qu'a l'achat de la demeure evite une migration de
     * donnees pour les maisons deja baties, et rend le jardin robuste si une
     * parcelle venait a manquer.
     *
     * @return GardenPlot[]
     */
    public function getPlots(PlayerHouse $house): array
    {
        $plots = $this->plotRepository->findForHouse($house);

        if (\count($plots) >= GardenPlot::PLOT_COUNT) {
            return $plots;
        }

        $existing = [];
        foreach ($plots as $plot) {
            $existing[$plot->getPosition()] = true;
        }

        for ($position = 0; $position < GardenPlot::PLOT_COUNT; ++$position) {
            if (isset($existing[$position])) {
                continue;
            }

            $plot = new GardenPlot();
            $plot->setHouse($house);
            $plot->setPosition($position);
            $this->entityManager->persist($plot);
            $plots[] = $plot;
        }

        $this->entityManager->flush();

        usort($plots, static fn (GardenPlot $a, GardenPlot $b) => $a->getPosition() <=> $b->getPosition());

        return $plots;
    }

    /**
     * Met une plante en terre.
     *
     * L'unite plantee est **consommee** immediatement : sans cela, un joueur
     * pourrait planter puis revendre la plante avant la recolte, et le jardin
     * produirait a partir de rien.
     */
    public function plant(Player $player, GardenPlot $plot, Item $crop): void
    {
        $this->assertOwner($player, $plot);

        if (!$plot->isEmpty()) {
            throw new \InvalidArgumentException('Cette parcelle est deja plantee.');
        }

        if (!$this->isPlantable($crop)) {
            throw new \InvalidArgumentException('Cet objet ne se cultive pas.');
        }

        if (!$this->consumeOne($player, $crop)) {
            throw new \InvalidArgumentException(sprintf('Il vous faut un(e) %s a planter.', $crop->getName()));
        }

        $plot->plant($crop, new \DateTimeImmutable(sprintf('+%d seconds', GardenPlot::GROWTH_SECONDS)));
        $this->entityManager->flush();
    }

    /**
     * Recolte une parcelle mure.
     *
     * @return int nombre d'unites recoltees
     */
    public function harvest(Player $player, GardenPlot $plot): int
    {
        $this->assertOwner($player, $plot);

        $crop = $plot->getCrop();
        if (null === $crop) {
            throw new \InvalidArgumentException('Cette parcelle est vide.');
        }

        if (!$plot->isRipe()) {
            throw new \InvalidArgumentException(sprintf('Encore %d seconde(s) de pousse.', $plot->getRemainingSeconds()));
        }

        $quantity = random_int(GardenPlot::YIELD_MIN, GardenPlot::YIELD_MAX);
        for ($i = 0; $i < $quantity; ++$i) {
            $this->inventoryHelper->addItem($this->playerItemGenerator->generateFromItemId($crop->getId()), false);
        }

        $plot->clear();
        $this->entityManager->flush();

        $this->logger->info('Garden plot harvested', [
            'player_id' => $player->getId(),
            'crop' => $crop->getSlug(),
            'quantity' => $quantity,
        ]);

        return $quantity;
    }

    /**
     * Les plantes du sac, candidates a la mise en terre.
     *
     * @return array<string, array{item: Item, count: int}>
     */
    public function getPlantableStock(Player $player): array
    {
        $stock = [];

        foreach ($player->getInventories() as $inventory) {
            if ($inventory->getType() !== Inventory::TYPE_BAG) {
                continue;
            }

            foreach ($inventory->getItems() as $playerItem) {
                $item = $playerItem->getGenericItem();
                if (!$this->isPlantable($item) || !$playerItem->isExchangeable()) {
                    continue;
                }

                $slug = $item->getSlug();
                $stock[$slug] ??= ['item' => $item, 'count' => 0];
                ++$stock[$slug]['count'];
            }
        }

        ksort($stock);

        return $stock;
    }

    private function assertOwner(Player $player, GardenPlot $plot): void
    {
        if ($plot->getHouse()->getOwner()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Ce jardin n\'est pas le votre.');
        }
    }

    private function consumeOne(Player $player, Item $crop): bool
    {
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->getType() !== Inventory::TYPE_BAG) {
                continue;
            }

            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGenericItem()->getSlug() !== $crop->getSlug() || !$playerItem->isExchangeable()) {
                    continue;
                }

                $inventory->removeItem($playerItem);
                $this->entityManager->remove($playerItem);

                return true;
            }
        }

        return false;
    }
}
