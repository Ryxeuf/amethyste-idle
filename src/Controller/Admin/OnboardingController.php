<?php

namespace App\Controller\Admin;

use App\GameEngine\Onboarding\OnboardingFunnelReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Le tunnel d'entree, mesure (ONB-19b).
 *
 * GAME_ONBOARDING § 9 : *sans mesure, on repare a l'aveugle*. ONB-19a a ferme
 * ce qui ne doit jamais arriver ; cet ecran dit ou les gens s'arretent, ce
 * qu'aucun test ne peut dire.
 *
 * L'ecran est en **lecture pure** : il n'offre aucun bouton, ne declenche
 * aucune tache, et ne stocke rien. C'est un ecran de constat, et l'exploitant
 * qui veut agir agit ailleurs.
 */
#[Route('/admin/onboarding', name: 'admin_onboarding_')]
class OnboardingController extends AbstractController
{
    public function __construct(
        private readonly OnboardingFunnelReader $reader,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/onboarding/index.html.twig', [
            'funnel' => $this->reader->read(),
        ]);
    }
}
