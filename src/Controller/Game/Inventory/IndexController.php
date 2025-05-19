<?php

namespace App\Controller\Game\Inventory;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[Route('/game/inventory', name: 'app_game_inventory')]
#[AsController]
class IndexController extends AbstractController
{
    #[Route('', name: '_index')]
    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // L'utilisateur connecté
        $user = $this->getUser();
        
        // Données de base pour l'inventaire
        $inventoryData = [
            'gold' => 0, //method_exists($user, 'getGold') ? $user->getGold() : 0,
            // Autres données générales si nécessaires
        ];
        
        return $this->render('game/inventory/index.html.twig', [
            'inventory' => $inventoryData
        ]);
    }
}
