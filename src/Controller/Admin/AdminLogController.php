<?php

namespace App\Controller\Admin;

use App\Entity\App\AdminLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logs', name: 'admin_log_')]
#[IsGranted('ROLE_ADMIN')]
class AdminLogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;

        $qb = $this->em->getRepository(AdminLog::class)->createQueryBuilder('l')
            ->leftJoin('l.adminUser', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC');

        $search = $request->query->get('q', '');
        if ($search) {
            $qb->andWhere('LOWER(l.entityType) LIKE LOWER(:q) OR LOWER(l.action) LIKE LOWER(:q) OR LOWER(l.entityLabel) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $total = (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();
        $logs = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/log/index.html.twig', [
            'logs' => $logs,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }
}
