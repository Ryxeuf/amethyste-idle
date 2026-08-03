<?php

namespace App\Controller\Game;

use App\Entity\Game\Dungeon;
use App\GameEngine\Dungeon\GroupDungeonCombatService;
use App\GameEngine\Dungeon\GroupDungeonException;
use App\GameEngine\Dungeon\GroupDungeonService;
use App\Helper\PlayerHelper;
use App\Security\Attribute\RequiresVerifiedEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Donjon de groupe semi-synchrone (pivot PBBG, ZON-19) — formation.
 *
 * Points d'entree de formation/abandon d'un run de groupe. La boucle de combat
 * tour par tour partagee (jouable) arrive dans le sous-jalon suivant.
 */
class GroupDungeonController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly GroupDungeonService $groupDungeonService,
        private readonly GroupDungeonCombatService $combatService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/game/zone/dungeon/act', name: 'app_game_zone_dungeon_act', methods: ['POST'])]
    public function act(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('group_dungeon_act', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        $run = $this->groupDungeonService->getActiveRunForPlayer($player);
        if (null !== $run) {
            try {
                $this->combatService->act($player, $run, $request->request->getString('spell') ?: null);
            } catch (GroupDungeonException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->redirectToRoute('app_game_zone');
    }

    #[RequiresVerifiedEmail(channel: 'dungeon')]
    #[Route('/game/zone/dungeon/launch/{id}', name: 'app_game_zone_dungeon_launch', methods: ['POST'])]
    public function launch(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('group_dungeon_launch_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        $dungeon = $this->entityManager->getRepository(Dungeon::class)->find($id);
        if (null === $dungeon) {
            $this->addFlash('error', 'game.zone.dungeon.error.unknown');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $this->groupDungeonService->launch($player, $dungeon);
            $this->addFlash('success', 'game.zone.dungeon.flash.launched');
        } catch (GroupDungeonException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_game_zone');
    }

    #[Route('/game/zone/dungeon/abandon', name: 'app_game_zone_dungeon_abandon', methods: ['POST'])]
    public function abandon(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('group_dungeon_abandon', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        $run = $this->groupDungeonService->getActiveRunForPlayer($player);
        if (null !== $run) {
            try {
                $this->groupDungeonService->abandon($player, $run);
                $this->addFlash('success', 'game.zone.dungeon.flash.abandoned');
            } catch (GroupDungeonException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->redirectToRoute('app_game_zone');
    }
}
