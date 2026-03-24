<?php

namespace App\Controller\Game;

use App\Entity\App\PartyInvitation;
use App\Entity\App\PartyMember;
use App\Entity\App\Player;
use App\GameEngine\Party\PartyManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/party')]
class PartyController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PartyManager $partyManager,
    ) {
    }

    #[Route('', name: 'app_game_party', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $membership = $this->partyManager->getPlayerMembership($player);
        $party = $membership?->getParty();
        $invitations = $this->partyManager->getPendingInvitations($player);

        return $this->render('game/party/index.html.twig', [
            'party' => $party,
            'membership' => $membership,
            'invitations' => $invitations,
            'player' => $player,
            'maxSize' => PartyManager::MAX_PARTY_SIZE,
        ]);
    }

    #[Route('/create', name: 'app_game_party_create', methods: ['POST'])]
    public function create(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        try {
            $this->partyManager->createParty($player);

            return new JsonResponse([
                'success' => true,
                'message' => 'Groupe créé !',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/invite/{playerId}', name: 'app_game_party_invite', methods: ['POST'])]
    public function invite(int $playerId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(Player::class)->find($playerId);
        if (!$target) {
            return new JsonResponse(['error' => 'Joueur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->partyManager->invitePlayer($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Invitation envoyée à %s.', $target->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/invitation/{id}/accept', name: 'app_game_party_accept', methods: ['POST'])]
    public function acceptInvitation(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $invitation = $this->entityManager->getRepository(PartyInvitation::class)->find($id);
        if (!$invitation) {
            return new JsonResponse(['error' => 'Invitation introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->partyManager->acceptInvitation($player, $invitation);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Vous avez rejoint le groupe de %s !', $invitation->getParty()->getLeader()->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/invitation/{id}/decline', name: 'app_game_party_decline', methods: ['POST'])]
    public function declineInvitation(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $invitation = $this->entityManager->getRepository(PartyInvitation::class)->find($id);
        if (!$invitation) {
            return new JsonResponse(['error' => 'Invitation introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->partyManager->declineInvitation($player, $invitation);

            return new JsonResponse([
                'success' => true,
                'message' => 'Invitation déclinée.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/leave', name: 'app_game_party_leave', methods: ['POST'])]
    public function leave(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        try {
            $this->partyManager->leaveParty($player);

            return new JsonResponse([
                'success' => true,
                'message' => 'Vous avez quitté le groupe.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/kick/{memberId}', name: 'app_game_party_kick', methods: ['POST'])]
    public function kick(int $memberId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(PartyMember::class)->find($memberId);
        if (!$target) {
            return new JsonResponse(['error' => 'Membre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->partyManager->kickMember($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s a été expulsé du groupe.', $target->getPlayer()->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/transfer/{playerId}', name: 'app_game_party_transfer', methods: ['POST'])]
    public function transfer(int $playerId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(Player::class)->find($playerId);
        if (!$target) {
            return new JsonResponse(['error' => 'Joueur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->partyManager->transferLeader($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s est maintenant chef du groupe.', $target->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/disband', name: 'app_game_party_disband', methods: ['POST'])]
    public function disband(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        try {
            $this->partyManager->disbandParty($player);

            return new JsonResponse([
                'success' => true,
                'message' => 'Le groupe a été dissous.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
