<?php

namespace App\Controller\Admin;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/maps', name: 'admin_map_')]
#[IsGranted('ROLE_ADMIN')]
class MapController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $qb = $this->em->getRepository(Map::class)->createQueryBuilder('m');

        if ($search) {
            $qb->where('LOWER(m.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('m.name', 'ASC');
        $maps = $qb->getQuery()->getResult();

        $mapStats = [];
        foreach ($maps as $map) {
            $mapStats[$map->getId()] = [
                'players' => $this->em->getRepository(Player::class)->count(['map' => $map]),
                'mobs' => $this->em->getRepository(Mob::class)->count(['map' => $map]),
                'pnjs' => $this->em->getRepository(Pnj::class)->count(['map' => $map]),
            ];
        }

        return $this->render('admin/map/index.html.twig', [
            'maps' => $maps,
            'mapStats' => $mapStats,
            'search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(Map $map): Response
    {
        $players = $this->em->getRepository(Player::class)->findBy(['map' => $map]);
        $mobs = $this->em->getRepository(Mob::class)->findBy(['map' => $map]);
        $pnjs = $this->em->getRepository(Pnj::class)->findBy(['map' => $map]);

        return $this->render('admin/map/show.html.twig', [
            'map' => $map,
            'players' => $players,
            'mobs' => $mobs,
            'pnjs' => $pnjs,
        ]);
    }
}
