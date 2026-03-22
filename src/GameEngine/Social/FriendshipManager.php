<?php

namespace App\GameEngine\Social;

use App\Entity\App\Friendship;
use App\Entity\App\Player;
use App\Enum\FriendshipStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class FriendshipManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendRequest(Player $sender, Player $recipient): Friendship
    {
        if ($sender->getId() === $recipient->getId()) {
            throw new \InvalidArgumentException('Impossible de s\'ajouter soi-même en ami.');
        }

        $existing = $this->findFriendship($sender, $recipient);
        if ($existing) {
            throw new \InvalidArgumentException('Une relation existe déjà avec ce joueur.');
        }

        $reverse = $this->findFriendship($recipient, $sender);
        if ($reverse) {
            if ($reverse->getStatus() === FriendshipStatus::Blocked) {
                throw new \InvalidArgumentException('Impossible d\'envoyer une demande à ce joueur.');
            }
            throw new \InvalidArgumentException('Une relation existe déjà avec ce joueur.');
        }

        $friendship = new Friendship();
        $friendship->setPlayer($sender);
        $friendship->setFriend($recipient);
        $friendship->setStatus(FriendshipStatus::Pending);

        $this->entityManager->persist($friendship);
        $this->entityManager->flush();

        $this->publishNotification($recipient, 'friend_request', [
            'senderId' => $sender->getId(),
            'senderName' => $sender->getName(),
        ]);

        return $friendship;
    }

    public function accept(Friendship $friendship, Player $player): void
    {
        if ($friendship->getFriend()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Seul le destinataire peut accepter la demande.');
        }

        if ($friendship->getStatus() !== FriendshipStatus::Pending) {
            throw new \InvalidArgumentException('Cette demande n\'est plus en attente.');
        }

        $friendship->setStatus(FriendshipStatus::Accepted);
        $this->entityManager->flush();

        $this->publishNotification($friendship->getPlayer(), 'friend_accepted', [
            'friendId' => $player->getId(),
            'friendName' => $player->getName(),
        ]);
    }

    public function decline(Friendship $friendship, Player $player): void
    {
        if ($friendship->getFriend()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Seul le destinataire peut refuser la demande.');
        }

        if ($friendship->getStatus() !== FriendshipStatus::Pending) {
            throw new \InvalidArgumentException('Cette demande n\'est plus en attente.');
        }

        $this->entityManager->remove($friendship);
        $this->entityManager->flush();
    }

    public function block(Player $blocker, Player $blocked): void
    {
        $friendship = $this->findFriendship($blocker, $blocked);
        $reverse = $this->findFriendship($blocked, $blocker);

        if ($reverse) {
            $this->entityManager->remove($reverse);
        }

        if ($friendship) {
            $friendship->setStatus(FriendshipStatus::Blocked);
        } else {
            $friendship = new Friendship();
            $friendship->setPlayer($blocker);
            $friendship->setFriend($blocked);
            $friendship->setStatus(FriendshipStatus::Blocked);
            $this->entityManager->persist($friendship);
        }

        $this->entityManager->flush();
    }

    public function unfriend(Friendship $friendship, Player $player): void
    {
        if ($friendship->getPlayer()->getId() !== $player->getId()
            && $friendship->getFriend()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Vous n\'êtes pas concerné par cette relation.');
        }

        $this->entityManager->remove($friendship);
        $this->entityManager->flush();
    }

    /**
     * @return Friendship[]
     */
    public function getAcceptedFriends(Player $player): array
    {
        return $this->entityManager->getRepository(Friendship::class)->createQueryBuilder('f')
            ->where('(f.player = :player OR f.friend = :player)')
            ->andWhere('f.status = :status')
            ->setParameter('player', $player)
            ->setParameter('status', FriendshipStatus::Accepted)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Friendship[]
     */
    public function getPendingReceived(Player $player): array
    {
        return $this->entityManager->getRepository(Friendship::class)->findBy([
            'friend' => $player,
            'status' => FriendshipStatus::Pending,
        ]);
    }

    /**
     * @return Friendship[]
     */
    public function getPendingSent(Player $player): array
    {
        return $this->entityManager->getRepository(Friendship::class)->findBy([
            'player' => $player,
            'status' => FriendshipStatus::Pending,
        ]);
    }

    public function isOnline(Player $player): bool
    {
        $lastActivity = $player->getUpdatedAt();
        if (!$lastActivity) {
            return false;
        }

        $threshold = new \DateTime('-5 minutes');

        return $lastActivity > $threshold;
    }

    private function findFriendship(Player $player, Player $friend): ?Friendship
    {
        return $this->entityManager->getRepository(Friendship::class)->findOneBy([
            'player' => $player,
            'friend' => $friend,
        ]);
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
                    'topic' => 'friend/notification',
                    'type' => $type,
                    'data' => $data,
                ], JSON_THROW_ON_ERROR)
            );
            $this->hub->publish($update);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to publish friendship notification: ' . $e->getMessage());
        }
    }
}
