<?php

namespace App\Controller\Game\Fight;

use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight', name: 'app_game_fight')]
class FightIndexController extends AbstractController
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
        
        return $this->render('game/fight/index.html.twig', [
            'player' => $player,
            'fight' => $player->getFight(),
            'mob' => $player->getFight()->getMobs()->first(),
        ]);
    }
} 