<?php

namespace App\GameEngine\Social;

use App\Entity\App\ChatMessage;
use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\Zone;
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

    /**
     * Annonce d'un maitre du jeu, sur le canal global.
     *
     * Trois differences avec un message ordinaire, et elles comptent toutes :
     * elle porte son propre canal (`announcement`) pour que l'ecran la
     * distingue, elle echappe a la limite de debit — une annonce urgente ne se
     * fait pas refuser parce qu'on vient de parler — et sa longueur est celle
     * d'un message, pas d'un discours.
     *
     * Publiee sur le topic `chat/global` : personne n'a a s'abonner a quoi que
     * ce soit pour l'entendre.
     */
    public function sendGameMasterAnnouncement(Player $sender, string $content): ?ChatMessage
    {
        if (!$sender->isGameMaster()) {
            return null;
        }

        $content = $this->sanitizeContent($content);
        if (!$this->validateMessage($content)) {
            return null;
        }

        $message = new ChatMessage();
        $message->setChannel(ChatMessage::CHANNEL_ANNOUNCEMENT);
        $message->setContent($content);
        $message->setSender($sender);

        $this->em->persist($message);
        $this->em->flush();

        $this->publishMessage($message);

        return $message;
    }

    /**
     * Message du canal de service des maitres du jeu.
     *
     * Le canal n'existe que pour eux : l'emetteur est verifie ici, la lecture
     * l'est par le controleur. Un joueur ordinaire ne le voit pas et ne peut
     * pas s'y abonner — le topic n'apparait dans aucune de ses pages.
     */
    public function sendGameMasterMessage(Player $sender, string $content): ?ChatMessage
    {
        if (!$sender->isGameMaster()) {
            return null;
        }

        $content = $this->sanitizeContent($content);
        if (!$this->validateMessage($content)) {
            return null;
        }

        if ($this->isRateLimited($sender)) {
            return null;
        }

        $message = new ChatMessage();
        $message->setChannel(ChatMessage::CHANNEL_GM);
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

    /**
     * Chat de zone (pivot PBBG, ZON-14) : diffuse a tous les joueurs presents
     * dans la zone courante du joueur. Transpose le canal `map` gele.
     */
    public function sendZoneMessage(Player $sender, string $content): ?ChatMessage
    {
        $content = $this->sanitizeContent($content);
        if (!$this->validateMessage($content)) {
            return null;
        }

        if ($this->isRateLimited($sender)) {
            return null;
        }

        $zone = $sender->getCurrentZone();
        if (!$zone) {
            return null;
        }

        $message = new ChatMessage();
        $message->setChannel(ChatMessage::CHANNEL_ZONE);
        $message->setContent($content);
        $message->setSender($sender);
        $message->setZone($zone);

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

    public function sendGuildMessage(Player $sender, Guild $guild, string $content): ?ChatMessage
    {
        $content = $this->sanitizeContent($content);
        if (!$this->validateMessage($content)) {
            return null;
        }

        if ($this->isRateLimited($sender)) {
            return null;
        }

        $message = new ChatMessage();
        $message->setChannel(ChatMessage::CHANNEL_GUILD);
        $message->setContent($content);
        $message->setSender($sender);
        $message->setGuild($guild);

        $this->em->persist($message);
        $this->em->flush();

        $this->publishMessage($message);

        return $message;
    }

    /**
     * @return ChatMessage[]
     */
    public function getGuildHistory(Guild $guild, int $limit = 50): array
    {
        return $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.channel = :channel')
            ->andWhere('m.guild = :guild')
            ->andWhere('m.isDeleted = false')
            ->setParameter('channel', ChatMessage::CHANNEL_GUILD)
            ->setParameter('guild', $guild)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
            // Les annonces des MJ sont lues sur le canal general : elles y sont
            // diffusees en direct, elles doivent donc s'y retrouver a la
            // relecture. Leur canal propre ne sert qu'a les rendre autrement.
            ->where('m.channel IN (:channels)')
            ->andWhere('m.isDeleted = false')
            ->setParameter('channels', [ChatMessage::CHANNEL_GLOBAL, ChatMessage::CHANNEL_ANNOUNCEMENT])
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Historique du canal de service MJ.
     *
     * @return ChatMessage[]
     */
    public function getGameMasterHistory(int $limit = 50): array
    {
        return $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.channel = :channel')
            ->andWhere('m.isDeleted = false')
            ->setParameter('channel', ChatMessage::CHANNEL_GM)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Dernieres annonces des maitres du jeu.
     *
     * @return ChatMessage[]
     */
    public function getAnnouncementHistory(int $limit = 20): array
    {
        return $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.channel = :channel')
            ->andWhere('m.isDeleted = false')
            ->setParameter('channel', ChatMessage::CHANNEL_ANNOUNCEMENT)
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
    public function getZoneHistory(Zone $zone, int $limit = 50): array
    {
        return $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.channel = :channel')
            ->andWhere('m.zone = :zone')
            ->andWhere('m.isDeleted = false')
            ->setParameter('channel', ChatMessage::CHANNEL_ZONE)
            ->setParameter('zone', $zone)
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
            ChatMessage::CHANNEL_ZONE => $message->getZone() ? ['chat/zone/' . $message->getZone()->getId()] : [],
            ChatMessage::CHANNEL_PRIVATE => $message->getRecipient() ? [
                'chat/private/' . $message->getSender()->getId(),
                'chat/private/' . $message->getRecipient()->getId(),
            ] : [],
            ChatMessage::CHANNEL_GUILD => $message->getGuild() ? ['chat/guild/' . $message->getGuild()->getId()] : [],
            // L'annonce emprunte le canal global : tout le monde l'entend sans
            // s'etre abonne a rien.
            ChatMessage::CHANNEL_ANNOUNCEMENT => ['chat/global'],
            ChatMessage::CHANNEL_GM => ['chat/gm'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(ChatMessage $message): array
    {
        $sender = $message->getSender();
        $senderData = [
            'id' => $sender->getId(),
            'name' => $sender->getName(),
        ];

        $guildMember = $this->em->getRepository(GuildMember::class)->findOneBy(['player' => $sender]);
        if ($guildMember !== null) {
            $senderData['guildTag'] = $guildMember->getGuild()->getTag();
            $senderData['guildColor'] = $guildMember->getGuild()->getColor();
        }

        if ($sender->getPrestigeTitle() !== null) {
            $senderData['prestigeTitle'] = $sender->getPrestigeTitle();
        }

        // Le sceau MJ voyage avec le message : un message arrive par Mercure
        // doit se reconnaitre comme celui rendu par Twig, sans requete de plus.
        if ($sender->isGameMaster()) {
            $senderData['gameMaster'] = true;
        }

        $data = [
            'type' => 'chat_message',
            'id' => $message->getId(),
            'channel' => $message->getChannel(),
            'content' => $message->getContent(),
            'sender' => $senderData,
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

        if ($message->getZone()) {
            $data['zoneId'] = $message->getZone()->getId();
        }

        if ($message->getGuild()) {
            $data['guildId'] = $message->getGuild()->getId();
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
