<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildInvitation;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use App\Enum\GuildRank;
use Doctrine\ORM\EntityManagerInterface;

class GuildManager
{
    public const CREATION_COST = 5000;
    private const TAG_MIN_LENGTH = 3;
    private const TAG_MAX_LENGTH = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createGuild(Player $player, string $name, string $tag, ?string $description = null, ?string $color = null): Guild
    {
        // Check player is not already in a guild
        $existingMembership = $this->getPlayerMembership($player);
        if ($existingMembership) {
            throw new \InvalidArgumentException('Vous êtes déjà dans une guilde.');
        }

        // Validate tag
        $tag = mb_strtoupper(trim($tag));
        if (mb_strlen($tag) < self::TAG_MIN_LENGTH || mb_strlen($tag) > self::TAG_MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Le tag doit contenir entre %d et %d caractères.', self::TAG_MIN_LENGTH, self::TAG_MAX_LENGTH));
        }
        if (!preg_match('/^[A-Z0-9]+$/', $tag)) {
            throw new \InvalidArgumentException('Le tag ne peut contenir que des lettres et des chiffres.');
        }

        // Validate name
        $name = trim($name);
        if (mb_strlen($name) < 3 || mb_strlen($name) > 50) {
            throw new \InvalidArgumentException('Le nom de la guilde doit contenir entre 3 et 50 caractères.');
        }

        // Check uniqueness
        if ($this->entityManager->getRepository(Guild::class)->findOneBy(['name' => $name])) {
            throw new \InvalidArgumentException('Ce nom de guilde est déjà pris.');
        }
        if ($this->entityManager->getRepository(Guild::class)->findOneBy(['tag' => $tag])) {
            throw new \InvalidArgumentException('Ce tag de guilde est déjà pris.');
        }

        // Check cost
        if ($player->getGils() < self::CREATION_COST) {
            throw new \InvalidArgumentException(sprintf('Vous avez besoin de %d Gils pour créer une guilde.', self::CREATION_COST));
        }

        $player->removeGils(self::CREATION_COST);

        $guild = new Guild();
        $guild->setName($name);
        $guild->setTag($tag);
        $guild->setDescription($description);
        if ($color !== null) {
            $guild->setColor($color);
        }
        $guild->setLeader($player);
        $guild->setCreatedAt(new \DateTime());
        $guild->setUpdatedAt(new \DateTime());

        $member = new GuildMember();
        $member->setGuild($guild);
        $member->setPlayer($player);
        $member->setRank(GuildRank::Leader);
        $member->setCreatedAt(new \DateTime());
        $member->setUpdatedAt(new \DateTime());

        $guild->addMember($member);

        $this->entityManager->persist($guild);
        $this->entityManager->persist($member);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return $guild;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function invitePlayer(Player $inviter, Player $target): GuildInvitation
    {
        $membership = $this->getPlayerMembership($inviter);
        if (!$membership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans une guilde.');
        }

        if (!$membership->getRank()->canInvite()) {
            throw new \InvalidArgumentException('Vous n\'avez pas le rang requis pour inviter.');
        }

        if ($this->getPlayerMembership($target)) {
            throw new \InvalidArgumentException('Ce joueur est déjà dans une guilde.');
        }

        $guild = $membership->getGuild();

        // Check if already invited
        $existing = $this->entityManager->getRepository(GuildInvitation::class)->findOneBy([
            'guild' => $guild,
            'player' => $target,
        ]);
        if ($existing) {
            throw new \InvalidArgumentException('Ce joueur a déjà une invitation en attente.');
        }

        $invitation = new GuildInvitation();
        $invitation->setGuild($guild);
        $invitation->setPlayer($target);
        $invitation->setInvitedBy($inviter);
        $invitation->setCreatedAt(new \DateTime());
        $invitation->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        return $invitation;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function acceptInvitation(Player $player, GuildInvitation $invitation): GuildMember
    {
        if ($invitation->getPlayer()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cette invitation ne vous est pas destinée.');
        }

        if ($this->getPlayerMembership($player)) {
            throw new \InvalidArgumentException('Vous êtes déjà dans une guilde.');
        }

        $member = new GuildMember();
        $member->setGuild($invitation->getGuild());
        $member->setPlayer($player);
        $member->setRank(GuildRank::Recruit);
        $member->setCreatedAt(new \DateTime());
        $member->setUpdatedAt(new \DateTime());

        $invitation->getGuild()->addMember($member);
        $this->entityManager->persist($member);
        $this->entityManager->remove($invitation);
        $this->entityManager->flush();

        return $member;
    }

    public function declineInvitation(Player $player, GuildInvitation $invitation): void
    {
        if ($invitation->getPlayer()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cette invitation ne vous est pas destinée.');
        }

        $this->entityManager->remove($invitation);
        $this->entityManager->flush();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function leaveGuild(Player $player): void
    {
        $membership = $this->getPlayerMembership($player);
        if (!$membership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans une guilde.');
        }

        if ($membership->getRank() === GuildRank::Leader) {
            throw new \InvalidArgumentException('Le chef de guilde ne peut pas quitter. Transférez le leadership ou dissolvez la guilde.');
        }

        $guild = $membership->getGuild();
        $guild->removeMember($membership);
        $player->setPrestigeTitle(null);
        $this->entityManager->remove($membership);
        $this->entityManager->flush();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function kickMember(Player $kicker, GuildMember $target): void
    {
        $kickerMembership = $this->getPlayerMembership($kicker);
        if (!$kickerMembership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans une guilde.');
        }

        if ($kickerMembership->getGuild()->getId() !== $target->getGuild()->getId()) {
            throw new \InvalidArgumentException('Ce joueur n\'est pas dans votre guilde.');
        }

        if (!$kickerMembership->getRank()->canKick()) {
            throw new \InvalidArgumentException('Vous n\'avez pas le rang requis pour expulser.');
        }

        if (!$kickerMembership->getRank()->isHigherThan($target->getRank())) {
            throw new \InvalidArgumentException('Vous ne pouvez pas expulser un membre de rang égal ou supérieur.');
        }

        $guild = $target->getGuild();
        $guild->removeMember($target);
        $target->getPlayer()->setPrestigeTitle(null);
        $this->entityManager->remove($target);
        $this->entityManager->flush();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function promote(Player $promoter, GuildMember $target): void
    {
        $promoterMembership = $this->getPlayerMembership($promoter);
        if (!$promoterMembership || !$promoterMembership->getRank()->canPromote()) {
            throw new \InvalidArgumentException('Seul le chef de guilde peut promouvoir.');
        }

        if ($promoterMembership->getGuild()->getId() !== $target->getGuild()->getId()) {
            throw new \InvalidArgumentException('Ce joueur n\'est pas dans votre guilde.');
        }

        $newRank = match ($target->getRank()) {
            GuildRank::Recruit => GuildRank::Member,
            GuildRank::Member => GuildRank::Officer,
            default => throw new \InvalidArgumentException('Ce membre ne peut pas être promu davantage.'),
        };

        $target->setRank($newRank);
        $this->entityManager->flush();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function demote(Player $demoter, GuildMember $target): void
    {
        $demoterMembership = $this->getPlayerMembership($demoter);
        if (!$demoterMembership || !$demoterMembership->getRank()->canPromote()) {
            throw new \InvalidArgumentException('Seul le chef de guilde peut rétrograder.');
        }

        if ($demoterMembership->getGuild()->getId() !== $target->getGuild()->getId()) {
            throw new \InvalidArgumentException('Ce joueur n\'est pas dans votre guilde.');
        }

        $newRank = match ($target->getRank()) {
            GuildRank::Officer => GuildRank::Member,
            GuildRank::Member => GuildRank::Recruit,
            default => throw new \InvalidArgumentException('Ce membre ne peut pas être rétrogradé davantage.'),
        };

        $target->setRank($newRank);
        $this->entityManager->flush();
    }

    public function getPlayerMembership(Player $player): ?GuildMember
    {
        return $this->entityManager->getRepository(GuildMember::class)->findOneBy([
            'player' => $player,
        ]);
    }

    public function getPlayerGuild(Player $player): ?Guild
    {
        $membership = $this->getPlayerMembership($player);

        return $membership?->getGuild();
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
}
