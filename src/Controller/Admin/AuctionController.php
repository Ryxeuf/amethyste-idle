<?php

namespace App\Controller\Admin;

use App\Entity\App\PlayerItem;
use App\GameEngine\Auction\AuctionManager;
use App\Helper\PlayerHelper;
use App\Repository\AuctionListingRepository;
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
            if (!$playerItem->isBound() && $playerItem->getGear() === 0) {
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
}
