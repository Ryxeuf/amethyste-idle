<?php

namespace App\Controller\Game\Inventory;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/materials', name: 'app_game_inventory_materials_list')]
class MaterialsController extends AbstractController
{
    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Simuler la récupération des matériaux
        // Dans un vrai cas d'usage, vous récupéreriez ces données depuis la base de données
        $materials = [
            [
                'id' => 101,
                'name' => 'Minerai de Fer',
                'rarity' => 'common',
                'rarityName' => 'Matériau commun',
                'quantity' => 24,
                'description' => 'Utilisé pour forger des armes et armures basiques.',
            ],
            [
                'id' => 102,
                'name' => 'Lingot d\'Or',
                'rarity' => 'rare',
                'rarityName' => 'Matériau rare',
                'quantity' => 8,
                'description' => 'Utilisé pour les enchantements et objets de valeur.',
            ],
            [
                'id' => 103,
                'name' => 'Cristal Arcanique',
                'rarity' => 'rare',
                'rarityName' => 'Matériau magique',
                'quantity' => 5,
                'description' => 'Amplifie les propriétés magiques des objets.',
            ],
            [
                'id' => 104,
                'name' => 'Herbe Médicinale',
                'rarity' => 'common',
                'rarityName' => 'Matériau commun',
                'quantity' => 17,
                'description' => 'Utilisée pour créer des potions de soin.',
            ],
            [
                'id' => 105,
                'name' => 'Essence Éthérée',
                'rarity' => 'rare',
                'rarityName' => 'Matériau rare',
                'quantity' => 3,
                'description' => 'Utilisée pour les potions de mana avancées.',
            ],
            [
                'id' => 106,
                'name' => 'Écaille de Dragon',
                'rarity' => 'legendary',
                'rarityName' => 'Matériau légendaire',
                'quantity' => 1,
                'description' => 'Composant essentiel pour les équipements légendaires.',
            ],
        ];

        return $this->render('game/inventory/materials/_list.html.twig', [
            'materials' => $materials,
        ]);
    }
}
