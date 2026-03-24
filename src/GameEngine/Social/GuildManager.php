<?php

namespace App\GameEngine\Social;

use App\Entity\App\Guild;
use App\Entity\App\GuildInvitation;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use App\Enum\GuildRank;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class GuildManager
{
    public const CREATION_COST = 5000;
    public const MAX_MEMBERS = 50;
    public const TAG_MIN_LENGTH = 2;
    public const TAG_MAX_LENGTH = 5;
    public const NAME_MIN_LENGTH = 3;
    public const NAME_MAX_LENGTH = 50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function create(Player $leader, string $name, string $tag, ?string $description = null): Guild
    {
        $name = trim($name);
        $tag = trim(strtoupper($tag));

        $this->validateName($name);
        $this->validateTag($tag);

        if ($this->getPlayerGuild($leader)) {
            throw new \InvalidArgumentException('Vous êtes déjà membre d\'une guilde.');
        }

        if ($leader->getGils() < self::CREATION_COST) {
            throw new \InvalidArgumentException(sprintf('Il vous faut %d gils pour créer une guilde.', self::CREATION_COST));
        }

        $guild = new Guild();
        $guild->setName($name);
        $guild->setTag($tag);
        $guild->setDescription($description);
        $guild->setLeader($leader);

        $member = new GuildMember();
        $member->setGuild($guild);
        $member->setPlayer($leader);
        $member->setRank(GuildRank::Master);

        $leader->removeGils(self::CREATION_COST);

        $this->entityManager->persist($guild);
        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $guild;
    }

    public function invite(Guild $guild, Player $inviter, Player $target): GuildInvitation
    {
        $inviterMember = $this->getMembership($guild, $inviter);
        if (!$inviterMember || !$inviterMember->getRank()->canInvite()) {
            throw new \InvalidArgumentException('Vous n\'avez pas les droits pour inviter des joueurs.');
        }

        if ($this->getPlayerGuild($target)) {
            throw new \InvalidArgumentException('Ce joueur est déjà dans une guilde.');
        }

        $existingInvite = $this->entityManager->getRepository(GuildInvitation::class)->findOneBy([
            'guild' => $guild,
            'player' => $target,
        ]);
        if ($existingInvite) {
            throw new \InvalidArgumentException('Ce joueur a déjà une invitation en attente.');
        }

        if ($guild->getMembers()->count() >= self::MAX_MEMBERS) {
            throw new \InvalidArgumentException('La guilde a atteint le nombre maximum de membres.');
        }

        $invitation = new GuildInvitation();
        $invitation->setGuild($guild);
        $invitation->setPlayer($target);
        $invitation->setInvitedBy($inviter);

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        $this->publishNotification($target, 'guild_invite', [
            'guildId' => $guild->getId(),
            'guildName' => $guild->getName(),
            'inviterName' => $inviter->getName(),
        ]);

        return $invitation;
    }

    public function acceptInvitation(GuildInvitation $invitation): GuildMember
    {
        $player = $invitation->getPlayer();

        if ($this->getPlayerGuild($player)) {
            $this->entityManager->remove($invitation);
            $this->entityManager->flush();

            throw new \InvalidArgumentException('Vous êtes déjà dans une guilde.');
        }

        $guild = $invitation->getGuild();

        if ($guild->getMembers()->count() >= self::MAX_MEMBERS) {
            throw new \InvalidArgumentException('La guilde a atteint le nombre maximum de membres.');
        }

        $member = new GuildMember();
        $member->setGuild($guild);
        $member->setPlayer($player);
        $member->setRank(GuildRank::Recruit);

        $this->entityManager->persist($member);
        $this->entityManager->remove($invitation);
        $this->entityManager->flush();

        return $member;
    }

    public function declineInvitation(GuildInvitation $invitation): void
    {
        $this->entityManager->remove($invitation);
        $this->entityManager->flush();
    }

    public function leave(Guild $guild, Player $player): void
    {
        if ($guild->getLeader()->getId() === $player->getId()) {
            throw new \InvalidArgumentException('Le maître de guilde ne peut pas quitter. Transférez le leadership ou dissolvez la guilde.');
        }

        $membership = $this->getMembership($guild, $player);
        if (!$membership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas membre de cette guilde.');
        }

        $this->entityManager->remove($membership);
        $this->entityManager->flush();
    }

    public function kick(Guild $guild, Player $kicker, Player $target): void
    {
        $kickerMember = $this->getMembership($guild, $kicker);
        if (!$kickerMember || !$kickerMember->getRank()->canKick()) {
            throw new \InvalidArgumentException('Vous n\'avez pas les droits pour exclure des joueurs.');
        }

        $targetMember = $this->getMembership($guild, $target);
        if (!$targetMember) {
            throw new \InvalidArgumentException('Ce joueur n\'est pas membre de la guilde.');
        }

        if ($target->getId() === $guild->getLeader()->getId()) {
            throw new \InvalidArgumentException('Impossible d\'exclure le maître de guilde.');
        }

        if ($targetMember->getRank() === GuildRank::Officer && $kickerMember->getRank() !== GuildRank::Master) {
            throw new \InvalidArgumentException('Seul le maître de guilde peut exclure un officier.');
        }

        $this->entityManager->remove($targetMember);
        $this->entityManager->flush();

        $this->publishNotification($target, 'guild_kick', [
            'guildName' => $guild->getName(),
        ]);
    }

    public function promote(Guild $guild, Player $promoter, Player $target, GuildRank $newRank): void
    {
        $promoterMember = $this->getMembership($guild, $promoter);
        if (!$promoterMember || !$promoterMember->getRank()->canPromote()) {
            throw new \InvalidArgumentException('Seul le maître de guilde peut modifier les rangs.');
        }

        $targetMember = $this->getMembership($guild, $target);
        if (!$targetMember) {
            throw new \InvalidArgumentException('Ce joueur n\'est pas membre de la guilde.');
        }

        if ($newRank === GuildRank::Master) {
            throw new \InvalidArgumentException('Utilisez le transfert de leadership pour nommer un nouveau maître.');
        }

        $targetMember->setRank($newRank);
        $this->entityManager->flush();
    }

    public function transferLeadership(Guild $guild, Player $currentLeader, Player $newLeader): void
    {
        if ($guild->getLeader()->getId() !== $currentLeader->getId()) {
            throw new \InvalidArgumentException('Seul le maître de guilde peut transférer le leadership.');
        }

        $newLeaderMember = $this->getMembership($guild, $newLeader);
        if (!$newLeaderMember) {
            throw new \InvalidArgumentException('Ce joueur n\'est pas membre de la guilde.');
        }

        $currentLeaderMember = $this->getMembership($guild, $currentLeader);
        if ($currentLeaderMember) {
            $currentLeaderMember->setRank(GuildRank::Officer);
        }

        $newLeaderMember->setRank(GuildRank::Master);
        $guild->setLeader($newLeader);

        $this->entityManager->flush();
    }

    public function disband(Guild $guild, Player $player): void
    {
        if ($guild->getLeader()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Seul le maître de guilde peut dissoudre la guilde.');
        }

        // Remove all invitations
        $invitations = $this->entityManager->getRepository(GuildInvitation::class)->findBy(['guild' => $guild]);
        foreach ($invitations as $invitation) {
            $this->entityManager->remove($invitation);
        }

        $this->entityManager->remove($guild);
        $this->entityManager->flush();
    }

    public function getPlayerGuild(Player $player): ?Guild
    {
        $membership = $this->entityManager->getRepository(GuildMember::class)->findOneBy([
            'player' => $player,
        ]);

        return $membership?->getGuild();
    }

    public function getMembership(Guild $guild, Player $player): ?GuildMember
    {
        return $this->entityManager->getRepository(GuildMember::class)->findOneBy([
            'guild' => $guild,
            'player' => $player,
        ]);
    }

    /**
     * @return GuildInvitation[]
     */
    public function getPendingInvitations(Player $player): array
    {
        return $this->entityManager->getRepository(GuildInvitation::class)->findBy([
            'player' => $player,
        ]);
    }

    private function validateName(string $name): void
    {
        if (mb_strlen($name) < self::NAME_MIN_LENGTH || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Le nom doit faire entre %d et %d caractères.', self::NAME_MIN_LENGTH, self::NAME_MAX_LENGTH));
        }

        $existing = $this->entityManager->getRepository(Guild::class)->findOneBy(['name' => $name]);
        if ($existing) {
            throw new \InvalidArgumentException('Ce nom de guilde est déjà pris.');
        }
    }

    private function validateTag(string $tag): void
    {
        if (mb_strlen($tag) < self::TAG_MIN_LENGTH || mb_strlen($tag) > self::TAG_MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Le tag doit faire entre %d et %d caractères.', self::TAG_MIN_LENGTH, self::TAG_MAX_LENGTH));
        }

        if (!preg_match('/^[A-Z0-9]+$/', $tag)) {
            throw new \InvalidArgumentException('Le tag ne peut contenir que des lettres majuscules et des chiffres.');
        }

        $existing = $this->entityManager->getRepository(Guild::class)->findOneBy(['tag' => $tag]);
        if ($existing) {
            throw new \InvalidArgumentException('Ce tag de guilde est déjà pris.');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function publishNotification(Player $recipient, string $type, array $data): void
    {
        try {
            $update = new Update(
                'chat/private/' . $recipient->getId(),
                json_encode([
                    'topic' => 'guild/notification',
                    'type' => $type,
                    'data' => $data,
                ], JSON_THROW_ON_ERROR)
            );
            $this->hub->publish($update);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to publish guild notification: ' . $e->getMessage());
        }
    }
}
