<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\PlayerItem;
use App\Helper\InventoryHelper;
use App\Helper\ItemHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/items/use/{id}', name: 'app_game_inventory_items_use', methods: ['POST'])]
class UseItemController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly ItemHelper $itemHelper,
        private readonly InventoryHelper $inventoryHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($id);

        if (!$playerItem || !$this->inventoryHelper->hasItem($playerItem)) {
            $this->addFlash('error', 'Objet introuvable.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        $item = $playerItem->getGenericItem();

        if (!$item->isObject()) {
            $this->addFlash('error', 'Cet objet ne peut pas être utilisé.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        // Vérifier que l'objet a un sort lié (potion, parchemin, etc.)
        $spell = $this->itemHelper->getItemSpell($item);
        if (!$spell) {
            $this->addFlash('error', 'Cet objet ne peut pas être utilisé.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        // Vérifier que le joueur n'est pas en combat
        if ($player->getFight()) {
            $this->addFlash('error', 'Vous ne pouvez pas utiliser d\'objet pendant un combat.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        // Vérifier que le joueur n'est pas mort
        if ($player->isDead()) {
            $this->addFlash('error', 'Vous ne pouvez pas utiliser d\'objet étant mort.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        // Appliquer l'effet du sort sur le joueur
        $heal = $spell->getHeal();
        if ($heal !== null && $heal > 0) {
            $newLife = min($player->getLife() + $heal, $player->getMaxLife());
            $player->setLife($newLife);
            $this->addFlash('success', sprintf('Vous utilisez %s et récupérez %d PV.', $item->getName(), $heal));
        } else {
            $this->addFlash('success', sprintf('Vous utilisez %s.', $item->getName()));
        }

        // Décrémenter les usages
        $nbUsages = $playerItem->getNbUsages();
        if ($nbUsages > 0) {
            $playerItem->setNbUsages($nbUsages - 1);
            if ($playerItem->getNbUsages() <= 0) {
                $this->entityManager->remove($playerItem);
            }
        } elseif ($nbUsages == 0) {
            // Plus d'usages restants
            $this->entityManager->remove($playerItem);
        }
        // nbUsages == -1 signifie usage illimité

        $this->entityManager->flush();

        return $this->redirectToRoute('app_game_inventory_items_list');
    }
}
