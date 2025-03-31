<?php

namespace App\Controller\Game\Map;

use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/map/fight', name: 'app_game_map_fight')]
class FightController extends AbstractController
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            throw $this->createNotFoundException('Player not found');
        }
        if (!$player->getFight()) {
            throw $this->createNotFoundException('Fight not found');
        }
        
        
        // Vous pouvez ajouter ici la logique pour récupérer les données de la carte
        // Par exemple, récupérer les régions, les points d'intérêt, etc.
        
        return $this->render('game/map/fight.html.twig');
    }
} 