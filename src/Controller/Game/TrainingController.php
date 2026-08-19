<?php

namespace App\Controller\Game;

use App\GameEngine\Fight\TrainingFightLauncher;
use App\GameEngine\Tutorial\TrainingDummyOffer;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Dresser le mannequin de l'acte I.
 *
 * **La porte qui manquait.** `TrainingFightLauncher` etait ecrit, teste et
 * documente depuis ONB-11, et **aucun code ne l'appelait** : pas de route, pas
 * de bouton. Les etapes 3 et 5 de la chaine de l'acte I demandaient donc un
 * combat que rien ne permettait d'engager, et le tutoriel s'arretait la.
 *
 * Le combat ne coute **aucune energie** : c'est une lecon, pas une chasse, et
 * un nouveau venu a court d'energie devant le troisieme pas du tutoriel serait
 * bloque par le budget d'une journee sans avoir joue une seule fois.
 */
#[Route('/game/training')]
class TrainingController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly TrainingDummyOffer $dummyOffer,
        private readonly TrainingFightLauncher $launcher,
    ) {
    }

    #[Route('/dummy', name: 'app_game_training_dummy', methods: ['POST'])]
    public function dummy(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('training_dummy', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        if (null !== $player->getFight()) {
            // Deja en combat : le renvoyer sur le combat en cours plutot que
            // d'en poser un second, qui laisserait deux rencontres ouvertes.
            return $this->redirectToRoute('app_game_fight');
        }

        // C'est l'offre qui decide, pas l'URL : le mannequin demande est celui
        // de l'etape en cours. Un slug pris dans la requete aurait ouvert un
        // combat scripte a la demande, en zone sure, hors du tutoriel.
        $dummy = $this->dummyOffer->pendingFor($player);
        if (null === $dummy) {
            $this->addFlash('error', 'game.onboarding.training.error.not_now');

            return $this->redirectToRoute('app_game_zone');
        }

        $this->launcher->launch($player, $dummy);

        return $this->redirectToRoute('app_game_fight');
    }
}
