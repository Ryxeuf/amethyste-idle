<?php

namespace App\Controller\Game;

use App\Entity\App\GuildInvitation;
use App\Entity\App\Player;
use App\Enum\GuildRank;
use App\GameEngine\Social\GuildManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $guild = $this->guildManager->getPlayerGuild($player);
        $pendingInvitations = $this->guildManager->getPendingInvitations($player);

        if ($guild) {
            $membership = $this->guildManager->getMembership($guild, $player);

            return $this->render('game/guild/show.html.twig', [
                'player' => $player,
                'guild' => $guild,
                'membership' => $membership,
                'pendingInvitations' => $pendingInvitations,
            ]);
        }

        return $this->render('game/guild/index.html.twig', [
            'player' => $player,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    #[Route('/create', name: 'app_game_guild_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $name = $request->request->getString('name');
        $tag = $request->request->getString('tag');
        $description = $request->request->getString('description') ?: null;

        try {
            $this->guildManager->create($player, $name, $tag, $description);
            $this->addFlash('success', 'Guilde créée avec succès !');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_guild');
    }

    #[Route('/invite/{id}', name: 'app_game_guild_invite', methods: ['POST'])]
    public function invite(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $guild = $this->guildManager->getPlayerGuild($player);
        if (!$guild) {
            $this->addFlash('error', 'Vous n\'êtes pas dans une guilde.');

            return $this->redirectToRoute('app_game_guild');
        }

        $targetPlayer = $this->entityManager->getRepository(Player::class)->find($id);
        if (!$targetPlayer) {
            $this->addFlash('error', 'Joueur introuvable.');

            return $this->redirectToRoute('app_game_guild');
        }

        try {
            $this->guildManager->invite($guild, $player, $targetPlayer);
            $this->addFlash('success', 'Invitation envoyée à ' . $targetPlayer->getName() . '.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_guild');
    }

    #[Route('/invitation/{id}/accept', name: 'app_game_guild_accept', methods: ['POST'])]
    public function acceptInvitation(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $invitation = $this->entityManager->getRepository(GuildInvitation::class)->find($id);
        if (!$invitation || $invitation->getPlayer()->getId() !== $player->getId()) {
            $this->addFlash('error', 'Invitation introuvable.');

            return $this->redirectToRoute('app_game_guild');
        }

        try {
            $this->guildManager->acceptInvitation($invitation);
            $this->addFlash('success', 'Vous avez rejoint la guilde ' . $invitation->getGuild()->getName() . ' !');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_guild');
    }

    #[Route('/invitation/{id}/decline', name: 'app_game_guild_decline', methods: ['POST'])]
    public function declineInvitation(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $invitation = $this->entityManager->getRepository(GuildInvitation::class)->find($id);
        if (!$invitation || $invitation->getPlayer()->getId() !== $player->getId()) {
            $this->addFlash('error', 'Invitation introuvable.');

            return $this->redirectToRoute('app_game_guild');
        }

        $this->guildManager->declineInvitation($invitation);
        $this->addFlash('success', 'Invitation refusée.');

        return $this->redirectToRoute('app_game_guild');
    }

    #[Route('/leave', name: 'app_game_guild_leave', methods: ['POST'])]
    public function leave(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $guild = $this->guildManager->getPlayerGuild($player);
        if (!$guild) {
            $this->addFlash('error', 'Vous n\'êtes pas dans une guilde.');

            return $this->redirectToRoute('app_game_guild');
        }

        try {
            $this->guildManager->leave($guild, $player);
            $this->addFlash('success', 'Vous avez quitté la guilde.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_guild');
    }

    #[Route('/kick/{id}', name: 'app_game_guild_kick', methods: ['POST'])]
    public function kick(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $guild = $this->guildManager->getPlayerGuild($player);
        if (!$guild) {
            $this->addFlash('error', 'Vous n\'êtes pas dans une guilde.');

            return $this->redirectToRoute('app_game_guild');
        }

        $targetPlayer = $this->entityManager->getRepository(Player::class)->find($id);
        if (!$targetPlayer) {
            $this->addFlash('error', 'Joueur introuvable.');

            return $this->redirectToRoute('app_game_guild');
        }

        try {
            $this->guildManager->kick($guild, $player, $targetPlayer);
            $this->addFlash('success', $targetPlayer->getName() . ' a été exclu de la guilde.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_guild');
    }

    #[Route('/promote/{id}', name: 'app_game_guild_promote', methods: ['POST'])]
    public function promote(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $guild = $this->guildManager->getPlayerGuild($player);
        if (!$guild) {
            $this->addFlash('error', 'Vous n\'êtes pas dans une guilde.');

            return $this->redirectToRoute('app_game_guild');
        }

        $targetPlayer = $this->entityManager->getRepository(Player::class)->find($id);
        if (!$targetPlayer) {
            $this->addFlash('error', 'Joueur introuvable.');

            return $this->redirectToRoute('app_game_guild');
        }

        $rankValue = $request->request->getString('rank');
        $rank = GuildRank::tryFrom($rankValue);
        if (!$rank) {
            $this->addFlash('error', 'Rang invalide.');

            return $this->redirectToRoute('app_game_guild');
        }

        try {
            $this->guildManager->promote($guild, $player, $targetPlayer, $rank);
            $this->addFlash('success', $targetPlayer->getName() . ' est maintenant ' . $rank->label() . '.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_guild');
    }

    #[Route('/transfer/{id}', name: 'app_game_guild_transfer', methods: ['POST'])]
    public function transfer(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $guild = $this->guildManager->getPlayerGuild($player);
        if (!$guild) {
            $this->addFlash('error', 'Vous n\'êtes pas dans une guilde.');

            return $this->redirectToRoute('app_game_guild');
        }

        $targetPlayer = $this->entityManager->getRepository(Player::class)->find($id);
        if (!$targetPlayer) {
            $this->addFlash('error', 'Joueur introuvable.');

            return $this->redirectToRoute('app_game_guild');
        }

        try {
            $this->guildManager->transferLeadership($guild, $player, $targetPlayer);
            $this->addFlash('success', $targetPlayer->getName() . ' est le nouveau maître de guilde.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_guild');
    }

    #[Route('/disband', name: 'app_game_guild_disband', methods: ['POST'])]
    public function disband(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $guild = $this->guildManager->getPlayerGuild($player);
        if (!$guild) {
            $this->addFlash('error', 'Vous n\'êtes pas dans une guilde.');

            return $this->redirectToRoute('app_game_guild');
        }

        try {
            $this->guildManager->disband($guild, $player);
            $this->addFlash('success', 'La guilde a été dissoute.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_guild');
    }
}
