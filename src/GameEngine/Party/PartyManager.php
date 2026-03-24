<?php

namespace App\GameEngine\Party;

use App\Entity\App\Party;
use App\Entity\App\PartyInvitation;
use App\Entity\App\PartyMember;
use App\Entity\App\Player;
use Doctrine\ORM\EntityManagerInterface;

class PartyManager
{
    public const MAX_PARTY_SIZE = 4;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createParty(Player $player): Party
    {
        if ($this->getPlayerMembership($player)) {
            throw new \InvalidArgumentException('Vous êtes déjà dans un groupe.');
        }

        $party = new Party();
        $party->setLeader($player);
        $party->setMaxSize(self::MAX_PARTY_SIZE);
        $party->setCreatedAt(new \DateTime());
        $party->setUpdatedAt(new \DateTime());

        $member = new PartyMember();
        $member->setParty($party);
        $member->setPlayer($player);
        $member->setCreatedAt(new \DateTime());
        $member->setUpdatedAt(new \DateTime());

        $party->addMember($member);

        $this->entityManager->persist($party);
        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $party;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function invitePlayer(Player $inviter, Player $target): PartyInvitation
    {
        $membership = $this->getPlayerMembership($inviter);
        if (!$membership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans un groupe.');
        }

        $party = $membership->getParty();

        if ($party->getLeader()->getId() !== $inviter->getId()) {
            throw new \InvalidArgumentException('Seul le chef de groupe peut inviter.');
        }

        if ($party->isFull()) {
            throw new \InvalidArgumentException('Le groupe est complet.');
        }

        if ($this->getPlayerMembership($target)) {
            throw new \InvalidArgumentException('Ce joueur est déjà dans un groupe.');
        }

        $existing = $this->entityManager->getRepository(PartyInvitation::class)->findOneBy([
            'party' => $party,
            'player' => $target,
        ]);
        if ($existing) {
            throw new \InvalidArgumentException('Ce joueur a déjà une invitation en attente.');
        }

        $invitation = new PartyInvitation();
        $invitation->setParty($party);
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
    public function acceptInvitation(Player $player, PartyInvitation $invitation): PartyMember
    {
        if ($invitation->getPlayer()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cette invitation ne vous est pas destinée.');
        }

        if ($this->getPlayerMembership($player)) {
            throw new \InvalidArgumentException('Vous êtes déjà dans un groupe.');
        }

        $party = $invitation->getParty();
        if ($party->isFull()) {
            throw new \InvalidArgumentException('Le groupe est complet.');
        }

        $member = new PartyMember();
        $member->setParty($party);
        $member->setPlayer($player);
        $member->setCreatedAt(new \DateTime());
        $member->setUpdatedAt(new \DateTime());

        $party->addMember($member);
        $this->entityManager->persist($member);
        $this->entityManager->remove($invitation);
        $this->entityManager->flush();

        return $member;
    }

    public function declineInvitation(Player $player, PartyInvitation $invitation): void
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
    public function leaveParty(Player $player): void
    {
        $membership = $this->getPlayerMembership($player);
        if (!$membership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans un groupe.');
        }

        $party = $membership->getParty();

        $party->removeMember($membership);
        $this->entityManager->remove($membership);

        // If leader leaves, transfer leadership or disband
        if ($party->getLeader()->getId() === $player->getId()) {
            $this->handleLeaderLeave($party);
        } else {
            $this->entityManager->flush();
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function kickMember(Player $kicker, PartyMember $target): void
    {
        $kickerMembership = $this->getPlayerMembership($kicker);
        if (!$kickerMembership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans un groupe.');
        }

        $party = $kickerMembership->getParty();
        if ($party->getLeader()->getId() !== $kicker->getId()) {
            throw new \InvalidArgumentException('Seul le chef de groupe peut expulser.');
        }

        if ($target->getParty()->getId() !== $party->getId()) {
            throw new \InvalidArgumentException('Ce joueur n\'est pas dans votre groupe.');
        }

        if ($target->getPlayer()->getId() === $kicker->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez pas vous expulser vous-même.');
        }

        $party->removeMember($target);
        $this->entityManager->remove($target);
        $this->entityManager->flush();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function transferLeader(Player $currentLeader, Player $newLeader): void
    {
        $membership = $this->getPlayerMembership($currentLeader);
        if (!$membership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans un groupe.');
        }

        $party = $membership->getParty();
        if ($party->getLeader()->getId() !== $currentLeader->getId()) {
            throw new \InvalidArgumentException('Seul le chef de groupe peut transférer le leadership.');
        }

        $newLeaderMembership = $this->getPlayerMembership($newLeader);
        if (!$newLeaderMembership || $newLeaderMembership->getParty()->getId() !== $party->getId()) {
            throw new \InvalidArgumentException('Ce joueur n\'est pas dans votre groupe.');
        }

        $party->setLeader($newLeader);
        $this->entityManager->flush();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function disbandParty(Player $leader): void
    {
        $membership = $this->getPlayerMembership($leader);
        if (!$membership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans un groupe.');
        }

        $party = $membership->getParty();
        if ($party->getLeader()->getId() !== $leader->getId()) {
            throw new \InvalidArgumentException('Seul le chef de groupe peut dissoudre le groupe.');
        }

        // Remove all pending invitations
        $invitations = $this->entityManager->getRepository(PartyInvitation::class)->findBy([
            'party' => $party,
        ]);
        foreach ($invitations as $invitation) {
            $this->entityManager->remove($invitation);
        }

        $this->entityManager->remove($party);
        $this->entityManager->flush();
    }

    public function getPlayerMembership(Player $player): ?PartyMember
    {
        return $this->entityManager->getRepository(PartyMember::class)->findOneBy([
            'player' => $player,
        ]);
    }

    public function getPlayerParty(Player $player): ?Party
    {
        $membership = $this->getPlayerMembership($player);

        return $membership?->getParty();
    }

    /**
     * @return PartyInvitation[]
     */
    public function getPendingInvitations(Player $player): array
    {
        return $this->entityManager->getRepository(PartyInvitation::class)->findBy([
            'player' => $player,
        ]);
    }

    private function handleLeaderLeave(Party $party): void
    {
        if ($party->getMemberCount() === 0) {
            // Remove all pending invitations
            $invitations = $this->entityManager->getRepository(PartyInvitation::class)->findBy([
                'party' => $party,
            ]);
            foreach ($invitations as $invitation) {
                $this->entityManager->remove($invitation);
            }

            $this->entityManager->remove($party);
            $this->entityManager->flush();

            return;
        }

        // Transfer leadership to first remaining member
        $newLeader = $party->getMembers()->first();
        if ($newLeader) {
            $party->setLeader($newLeader->getPlayer());
        }

        $this->entityManager->flush();
    }
}
