<?php

namespace App\Controller\Game;

use App\Entity\App\AuctionListing;
use App\Entity\App\PlayerItem;
use App\Entity\App\TransactionLog;
use App\Entity\Game\Item;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/auction')]
class AuctionController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_auction', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        // Expire old listings
        $this->expireListings();

        // Filters
        $search = $request->query->get('q', '');
        $type = $request->query->get('type', '');
        $rarity = $request->query->get('rarity', '');
        $sortBy = $request->query->get('sort', 'newest');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(AuctionListing::class, 'a')
            ->join('a.item', 'i')
            ->where('a.status = :status')
            ->setParameter('status', AuctionListing::STATUS_ACTIVE);

        if ($search) {
            $qb->andWhere('LOWER(i.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        if ($type) {
            $qb->andWhere('i.type = :type')
               ->setParameter('type', $type);
        }

        if ($rarity) {
            $qb->andWhere('i.rarity = :rarity')
               ->setParameter('rarity', $rarity);
        }

        match ($sortBy) {
            'price_asc' => $qb->orderBy('a.price', 'ASC'),
            'price_desc' => $qb->orderBy('a.price', 'DESC'),
            'expiring' => $qb->orderBy('a.expiresAt', 'ASC'),
            default => $qb->orderBy('a.createdAt', 'DESC'),
        };

        $total = (int) (clone $qb)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();
        $listings = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Player's own listings
        $myListings = $this->entityManager->getRepository(AuctionListing::class)->findBy(
            ['seller' => $player, 'status' => AuctionListing::STATUS_ACTIVE],
            ['createdAt' => 'DESC']
        );

        // Price history (last 20 sold items)
        $priceHistory = $this->entityManager->createQueryBuilder()
            ->select('i.name, a.price, a.soldAt')
            ->from(AuctionListing::class, 'a')
            ->join('a.item', 'i')
            ->where('a.status = :status')
            ->setParameter('status', AuctionListing::STATUS_SOLD)
            ->orderBy('a.soldAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $this->render('game/auction/index.html.twig', [
            'player' => $player,
            'listings' => $listings,
            'myListings' => $myListings,
            'priceHistory' => $priceHistory,
            'search' => $search,
            'type' => $type,
            'rarity' => $rarity,
            'sortBy' => $sortBy,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/sell', name: 'app_game_auction_sell', methods: ['POST'])]
    public function sell(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $playerItemId = (int) ($data['playerItemId'] ?? 0);
        $price = (int) ($data['price'] ?? 0);
        $duration = (int) ($data['duration'] ?? 24);

        if ($price < 1) {
            return new JsonResponse(['error' => 'Le prix doit etre au minimum 1 Gil'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($duration, [24, 48, 72], true)) {
            return new JsonResponse(['error' => 'Duree invalide'], Response::HTTP_BAD_REQUEST);
        }

        $player = $this->playerHelper->getPlayer();

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($playerItemId);
        if (!$playerItem) {
            return new JsonResponse(['error' => 'Objet introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Check that this item belongs to the player
        $bag = $this->playerHelper->getBagInventory();
        if ($playerItem->getInventory() === null || $playerItem->getInventory()->getId() !== $bag->getId()) {
            return new JsonResponse(['error' => 'Cet objet ne vous appartient pas'], Response::HTTP_FORBIDDEN);
        }

        // Prevent listing equipped items
        if ($playerItem->getGear() > 0) {
            return new JsonResponse(['error' => 'Impossible de vendre un objet equipe'], Response::HTTP_BAD_REQUEST);
        }

        // Prevent listing soulbound items
        if ($playerItem->isBound()) {
            return new JsonResponse(['error' => 'Cet objet est lie a votre personnage et ne peut pas etre vendu'], Response::HTTP_BAD_REQUEST);
        }

        // Check max active listings (limit to 20)
        $activeCount = $this->entityManager->getRepository(AuctionListing::class)->count([
            'seller' => $player,
            'status' => AuctionListing::STATUS_ACTIVE,
        ]);
        if ($activeCount >= 20) {
            return new JsonResponse(['error' => 'Vous avez atteint la limite de 20 annonces actives'], Response::HTTP_BAD_REQUEST);
        }

        // Create listing
        $listing = new AuctionListing();
        $listing->setSeller($player);
        $listing->setPlayerItem($playerItem);
        $listing->setItem($playerItem->getGenericItem());
        $listing->setPrice($price);
        $listing->setDurationHours($duration);
        $listing->setExpiresAt(new \DateTime("+{$duration} hours"));
        $listing->setTaxAmount($listing->calculateTax());
        $listing->setCreatedAt(new \DateTime());
        $listing->setUpdatedAt(new \DateTime());

        // Remove item from player inventory
        $playerItem->setInventory(null);

        $this->logTransaction(
            TransactionLog::TYPE_AUCTION_LIST,
            $player,
            $playerItem->getGenericItem(),
            1,
            $price,
            sprintf('Mise en vente de %s a %d Gils (%dh)', $playerItem->getGenericItem()->getName(), $price, $duration)
        );

        $this->entityManager->persist($listing);
        $this->entityManager->persist($playerItem);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('%s mis en vente pour %d Gils (duree: %dh, taxe: %d Gils)', $playerItem->getGenericItem()->getName(), $price, $duration, $listing->getTaxAmount()),
        ]);
    }

    #[Route('/buy/{id}', name: 'app_game_auction_buy', methods: ['POST'])]
    public function buy(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        $listing = $this->entityManager->getRepository(AuctionListing::class)->find($id);
        if (!$listing || !$listing->isActive()) {
            return new JsonResponse(['error' => 'Annonce introuvable ou expiree'], Response::HTTP_NOT_FOUND);
        }

        if ($listing->isExpired()) {
            $listing->setStatus(AuctionListing::STATUS_EXPIRED);
            $this->entityManager->flush();

            return new JsonResponse(['error' => 'Cette annonce a expire'], Response::HTTP_BAD_REQUEST);
        }

        // Can't buy own item
        if ($listing->getSeller()->getId() === $player->getId()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas acheter votre propre objet'], Response::HTTP_BAD_REQUEST);
        }

        if ($player->getGils() < $listing->getPrice()) {
            return new JsonResponse([
                'error' => sprintf('Pas assez de Gils ! (requis: %d, possedes: %d)', $listing->getPrice(), $player->getGils()),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Process transaction
        $player->removeGils($listing->getPrice());

        // Seller receives price minus tax
        $sellerProceeds = $listing->getSellerProceeds();
        $listing->getSeller()->addGils($sellerProceeds);

        // Transfer item to buyer
        $bag = $this->playerHelper->getBagInventory();
        $playerItem = $listing->getPlayerItem();
        if ($playerItem !== null) {
            $playerItem->setInventory($bag);
            $this->entityManager->persist($playerItem);
        } else {
            // If player item was deleted, create a new one
            $newItem = new PlayerItem();
            $newItem->setGenericItem($listing->getItem());
            $newItem->setInventory($bag);
            $newItem->setGear(0);
            $this->entityManager->persist($newItem);
        }

        // Update listing
        $listing->setStatus(AuctionListing::STATUS_SOLD);
        $listing->setBuyer($player);
        $listing->setSoldAt(new \DateTime());

        // Log for buyer
        $this->logTransaction(
            TransactionLog::TYPE_AUCTION_BUY,
            $player,
            $listing->getItem(),
            1,
            $listing->getPrice(),
            sprintf('Achat de %s a %s pour %d Gils', $listing->getItem()->getName(), $listing->getSeller()->getName(), $listing->getPrice()),
            $listing->getSeller()
        );

        $this->entityManager->persist($listing);
        $this->entityManager->persist($player);
        $this->entityManager->persist($listing->getSeller());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Vous avez achete %s pour %d Gils.', $listing->getItem()->getName(), $listing->getPrice()),
            'gils' => $player->getGils(),
        ]);
    }

    #[Route('/cancel/{id}', name: 'app_game_auction_cancel', methods: ['POST'])]
    public function cancel(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        $listing = $this->entityManager->getRepository(AuctionListing::class)->find($id);
        if (!$listing || !$listing->isActive()) {
            return new JsonResponse(['error' => 'Annonce introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($listing->getSeller()->getId() !== $player->getId()) {
            return new JsonResponse(['error' => 'Ce n\'est pas votre annonce'], Response::HTTP_FORBIDDEN);
        }

        // Return item to seller
        $bag = $this->playerHelper->getBagInventory();
        $playerItem = $listing->getPlayerItem();
        if ($playerItem !== null) {
            $playerItem->setInventory($bag);
            $this->entityManager->persist($playerItem);
        }

        $listing->setStatus(AuctionListing::STATUS_CANCELLED);

        $this->logTransaction(
            TransactionLog::TYPE_AUCTION_CANCEL,
            $player,
            $listing->getItem(),
            1,
            0,
            sprintf('Annulation de la vente de %s', $listing->getItem()->getName())
        );

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('%s a ete retire de l\'hotel des ventes.', $listing->getItem()->getName()),
        ]);
    }

    #[Route('/history', name: 'app_game_auction_history', methods: ['GET'])]
    public function history(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $itemSlug = $request->query->get('item', '');
        if (!$itemSlug) {
            return new JsonResponse([]);
        }

        $item = $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $itemSlug]);
        if (!$item) {
            return new JsonResponse([]);
        }

        $history = $this->entityManager->createQueryBuilder()
            ->select('a.price, a.soldAt')
            ->from(AuctionListing::class, 'a')
            ->where('a.item = :item')
            ->andWhere('a.status = :status')
            ->setParameter('item', $item)
            ->setParameter('status', AuctionListing::STATUS_SOLD)
            ->orderBy('a.soldAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $data = array_map(fn ($h) => [
            'price' => $h['price'],
            'date' => $h['soldAt']?->format('d/m H:i'),
        ], $history);

        // Average price
        $avg = count($data) > 0 ? (int) (array_sum(array_column($data, 'price')) / count($data)) : 0;

        return new JsonResponse([
            'history' => $data,
            'average' => $avg,
            'itemName' => $item->getName(),
        ]);
    }

    private function expireListings(): void
    {
        $expired = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(AuctionListing::class, 'a')
            ->where('a.status = :status')
            ->andWhere('a.expiresAt < :now')
            ->setParameter('status', AuctionListing::STATUS_ACTIVE)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        foreach ($expired as $listing) {
            $listing->setStatus(AuctionListing::STATUS_EXPIRED);

            // Return item to seller
            $playerItem = $listing->getPlayerItem();
            if ($playerItem !== null) {
                // Find seller's bag
                $sellerBag = null;
                foreach ($listing->getSeller()->getInventories() as $inv) {
                    if ($inv->isBag()) {
                        $sellerBag = $inv;
                        break;
                    }
                }
                if ($sellerBag) {
                    $playerItem->setInventory($sellerBag);
                    $this->entityManager->persist($playerItem);
                }
            }

            $this->entityManager->persist($listing);
        }

        if (count($expired) > 0) {
            $this->entityManager->flush();
        }
    }

    private function logTransaction(
        string $type,
        \App\Entity\App\Player $player,
        Item $item,
        int $quantity,
        int $gilsAmount,
        string $description,
        ?\App\Entity\App\Player $otherPlayer = null,
    ): void {
        $log = new TransactionLog();
        $log->setType($type);
        $log->setPlayer($player);
        $log->setItem($item);
        $log->setQuantity($quantity);
        $log->setGilsAmount($gilsAmount);
        $log->setDescription($description);
        $log->setOtherPlayer($otherPlayer);
        $log->setCreatedAt(new \DateTime());
        $log->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($log);
    }
}
