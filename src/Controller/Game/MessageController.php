<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\Entity\App\PrivateMessage;
use App\Helper\PlayerHelper;
use App\Repository\PrivateMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/game/messages', name: 'app_game_messages_')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PrivateMessageRepository $messageRepository,
        private readonly EntityManagerInterface $em,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('', name: 'inbox')]
    public function inbox(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_dashboard');
        }

        $tab = $request->query->getString('tab', 'inbox');
        $messages = $tab === 'sent'
            ? $this->messageRepository->findSentForPlayer($player)
            : $this->messageRepository->findInboxForPlayer($player);

        return $this->render('game/messages/inbox.html.twig', [
            'messages' => $messages,
            'tab' => $tab,
            'player' => $player,
        ]);
    }

    #[Route('/{id}', name: 'read', requirements: ['id' => '\d+'])]
    public function read(int $id): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_dashboard');
        }

        $message = $this->messageRepository->find($id);
        if (!$message || ($message->getReceiver()->getId() !== $player->getId() && $message->getSender()->getId() !== $player->getId())) {
            $this->addFlash('error', 'Message introuvable.');

            return $this->redirectToRoute('app_game_messages_inbox');
        }

        if ($message->getReceiver()->getId() === $player->getId() && !$message->isRead()) {
            $message->markAsRead();
            $this->em->flush();
        }

        return $this->render('game/messages/read.html.twig', [
            'message' => $message,
            'player' => $player,
        ]);
    }

    #[Route('/compose', name: 'compose')]
    public function compose(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_dashboard');
        }

        $recipientId = $request->query->getInt('to', 0);
        $replySubject = $request->query->getString('subject', '');
        $recipient = $recipientId > 0 ? $this->em->getRepository(Player::class)->find($recipientId) : null;

        return $this->render('game/messages/compose.html.twig', [
            'player' => $player,
            'recipient' => $recipient,
            'replySubject' => $replySubject,
        ]);
    }

    #[Route('/send', name: 'send', methods: ['POST'])]
    public function send(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_dashboard');
        }

        if (!$this->isCsrfTokenValid('message_send', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_game_messages_compose');
        }

        $recipientName = trim($request->request->getString('recipient'));
        $subject = trim($request->request->getString('subject'));
        $body = trim($request->request->getString('body'));

        if ($recipientName === '' || $subject === '' || $body === '') {
            $this->addFlash('error', 'Tous les champs sont obligatoires.');

            return $this->redirectToRoute('app_game_messages_compose');
        }

        if (mb_strlen($subject) > 100) {
            $this->addFlash('error', 'Le sujet ne doit pas depasser 100 caracteres.');

            return $this->redirectToRoute('app_game_messages_compose');
        }

        if (mb_strlen($body) > 2000) {
            $this->addFlash('error', 'Le message ne doit pas depasser 2000 caracteres.');

            return $this->redirectToRoute('app_game_messages_compose');
        }

        $receiver = $this->em->getRepository(Player::class)->findOneBy(['name' => $recipientName]);
        if (!$receiver) {
            $this->addFlash('error', 'Joueur introuvable.');

            return $this->redirectToRoute('app_game_messages_compose');
        }

        if ($receiver->getId() === $player->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous envoyer un message.');

            return $this->redirectToRoute('app_game_messages_compose');
        }

        if ($receiver->isPlayerBlocked($player->getId())) {
            $this->addFlash('error', 'Ce joueur vous a bloque.');

            return $this->redirectToRoute('app_game_messages_compose');
        }

        $message = new PrivateMessage();
        $message->setSender($player);
        $message->setReceiver($receiver);
        $message->setSubject($subject);
        $message->setBody($body);

        $this->em->persist($message);
        $this->em->flush();

        $this->messageRepository->enforceLimit($receiver);

        $this->publishNewMessageNotification($receiver, $player);

        $this->addFlash('success', 'Message envoye.');

        return $this->redirectToRoute('app_game_messages_inbox');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_dashboard');
        }

        if (!$this->isCsrfTokenValid('message_delete_' . $id, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_game_messages_inbox');
        }

        $message = $this->messageRepository->find($id);
        if (!$message || ($message->getReceiver()->getId() !== $player->getId() && $message->getSender()->getId() !== $player->getId())) {
            $this->addFlash('error', 'Message introuvable.');

            return $this->redirectToRoute('app_game_messages_inbox');
        }

        $this->em->remove($message);
        $this->em->flush();

        $this->addFlash('success', 'Message supprime.');

        return $this->redirectToRoute('app_game_messages_inbox');
    }

    #[Route('/block/{id}', name: 'block', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function block(int $id, Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_dashboard');
        }

        if (!$this->isCsrfTokenValid('message_block_' . $id, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_game_messages_inbox');
        }

        $target = $this->em->getRepository(Player::class)->find($id);
        if (!$target || $target->getId() === $player->getId()) {
            $this->addFlash('error', 'Joueur introuvable.');

            return $this->redirectToRoute('app_game_messages_inbox');
        }

        if ($player->isPlayerBlocked($target->getId())) {
            $player->unblockPlayer($target->getId());
            $this->addFlash('success', $target->getName() . ' a ete debloque.');
        } else {
            $player->blockPlayer($target->getId());
            $this->addFlash('success', $target->getName() . ' a ete bloque.');
        }

        $this->em->flush();

        return $this->redirectToRoute('app_game_messages_inbox');
    }

    private function publishNewMessageNotification(Player $receiver, Player $sender): void
    {
        try {
            $topic = 'player/' . $receiver->getId() . '/messages';
            $update = new Update(
                $topic,
                json_encode([
                    'type' => 'new_message',
                    'senderName' => $sender->getName(),
                ], JSON_THROW_ON_ERROR),
            );
            $this->hub->publish($update);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to publish message notification: {error}', ['error' => $e->getMessage()]);
        }
    }
}
