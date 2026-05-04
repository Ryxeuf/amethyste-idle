<?php

namespace App\Controller\Game;

use App\Entity\Game\Mount;
use App\GameEngine\Mount\MountActivationService;
use App\GameEngine\Mount\MountNotOwnedException;
use App\Helper\PlayerHelper;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MountController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerMountRepository $playerMountRepository,
        private readonly MountActivationService $mountActivationService,
    ) {
    }

    #[Route('/game/mounts', name: 'app_game_mounts', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $mounts = $this->entityManager
            ->getRepository(Mount::class)
            ->findBy(['enabled' => true], ['requiredLevel' => 'ASC', 'gilCost' => 'ASC']);

        $player = $this->playerHelper->getPlayer();
        $ownedMountIds = null !== $player
            ? $this->playerMountRepository->findOwnedMountIds($player)
            : [];
        $activeMount = null !== $player ? $player->getActiveMount() : null;

        return $this->render('game/mount/index.html.twig', [
            'mounts' => $mounts,
            'ownedMountIds' => $ownedMountIds,
            'activeMountId' => null !== $activeMount ? $activeMount->getId() : null,
            'obtentionLabels' => [
                Mount::OBTENTION_QUEST => 'game.mount.obtention.quest',
                Mount::OBTENTION_DROP => 'game.mount.obtention.drop',
                Mount::OBTENTION_PURCHASE => 'game.mount.obtention.purchase',
                Mount::OBTENTION_ACHIEVEMENT => 'game.mount.obtention.achievement',
            ],
        ]);
    }

    #[Route('/game/mounts/{id}/mount', name: 'app_game_mounts_mount', methods: ['POST'])]
    public function mount(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game_mounts');
        }

        if (!$this->isCsrfTokenValid('mount_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.mount.flash.csrf_invalid');

            return $this->redirectToRoute('app_game_mounts');
        }

        $mount = $this->entityManager->getRepository(Mount::class)->find($id);
        if (!$mount instanceof Mount) {
            $this->addFlash('error', 'game.mount.flash.unknown');

            return $this->redirectToRoute('app_game_mounts');
        }

        try {
            $this->mountActivationService->mount($player, $mount);
            $this->addFlash('success', 'game.mount.flash.mounted');
        } catch (MountNotOwnedException) {
            $this->addFlash('error', 'game.mount.flash.not_owned');
        } catch (\DomainException) {
            $this->addFlash('error', 'game.mount.flash.disabled');
        }

        return $this->redirectToRoute('app_game_mounts');
    }

    #[Route('/game/mounts/unmount', name: 'app_game_mounts_unmount', methods: ['POST'])]
    public function unmount(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game_mounts');
        }

        if (!$this->isCsrfTokenValid('unmount', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.mount.flash.csrf_invalid');

            return $this->redirectToRoute('app_game_mounts');
        }

        $this->mountActivationService->unmount($player);
        $this->addFlash('success', 'game.mount.flash.unmounted');

        return $this->redirectToRoute('app_game_mounts');
    }
}
