<?php

namespace App\Controller\Game\Inventory;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/materia', name: 'app_game_inventory_materia_list')]
class MateriaController extends AbstractController
{
    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Simuler la récupération des materias
        // Dans un vrai cas d'usage, vous récupéreriez ces données depuis la base de données
        $materias = [
            [
                'id' => 301,
                'name' => 'Materia de Feu',
                'level' => 2,
                'rarity' => 'uncommon',
                'description' => 'Ajoute des dégâts de feu à l\'arme.',
                'effects' => '+15 dégâts de feu par attaque'
            ],
            [
                'id' => 302,
                'name' => 'Materia de Soin',
                'level' => 1,
                'rarity' => 'common',
                'description' => 'Permet de se soigner pendant le combat.',
                'effects' => 'Soigne 20 PV après chaque combat'
            ],
            [
                'id' => 303,
                'name' => 'Materia d\'Agilité',
                'level' => 3,
                'rarity' => 'rare',
                'description' => 'Augmente la vitesse et l\'esquive.',
                'effects' => '+20% à la vitesse, +10% à l\'esquive'
            ]
        ];
        
        return $this->render('game/inventory/materia/_list.html.twig', [
            'materias' => $materias
        ]);
    }
} 