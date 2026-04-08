<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Realtime\Fight\FightTurnPublisher;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/item', name: 'app_game_fight_item', methods: ['POST'])]
class FightItemController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly SpellApplicator $spellApplicator,
        private readonly MobActionHandler $mobActionHandler,
        private readonly CombatLogger $combatLogger,
        private readonly FightTurnResolver $turnResolver,
        private readonly FightTurnPublisher $fightTurnPublisher,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player || !$player->getFight()) {
            return new JsonResponse(['error' => 'No active fight'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['itemId'])) {
            return new JsonResponse(['error' => 'Invalid request data'], Response::HTTP_BAD_REQUEST);
        }

        $fight = $player->getFight();
        $isCoop = $fight->isCoopFight();

        // Coop turn validation
        if ($isCoop && !$this->turnResolver->isPlayerTurn($fight, $player->getId())) {
            return new JsonResponse(['error' => 'Ce n\'est pas votre tour !', 'success' => false]);
        }

        if ($player->isDead()) {
            return new JsonResponse(['error' => 'Vous êtes vaincu !', 'success' => false]);
        }

        // Find the item in player's inventory
        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find((int) $data['itemId']);
        if (!$playerItem) {
            return new JsonResponse(['error' => 'Objet introuvable'], Response::HTTP_NOT_FOUND);
        }

        $item = $playerItem->getGenericItem();
        $messages = [];

        // Items with a linked spell: apply the spell on self (heal, buff, etc.)
        if ($item->getSpell()) {
            $spell = $item->getSpell();

            // Apply spell on player (self-target for consumables)
            $target = $player;
            if (isset($data['targetId']) && isset($data['targetType']) && $data['targetType'] === 'mob') {
                foreach ($fight->getMobs() as $mob) {
                    if ($mob->getId() === (int) $data['targetId']) {
                        $target = $mob;
                        break;
                    }
                }
            }

            // Track heal usage for boss_challenge quests
            if ($spell->getHeal() > 0 && $target === $player) {
                $fight->setMetadataValue('heal_used', true);
            }

            $spellMessages = $this->spellApplicator->apply($spell, $player, $target, ['fight' => $fight]);
            $messages[] = sprintf('Vous utilisez %s !', $item->getName());
            $messages = array_merge($messages, $spellMessages);
            $this->combatLogger->logItem($fight, $player, $item->getName());

            if ($spell->getHeal() > 0) {
                $messages[] = sprintf('Vous récupérez %d PV.', $spell->getHeal());
            }
            if ($spell->getDamage() > 0) {
                $messages[] = sprintf('Vous infligez %d dégâts.', $spell->getDamage());
            }
        } else {
            return new JsonResponse(['error' => 'Cet objet ne peut pas être utilisé en combat'], Response::HTTP_BAD_REQUEST);
        }

        // Decrement usages
        $nbUsages = $item->getNbUsages();
        if ($nbUsages > 0) {
            // Limited usage item - track usages via removing from inventory
            $this->entityManager->remove($playerItem);
        }
        // nbUsages == -1 means unlimited

        // Advance fight step
        $fight->setStep($fight->getStep() + 1);

        // Tour du mob / avancement coop
        $mobResult = ['messages' => [], 'dangerAlert' => null];
        if ($isCoop) {
            if (!$fight->isTerminated()) {
                $turnResult = $this->turnResolver->advanceCoopTurn($fight, $this->mobActionHandler);
                $messages = array_merge($messages, $turnResult['messages']);
                $mobResult['dangerAlert'] = $turnResult['dangerAlert'];
            }
        } else {
            if (!$fight->isTerminated()) {
                $mobResult = $this->mobActionHandler->doAction($fight);
                $fight->setStep($fight->getStep() + 1);
            }
        }

        // Log victoire/defaite
        if ($fight->isTerminated()) {
            if ($fight->isVictory()) {
                $this->combatLogger->logVictory($fight);
            } else {
                $this->combatLogger->logDefeat($fight);
            }
        }

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        // Publish turn change via Mercure for coop
        if ($isCoop) {
            if ($fight->isTerminated()) {
                $this->fightTurnPublisher->publishFightEnd($fight);
            } else {
                $this->fightTurnPublisher->publishTurnChange($fight);
            }
        }

        return new JsonResponse([
            'success' => true,
            'messages' => array_merge($messages, $mobResult['messages']),
            'dangerAlert' => $mobResult['dangerAlert'],
            'fight' => [
                'step' => $fight->getStep(),
                'terminated' => $fight->isTerminated(),
                'victory' => $fight->isVictory(),
            ],
        ]);
    }
}
