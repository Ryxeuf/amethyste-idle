<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class IndexController extends AbstractController
{
    #[Route('/game', name: 'app_game')]
    #[IsGranted('ROLE_USER')]
    public function __invoke(): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        return $this->render('game/index.html.twig', [
            'user' => $user,
        ]);
    }
} 
