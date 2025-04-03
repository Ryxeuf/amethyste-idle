<?php

namespace App\Controller\Game\Fight;

use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/attack', name: 'app_game_fight_attack')]
class FightAttackController extends AbstractController
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function __invoke(): Response
    {
        return $this->render('game/fight/attack.html.twig');
    }
}
