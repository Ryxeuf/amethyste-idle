<?php

namespace App\Controller\Game\Fight;

use App\Event\Fight\CombatFleeEvent;
use App\GameEngine\Fight\CombatLogArchiver;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\StatusEffectManager;
use App\GameEngine\Realtime\Fight\FightTurnPublisher;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/game/fight/flee', name: 'app_game_fight_flee', methods: ['POST'])]
class FightFleeController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly StatusEffectManager $statusEffectManager,
        private readonly CombatLogArchiver $combatLogArchiver,
        private readonly CombatLogger $combatLogger,
        private readonly FightTurnResolver $turnResolver,
        private readonly MobActionHandler $mobActionHandler,
        private readonly FightTurnPublisher $fightTurnPublisher,
        private readonly EventDispatcherInterface $eventDispatcher,
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
        $isCoop = $fight->isCoopFight();
        $isWorldBoss = $fight->isWorldBossFight();

        // Coop turn validation
        if ($isCoop && !$this->turnResolver->isPlayerTurn($fight, $player->getId())) {
            return new JsonResponse(['error' => 'Ce n\'est pas votre tour !', 'success' => false]);
        }

        // Cannot flee from boss fights (sauf world boss — on peut quitter le raid)
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
            $player->setIsMoving(false);

            // End fight - remove player from fight
            $player->setFight(null);
            $fight->removePlayer($player);

            if ($isWorldBoss || $isCoop) {
                // Coop / World boss : le joueur quitte, le combat continue pour les autres
                $this->entityManager->persist($player);

                if ($isCoop) {
                    // Advance coop turn since this player left
                    $remainingPlayers = $fight->getPlayers()->filter(fn ($p) => !$p->isDead() && $p->getFight() !== null);
                    if ($remainingPlayers->count() === 0) {
                        // All players fled or dead — nettoyage complet
                        $this->combatLogArchiver->archive($fight);
                        $this->statusEffectManager->clearAllEffects($fight);

                        foreach ($fight->getMobs() as $fightMob) {
                            $this->entityManager->remove($fightMob);
                        }

                        $this->entityManager->remove($fight);
                    } elseif ($remainingPlayers->count() === 1) {
                        // Only one player left — switch to solo mode
                        $fight->setCurrentTurnKey(null);
                    } else {
                        // Advance to next player
                        $this->turnResolver->advanceCoopTurn($fight, $this->mobActionHandler);
                    }
                }

                $this->entityManager->flush();

                if ($isCoop && $fight->getCurrentTurnKey() !== null) {
                    $this->fightTurnPublisher->publishTurnChange($fight);
                }
            } else {
                // Combat classique solo : nettoyage complet (aligné sur défaite/victoire)
                $this->combatLogArchiver->archive($fight);
                $this->statusEffectManager->clearAllEffects($fight);

                foreach ($fight->getMobs() as $fightMob) {
                    $this->entityManager->remove($fightMob);
                }

                $this->entityManager->remove($fight);
                $this->entityManager->persist($player);
                $this->entityManager->flush();
            }

            $this->eventDispatcher->dispatch(new CombatFleeEvent($player), CombatFleeEvent::NAME);

            return new JsonResponse([
                'success' => true,
                'fled' => true,
                'message' => ($isWorldBoss || $isCoop)
                    ? 'Vous quittez le combat !'
                    : 'Vous avez réussi à fuir !',
            ]);
        }

        // Failed flee - advance step
        $fight->setStep($fight->getStep() + 1);

        // Coop: advance turn after failed flee
        if ($isCoop && !$fight->isTerminated()) {
            $turnResult = $this->turnResolver->advanceCoopTurn($fight, $this->mobActionHandler);
            $this->entityManager->flush();
            $this->fightTurnPublisher->publishTurnChange($fight);

            return new JsonResponse([
                'success' => true,
                'fled' => false,
                'message' => 'La fuite a échoué !',
                'messages' => $turnResult['messages'],
                'fleeChance' => $fleeChance,
                'fight' => [
                    'step' => $fight->getStep(),
                    'terminated' => $fight->isTerminated(),
                ],
            ]);
        }

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
