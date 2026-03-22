<?php

namespace App\Controller\Game;

use App\Entity\App\Friendship;
use App\Entity\App\Player;
use App\GameEngine\Social\FriendshipManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/friends')]
class FriendController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly FriendshipManager $friendshipManager,
    ) {
    }

    #[Route('', name: 'app_game_friends', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $friends = $this->friendshipManager->getAcceptedFriends($player);
        $pendingReceived = $this->friendshipManager->getPendingReceived($player);
        $pendingSent = $this->friendshipManager->getPendingSent($player);

        $friendList = [];
        foreach ($friends as $friendship) {
            $friendPlayer = $friendship->getPlayer()->getId() === $player->getId()
                ? $friendship->getFriend()
                : $friendship->getPlayer();
            $friendList[] = [
                'friendship' => $friendship,
                'player' => $friendPlayer,
                'online' => $this->friendshipManager->isOnline($friendPlayer),
            ];
        }

        usort($friendList, function (array $a, array $b): int {
            if ($a['online'] !== $b['online']) {
                return $b['online'] <=> $a['online'];
            }

            return $a['player']->getName() <=> $b['player']->getName();
        });

        return $this->render('game/friends/index.html.twig', [
            'player' => $player,
            'friends' => $friendList,
            'pendingReceived' => $pendingReceived,
            'pendingSent' => $pendingSent,
        ]);
    }

    #[Route('/request/{id}', name: 'app_game_friends_request', methods: ['POST'])]
    public function sendRequest(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $targetPlayer = $this->entityManager->getRepository(Player::class)->find($id);
        if (!$targetPlayer) {
            $this->addFlash('error', 'Joueur introuvable.');

            return $this->redirectToRoute('app_game_friends');
        }

        try {
            $this->friendshipManager->sendRequest($player, $targetPlayer);
            $this->addFlash('success', 'Demande d\'ami envoyée à ' . $targetPlayer->getName() . '.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, '/game/player/')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_game_friends');
    }

    #[Route('/accept/{id}', name: 'app_game_friends_accept', methods: ['POST'])]
    public function accept(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);
        if (!$friendship) {
            $this->addFlash('error', 'Demande introuvable.');

            return $this->redirectToRoute('app_game_friends');
        }

        try {
            $this->friendshipManager->accept($friendship, $player);
            $this->addFlash('success', $friendship->getPlayer()->getName() . ' est maintenant votre ami.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_friends');
    }

    #[Route('/decline/{id}', name: 'app_game_friends_decline', methods: ['POST'])]
    public function decline(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);
        if (!$friendship) {
            $this->addFlash('error', 'Demande introuvable.');

            return $this->redirectToRoute('app_game_friends');
        }

        try {
            $this->friendshipManager->decline($friendship, $player);
            $this->addFlash('success', 'Demande refusée.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_friends');
    }

    #[Route('/remove/{id}', name: 'app_game_friends_remove', methods: ['POST'])]
    public function remove(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $friendship = $this->entityManager->getRepository(Friendship::class)->find($id);
        if (!$friendship) {
            $this->addFlash('error', 'Relation introuvable.');

            return $this->redirectToRoute('app_game_friends');
        }

        try {
            $this->friendshipManager->unfriend($friendship, $player);
            $this->addFlash('success', 'Ami retiré.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_friends');
    }

    #[Route('/block/{id}', name: 'app_game_friends_block', methods: ['POST'])]
    public function block(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $targetPlayer = $this->entityManager->getRepository(Player::class)->find($id);
        if (!$targetPlayer) {
            $this->addFlash('error', 'Joueur introuvable.');

            return $this->redirectToRoute('app_game_friends');
        }

        $this->friendshipManager->block($player, $targetPlayer);
        $this->addFlash('success', $targetPlayer->getName() . ' a été bloqué.');

        return $this->redirectToRoute('app_game_friends');
    }
}
