<?php

namespace App\Controller\Game;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/support', name: 'app_game_support')]
class SupportController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('game/support.html.twig');
    }
}
