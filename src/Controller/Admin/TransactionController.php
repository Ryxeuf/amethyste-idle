<?php

namespace App\Controller\Admin;

use App\Entity\App\AuctionListing;
use App\Entity\App\TransactionLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/transactions', name: 'admin_transaction_')]
#[IsGranted('ROLE_ADMIN')]
class TransactionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $type = $request->query->get('type', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;

        $qb = $this->em->createQueryBuilder()
            ->select('t')
            ->from(TransactionLog::class, 't')
            ->orderBy('t.createdAt', 'DESC');

        if ($type) {
            $qb->where('t.type = :type')
               ->setParameter('type', $type);
        }

        $total = (int) (clone $qb)->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();
        $transactions = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Stats
        $stats = [
            'total_transactions' => $this->em->getRepository(TransactionLog::class)->count([]),
            'shop_buys' => $this->em->getRepository(TransactionLog::class)->count(['type' => TransactionLog::TYPE_SHOP_BUY]),
            'shop_sells' => $this->em->getRepository(TransactionLog::class)->count(['type' => TransactionLog::TYPE_SHOP_SELL]),
            'auction_buys' => $this->em->getRepository(TransactionLog::class)->count(['type' => TransactionLog::TYPE_AUCTION_BUY]),
            'trades' => $this->em->getRepository(TransactionLog::class)->count(['type' => TransactionLog::TYPE_TRADE]),
            'active_auctions' => $this->em->getRepository(AuctionListing::class)->count(['status' => AuctionListing::STATUS_ACTIVE]),
        ];

        return $this->render('admin/transaction/index.html.twig', [
            'transactions' => $transactions,
            'stats' => $stats,
            'type' => $type,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }
}
