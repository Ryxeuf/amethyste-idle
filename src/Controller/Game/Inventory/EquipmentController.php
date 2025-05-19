<?php

namespace App\Controller\Game\Inventory;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/equipment', name: 'app_game_inventory_equipment_list')]
class EquipmentController extends AbstractController
{
    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Simuler la récupération des équipements
        // Dans un vrai cas d'usage, vous récupéreriez ces données depuis la base de données
        $equipments = [
            [
                'id' => 1,
                'name' => 'Épée d\'acier',
                'slotType' => 'main_weapon',
                'slotTypeName' => 'Arme principale',
                'level' => 5,
                'rarity' => 'uncommon',
                'description' => 'Une épée bien forgée avec une lame tranchante.',
                'stats' => [
                    'Attaque' => 10,
                    'Vitesse' => 2
                ]
            ],
            [
                'id' => 2,
                'name' => 'Bouclier de fer',
                'slotType' => 'side_weapon',
                'slotTypeName' => 'Arme secondaire',
                'level' => 4,
                'rarity' => 'common',
                'description' => 'Un bouclier robuste qui offre une bonne protection.',
                'stats' => [
                    'Défense' => 8,
                    'Santé' => 15
                ]
            ],
            [
                'id' => 3,
                'name' => 'Heaume du gardien',
                'slotType' => 'head',
                'slotTypeName' => 'Tête',
                'level' => 6,
                'rarity' => 'rare',
                'description' => 'Un casque enchanté qui accroît la perception.',
                'stats' => [
                    'Défense' => 6,
                    'Magie' => 4,
                    'Santé' => 10
                ]
            ]
        ];
        
        // Récupérer les équipements portés (exemple statique)
        $equipped = [
            'head' => null,
            'shoulder' => null,
            'neck' => null,
            'chest' => null,
            'hand' => null,
            'main_weapon' => [
                'id' => 1,
                'name' => 'Épée d\'acier'
            ],
            'side_weapon' => [
                'id' => 2,
                'name' => 'Bouclier de fer'
            ],
            'belt' => null,
            'leg' => null,
            'foot' => null,
            'ring_1' => null,
            'ring_2' => null
        ];
        
        // Statistiques du personnage (exemple)
        $stats = [
            'attack' => 25,
            'defense' => 18,
            'magic' => 12,
            'speed' => 15,
            'health' => 100,
            'mana' => 50
        ];
        
        return $this->render('game/inventory/equipment/_list.html.twig', [
            'equipments' => $equipments,
            'equipped' => $equipped,
            'stats' => $stats
        ]);
    }
} 