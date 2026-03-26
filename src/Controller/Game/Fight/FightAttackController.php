<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightCalculator;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/attack', name: 'app_game_fight_attack', methods: ['POST'])]
class FightAttackController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly MobActionHandler $mobActionHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatLogger $combatLogger,
        private readonly FightTurnResolver $turnResolver,
        private readonly EnchantmentManager $enchantmentManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        $fight = $player->getFight();
        if (!$fight) {
            return new JsonResponse(['error' => 'Fight not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['targetId']) || !isset($data['targetType'])) {
            return new JsonResponse(['error' => 'Invalid request data'], Response::HTTP_BAD_REQUEST);
        }

        $target = $this->findTarget($fight, (int) $data['targetId'], $data['targetType']);
        if (!$target) {
            return new JsonResponse(['error' => 'Target not found'], Response::HTTP_NOT_FOUND);
        }

        $messages = [];
        $mobResult = ['messages' => [], 'dangerAlert' => null];
        $mobFirst = $this->turnResolver->isMobFirst($fight);

        // --- Mob agit en premier si plus rapide ---
        if ($mobFirst && !$fight->isTerminated()) {
            $mobResult = $this->mobActionHandler->doAction($fight);
            $fight->setStep($fight->getStep() + 1);
        }

        // --- Tour du joueur (s'il est encore vivant) ---
        if (!$player->isDead()) {
            $hit = FightCalculator::hasAttackHit($player->getHit());

            if ($hit) {
                $damage = $this->calculateDamage($player, $target);
                $target->setLife(max(0, $target->getLife() - $damage));
                $messages[] = sprintf('%s attaque %s pour %d degats !', $player->getName(), $target->getName(), $damage);

                $this->combatLogger->logAttack($fight, $player, $target, $damage);

                // Tracker la contribution pour les combats world boss
                if ($fight->isWorldBossFight()) {
                    $fight->addContribution($player->getId(), $damage);
                }

                if ($target->getLife() === 0) {
                    $target->setDiedAt(new \DateTime());
                    $messages[] = sprintf('%s est vaincu !', $target->getName());
                    $this->combatLogger->logDeath($fight, $target);
                }
            } else {
                $messages[] = sprintf('%s rate son attaque !', $player->getName());
            }
        }

        $fight->setStep($fight->getStep() + 1);

        // --- Mob agit apres si plus lent ---
        if (!$mobFirst && !$fight->isTerminated()) {
            $mobResult = $this->mobActionHandler->doAction($fight);
            $fight->setStep($fight->getStep() + 1);
        }

        // Log victoire/defaite
        if ($fight->isTerminated()) {
            if ($fight->isVictory()) {
                $this->combatLogger->logVictory($fight);
            } else {
                $this->combatLogger->logDefeat($fight);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'hit' => $hit ?? false,
            'messages' => $mobFirst
                ? array_merge($mobResult['messages'], $messages)
                : array_merge($messages, $mobResult['messages']),
            'dangerAlert' => $mobResult['dangerAlert'],
            'fight' => [
                'step' => $fight->getStep(),
                'terminated' => $fight->isTerminated(),
                'victory' => $fight->isVictory(),
            ],
        ]);
    }

    private function findTarget(Fight $fight, int $targetId, string $targetType): ?CharacterInterface
    {
        if ($targetType === 'mob') {
            foreach ($fight->getMobs() as $mob) {
                if ($mob->getId() === $targetId) {
                    return $mob;
                }
            }
        } elseif ($targetType === 'player') {
            foreach ($fight->getPlayers() as $player) {
                if ($player->getId() === $targetId) {
                    return $player;
                }
            }
        }

        return null;
    }

    private function calculateDamage(Player $attacker, CharacterInterface $target): int
    {
        $baseDamage = 3;
        $variance = random_int(0, 2);

        // Bonus d'enchantement sur les degats
        $enchantBonuses = $this->enchantmentManager->getEnchantmentBonuses($attacker);
        $enchantDamage = (int) ($enchantBonuses['damage'] ?? 0);

        return $baseDamage + $variance + $enchantDamage;
    }
}
