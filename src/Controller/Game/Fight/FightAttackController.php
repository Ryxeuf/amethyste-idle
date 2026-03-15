<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\GameEngine\Fight\Handler\PlayerActionHandlerInterface;
use App\GameEngine\Fight\PlayerActionHandler;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/attack', name: 'app_game_fight_attack')]
class FightAttackController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerActionHandler $playerActionHandler,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        $fight = $player->getFight();
        if (!$fight) {
            return new JsonResponse(['error' => 'Fight not found'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['targetId']) || !isset($data['targetType'])) {
            return new JsonResponse(['error' => 'Invalid request data', 'data' => $data], Response::HTTP_BAD_REQUEST);
        }

        // Trouver la cible
        $target = $this->findTarget($fight, $data['targetId'], $data['targetType']);
        if (!$target) {
            return new JsonResponse(['error' => 'Target not found'], Response::HTTP_NOT_FOUND);
        }

        // $fight = $this->playerActionHandler->doAction($fight, PlayerActionHandlerInterface::ACTION_ATTACK, $data['targetId'], $data['targetType']);

        // Effectuer l'attaque
        $damage = $this->calculateDamage($player, $target);
        $target->setLife(max(0, $target->getLife() - $damage));

        // Vérifier si la cible est morte
        if ($target->getLife() === 0) {
            $target->setDiedAt(new \DateTime());
        }

        // Mettre à jour le combat
        $fight->setStep($fight->getStep() + 1);

        // Sauvegarder les changements
        $this->entityManager->flush();

        // Retourner les données mises à jour
        return new JsonResponse([
            'success' => true,
            // 'damage' => $damage,
            // 'target' => [
            //     'id' => $target->getId(),
            //     'life' => $target->getLife(),
            //     'maxLife' => $target->getMaxLife(),
            //     'isDead' => $target->isDead()
            // ],
            'fight' => [
                'step' => $fight->getStep(),
                'terminated' => $fight->isTerminated(),
                'victory' => $fight->isVictory(),
            ],
        ]);
    }

    private function findTarget(Fight $fight, int $targetId, string $targetType): ?CharacterInterface
    {
        if ($targetType === 'player') {
            foreach ($fight->getPlayers() as $player) {
                if ($player->getId() === $targetId) {
                    return $player;
                }
            }
        } elseif ($targetType === 'mob') {
            foreach ($fight->getMobs() as $mob) {
                if ($mob->getId() === $targetId) {
                    return $mob;
                }
            }
        }

        return null;
    }

    private function calculateDamage(Player $attacker, CharacterInterface $target): int
    {
        // Calculer les dégâts de base
        $baseDamage = $attacker->getHit();

        // Appliquer les bonus/malus
        $damage = $baseDamage;

        // Vérifier si c'est un coup critique
        // if (rand(1, 100) <= $attacker->getCritical()) {
        //     $damage *= 2;
        // }

        return $damage;
    }
}
