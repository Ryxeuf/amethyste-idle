<?php

namespace App\Controller\Game\Inventory;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory', name: 'app_game_inventory')]
class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Vous pouvez ajouter ici la logique pour récupérer les données d'inventaire
        // Par exemple, récupérer les équipements, objets et matériaux du joueur
        
        return $this->render('game/inventory/index.html.twig');
    }
}
