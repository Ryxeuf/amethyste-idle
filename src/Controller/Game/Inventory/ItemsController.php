<?php

namespace App\Controller\Game\Inventory;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/items', name: 'app_game_inventory_items_list')]
class ItemsController extends AbstractController
{
    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Simuler la récupération des objets
        // Dans un vrai cas d'usage, vous récupéreriez ces données depuis la base de données
        $items = [
            [
                'id' => 1,
                'name' => 'Potion de Vie',
                'type' => 'Consommable',
                'quantity' => 5,
                'description' => 'Restaure 50 points de vie.'
            ],
            [
                'id' => 2,
                'name' => 'Potion de Mana',
                'type' => 'Consommable',
                'quantity' => 3,
                'description' => 'Restaure 30 points de mana.'
            ],
            [
                'id' => 3,
                'name' => 'Élixir de Force',
                'type' => 'Consommable',
                'quantity' => 2,
                'description' => '+20% d\'attaque pendant 5 minutes.'
            ],
            [
                'id' => 4,
                'name' => 'Antidote',
                'type' => 'Consommable',
                'quantity' => 7,
                'description' => 'Guérit les effets de poison.'
            ],
            [
                'id' => 5,
                'name' => 'Bombe Incendiaire',
                'type' => 'Consommable',
                'quantity' => 4,
                'description' => 'Inflige 75 dégâts de feu à tous les ennemis.'
            ],
            [
                'id' => 6,
                'name' => 'Parchemin de Téléportation',
                'type' => 'Consommable',
                'quantity' => 1,
                'description' => 'Téléporte instantanément à la ville la plus proche.'
            ]
        ];
        
        return $this->render('game/inventory/items/_list.html.twig', [
            'items' => $items
        ]);
    }
} 