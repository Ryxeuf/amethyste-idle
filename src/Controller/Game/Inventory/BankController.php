<?php

namespace App\Controller\Game\Inventory;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/bank', name: 'app_game_inventory_bank_list')]
class BankController extends AbstractController
{
    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Simuler la récupération des objets en banque
        // Dans un vrai cas d'usage, vous récupéreriez ces données depuis la base de données
        $bankItems = [
            [
                'id' => 201,
                'name' => 'Amulette de Protection',
                'type' => 'Talisman',
                'quantity' => 1,
                'description' => 'Réduit les dégâts subis de 10%.'
            ],
            [
                'id' => 202,
                'name' => 'Clé du Donjon',
                'type' => 'Clé spéciale',
                'quantity' => 2,
                'description' => 'Permet d\'accéder au Donjon des Ombres.'
            ],
            [
                'id' => 203,
                'name' => 'Grimoire Ancien',
                'type' => 'Livre',
                'quantity' => 1,
                'description' => 'Contient des sorts anciens. À déchiffrer.'
            ]
        ];
        
        return $this->render('game/inventory/bank/_list.html.twig', [
            'bankItems' => $bankItems
        ]);
    }
} 