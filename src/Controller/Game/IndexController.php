<?php

namespace App\Controller\Game;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/game', name: 'app_game')]
    public function __invoke(): Response
    {
        $user = $this->getUser();
        
        return $this->render('game/index.html.twig', [
            'user' => $user,
        ]);
    }
} 
