<?php

namespace App\Controller\Game;

use App\Entity\Game\Dungeon;
use App\Enum\DungeonDifficulty;
use App\GameEngine\Dungeon\DungeonManager;
use App\Helper\PlayerHelper;
use App\Repository\DungeonRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/dungeon')]
class DungeonController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly DungeonManager $dungeonManager,
        private readonly DungeonRunRepository $dungeonRunRepository,
    ) {
    }

    #[Route('', name: 'app_game_dungeon_list', methods: ['GET'])]
    public function list(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $dungeons = $this->entityManager->getRepository(Dungeon::class)->findAll();
        $activeRun = $this->dungeonRunRepository->findActiveRun($player);
        $history = $this->dungeonRunRepository->findPlayerHistory($player, 10);

        return $this->render('game/dungeon/list.html.twig', [
            'player' => $player,
            'dungeons' => $dungeons,
            'activeRun' => $activeRun,
            'history' => $history,
        ]);
    }

    #[Route('/{slug}', name: 'app_game_dungeon_show', methods: ['GET'])]
    public function show(string $slug): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $dungeon = $this->entityManager->getRepository(Dungeon::class)->findOneBy(['slug' => $slug]);
        if (!$dungeon) {
            throw $this->createNotFoundException('Donjon introuvable');
        }

        $meetsLevel = $this->dungeonManager->meetsLevelRequirement($player, $dungeon);
        $activeRun = $this->dungeonRunRepository->findActiveRun($player);
        $missingItems = $this->dungeonManager->getMissingEntryItems($player, $dungeon);

        $cooldowns = [];
        foreach (DungeonDifficulty::cases() as $diff) {
            $cooldowns[$diff->value] = $this->dungeonManager->getCooldownRemaining($player, $dungeon, $diff);
        }

        return $this->render('game/dungeon/show.html.twig', [
            'player' => $player,
            'dungeon' => $dungeon,
            'meetsLevel' => $meetsLevel,
            'activeRun' => $activeRun,
            'cooldowns' => $cooldowns,
            'difficulties' => DungeonDifficulty::cases(),
            'missingItems' => $missingItems,
        ]);
    }

    #[Route('/{slug}/enter', name: 'app_game_dungeon_enter', methods: ['POST'])]
    public function enter(string $slug, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $dungeon = $this->entityManager->getRepository(Dungeon::class)->findOneBy(['slug' => $slug]);
        if (!$dungeon) {
            throw $this->createNotFoundException('Donjon introuvable');
        }

        $difficultyValue = $request->request->getString('difficulty', 'normal');
        $difficulty = DungeonDifficulty::tryFrom($difficultyValue) ?? DungeonDifficulty::Normal;

        $result = $this->dungeonManager->enter($player, $dungeon, $difficulty);

        if ($result['error'] !== null) {
            $this->addFlash('error', $result['error']);

            return $this->redirectToRoute('app_game_dungeon_show', ['slug' => $slug]);
        }

        $this->addFlash('success', sprintf('Vous entrez dans %s en difficulte %s !', $dungeon->getName(), $difficulty->label()));

        return $this->redirectToRoute('app_game_map');
    }
}
