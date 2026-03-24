<?php

namespace App\Controller\Game;

use App\Entity\App\GuildInvitation;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use App\GameEngine\Guild\GuildManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/guild')]
class GuildController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly GuildManager $guildManager,
    ) {
    }

    #[Route('', name: 'app_game_guild', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $membership = $this->guildManager->getPlayerMembership($player);
        $guild = $membership?->getGuild();
        $invitations = $this->guildManager->getPendingInvitations($player);

        return $this->render('game/guild/index.html.twig', [
            'guild' => $guild,
            'membership' => $membership,
            'invitations' => $invitations,
            'creationCost' => GuildManager::CREATION_COST,
            'player' => $player,
        ]);
    }

    #[Route('/create', name: 'app_game_guild_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $body = json_decode($request->getContent(), true) ?? [];
        $name = trim($body['name'] ?? '');
        $tag = trim($body['tag'] ?? '');
        $description = trim($body['description'] ?? '') ?: null;

        if (!$name || !$tag) {
            return new JsonResponse(['error' => 'Nom et tag requis.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $guild = $this->guildManager->createGuild($player, $name, $tag, $description);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Guilde "%s" [%s] créée !', $guild->getName(), $guild->getTag()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/invite/{playerId}', name: 'app_game_guild_invite', methods: ['POST'])]
    public function invite(int $playerId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(Player::class)->find($playerId);
        if (!$target) {
            return new JsonResponse(['error' => 'Joueur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->invitePlayer($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Invitation envoyée à %s.', $target->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/invitation/{id}/accept', name: 'app_game_guild_accept', methods: ['POST'])]
    public function acceptInvitation(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $invitation = $this->entityManager->getRepository(GuildInvitation::class)->find($id);
        if (!$invitation) {
            return new JsonResponse(['error' => 'Invitation introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->acceptInvitation($player, $invitation);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Vous avez rejoint la guilde "%s" !', $invitation->getGuild()->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/invitation/{id}/decline', name: 'app_game_guild_decline', methods: ['POST'])]
    public function declineInvitation(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $invitation = $this->entityManager->getRepository(GuildInvitation::class)->find($id);
        if (!$invitation) {
            return new JsonResponse(['error' => 'Invitation introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->declineInvitation($player, $invitation);

            return new JsonResponse([
                'success' => true,
                'message' => 'Invitation déclinée.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/leave', name: 'app_game_guild_leave', methods: ['POST'])]
    public function leave(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        try {
            $this->guildManager->leaveGuild($player);

            return new JsonResponse([
                'success' => true,
                'message' => 'Vous avez quitté la guilde.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/kick/{memberId}', name: 'app_game_guild_kick', methods: ['POST'])]
    public function kick(int $memberId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(GuildMember::class)->find($memberId);
        if (!$target) {
            return new JsonResponse(['error' => 'Membre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->kickMember($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s a été expulsé de la guilde.', $target->getPlayer()->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/promote/{memberId}', name: 'app_game_guild_promote', methods: ['POST'])]
    public function promote(int $memberId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(GuildMember::class)->find($memberId);
        if (!$target) {
            return new JsonResponse(['error' => 'Membre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->promote($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s a été promu %s.', $target->getPlayer()->getName(), $target->getRank()->label()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/demote/{memberId}', name: 'app_game_guild_demote', methods: ['POST'])]
    public function demote(int $memberId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(GuildMember::class)->find($memberId);
        if (!$target) {
            return new JsonResponse(['error' => 'Membre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->demote($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s a été rétrogradé %s.', $target->getPlayer()->getName(), $target->getRank()->label()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
