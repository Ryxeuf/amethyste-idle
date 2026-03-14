<?php

namespace App\Controller\Admin;

use App\Service\AdminLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/maintenance', name: 'admin_maintenance_')]
#[IsGranted('ROLE_ADMIN')]
class MaintenanceController extends AbstractController
{
    public function __construct(
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $flagPath = $this->getParameter('kernel.project_dir') . '/var/maintenance.flag';
        $isActive = is_file($flagPath);

        return $this->render('admin/maintenance/index.html.twig', [
            'isActive' => $isActive,
        ]);
    }

    #[Route('/toggle', name: 'toggle', methods: ['POST'])]
    public function toggle(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('maintenance_toggle', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_maintenance_index');
        }

        $flagPath = $this->getParameter('kernel.project_dir') . '/var/maintenance.flag';

        if (is_file($flagPath)) {
            unlink($flagPath);
            $this->addFlash('success', 'Mode maintenance desactive. Le site est de nouveau accessible.');
            $this->adminLogger->log('maintenance_off', 'System', null, 'Maintenance desactivee');
        } else {
            file_put_contents($flagPath, date('Y-m-d H:i:s'));
            $this->addFlash('success', 'Mode maintenance active. Le site est inaccessible aux joueurs.');
            $this->adminLogger->log('maintenance_on', 'System', null, 'Maintenance activee');
        }

        return $this->redirectToRoute('admin_maintenance_index');
    }
}
