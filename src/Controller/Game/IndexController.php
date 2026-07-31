<?php

namespace App\Controller\Game;

use App\GameEngine\Player\PlayerHubDigest;
use App\GameEngine\Retention\WeeklyRecapService;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\ExpeditionService;
use App\GameEngine\Zone\LifeRegenManager;
use App\GameEngine\Zone\PlayerZoneSynchronizer;
use App\GameEngine\Zone\ZoneTravelService;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Le hub : premier ecran apres connexion.
 *
 * Il repond a deux questions et s'interdit le reste : **qu'est-ce qui
 * m'attend** (`pending`, `recap`) et **je fais quoi maintenant** (`resume`).
 * Ce qui relevait de la fiche de personnage — l'equipement porte, le detail de
 * l'inventaire — est parti chez lui, dans l'inventaire.
 *
 * Les regularisations paresseuses (arrivee de voyage, energie, PV, expedition)
 * sont les memes que sur l'ecran de zone, et pour la meme raison : sans elles,
 * le hub annoncerait un voyage termine il y a deux heures comme s'il durait
 * encore. C'est le premier ecran vu apres connexion, donc le premier a devoir
 * dire la verite.
 */
class IndexController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerZoneSynchronizer $playerZoneSynchronizer,
        private readonly PlayerHubDigest $hubDigest,
        private readonly ZoneTravelService $zoneTravelService,
        private readonly ActionEnergyManager $actionEnergyManager,
        private readonly LifeRegenManager $lifeRegenManager,
        private readonly ExpeditionService $expeditionService,
        // En dernier : une dependance nouvelle s'ajoute en queue, jamais au
        // milieu — un service insere entre deux autres decalerait sans un mot
        // toute construction positionnelle.
        private readonly WeeklyRecapService $weeklyRecap,
    ) {
    }

    #[Route('/game', name: 'app_game')]
    public function __invoke(): Response
    {
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return $this->redirectToRoute('app_home');
        }

        $this->zoneTravelService->settleArrival($player);
        $this->actionEnergyManager->refresh($player, true);
        $this->lifeRegenManager->refresh($player, true);
        $this->expeditionService->settle($player);

        // Un joueur sans position en recoit une ici : le hub est le premier
        // ecran apres connexion, et « position inconnue » n'est pas un etat
        // qu'on doit lire avant meme d'avoir ouvert l'ecran de zone.
        $this->playerZoneSynchronizer->resolveOrAssign($player, true);

        return $this->render('game/index.html.twig', [
            'player' => $player,
            'resume' => $this->hubDigest->resume($player),
            'pending' => $this->hubDigest->pending($player),
            'recap' => $this->hubDigest->recap($player),
            // RET-08 : l'assiduite n'est plus un encart a elle seule — elle est
            // la cinquieme ligne du bloc « La semaine ».
            'week' => $this->hubDigest->week($player),
            // RET-09 : le lundi. L'appel **consomme** la semaine close — c'est
            // le rendu qui la marque comme vue, et la visite suivante rend le
            // bloc compact. Il vient apres `week()` par lisibilite seulement :
            // les deux lectures sont independantes.
            'weekRecap' => $this->weeklyRecap->consume($player),
            'domainExperiences' => $player->getDomainExperiences(),
            'quests' => $this->hubDigest->quests($player),
            'energy' => [
                'current' => $player->getActionEnergy(),
                'max' => $player->getMaxActionEnergy(),
                'nextPointIn' => $this->actionEnergyManager->secondsUntilNextPoint($player),
            ],
            'life' => [
                'current' => $player->getLife(),
                'max' => $player->getMaxLife(),
                'fullIn' => $this->lifeRegenManager->secondsUntilFull($player),
            ],
        ]);
    }
}
