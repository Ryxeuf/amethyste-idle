<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerItem;
use App\Entity\App\Pnj;
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
        $shopItems = $this->getShopItems($pnj);

        return $this->render('game/shop/index.html.twig', [
            'pnj' => $pnj,
            'shopItems' => $shopItems,
            'player' => $player,
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

        // Check item is in this shop
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
                'error' => sprintf('Pas assez de Gils ! (requis: %d, possédés: %d)', $totalCost, $player->getGils()),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Debit gils
        $player->removeGils($totalCost);

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

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Vous avez acheté %dx %s pour %d Gils.', $quantity, $item->getName(), $totalCost),
            'gils' => $player->getGils(),
        ]);
    }

    #[Route('/{id}/sell', name: 'app_game_shop_sell', methods: ['POST'])]
    public function sell(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $playerItemId = $data['playerItemId'] ?? null;

        if (!$playerItemId) {
            return new JsonResponse(['error' => 'Item invalide'], Response::HTTP_BAD_REQUEST);
        }

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find((int) $playerItemId);
        if (!$playerItem) {
            return new JsonResponse(['error' => 'Objet introuvable'], Response::HTTP_NOT_FOUND);
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
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Vous avez vendu %s pour %d Gils.', $item->getName(), $sellPrice),
            'gils' => $player->getGils(),
        ]);
    }

    /**
     * @return Item[]
     */
    private function getShopItems(Pnj $pnj): array
    {
        $slugs = $pnj->getShopItems();
        if (!$slugs) {
            return [];
        }

        return $this->entityManager->getRepository(Item::class)->findBy(['slug' => $slugs]);
    }
}
