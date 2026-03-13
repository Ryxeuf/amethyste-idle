<?php

namespace App\Controller\Admin;

use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\Quest;
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
                'label' => 'Utilisateurs',
                'count' => $this->em->getRepository(User::class)->count([]),
                'color' => 'blue',
                'route' => null,
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
                'route' => null,
            ],
        ];

        return $this->render('admin/dashboard/index.html.twig', [
            'metrics' => $metrics,
        ]);
    }
}
