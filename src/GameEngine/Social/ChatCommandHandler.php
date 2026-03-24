<?php

namespace App\GameEngine\Social;

use App\Entity\App\Player;
use App\GameEngine\Guild\GuildManager;
use Doctrine\ORM\EntityManagerInterface;

class ChatCommandHandler
{
    public function __construct(
        private readonly ChatManager $chatManager,
        private readonly GuildManager $guildManager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function isCommand(string $content): bool
    {
        return str_starts_with($content, '/');
    }

    /**
     * @return array{success: bool, message?: string, messageId?: int, system?: bool}
     */
    public function handle(Player $sender, string $content): array
    {
        $parts = preg_split('/\s+/', trim($content), 3);
        $command = mb_strtolower($parts[0] ?? '');

        return match ($command) {
            '/whisper', '/w', '/mp' => $this->handleWhisper($sender, $parts),
            '/zone', '/z' => $this->handleZone($sender, $parts),
            '/global', '/g' => $this->handleGlobal($sender, $parts),
            '/guild', '/gu' => $this->handleGuild($sender, $parts),
            '/emote', '/e', '/me' => $this->handleEmote($sender, $parts),
            '/who' => $this->handleWho($sender),
            '/help', '/?' => $this->handleHelp(),
            default => ['success' => false, 'message' => "Commande inconnue : {$parts[0]}. Tapez /help pour la liste des commandes."],
        };
    }

    /**
     * @param string[] $parts
     */
    private function handleWhisper(Player $sender, array $parts): array
    {
        $recipientName = $parts[1] ?? '';
        $message = $parts[2] ?? '';

        if ($recipientName === '' || $message === '') {
            return ['success' => false, 'message' => 'Usage : /whisper <joueur> <message>'];
        }

        $recipient = $this->em->getRepository(Player::class)->findOneBy(['name' => $recipientName]);
        if (!$recipient) {
            return ['success' => false, 'message' => "Joueur \"{$recipientName}\" introuvable."];
        }

        if ($recipient->getId() === $sender->getId()) {
            return ['success' => false, 'message' => 'Vous ne pouvez pas vous envoyer un message a vous-meme.'];
        }

        $chatMessage = $this->chatManager->sendPrivateMessage($sender, $recipient, $message);
        if (!$chatMessage) {
            return ['success' => false, 'message' => 'Impossible d\'envoyer le message prive.'];
        }

        return ['success' => true, 'message' => "Message envoye a {$recipient->getName()}.", 'messageId' => $chatMessage->getId()];
    }

    /**
     * @param string[] $parts
     */
    private function handleZone(Player $sender, array $parts): array
    {
        $message = trim(implode(' ', \array_slice($parts, 1)));
        if ($message === '') {
            return ['success' => false, 'message' => 'Usage : /zone <message>'];
        }

        $chatMessage = $this->chatManager->sendMapMessage($sender, $message);
        if (!$chatMessage) {
            return ['success' => false, 'message' => 'Impossible d\'envoyer le message de zone. Etes-vous sur une carte ?'];
        }

        return ['success' => true, 'messageId' => $chatMessage->getId()];
    }

    /**
     * @param string[] $parts
     */
    private function handleGlobal(Player $sender, array $parts): array
    {
        $message = trim(implode(' ', \array_slice($parts, 1)));
        if ($message === '') {
            return ['success' => false, 'message' => 'Usage : /global <message>'];
        }

        $chatMessage = $this->chatManager->sendGlobalMessage($sender, $message);
        if (!$chatMessage) {
            return ['success' => false, 'message' => 'Impossible d\'envoyer le message global.'];
        }

        return ['success' => true, 'messageId' => $chatMessage->getId()];
    }

    /**
     * @param string[] $parts
     */
    /**
     * @param string[] $parts
     */
    private function handleGuild(Player $sender, array $parts): array
    {
        $message = trim(implode(' ', \array_slice($parts, 1)));
        if ($message === '') {
            return ['success' => false, 'message' => 'Usage : /guild <message>'];
        }

        $guild = $this->guildManager->getPlayerGuild($sender);
        if (!$guild) {
            return ['success' => false, 'message' => 'Vous n\'etes dans aucune guilde.'];
        }

        $chatMessage = $this->chatManager->sendGuildMessage($sender, $guild, $message);
        if (!$chatMessage) {
            return ['success' => false, 'message' => 'Impossible d\'envoyer le message de guilde.'];
        }

        return ['success' => true, 'messageId' => $chatMessage->getId()];
    }

    private function handleEmote(Player $sender, array $parts): array
    {
        $action = trim(implode(' ', \array_slice($parts, 1)));
        if ($action === '') {
            return ['success' => false, 'message' => 'Usage : /emote <action>'];
        }

        $emoteContent = "* {$sender->getName()} {$action} *";

        $map = $sender->getMap();
        if ($map) {
            $chatMessage = $this->chatManager->sendMapMessage($sender, $emoteContent);
        } else {
            $chatMessage = $this->chatManager->sendGlobalMessage($sender, $emoteContent);
        }

        if (!$chatMessage) {
            return ['success' => false, 'message' => 'Impossible d\'envoyer l\'emote.'];
        }

        return ['success' => true, 'messageId' => $chatMessage->getId()];
    }

    private function handleWho(Player $sender): array
    {
        $map = $sender->getMap();
        if (!$map) {
            return ['success' => false, 'message' => 'Vous n\'etes sur aucune carte.'];
        }

        /** @var Player[] $players */
        $players = $this->em->getRepository(Player::class)->findBy(['map' => $map]);
        $names = array_map(fn (Player $p) => $p->getName(), $players);
        sort($names);

        $count = \count($names);
        $mapName = $map->getName();

        return [
            'success' => true,
            'system' => true,
            'message' => "{$count} joueur(s) sur {$mapName} : " . implode(', ', $names),
        ];
    }

    /**
     * @return array{success: bool, system: bool, message: string}
     */
    private function handleHelp(): array
    {
        $help = implode("\n", [
            'Commandes disponibles :',
            '/whisper <joueur> <message> — Message prive (alias: /w, /mp)',
            '/zone <message> — Message de zone (alias: /z)',
            '/global <message> — Message global (alias: /g)',
            '/guild <message> — Message de guilde (alias: /gu)',
            '/emote <action> — Emote (alias: /e, /me)',
            '/who — Liste des joueurs sur la carte',
            '/help — Affiche cette aide',
        ]);

        return ['success' => true, 'system' => true, 'message' => $help];
    }
}
