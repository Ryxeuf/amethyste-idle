<?php

namespace App\Controller\Admin;

use App\Entity\App\AdminLog;
use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\Quest;
use App\Entity\Game\Recipe;
use App\Entity\Game\Spell;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
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
                'label' => 'Recettes',
                'count' => $this->em->getRepository(Recipe::class)->count([]),
                'color' => 'orange',
                'route' => 'admin_dashboard',
            ],
            [
                'label' => 'Utilisateurs',
                'count' => $this->em->getRepository(User::class)->count([]),
                'color' => 'blue',
                'route' => 'admin_user_index',
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

        $zoneStats = $this->buildZoneStats();

        return $this->render('admin/dashboard/index.html.twig', [
            'metrics' => $metrics,
            'liveStats' => $liveStats,
            'maintenanceActive' => $maintenanceActive,
            'recentLogs' => $recentLogs,
            'zoneStats' => $zoneStats,
        ]);
    }

    /**
     * Repartition du monde par zone.
     *
     * Comptait par carte : depuis le pivot, la position d'un joueur est sa zone
     * (regle #7), et toute zone creee sans carte d'origine restait invisible ici
     * — colonnes a zero pour une zone pourtant peuplee.
     *
     * @return list<array{id: int, name: string, type: string, pnjCount: int, mobCount: int, playerCount: int}>
     */
    private function buildZoneStats(): array
    {
        /** @var list<Zone> $zones */
        $zones = $this->em->getRepository(Zone::class)
            ->createQueryBuilder('z')
            ->andWhere('z.enabled = true')
            ->orderBy('z.name', 'ASC')
            ->getQuery()
            ->getResult();

        $rows = [];
        foreach ($zones as $zone) {
            $rows[$zone->getId()] = [
                'id' => $zone->getId(),
                'name' => $zone->getName(),
                'type' => $zone->getType(),
                'pnjCount' => 0,
                'mobCount' => 0,
                'playerCount' => 0,
            ];
        }

        if ([] === $rows) {
            return [];
        }

        $ids = array_keys($rows);
        $connectedThreshold = new \DateTimeImmutable('-15 minutes');

        $families = [
            'pnjCount' => [Pnj::class, 'zone', null],
            'mobCount' => [Mob::class, 'zone', 'e.diedAt IS NULL'],
            'playerCount' => [Player::class, 'currentZone', 'e.updatedAt >= :threshold'],
        ];

        foreach ($families as $key => [$entity, $field, $condition]) {
            $qb = $this->em->createQueryBuilder()
                ->select('z.id AS zoneId, COUNT(e.id) AS total')
                ->from($entity, 'e')
                ->join('e.' . $field, 'z')
                ->where('z.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->groupBy('z.id');

            if (null !== $condition) {
                $qb->andWhere($condition);
            }
            if (str_contains((string) $condition, ':threshold')) {
                $qb->setParameter('threshold', $connectedThreshold);
            }

            foreach ($qb->getQuery()->getResult() as $row) {
                $rows[(int) $row['zoneId']][$key] = (int) $row['total'];
            }
        }

        return array_values($rows);
    }
}
