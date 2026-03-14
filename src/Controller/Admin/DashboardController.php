<?php

namespace App\Controller\Admin;

use App\Entity\App\AdminLog;
use App\Entity\App\Fight;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\CraftRecipe;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\Quest;
use App\Entity\Game\Spell;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'dashboard')]
    public function index(): Response
    {
        $metrics = [
            [
                'label' => 'Joueurs',
                'count' => $this->em->getRepository(Player::class)->count([]),
                'color' => 'purple',
                'route' => 'admin_player_index',
            ],
            [
                'label' => 'Items',
                'count' => $this->em->getRepository(Item::class)->count([]),
                'color' => 'yellow',
                'route' => 'admin_item_index',
            ],
            [
                'label' => 'Monstres',
                'count' => $this->em->getRepository(Monster::class)->count([]),
                'color' => 'red',
                'route' => 'admin_monster_index',
            ],
            [
                'label' => 'Sorts',
                'count' => $this->em->getRepository(Spell::class)->count([]),
                'color' => 'indigo',
                'route' => 'admin_spell_index',
            ],
            [
                'label' => 'Quetes',
                'count' => $this->em->getRepository(Quest::class)->count([]),
                'color' => 'green',
                'route' => 'admin_quest_index',
            ],
            [
                'label' => 'Cartes',
                'count' => $this->em->getRepository(Map::class)->count([]),
                'color' => 'pink',
                'route' => 'admin_map_index',
            ],
            [
                'label' => 'Recettes',
                'count' => $this->em->getRepository(CraftRecipe::class)->count([]),
                'color' => 'orange',
                'route' => 'admin_craft_recipe_index',
            ],
            [
                'label' => 'Utilisateurs',
                'count' => $this->em->getRepository(User::class)->count([]),
                'color' => 'blue',
                'route' => null,
            ],
        ];

        $activeFights = $this->em->getRepository(Fight::class)->count(['inProgress' => true]);
        $activeMobs = $this->em->getRepository(Mob::class)->count([]);
        $totalGils = (int) $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(p.gils), 0)')
            ->from(Player::class, 'p')
            ->getQuery()
            ->getSingleScalarResult();
        $bannedPlayers = (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.isBanned = true')
            ->getQuery()
            ->getSingleScalarResult();

        $liveStats = [
            ['label' => 'Combats en cours', 'value' => $activeFights, 'color' => 'red'],
            ['label' => 'Mobs actifs', 'value' => $activeMobs, 'color' => 'orange'],
            ['label' => 'Gils en circulation', 'value' => number_format($totalGils, 0, ',', ' '), 'color' => 'yellow'],
            ['label' => 'Joueurs bannis', 'value' => $bannedPlayers, 'color' => 'gray'],
        ];

        $maintenanceActive = is_file($this->getParameter('kernel.project_dir') . '/var/maintenance.flag');

        $recentLogs = $this->em->getRepository(AdminLog::class)
            ->createQueryBuilder('l')
            ->leftJoin('l.adminUser', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard/index.html.twig', [
            'metrics' => $metrics,
            'liveStats' => $liveStats,
            'maintenanceActive' => $maintenanceActive,
            'recentLogs' => $recentLogs,
        ]);
    }
}
