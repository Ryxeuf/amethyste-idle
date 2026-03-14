<?php

namespace App\Controller\Game\Fight;

use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/flee', name: 'app_game_fight_flee')]
class FightFleeController extends AbstractController
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
        if (!$player || !$player->getFight()) {
            return new JsonResponse(['error' => 'No active fight'], Response::HTTP_NOT_FOUND);
        }

        $fight = $player->getFight();

        // Cannot flee from boss fights
        $mob = $fight->getMobs()->first();
        if ($mob && $mob->getMonster()->isBoss()) {
            return new JsonResponse(['error' => 'Impossible de fuir un boss !', 'success' => false]);
        }

        // Flee chance based on player speed vs mob speed (50-90%)
        $playerSpeed = $player->getSpeed();
        $mobSpeed = $mob ? $mob->getSpeed() : 10;
        $fleeChance = min(90, max(50, 50 + ($playerSpeed - $mobSpeed) * 2));
        $success = random_int(1, 100) <= $fleeChance;

        if ($success) {
            // End fight - remove player from fight
            $player->setFight(null);
            $fight->setInProgress(false);

            // Remove mobs from fight
            foreach ($fight->getMobs() as $fightMob) {
                $fightMob->setFight(null);
                $this->entityManager->persist($fightMob);
            }

            $this->entityManager->persist($player);
            $this->entityManager->persist($fight);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'fled' => true,
                'message' => 'Vous avez réussi à fuir !',
            ]);
        }

        // Failed flee - mob gets a free hit, advance step
        $fight->setStep($fight->getStep() + 1);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'fled' => false,
            'message' => 'La fuite a échoué !',
            'fleeChance' => $fleeChance,
            'fight' => [
                'step' => $fight->getStep(),
                'terminated' => $fight->isTerminated(),
            ],
        ]);
    }
}
