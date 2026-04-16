<?php

namespace App\Controller\Game\Map;

use App\Entity\App\Fight;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/map', name: 'app_game_map')]
class IndexController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Réconcilier fight_id depuis la BDD pour éviter un état stale de l'identity map Doctrine
        if ($player->getFight() !== null && $this->entityManager->contains($player)) {
            $fightId = $this->entityManager->getConnection()->fetchOne(
                'SELECT fight_id FROM player WHERE id = ?',
                [$player->getId()],
            );
            if ($fightId === null || $fightId === false || $fightId === '') {
                $player->setFight(null);
            } else {
                $player->setFight($this->entityManager->getReference(Fight::class, (int) $fightId));
            }
        }

        if ($player->getFight() !== null && !$player->getFight()->isTerminated()) {
            return $this->redirectToRoute('app_game_fight');
        }

        $coordinates = $player->getCoordinates();
        [$x, $y] = array_map('intval', explode('.', $coordinates));

        $baseDelay = 120;
        $defaultSpeed = 10;
        $stepDelay = max(40, (int) round($baseDelay * $defaultSpeed / max(1, $player->getSpeed())));

        return $this->render('game/map/index.html.twig', [
            'mapId' => $player->getMap()->getId(),
            'playerX' => $x,
            'playerY' => $y,
            'playerId' => $player->getId(),
            'stepDelay' => $stepDelay,
        ]);
    }
}
