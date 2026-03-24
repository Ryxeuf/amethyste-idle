<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\Game\Item;
use App\Entity\Game\Quest;
use App\Event\Game\QuestCompletedEvent;
use App\GameEngine\Quest\PlayerQuestHelper;
use App\GameEngine\Quest\PlayerQuestUpdater;
use App\GameEngine\Quest\QuestGiverResolver;
use App\GameEngine\Quest\QuestTrackingFormater;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/game/quests')]
class QuestController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerQuestHelper $playerQuestHelper,
        private readonly QuestTrackingFormater $questTrackingFormater,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly InventoryHelper $inventoryHelper,
        private readonly QuestGiverResolver $questGiverResolver,
    ) {
    }

    #[Route('', name: 'app_game_quests', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activeQuests = $this->playerQuestHelper->getCurrentQuests();
        $completedQuests = $this->playerQuestHelper->getCompletedQuests();
        $availableQuests = $this->playerQuestHelper->getAvailableQuests();

        // Calculate progress for each active quest
        $questProgress = [];
        foreach ($activeQuests as $playerQuest) {
            $questProgress[$playerQuest->getId()] = $this->playerQuestHelper->getPlayerQuestProgress($playerQuest);
        }

        // Resolve quest givers, types and chain info for all quests
        $allQuests = array_merge(
            $availableQuests,
            array_map(fn ($pq) => $pq->getQuest(), $activeQuests),
        );
        $questGivers = $this->questGiverResolver->getQuestGivers($allQuests);
        $questTypes = [];
        $questChains = [];
        foreach ($allQuests as $quest) {
            $questTypes[$quest->getId()] = $this->questGiverResolver->getQuestType($quest);
            $questChains[$quest->getId()] = $this->questGiverResolver->getChainInfo($quest);
        }

        return $this->render('game/quest/index.html.twig', [
            'activeQuests' => $activeQuests,
            'completedQuests' => $completedQuests,
            'availableQuests' => $availableQuests,
            'questProgress' => $questProgress,
            'questGivers' => $questGivers,
            'questTypes' => $questTypes,
            'questChains' => $questChains,
            'player' => $this->playerHelper->getPlayer(),
        ]);
    }

    #[Route('/accept/{id}', name: 'app_game_quest_accept', methods: ['POST'])]
    public function accept(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $quest = $this->entityManager->getRepository(Quest::class)->find($id);
        if (!$quest) {
            return new JsonResponse(['error' => 'Quête introuvable'], Response::HTTP_NOT_FOUND);
        }

        $player = $this->playerHelper->getPlayer();

        // Check if already accepted
        $existing = $this->entityManager->getRepository(PlayerQuest::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
        if ($existing) {
            return new JsonResponse(['error' => 'Quête déjà acceptée'], Response::HTTP_BAD_REQUEST);
        }

        // Check if already completed
        $completed = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
        if ($completed) {
            return new JsonResponse(['error' => 'Quête déjà complétée'], Response::HTTP_BAD_REQUEST);
        }

        // Check prerequisites
        $prerequisiteIds = $quest->getPrerequisiteQuests();
        if (!empty($prerequisiteIds)) {
            $completedPrerequisites = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findBy([
                'player' => $player,
                'quest' => $prerequisiteIds,
            ]);
            if (\count($completedPrerequisites) < \count($prerequisiteIds)) {
                return new JsonResponse(['error' => 'Prérequis de quête non remplis'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Create tracking data from requirements
        $tracking = $this->questTrackingFormater->formatTracking($quest);

        $playerQuest = new PlayerQuest();
        $playerQuest->setPlayer($player);
        $playerQuest->setQuest($quest);
        $playerQuest->setTracking($tracking);

        $this->entityManager->persist($playerQuest);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Quête "%s" acceptée !', $quest->getName()),
        ]);
    }

    #[Route('/abandon/{id}', name: 'app_game_quest_abandon', methods: ['POST'])]
    public function abandon(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $playerQuest = $this->playerQuestHelper->getQuest($id);
        if (!$playerQuest) {
            return new JsonResponse(['error' => 'Quête introuvable'], Response::HTTP_NOT_FOUND);
        }

        $questName = $playerQuest->getQuest()->getName();
        $this->entityManager->remove($playerQuest);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Quête "%s" abandonnée.', $questName),
        ]);
    }

    #[Route('/deliver/{pnjId}', name: 'app_game_quest_deliver', methods: ['POST'])]
    public function deliver(int $pnjId, PlayerQuestUpdater $playerQuestUpdater): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $quests = $this->playerQuestHelper->getCurrentQuests();
        $delivered = [];

        foreach ($quests as $playerQuest) {
            $tracking = $playerQuest->getTracking();
            if (!isset($tracking['deliver'])) {
                continue;
            }
            foreach ($tracking['deliver'] as $entry) {
                if ($entry['pnj_id'] !== $pnjId || $entry['count'] >= $entry['necessary']) {
                    continue;
                }
                // Check if player has the required item
                $itemSlug = $entry['item_slug'];
                $needed = $entry['necessary'] - ($entry['count'] ?? 0);
                $removed = $this->inventoryHelper->removeItemBySlug($itemSlug, $needed);
                if ($removed > 0) {
                    $playerQuestUpdater->updateDelivered($itemSlug, $pnjId, $removed);
                    $delivered[] = sprintf('%d x %s', $removed, $entry['name'] ?? $itemSlug);
                }
            }
        }

        if (empty($delivered)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'avez rien a livrer a ce PNJ.',
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Livre : %s', implode(', ', $delivered)),
        ]);
    }

    #[Route('/complete/{id}', name: 'app_game_quest_complete', methods: ['POST'])]
    public function complete(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $playerQuest = $this->playerQuestHelper->getQuest($id);
        if (!$playerQuest) {
            return new JsonResponse(['error' => 'Quête introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Check if quest is completed
        if (!$this->playerQuestHelper->isPlayerQuestCompleted($playerQuest)) {
            $progress = $this->playerQuestHelper->getPlayerQuestProgress($playerQuest);

            return new JsonResponse([
                'error' => sprintf('Quête non terminée (%d%%)', $progress),
            ], Response::HTTP_BAD_REQUEST);
        }

        $player = $this->playerHelper->getPlayer();
        $quest = $playerQuest->getQuest();
        $rewards = $quest->getRewards();
        $messages = [sprintf('Quête "%s" complétée !', $quest->getName())];

        // Handle quest with choices
        $choiceMade = null;
        $choiceOutcome = $quest->getChoiceOutcome();
        if (!empty($choiceOutcome)) {
            $body = json_decode($request->getContent(), true) ?? [];
            $choiceKey = $request->request->get('choice') ?? $body['choice'] ?? null;
            if (!$choiceKey) {
                return new JsonResponse([
                    'error' => 'Cette quête nécessite un choix.',
                    'choices' => $choiceOutcome,
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate the choice exists
            $validChoice = null;
            foreach ($choiceOutcome as $outcome) {
                if ($outcome['key'] === $choiceKey) {
                    $validChoice = $outcome;
                    break;
                }
            }
            if (!$validChoice) {
                return new JsonResponse(['error' => 'Choix invalide.'], Response::HTTP_BAD_REQUEST);
            }

            $choiceMade = $choiceKey;
            $messages[] = sprintf('Choix : %s', $validChoice['label']);

            // Apply bonus rewards from choice
            $bonusRewards = $validChoice['bonusRewards'] ?? [];
            $this->applyRewards($bonusRewards, $player, $messages);
        }

        // Apply base rewards
        $this->applyRewards($rewards, $player, $messages);

        // Create completed record
        $completedQuest = new PlayerQuestCompleted();
        $completedQuest->setPlayer($player);
        $completedQuest->setQuest($quest);
        $completedQuest->setChoiceMade($choiceMade);

        // Remove active quest
        $this->entityManager->remove($playerQuest);
        $this->entityManager->persist($completedQuest);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new QuestCompletedEvent($player, $quest), QuestCompletedEvent::NAME);

        return new JsonResponse([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * @param array<string, mixed> $rewards
     * @param string[]             $messages
     */
    private function applyRewards(array $rewards, Player $player, array &$messages): void
    {
        // Apply gold/gils rewards (fixtures use 'gold', support both keys)
        $gils = (int) ($rewards['gils'] ?? $rewards['gold'] ?? 0);
        if ($gils > 0) {
            $player->addGils($gils);
            $messages[] = sprintf('+%d Gils', $gils);
        }

        // Apply XP rewards — distribute evenly across all player domains
        $xp = (int) ($rewards['xp'] ?? 0);
        if ($xp > 0) {
            $domainExperiences = $player->getDomainExperiences();
            $domainCount = count($domainExperiences);
            if ($domainCount > 0) {
                $xpPerDomain = max(1, intdiv($xp, $domainCount));
                foreach ($domainExperiences as $domainExperience) {
                    $domainExperience->setTotalExperience(
                        $domainExperience->getTotalExperience() + $xpPerDomain
                    );
                }
            }
            $messages[] = sprintf('+%d XP', $xp);
        }

        // Apply item rewards
        $items = $rewards['items'] ?? [];
        $itemRepository = $this->entityManager->getRepository(Item::class);
        foreach ($items as $key => $itemData) {
            // Support two formats:
            // 1. Array with genericItemSlug + count: ['genericItemSlug' => 'beer-pint', 'count' => 1]
            // 2. Simple key-value: 'slug' => count
            if (\is_array($itemData) && isset($itemData['genericItemSlug'])) {
                $slug = $itemData['genericItemSlug'];
                $count = (int) ($itemData['count'] ?? 1);
            } elseif (\is_string($key)) {
                $slug = $key;
                $count = (int) $itemData;
            } else {
                continue;
            }

            $item = $itemRepository->findOneBy(['slug' => $slug]);
            if (!$item) {
                continue;
            }

            for ($i = 0; $i < $count; ++$i) {
                $this->inventoryHelper->addItemId($item->getId(), false);
            }
            $itemName = $item->getName();
            $messages[] = $count > 1
                ? sprintf('+%d %s', $count, $itemName)
                : sprintf('+%s', $itemName);
        }
    }
}
