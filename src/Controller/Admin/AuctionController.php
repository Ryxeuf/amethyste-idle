<?php

namespace App\Controller\Admin;

use App\Repository\AuctionListingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/auction', name: 'admin_auction_')]
class AuctionController extends AbstractController
{
    public function __construct(
        private readonly AuctionListingRepository $listingRepository,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $stats = $this->listingRepository->getAuctionStats();

        return $this->render('admin/auction/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}
