<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\App\Parameter;
use App\GameEngine\World\UtcDayCycleFactorProvider;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/world/time', name: 'admin_world_time_')]
#[IsGranted('ROLE_ADMIN')]
class WorldTimeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $param = $this->em->getRepository(Parameter::class)->findOneBy([
            'name' => UtcDayCycleFactorProvider::PARAM_NAME,
        ]);

        $current = $param?->getValue() ?? '1';

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_world_time', $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
            } else {
                $raw = str_replace(',', '.', trim((string) $request->request->get('utc_day_cycle_factor', '1')));
                $val = (float) $raw;
                if ($val < 0.01 || $val > 24.0) {
                    $this->addFlash('error', 'Le facteur doit etre entre 0.01 et 24 (1 = rythme UTC reel).');
                } else {
                    if ($param === null) {
                        $param = new Parameter();
                        $param->setName(UtcDayCycleFactorProvider::PARAM_NAME);
                        $this->em->persist($param);
                    }
                    $param->setValue((string) $val);
                    $this->em->flush();
                    $this->adminLogger->log('world_time_factor', 'Parameter', $param->getId(), UtcDayCycleFactorProvider::PARAM_NAME, ['value' => $val]);
                    $this->addFlash('success', 'Facteur jour/nuit UTC enregistre : ' . $val . '.');
                    $current = (string) $val;
                }
            }
        }

        return $this->render('admin/world_time/index.html.twig', [
            'currentFactor' => $current,
        ]);
    }
}
