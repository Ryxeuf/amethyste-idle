<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerItem;
use App\Enum\AuctionStatus;
use App\Enum\ItemRarity;
use App\GameEngine\Auction\AuctionManager;
use App\Helper\PlayerHelper;
use App\Repository\AuctionListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/auction')]
class AuctionController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly AuctionListingRepository $listingRepository,
        private readonly AuctionManager $auctionManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_auction', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $search = $request->query->get('q', '');
        $type = $request->query->get('type', '');
        $rarity = $request->query->get('rarity', '');
        $page = max(1, $request->query->getInt('page', 1));

        $result = $this->listingRepository->findActiveListings(
            $search ?: null,
            $type ?: null,
            $rarity ?: null,
            $page,
        );

        return $this->render('game/auction/index.html.twig', [
            'listings' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $result['page'],
            'search' => $search,
            'type' => $type,
            'rarity' => $rarity,
            'player' => $player,
            'itemTypes' => $this->getItemTypeLabels(),
            'rarities' => ItemRarity::cases(),
        ]);
    }

    #[Route('/my-listings', name: 'app_game_auction_my_listings', methods: ['GET'])]
    public function myListings(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $activeListings = $this->listingRepository->findBySeller($player, AuctionStatus::Active);
        $historyListings = $this->listingRepository->findBySeller($player);

        $totalRevenue = 0;
        foreach ($historyListings as $listing) {
            if ($listing->getStatus() === AuctionStatus::Sold) {
                $regionTax = (int) floor($listing->getTotalPrice() * (float) $listing->getRegionTaxRate());
                $totalRevenue += $listing->getTotalPrice() - $regionTax;
            }
        }

        return $this->render('game/auction/my_listings.html.twig', [
            'activeListings' => $activeListings,
            'historyListings' => $historyListings,
            'totalRevenue' => $totalRevenue,
            'player' => $player,
        ]);
    }

    #[Route('/sell', name: 'app_game_auction_sell', methods: ['GET'])]
    public function sellForm(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $bag = $this->playerHelper->getBagInventory();
        $sellableItems = [];
        foreach ($bag->getItems() as $playerItem) {
            if (!$playerItem->isBound() && $playerItem->getGear() === 0) {
                $sellableItems[] = $playerItem;
            }
        }

        $selectedItemId = $request->query->getInt('item', 0);

        return $this->render('game/auction/sell.html.twig', [
            'player' => $player,
            'sellableItems' => $sellableItems,
            'selectedItemId' => $selectedItemId,
        ]);
    }

    #[Route('/sell', name: 'app_game_auction_sell_submit', methods: ['POST'])]
    public function sell(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('auction_sell', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_auction_sell');
        }

        $playerItemId = $request->request->getInt('player_item_id');
        $pricePerUnit = $request->request->getInt('price_per_unit');

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($playerItemId);
        if (!$playerItem || $playerItem->getInventory()?->getPlayer()?->getId() !== $player->getId()) {
            $this->addFlash('error', 'Objet introuvable dans votre inventaire.');

            return $this->redirectToRoute('app_game_auction_sell');
        }

        try {
            $this->auctionManager->createListing($player, $playerItem, $pricePerUnit);
            $this->addFlash('success', sprintf(
                '%s mis en vente pour %d Gils (frais: %d Gils).',
                $playerItem->getGenericItem()->getName(),
                $pricePerUnit,
                (int) ceil($pricePerUnit * 0.05),
            ));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_game_auction_sell');
        }

        return $this->redirectToRoute('app_game_auction_my_listings');
    }

    #[Route('/buy/{id}', name: 'app_game_auction_buy', methods: ['POST'])]
    public function buy(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('auction_buy_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_auction');
        }

        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            $this->addFlash('error', 'Annonce introuvable.');

            return $this->redirectToRoute('app_game_auction');
        }

        try {
            $this->auctionManager->buyListing($player, $listing);
            $this->addFlash('success', sprintf(
                'Vous avez achete %s pour %d Gils.',
                $listing->getPlayerItem()->getGenericItem()->getName(),
                $listing->getTotalPrice(),
            ));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_auction');
    }

    #[Route('/cancel/{id}', name: 'app_game_auction_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('auction_cancel_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_auction_my_listings');
        }

        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            $this->addFlash('error', 'Annonce introuvable.');

            return $this->redirectToRoute('app_game_auction_my_listings');
        }

        try {
            $this->auctionManager->cancelListing($player, $listing);
            $this->addFlash('success', 'Annonce annulee. L\'objet a ete retourne dans votre inventaire.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_auction_my_listings');
    }

    /**
     * @return array<string, string>
     */
    private function getItemTypeLabels(): array
    {
        return [
            'stuff' => 'Consommable',
            'gear' => 'Equipement',
            'materia' => 'Materia',
            'resource' => 'Ressource',
            'tool' => 'Outil',
        ];
    }
}
