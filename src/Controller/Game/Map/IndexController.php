<?php

namespace App\Controller\Game\Map;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/map', name: 'app_game_map')]
class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Vous pouvez ajouter ici la logique pour récupérer les données de la carte
        // Par exemple, récupérer les régions, les points d'intérêt, etc.
        
        return $this->render('game/map/index.html.twig');
    }
} 