<?php

namespace App\Controller\Admin;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\GameEngine\Auction\AuctionManager;
use App\Helper\PlayerHelper;
use App\Repository\AuctionListingRepository;
use App\Repository\AuctionTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/auction', name: 'admin_auction_')]
class AuctionController extends AbstractController
{
    public function __construct(
        private readonly AuctionListingRepository $listingRepository,
        private readonly AuctionManager $auctionManager,
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuctionTransactionRepository $transactionRepository,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $stats = $this->listingRepository->getAuctionStats();
        $flashSales = $this->listingRepository->findActiveFlashSales();

        return $this->render('admin/auction/index.html.twig', [
            'stats' => $stats,
            'flashSales' => $flashSales,
        ]);
    }

    #[Route('/flash/new', name: 'flash_new', methods: ['GET'])]
    public function flashNew(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $admin = $this->playerHelper->getPlayer();
        if ($admin === null) {
            $this->addFlash('error', 'Aucun personnage actif : impossible de creer une vente flash.');

            return $this->redirectToRoute('admin_auction_index');
        }

        $bag = $this->playerHelper->getBagInventory();
        $sellableItems = [];
        foreach ($bag->getItems() as $playerItem) {
            if ($playerItem->isExchangeable()) {
                $sellableItems[] = $playerItem;
            }
        }

        return $this->render('admin/auction/flash_new.html.twig', [
            'sellableItems' => $sellableItems,
            'minDuration' => AuctionManager::FLASH_SALE_MIN_DURATION_HOURS,
            'maxDuration' => AuctionManager::FLASH_SALE_MAX_DURATION_HOURS,
            'defaultDuration' => AuctionManager::FLASH_SALE_DEFAULT_DURATION_HOURS,
        ]);
    }

    #[Route('/flash/new', name: 'flash_create', methods: ['POST'])]
    public function flashCreate(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_flash_sale', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('admin_auction_flash_new');
        }

        $admin = $this->playerHelper->getPlayer();
        if ($admin === null) {
            $this->addFlash('error', 'Aucun personnage actif.');

            return $this->redirectToRoute('admin_auction_index');
        }

        $playerItemId = $request->request->getInt('player_item_id');
        $pricePerUnit = $request->request->getInt('price_per_unit');
        $durationHours = $request->request->getInt('duration_hours', AuctionManager::FLASH_SALE_DEFAULT_DURATION_HOURS);
        $quantity = max(1, $request->request->getInt('quantity', 1));

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($playerItemId);
        if (!$playerItem || $playerItem->getInventory()?->getPlayer()?->getId() !== $admin->getId()) {
            $this->addFlash('error', 'Objet introuvable dans votre inventaire.');

            return $this->redirectToRoute('admin_auction_flash_new');
        }

        try {
            $this->auctionManager->createFlashSaleListing($admin, $playerItem, $pricePerUnit, $durationHours, $quantity);
            $this->addFlash('success', sprintf(
                'Vente flash creee : %s a %d Gils pour %d heure(s).',
                $playerItem->getGenericItem()->getName(),
                $pricePerUnit,
                $durationHours,
            ));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('admin_auction_flash_new');
        }

        return $this->redirectToRoute('admin_auction_index');
    }

    #[Route('/flash/{id}/cancel', name: 'flash_cancel', methods: ['POST'])]
    public function flashCancel(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_flash_cancel_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('admin_auction_index');
        }

        $admin = $this->playerHelper->getPlayer();
        if ($admin === null) {
            $this->addFlash('error', 'Aucun personnage actif.');

            return $this->redirectToRoute('admin_auction_index');
        }

        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            $this->addFlash('error', 'Annonce introuvable.');

            return $this->redirectToRoute('admin_auction_index');
        }

        try {
            $this->auctionManager->cancelFlashSale($admin, $listing);
            $this->addFlash('success', 'Vente flash annulee et objet retourne dans votre inventaire.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_auction_index');
    }

    /**
     * Journal economique (ECO-16b).
     *
     * Les regles d'ECO-16a refusent ce qui est certainement abusif. Restent les
     * cas qui ne se prouvent pas a la transaction et ne se voient qu'a
     * l'echelle : ils ne se bloquent pas, ils se donnent a voir.
     */
    #[Route('/journal', name: 'journal', methods: ['GET'])]
    public function journal(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $windowHours = max(1, $request->query->getInt('hours', 168));

        return $this->render('admin/auction/journal.html.twig', [
            'windowHours' => $windowHours,
            'transactions' => $this->transactionRepository->findRecent(50),
            'activeListings' => $this->listingRepository->findActiveForModeration(50),
            'pairs' => $this->transactionRepository->findTopTradingPairs($windowHours),
            'outliers' => $this->transactionRepository->findPriceOutliers($windowHours),
            'dailyVolume' => $this->transactionRepository->findDailyVolume(14),
            'outlierRatio' => AuctionTransactionRepository::OUTLIER_RATIO,
        ]);
    }

    #[Route('/listing/{id}/cancel', name: 'listing_cancel', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function cancelListing(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_listing_cancel_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('admin_auction_journal');
        }

        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            $this->addFlash('error', 'Annonce introuvable.');

            return $this->redirectToRoute('admin_auction_journal');
        }

        $reason = trim((string) $request->request->get('reason', ''));

        try {
            $this->auctionManager->cancelListingAsModerator($listing, '' !== $reason ? $reason : 'moderation');
            $this->addFlash('success', 'Annonce annulee, objet rendu au vendeur et mise eventuelle remboursee.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_auction_journal');
    }

    /**
     * Suspend l'acces au marche d'un joueur, ou leve la suspension avec `days=0`.
     */
    #[Route('/player/{id}/suspend', name: 'player_suspend', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function suspendPlayer(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_trade_suspend_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('admin_auction_journal');
        }

        $player = $this->entityManager->getRepository(Player::class)->find($id);
        if (!$player instanceof Player) {
            $this->addFlash('error', 'Personnage introuvable.');

            return $this->redirectToRoute('admin_auction_journal');
        }

        $days = max(0, min(365, $request->request->getInt('days', 7)));
        $player->setTradeSuspendedUntil($days > 0 ? new \DateTimeImmutable(sprintf('+%d days', $days)) : null);
        $this->entityManager->flush();

        $this->addFlash('success', $days > 0
            ? sprintf('Acces au marche suspendu %d jour(s) pour %s.', $days, $player->getName())
            : sprintf('Suspension levee pour %s.', $player->getName()));

        return $this->redirectToRoute('admin_auction_journal');
    }
}
