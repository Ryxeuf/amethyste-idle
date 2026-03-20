<?php

namespace App\GameEngine\Social;

use App\Entity\App\ChatMessage;
use App\Entity\App\Map;
use App\Entity\App\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ChatManager
{
    private const MAX_MESSAGE_LENGTH = 500;
    private const RATE_LIMIT_SECONDS = 1;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendGlobalMessage(Player $sender, string $content): ?ChatMessage
    {
        $content = $this->sanitizeContent($content);
        if (!$this->validateMessage($content)) {
            return null;
        }

        if ($this->isRateLimited($sender)) {
            return null;
        }

        $message = new ChatMessage();
        $message->setChannel(ChatMessage::CHANNEL_GLOBAL);
        $message->setContent($content);
        $message->setSender($sender);

        $this->em->persist($message);
        $this->em->flush();

        $this->publishMessage($message);

        return $message;
    }

    public function sendMapMessage(Player $sender, string $content): ?ChatMessage
    {
        $content = $this->sanitizeContent($content);
        if (!$this->validateMessage($content)) {
            return null;
        }

        if ($this->isRateLimited($sender)) {
            return null;
        }

        $map = $sender->getMap();
        if (!$map) {
            return null;
        }

        $message = new ChatMessage();
        $message->setChannel(ChatMessage::CHANNEL_MAP);
        $message->setContent($content);
        $message->setSender($sender);
        $message->setMap($map);

        $this->em->persist($message);
        $this->em->flush();

        $this->publishMessage($message);

        return $message;
    }

    public function sendPrivateMessage(Player $sender, Player $recipient, string $content): ?ChatMessage
    {
        $content = $this->sanitizeContent($content);
        if (!$this->validateMessage($content)) {
            return null;
        }

        if ($this->isRateLimited($sender)) {
            return null;
        }

        if ($sender->getId() === $recipient->getId()) {
            return null;
        }

        $message = new ChatMessage();
        $message->setChannel(ChatMessage::CHANNEL_PRIVATE);
        $message->setContent($content);
        $message->setSender($sender);
        $message->setRecipient($recipient);

        $this->em->persist($message);
        $this->em->flush();

        $this->publishMessage($message);

        return $message;
    }

    public function deleteMessage(int $messageId, string $moderatorName): bool
    {
        $message = $this->em->getRepository(ChatMessage::class)->find($messageId);
        if (!$message) {
            return false;
        }

        $message->setIsDeleted(true);
        $message->setDeletedBy($moderatorName);
        $this->em->flush();

        $this->publishDeletion($message);

        return true;
    }

    /**
     * @return ChatMessage[]
     */
    public function getGlobalHistory(int $limit = 50): array
    {
        return $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.channel = :channel')
            ->andWhere('m.isDeleted = false')
            ->setParameter('channel', ChatMessage::CHANNEL_GLOBAL)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ChatMessage[]
     */
    public function getMapHistory(Map $map, int $limit = 50): array
    {
        return $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.channel = :channel')
            ->andWhere('m.map = :map')
            ->andWhere('m.isDeleted = false')
            ->setParameter('channel', ChatMessage::CHANNEL_MAP)
            ->setParameter('map', $map)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ChatMessage[]
     */
    public function getPrivateHistory(Player $player1, Player $player2, int $limit = 50): array
    {
        return $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->leftJoin('m.recipient', 'r')
            ->addSelect('r')
            ->where('m.channel = :channel')
            ->andWhere('(m.sender = :p1 AND m.recipient = :p2) OR (m.sender = :p2 AND m.recipient = :p1)')
            ->andWhere('m.isDeleted = false')
            ->setParameter('channel', ChatMessage::CHANNEL_PRIVATE)
            ->setParameter('p1', $player1)
            ->setParameter('p2', $player2)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ChatMessage[]
     */
    public function getPrivateConversations(Player $player): array
    {
        return $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->leftJoin('m.recipient', 'r')
            ->addSelect('r')
            ->where('m.channel = :channel')
            ->andWhere('(m.sender = :player OR m.recipient = :player)')
            ->andWhere('m.isDeleted = false')
            ->setParameter('channel', ChatMessage::CHANNEL_PRIVATE)
            ->setParameter('player', $player)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function publishMessage(ChatMessage $message): void
    {
        $data = $this->serializeMessage($message);

        $topics = $this->getTopicsForMessage($message);

        foreach ($topics as $topic) {
            $payload = array_merge($data, ['topic' => $topic]);
            $update = new Update(
                $topic,
                json_encode($payload, JSON_THROW_ON_ERROR)
            );
            $this->hub->publish($update);
        }

        $this->logger->info('Chat message published on {channel}', [
            'channel' => $message->getChannel(),
            'senderId' => $message->getSender()->getId(),
        ]);
    }

    private function publishDeletion(ChatMessage $message): void
    {
        $data = [
            'type' => 'chat_delete',
            'messageId' => $message->getId(),
        ];

        $topics = $this->getTopicsForMessage($message);

        foreach ($topics as $topic) {
            $payload = array_merge($data, ['topic' => $topic]);
            $update = new Update(
                $topic,
                json_encode($payload, JSON_THROW_ON_ERROR)
            );
            $this->hub->publish($update);
        }
    }

    /**
     * @return string[]
     */
    private function getTopicsForMessage(ChatMessage $message): array
    {
        return match ($message->getChannel()) {
            ChatMessage::CHANNEL_GLOBAL => ['chat/global'],
            ChatMessage::CHANNEL_MAP => $message->getMap() ? ['chat/map/' . $message->getMap()->getId()] : [],
            ChatMessage::CHANNEL_PRIVATE => $message->getRecipient() ? [
                'chat/private/' . $message->getSender()->getId(),
                'chat/private/' . $message->getRecipient()->getId(),
            ] : [],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(ChatMessage $message): array
    {
        $data = [
            'type' => 'chat_message',
            'id' => $message->getId(),
            'channel' => $message->getChannel(),
            'content' => $message->getContent(),
            'sender' => [
                'id' => $message->getSender()->getId(),
                'name' => $message->getSender()->getName(),
            ],
            'createdAt' => $message->getCreatedAt()?->format('H:i') ?? '',
        ];

        if ($message->getRecipient()) {
            $data['recipient'] = [
                'id' => $message->getRecipient()->getId(),
                'name' => $message->getRecipient()->getName(),
            ];
        }

        if ($message->getMap()) {
            $data['mapId'] = $message->getMap()->getId();
        }

        return $data;
    }

    private function sanitizeContent(string $content): string
    {
        $content = trim($content);
        $content = strip_tags($content);

        if (mb_strlen($content) > self::MAX_MESSAGE_LENGTH) {
            $content = mb_substr($content, 0, self::MAX_MESSAGE_LENGTH);
        }

        return $content;
    }

    private function validateMessage(string $content): bool
    {
        return mb_strlen($content) > 0;
    }

    private function isRateLimited(Player $sender): bool
    {
        $lastMessage = $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->where('m.sender = :sender')
            ->setParameter('sender', $sender)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastMessage) {
            return false;
        }

        $createdAt = $lastMessage->getCreatedAt();
        if (!$createdAt) {
            return false;
        }

        $diff = time() - $createdAt->getTimestamp();

        return $diff < self::RATE_LIMIT_SECONDS;
    }
}
