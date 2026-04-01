<?php

namespace App\Controller\Admin;

use App\Entity\App\ObjectLayer;
use App\Event\Map\SpotAvailableEvent;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/admin/harvest-respawn', name: 'admin_harvest_respawn_')]
#[IsGranted('ROLE_MODERATOR')]
class HarvestRespawnController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filterMapId = $request->query->getInt('map', 0);

        $qb = $this->em->getRepository(ObjectLayer::class)->createQueryBuilder('o')
            ->where('o.type = :type')
            ->setParameter('type', ObjectLayer::TYPE_HARVEST_SPOT)
            ->leftJoin('o.map', 'm')
            ->addSelect('m');

        if ($filterMapId > 0) {
            $qb->andWhere('m.id = :mapId')->setParameter('mapId', $filterMapId);
        }

        $allSpots = $qb->orderBy('m.name', 'ASC')
            ->addOrderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();

        $totalSpots = count($allSpots);
        $inCooldown = [];
        $availableCount = 0;

        foreach ($allSpots as $spot) {
            if ($spot->getUsedAt() !== null && !$spot->isAvailable()) {
                $inCooldown[] = $spot;
            } else {
                ++$availableCount;
            }
        }

        // Trier par temps restant (le plus proche du respawn en premier)
        usort($inCooldown, fn (ObjectLayer $a, ObjectLayer $b) => $a->getRemainingRespawnSeconds() <=> $b->getRemainingRespawnSeconds());

        // Récupérer les cartes pour le filtre
        $maps = $this->em->createQuery('SELECT DISTINCT m.id, m.name FROM App\Entity\App\ObjectLayer o JOIN o.map m WHERE o.type = :type ORDER BY m.name ASC')
            ->setParameter('type', ObjectLayer::TYPE_HARVEST_SPOT)
            ->getResult();

        return $this->render('admin/harvest_respawn/index.html.twig', [
            'inCooldown' => $inCooldown,
            'totalSpots' => $totalSpots,
            'availableCount' => $availableCount,
            'maps' => $maps,
            'filterMapId' => $filterMapId,
        ]);
    }

    #[Route('/{id}/force-respawn', name: 'force_respawn', methods: ['POST'])]
    public function forceRespawn(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('force_respawn_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_harvest_respawn_index');
        }

        $spot = $this->em->getRepository(ObjectLayer::class)->find($id);
        if (!$spot || !$spot->isHarvestSpot()) {
            $this->addFlash('error', 'Spot non trouve.');

            return $this->redirectToRoute('admin_harvest_respawn_index');
        }

        $spot->setUsedAt(null);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new SpotAvailableEvent($spot), SpotAvailableEvent::NAME);

        $this->adminLogger->log('force_respawn', 'ObjectLayer', $spot->getId(), $spot->getName());
        $this->addFlash('success', sprintf('Spot "%s" force en respawn.', $spot->getName()));

        return $this->redirectToRoute('admin_harvest_respawn_index');
    }

    #[Route('/force-respawn-all', name: 'force_respawn_all', methods: ['POST'])]
    public function forceRespawnAll(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('force_respawn_all', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_harvest_respawn_index');
        }

        $spots = $this->em->getRepository(ObjectLayer::class)->createQueryBuilder('o')
            ->where('o.type = :type')
            ->andWhere('o.usedAt IS NOT NULL')
            ->setParameter('type', ObjectLayer::TYPE_HARVEST_SPOT)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($spots as $spot) {
            if (!$spot->isAvailable()) {
                $spot->setUsedAt(null);
                $this->eventDispatcher->dispatch(new SpotAvailableEvent($spot), SpotAvailableEvent::NAME);
                ++$count;
            }
        }

        $this->em->flush();

        $this->adminLogger->log('force_respawn_all', 'ObjectLayer', null, null, ['count' => $count]);
        $this->addFlash('success', sprintf('%d spot(s) force(s) en respawn.', $count));

        return $this->redirectToRoute('admin_harvest_respawn_index');
    }
}
