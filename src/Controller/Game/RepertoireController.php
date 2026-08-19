<?php

namespace App\Controller\Game;

use App\GameEngine\Repertoire\RepertoireState;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * L'etat du Repertoire (REP-05).
 *
 * **Ouvert a tous.** Le Scriptorium — la porte des Mages livree par FAC-09a — est
 * l'endroit ou l'on *voit* le Repertoire s'ecrire, mais il demande une
 * exaltation. Le savoir du serveur, lui, est un projet collectif : un joueur qui
 * n'a pas les couleurs du Cercle doit pouvoir suivre ce que son monde retrouve,
 * sinon la campagne que le canon appelle legitime ne serait le projet que d'une
 * maison.
 *
 * L'ecran est donc joignable depuis le Codex, et le Scriptorium y mene aussi.
 */
#[Route('/game/repertoire')]
class RepertoireController extends AbstractController
{
    /**
     * La zone d'ou le Repertoire se lit « sur place » (FAC-09a).
     *
     * Le Scriptorium est la seule des cinq portes dont le contenu soit un ecran
     * deja ecrit ailleurs — les quatre autres attendent FAC-09b→e. Nommer la
     * zone ici plutot que d'inventer une table porte→route pour un seul cas :
     * le mecanisme general viendra quand il y aura plusieurs cas a servir.
     */
    public const SCRIPTORIUM = 'le-scriptorium';

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly RepertoireState $state,
    ) {
    }

    #[Route('', name: 'app_game_repertoire', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        return $this->render('game/repertoire/index.html.twig', [
            'player' => $player,
            'state' => $this->state->snapshot(),
            'inScriptorium' => $player?->getCurrentZone()?->getSlug() === self::SCRIPTORIUM,
        ]);
    }
}
