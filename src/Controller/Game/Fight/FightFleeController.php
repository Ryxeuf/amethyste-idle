<?php

namespace App\Controller\Game\Fight;

use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/flee', name: 'app_game_fight_flee', methods: ['POST'])]
class FightFleeController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly StatusEffectManager $statusEffectManager,
        private readonly CombatLogger $combatLogger,
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

        // Cannot flee from boss fights (sauf world boss — on peut quitter le raid)
        $isWorldBoss = $fight->isWorldBossFight();
        if (!$isWorldBoss) {
            foreach ($fight->getMobs() as $fightMob) {
                if (!$fightMob->isDead() && $fightMob->getMonster()->isBoss()) {
                    return new JsonResponse(['error' => 'Impossible de fuir un boss !', 'success' => false]);
                }
            }
        }

        // Cannot flee while berserk
        if ($this->statusEffectManager->isCharacterBerserk($fight, $player)) {
            return new JsonResponse(['error' => 'Impossible de fuir en etat de rage !', 'success' => false]);
        }

        // Cannot flee while paralyzed/frozen
        if ($this->statusEffectManager->isCharacterParalyzed($fight, $player)
            || $this->statusEffectManager->isCharacterFrozen($fight, $player)) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas bouger !', 'success' => false]);
        }

        // Flee chance based on player speed vs fastest mob speed (50-90%)
        $playerSpeed = $player->getSpeed();
        $mobSpeed = 10;
        foreach ($fight->getMobs() as $fightMob) {
            if (!$fightMob->isDead()) {
                $mobSpeed = max($mobSpeed, $fightMob->getSpeed());
            }
        }
        $fleeChance = min(90, max(50, 50 + ($playerSpeed - $mobSpeed) * 2));
        $success = random_int(1, 100) <= $fleeChance;

        $this->combatLogger->logFlee($fight, $player, $success);

        if ($success) {
            // Repositionner le joueur sur sa case precedente (avant le combat)
            $player->setCoordinates($player->getLastCoordinates());

            // End fight - remove player from fight
            $player->setFight(null);
            $fight->removePlayer($player);

            if ($isWorldBoss) {
                // World boss : le joueur quitte, le combat continue pour les autres
                $this->entityManager->persist($player);
                $this->entityManager->flush();
            } else {
                // Combat classique : fin complète du combat
                $fight->setInProgress(false);
                foreach ($fight->getMobs() as $fightMob) {
                    $fightMob->setFight(null);
                    $this->entityManager->persist($fightMob);
                }
                $this->entityManager->persist($player);
                $this->entityManager->persist($fight);
                $this->entityManager->flush();
            }

            return new JsonResponse([
                'success' => true,
                'fled' => true,
                'message' => $isWorldBoss
                    ? 'Vous quittez le raid !'
                    : 'Vous avez réussi à fuir !',
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
