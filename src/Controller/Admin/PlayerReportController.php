<?php

namespace App\Controller\Admin;

use App\Entity\App\PlayerReport;
use App\Entity\User;
use App\Enum\PlayerReportStatus;
use App\GameEngine\Renown\PlayerReportManager;
use App\Repository\PlayerReportRepository;
use App\Service\AdminLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reports', name: 'admin_report_')]
#[IsGranted('ROLE_MODERATOR')]
class PlayerReportController extends AbstractController
{
    private const PAGE_SIZE = 30;

    public function __construct(
        private readonly PlayerReportRepository $repository,
        private readonly PlayerReportManager $reportManager,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $statusParam = $request->query->get('status', 'pending');
        $status = $statusParam === 'all' ? null : PlayerReportStatus::tryFrom((string) $statusParam);
        $page = max(1, $request->query->getInt('page', 1));

        $reports = $this->repository->findForAdmin($status, $page, self::PAGE_SIZE);
        $total = $this->repository->countForAdmin($status);

        return $this->render('admin/report/index.html.twig', [
            'reports' => $reports,
            'currentStatus' => $status,
            'statusParam' => $statusParam,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / self::PAGE_SIZE)),
            'total' => $total,
            'pendingCount' => $this->repository->countForAdmin(PlayerReportStatus::Pending),
        ]);
    }

    #[Route('/{id}/accept', name: 'accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function accept(Request $request, PlayerReport $report): Response
    {
        if (!$this->isCsrfTokenValid('report_accept_' . $report->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('admin_report_index', $request->query->all());
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->reportManager->acceptReport($report, $user);
        $this->adminLogger->log(
            'player_report_accept',
            'PlayerReport',
            $report->getId(),
            'Signalement contre ' . $report->getReportedPlayer()->getName(),
        );
        $this->addFlash('success', 'Signalement accepte. Malus renommee applique.');

        return $this->redirectToRoute('admin_report_index', $request->query->all());
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(Request $request, PlayerReport $report): Response
    {
        if (!$this->isCsrfTokenValid('report_reject_' . $report->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('admin_report_index', $request->query->all());
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->reportManager->rejectReport($report, $user);
        $this->adminLogger->log(
            'player_report_reject',
            'PlayerReport',
            $report->getId(),
            'Signalement contre ' . $report->getReportedPlayer()->getName(),
        );
        $this->addFlash('success', 'Signalement rejete.');

        return $this->redirectToRoute('admin_report_index', $request->query->all());
    }
}
