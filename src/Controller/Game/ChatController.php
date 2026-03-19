<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\GameEngine\Social\ChatManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game/chat', name: 'app_game_chat_')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly ChatManager $chatManager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_dashboard');
        }

        $globalMessages = array_reverse($this->chatManager->getGlobalHistory(50));
        $mapMessages = $player->getMap()
            ? array_reverse($this->chatManager->getMapHistory($player->getMap(), 50))
            : [];

        return $this->render('game/chat/index.html.twig', [
            'player' => $player,
            'globalMessages' => $globalMessages,
            'mapMessages' => $mapMessages,
        ]);
    }

    #[Route('/send', name: 'send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return new JsonResponse(['error' => 'Joueur introuvable'], 403);
        }

        $content = trim((string) $request->request->get('content', ''));
        $channel = $request->request->get('channel', 'global');
        $recipientId = $request->request->getInt('recipient_id', 0);

        if ($content === '') {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $message = match ($channel) {
            'global' => $this->chatManager->sendGlobalMessage($player, $content),
            'map' => $this->chatManager->sendMapMessage($player, $content),
            'private' => $this->sendPrivate($player, $recipientId, $content),
            default => null,
        };

        if (!$message) {
            return new JsonResponse(['error' => 'Impossible d\'envoyer le message'], 400);
        }

        return new JsonResponse(['success' => true, 'messageId' => $message->getId()]);
    }

    #[Route('/history/{channel}', name: 'history', methods: ['GET'])]
    public function history(string $channel, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return new JsonResponse(['error' => 'Joueur introuvable'], 403);
        }

        $messages = match ($channel) {
            'global' => $this->chatManager->getGlobalHistory(50),
            'map' => $player->getMap()
                ? $this->chatManager->getMapHistory($player->getMap(), 50)
                : [],
            'private' => $this->getPrivateHistory($player, $request),
            default => [],
        };

        $data = array_map(fn ($m) => [
            'id' => $m->getId(),
            'channel' => $m->getChannel(),
            'content' => $m->getContent(),
            'sender' => ['id' => $m->getSender()->getId(), 'name' => $m->getSender()->getName()],
            'recipient' => $m->getRecipient() ? ['id' => $m->getRecipient()->getId(), 'name' => $m->getRecipient()->getName()] : null,
            'createdAt' => $m->getCreatedAt()->format('H:i'),
        ], array_reverse($messages));

        return new JsonResponse(['messages' => $data]);
    }

    #[Route('/conversations', name: 'conversations', methods: ['GET'])]
    public function conversations(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return new JsonResponse(['error' => 'Joueur introuvable'], 403);
        }

        $messages = $this->chatManager->getPrivateConversations($player);

        $contacts = [];
        foreach ($messages as $msg) {
            $other = $msg->getSender()->getId() === $player->getId()
                ? $msg->getRecipient()
                : $msg->getSender();

            if ($other && !isset($contacts[$other->getId()])) {
                $contacts[$other->getId()] = [
                    'id' => $other->getId(),
                    'name' => $other->getName(),
                    'lastMessage' => $msg->getContent(),
                    'lastMessageAt' => $msg->getCreatedAt()->format('H:i'),
                ];
            }
        }

        return new JsonResponse(['conversations' => array_values($contacts)]);
    }

    #[Route('/players/search', name: 'players_search', methods: ['GET'])]
    public function searchPlayers(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return new JsonResponse(['error' => 'Joueur introuvable'], 403);
        }

        $query = trim((string) $request->query->get('q', ''));
        if (mb_strlen($query) < 2) {
            return new JsonResponse(['players' => []]);
        }

        $players = $this->em->getRepository(Player::class)->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE LOWER(:q)')
            ->andWhere('p.id != :currentId')
            ->setParameter('q', '%' . $query . '%')
            ->setParameter('currentId', $player->getId())
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = array_map(fn (Player $p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
        ], $players);

        return new JsonResponse(['players' => $data]);
    }

    private function sendPrivate(Player $sender, int $recipientId, string $content): ?\App\Entity\App\ChatMessage
    {
        if ($recipientId <= 0) {
            return null;
        }

        $recipient = $this->em->getRepository(Player::class)->find($recipientId);
        if (!$recipient) {
            return null;
        }

        return $this->chatManager->sendPrivateMessage($sender, $recipient, $content);
    }

    /**
     * @return \App\Entity\App\ChatMessage[]
     */
    private function getPrivateHistory(Player $player, Request $request): array
    {
        $withId = $request->query->getInt('with', 0);
        if ($withId <= 0) {
            return [];
        }

        $other = $this->em->getRepository(Player::class)->find($withId);
        if (!$other) {
            return [];
        }

        return $this->chatManager->getPrivateHistory($player, $other, 50);
    }
}
