<?php

namespace App\Controller\Game\Fight;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboStreamResponse;

#[Route('/game/fight/timeline', name: 'app_game_fight_timeline')]
class FightTimelineController extends AbstractController
{
    public function __invoke(): Response
    {
        $response = $this->render('game/fight/timeline.html.twig', [], new TurboStreamResponse());
        $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');

        return $response;
    }
}
