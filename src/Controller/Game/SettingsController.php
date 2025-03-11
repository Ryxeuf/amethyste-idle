<?php

namespace App\Controller\Game;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/settings', name: 'app_game_settings')]
class SettingsController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('game/settings.html.twig');
    }
}
