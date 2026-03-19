<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerItem;
use App\Entity\App\Pnj;
use App\Entity\App\ShopStock;
use App\Entity\App\TransactionLog;
use App\Entity\Game\Item;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/shop')]
class ShopController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/{id}', name: 'app_game_shop', methods: ['GET'])]
    public function index(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj || !$pnj->isMerchant()) {
            throw $this->createNotFoundException('Boutique introuvable');
        }

        $player = $this->playerHelper->getPlayer();
        $shopItems = $this->getShopItemsWithStock($pnj);

        $equippedItems = $this->getEquippedItems();

        return $this->render('game/shop/index.html.twig', [
            'pnj' => $pnj,
            'shopItems' => $shopItems,
            'player' => $player,
            'equippedItems' => $equippedItems,
        ]);
    }

    #[Route('/{id}/buy', name: 'app_game_shop_buy', methods: ['POST'])]
    public function buy(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj || !$pnj->isMerchant()) {
            return new JsonResponse(['error' => 'Boutique introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $itemSlug = $data['itemSlug'] ?? null;
        $quantity = max(1, (int) ($data['quantity'] ?? 1));

        if (!$itemSlug) {
            return new JsonResponse(['error' => 'Item invalide'], Response::HTTP_BAD_REQUEST);
        }

        $shopSlugs = $pnj->getShopItems();
        if (!in_array($itemSlug, $shopSlugs, true)) {
            return new JsonResponse(['error' => 'Cet objet n\'est pas en vente ici'], Response::HTTP_BAD_REQUEST);
        }

        $item = $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $itemSlug]);
        if (!$item) {
            return new JsonResponse(['error' => 'Objet introuvable'], Response::HTTP_NOT_FOUND);
        }

        $player = $this->playerHelper->getPlayer();
        $totalCost = ($item->getPrice() ?? 0) * $quantity;

        if ($player->getGils() < $totalCost) {
            return new JsonResponse([
                'error' => sprintf('Pas assez de Gils ! (requis: %d, possedes: %d)', $totalCost, $player->getGils()),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check stock
        $stock = $this->entityManager->getRepository(ShopStock::class)->findOneBy([
            'pnj' => $pnj,
            'item' => $item,
        ]);

        if ($stock !== null) {
            $this->restockIfNeeded($stock);

            if ($stock->hasLimitedStock() && $stock->getCurrentStock() < $quantity) {
                return new JsonResponse([
                    'error' => sprintf('Stock insuffisant ! (disponible: %d)', $stock->getCurrentStock()),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Debit gils
        $player->removeGils($totalCost);

        // Decrement stock
        if ($stock !== null && $stock->hasLimitedStock()) {
            for ($i = 0; $i < $quantity; ++$i) {
                $stock->decrementStock();
            }
            $this->entityManager->persist($stock);
        }

        // Add item to player bag inventory
        $bag = $this->playerHelper->getBagInventory();
        for ($i = 0; $i < $quantity; ++$i) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($item);
            $playerItem->setInventory($bag);
            $playerItem->setGear(0);
            if ($item->isBoundToPlayer()) {
                $playerItem->setBoundToPlayerId($player->getId());
            }
            $this->entityManager->persist($playerItem);
        }

        // Log transaction
        $this->logTransaction(
            TransactionLog::TYPE_SHOP_BUY,
            $player,
            $item,
            $quantity,
            $totalCost,
            sprintf('Achat de %dx %s chez %s', $quantity, $item->getName(), $pnj->getName())
        );

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        $stockInfo = null;
        if ($stock !== null && $stock->hasLimitedStock()) {
            $stockInfo = $stock->getCurrentStock();
        }

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Vous avez achete %dx %s pour %d Gils.', $quantity, $item->getName(), $totalCost),
            'gils' => $player->getGils(),
            'stock' => $stockInfo,
        ]);
    }

    #[Route('/{id}/sell', name: 'app_game_shop_sell', methods: ['POST'])]
    public function sell(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj) {
            return new JsonResponse(['error' => 'Boutique introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $playerItemId = $data['playerItemId'] ?? null;

        if (!$playerItemId) {
            return new JsonResponse(['error' => 'Item invalide'], Response::HTTP_BAD_REQUEST);
        }

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find((int) $playerItemId);
        if (!$playerItem) {
            return new JsonResponse(['error' => 'Objet introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Prevent selling equipped items
        if ($playerItem->getGear() > 0) {
            return new JsonResponse(['error' => 'Impossible de vendre un objet equipe'], Response::HTTP_BAD_REQUEST);
        }

        $player = $this->playerHelper->getPlayer();
        $item = $playerItem->getGenericItem();

        // Soulbound items cannot be sold
        if ($playerItem->isBound()) {
            return new JsonResponse([
                'error' => 'Cet objet est lié à votre personnage et ne peut pas être vendu.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Sell price = 30% of buy price
        $sellPrice = max(1, (int) (($item->getPrice() ?? 0) * 0.3));

        $player->addGils($sellPrice);
        $this->entityManager->remove($playerItem);

        // Log transaction
        $this->logTransaction(
            TransactionLog::TYPE_SHOP_SELL,
            $player,
            $item,
            1,
            $sellPrice,
            sprintf('Vente de %s chez %s', $item->getName(), $pnj->getName())
        );

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Vous avez vendu %s pour %d Gils.', $item->getName(), $sellPrice),
            'gils' => $player->getGils(),
        ]);
    }

    /**
     * @return array<array{item: Item, stock: ShopStock|null}>
     */
    private function getShopItemsWithStock(Pnj $pnj): array
    {
        $slugs = $pnj->getShopItems();
        if (!$slugs) {
            return [];
        }

        $items = $this->entityManager->getRepository(Item::class)->findBy(['slug' => $slugs]);
        $stocks = $this->entityManager->getRepository(ShopStock::class)->findBy(['pnj' => $pnj]);

        $stockByItemId = [];
        foreach ($stocks as $stock) {
            $this->restockIfNeeded($stock);
            $stockByItemId[$stock->getItem()->getId()] = $stock;
        }

        $result = [];
        foreach ($items as $item) {
            $stock = $stockByItemId[$item->getId()] ?? null;
            $result[] = [
                'item' => $item,
                'stock' => $stock,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, PlayerItem>
     */
    private function getEquippedItems(): array
    {
        $bag = $this->playerHelper->getBagInventory();
        $equipped = [];

        foreach ($bag->getItems() as $playerItem) {
            if ($playerItem->getGear() > 0 && $playerItem->getGenericItem()->getGearLocation()) {
                $equipped[$playerItem->getGenericItem()->getGearLocation()] = $playerItem;
            }
        }

        return $equipped;
    }

    private function restockIfNeeded(ShopStock $stock): void
    {
        if ($stock->needsRestock()) {
            $stock->restock();
            $this->entityManager->persist($stock);
        }
    }

    private function logTransaction(
        string $type,
        \App\Entity\App\Player $player,
        Item $item,
        int $quantity,
        int $gilsAmount,
        string $description,
    ): void {
        $log = new TransactionLog();
        $log->setType($type);
        $log->setPlayer($player);
        $log->setItem($item);
        $log->setQuantity($quantity);
        $log->setGilsAmount($gilsAmount);
        $log->setDescription($description);
        $log->setCreatedAt(new \DateTime());
        $log->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($log);
    }
}
