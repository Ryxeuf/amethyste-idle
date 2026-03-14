<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\Game\Quest;
use App\GameEngine\Quest\PlayerQuestHelper;
use App\GameEngine\Quest\QuestTrackingFormater;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/quests')]
class QuestController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerQuestHelper $playerQuestHelper,
        private readonly QuestTrackingFormater $questTrackingFormater,
    ) {
    }

    #[Route('', name: 'app_game_quests', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activeQuests = $this->playerQuestHelper->getCurrentQuests();
        $completedQuests = $this->playerQuestHelper->getCompletedQuests();

        // Calculate progress for each active quest
        $questProgress = [];
        foreach ($activeQuests as $playerQuest) {
            $questProgress[$playerQuest->getId()] = $this->playerQuestHelper->getPlayerQuestProgress($playerQuest);
        }

        return $this->render('game/quest/index.html.twig', [
            'activeQuests' => $activeQuests,
            'completedQuests' => $completedQuests,
            'questProgress' => $questProgress,
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

    #[Route('/complete/{id}', name: 'app_game_quest_complete', methods: ['POST'])]
    public function complete(int $id): Response
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

        // Apply rewards
        if (isset($rewards['gils'])) {
            $player->addGils((int) $rewards['gils']);
            $messages[] = sprintf('+%d Gils', $rewards['gils']);
        }

        // Create completed record
        $completedQuest = new PlayerQuestCompleted();
        $completedQuest->setPlayer($player);
        $completedQuest->setQuest($quest);

        // Remove active quest
        $this->entityManager->remove($playerQuest);
        $this->entityManager->persist($completedQuest);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'messages' => $messages,
        ]);
    }
}
