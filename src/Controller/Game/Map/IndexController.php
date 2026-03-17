<?php

namespace App\Controller\Game\Map;

use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/map', name: 'app_game_map')]
class IndexController extends AbstractController
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_dashboard');
        }
        if ($player->getFight() !== null && !$player->getFight()->isTerminated()) {
            return $this->redirectToRoute('app_game_fight');
        }

        $coordinates = $player->getCoordinates() ?? '0.0';
        [$x, $y] = array_map('intval', explode('.', $coordinates));

        return $this->render('game/map/index.html.twig', [
            'mapId' => $player->getMap()->getId(),
            'playerX' => $x,
            'playerY' => $y,
            'playerId' => $player->getId(),
        ]);
    }
}
