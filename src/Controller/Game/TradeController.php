<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\TradeOffer;
use App\Entity\App\TransactionLog;
use App\Entity\Game\Item;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/trade')]
class TradeController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_trade', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        // Get pending trades for this player
        $receivedTrades = $this->entityManager->getRepository(TradeOffer::class)->findBy(
            ['receiver' => $player, 'status' => TradeOffer::STATUS_PENDING],
            ['createdAt' => 'DESC']
        );

        $sentTrades = $this->entityManager->getRepository(TradeOffer::class)->findBy(
            ['initiator' => $player, 'status' => TradeOffer::STATUS_PENDING],
            ['createdAt' => 'DESC']
        );

        // Get nearby players on the same map
        $nearbyPlayers = [];
        if ($player->getMap()) {
            $nearbyPlayers = $this->entityManager->getRepository(Player::class)->findBy(
                ['map' => $player->getMap()],
            );
            // Remove self
            $nearbyPlayers = array_filter($nearbyPlayers, fn (Player $p) => $p->getId() !== $player->getId());
        }

        return $this->render('game/trade/index.html.twig', [
            'player' => $player,
            'receivedTrades' => $receivedTrades,
            'sentTrades' => $sentTrades,
            'nearbyPlayers' => array_values($nearbyPlayers),
        ]);
    }

    #[Route('/create', name: 'app_game_trade_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $receiverId = (int) ($data['receiverId'] ?? 0);
        $itemIds = $data['itemIds'] ?? [];
        $gilsAmount = max(0, (int) ($data['gils'] ?? 0));

        if ($receiverId <= 0) {
            return new JsonResponse(['error' => 'Joueur invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($itemIds) && $gilsAmount === 0) {
            return new JsonResponse(['error' => 'Vous devez proposer au moins un objet ou des Gils'], Response::HTTP_BAD_REQUEST);
        }

        $player = $this->playerHelper->getPlayer();

        $receiver = $this->entityManager->getRepository(Player::class)->find($receiverId);
        if (!$receiver) {
            return new JsonResponse(['error' => 'Joueur introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($receiver->getId() === $player->getId()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas echanger avec vous-meme'], Response::HTTP_BAD_REQUEST);
        }

        // Check gils
        if ($gilsAmount > 0 && $player->getGils() < $gilsAmount) {
            return new JsonResponse(['error' => 'Pas assez de Gils'], Response::HTTP_BAD_REQUEST);
        }

        // Validate items belong to player
        $bag = $this->playerHelper->getBagInventory();
        $validItemIds = [];
        foreach ($itemIds as $itemId) {
            $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find((int) $itemId);
            if (!$playerItem || $playerItem->getInventory()?->getId() !== $bag->getId() || $playerItem->getGear() > 0 || $playerItem->isBound()) {
                return new JsonResponse(['error' => 'Un des objets proposes est invalide ou lie au personnage'], Response::HTTP_BAD_REQUEST);
            }
            $validItemIds[] = (int) $itemId;
        }

        // Check for existing pending trade with same receiver
        $existingTrade = $this->entityManager->getRepository(TradeOffer::class)->findOneBy([
            'initiator' => $player,
            'receiver' => $receiver,
            'status' => TradeOffer::STATUS_PENDING,
        ]);
        if ($existingTrade) {
            return new JsonResponse(['error' => 'Vous avez deja un echange en attente avec ce joueur'], Response::HTTP_BAD_REQUEST);
        }

        $trade = new TradeOffer();
        $trade->setInitiator($player);
        $trade->setReceiver($receiver);
        $trade->setInitiatorItems($validItemIds);
        $trade->setInitiatorGils($gilsAmount);
        $trade->setCreatedAt(new \DateTime());
        $trade->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($trade);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Proposition d\'echange envoyee a %s.', $receiver->getName()),
            'tradeId' => $trade->getId(),
        ]);
    }

    #[Route('/{id}/update', name: 'app_game_trade_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $player = $this->playerHelper->getPlayer();

        $trade = $this->entityManager->getRepository(TradeOffer::class)->find($id);
        if (!$trade || !$trade->isPending()) {
            return new JsonResponse(['error' => 'Echange introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Only receiver can add counter-offer items
        if ($trade->getReceiver()->getId() !== $player->getId()) {
            return new JsonResponse(['error' => 'Seul le destinataire peut modifier sa partie'], Response::HTTP_FORBIDDEN);
        }

        $itemIds = $data['itemIds'] ?? [];
        $gilsAmount = max(0, (int) ($data['gils'] ?? 0));

        if ($gilsAmount > 0 && $player->getGils() < $gilsAmount) {
            return new JsonResponse(['error' => 'Pas assez de Gils'], Response::HTTP_BAD_REQUEST);
        }

        // Validate items
        $bag = $this->playerHelper->getBagInventory();
        $validItemIds = [];
        foreach ($itemIds as $itemId) {
            $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find((int) $itemId);
            if (!$playerItem || $playerItem->getInventory()?->getId() !== $bag->getId() || $playerItem->getGear() > 0 || $playerItem->isBound()) {
                return new JsonResponse(['error' => 'Un des objets proposes est invalide ou lie au personnage'], Response::HTTP_BAD_REQUEST);
            }
            $validItemIds[] = (int) $itemId;
        }

        $trade->setReceiverItems($validItemIds);
        $trade->setReceiverGils($gilsAmount);
        // Reset confirmations when offer changes
        $trade->setInitiatorConfirmed(false);
        $trade->setReceiverConfirmed(false);
        $trade->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Offre mise a jour.',
        ]);
    }

    #[Route('/{id}/confirm', name: 'app_game_trade_confirm', methods: ['POST'])]
    public function confirm(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        $trade = $this->entityManager->getRepository(TradeOffer::class)->find($id);
        if (!$trade || !$trade->isPending()) {
            return new JsonResponse(['error' => 'Echange introuvable'], Response::HTTP_NOT_FOUND);
        }

        $isInitiator = $trade->getInitiator()->getId() === $player->getId();
        $isReceiver = $trade->getReceiver()->getId() === $player->getId();

        if (!$isInitiator && !$isReceiver) {
            return new JsonResponse(['error' => 'Vous ne participez pas a cet echange'], Response::HTTP_FORBIDDEN);
        }

        if ($isInitiator) {
            $trade->setInitiatorConfirmed(true);
        } else {
            $trade->setReceiverConfirmed(true);
        }

        // If both confirmed, execute the trade
        if ($trade->areBothConfirmed()) {
            $result = $this->executeTrade($trade);
            if ($result !== null) {
                return new JsonResponse(['error' => $result], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Echange complete avec succes !',
                'completed' => true,
                'gils' => $player->getGils(),
            ]);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Vous avez confirme l\'echange. En attente de l\'autre joueur.',
            'completed' => false,
        ]);
    }

    #[Route('/{id}/decline', name: 'app_game_trade_decline', methods: ['POST'])]
    public function decline(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        $trade = $this->entityManager->getRepository(TradeOffer::class)->find($id);
        if (!$trade || !$trade->isPending()) {
            return new JsonResponse(['error' => 'Echange introuvable'], Response::HTTP_NOT_FOUND);
        }

        $isInitiator = $trade->getInitiator()->getId() === $player->getId();
        $isReceiver = $trade->getReceiver()->getId() === $player->getId();

        if (!$isInitiator && !$isReceiver) {
            return new JsonResponse(['error' => 'Vous ne participez pas a cet echange'], Response::HTTP_FORBIDDEN);
        }

        $trade->setStatus($isInitiator ? TradeOffer::STATUS_CANCELLED : TradeOffer::STATUS_DECLINED);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $isInitiator ? 'Echange annule.' : 'Echange refuse.',
        ]);
    }

    private function executeTrade(TradeOffer $trade): ?string
    {
        $initiator = $trade->getInitiator();
        $receiver = $trade->getReceiver();

        // Check gils
        if ($trade->getInitiatorGils() > 0 && $initiator->getGils() < $trade->getInitiatorGils()) {
            return 'L\'initiateur n\'a plus assez de Gils';
        }
        if ($trade->getReceiverGils() > 0 && $receiver->getGils() < $trade->getReceiverGils()) {
            return 'Le destinataire n\'a plus assez de Gils';
        }

        // Get bags
        $initiatorBag = null;
        $receiverBag = null;
        foreach ($initiator->getInventories() as $inv) {
            if ($inv->isBag()) {
                $initiatorBag = $inv;
                break;
            }
        }
        foreach ($receiver->getInventories() as $inv) {
            if ($inv->isBag()) {
                $receiverBag = $inv;
                break;
            }
        }

        if (!$initiatorBag || !$receiverBag) {
            return 'Inventaire introuvable';
        }

        // Validate items still exist and belong to correct players
        $initiatorPlayerItems = [];
        foreach ($trade->getInitiatorItems() as $itemId) {
            $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($itemId);
            if (!$playerItem || $playerItem->getInventory()?->getId() !== $initiatorBag->getId()) {
                return 'Un objet de l\'initiateur n\'est plus disponible';
            }
            $initiatorPlayerItems[] = $playerItem;
        }

        $receiverPlayerItems = [];
        foreach ($trade->getReceiverItems() as $itemId) {
            $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($itemId);
            if (!$playerItem || $playerItem->getInventory()?->getId() !== $receiverBag->getId()) {
                return 'Un objet du destinataire n\'est plus disponible';
            }
            $receiverPlayerItems[] = $playerItem;
        }

        // Transfer gils
        if ($trade->getInitiatorGils() > 0) {
            $initiator->removeGils($trade->getInitiatorGils());
            $receiver->addGils($trade->getInitiatorGils());
        }
        if ($trade->getReceiverGils() > 0) {
            $receiver->removeGils($trade->getReceiverGils());
            $initiator->addGils($trade->getReceiverGils());
        }

        // Transfer items: initiator -> receiver
        foreach ($initiatorPlayerItems as $playerItem) {
            $playerItem->setInventory($receiverBag);
            $this->entityManager->persist($playerItem);
        }

        // Transfer items: receiver -> initiator
        foreach ($receiverPlayerItems as $playerItem) {
            $playerItem->setInventory($initiatorBag);
            $this->entityManager->persist($playerItem);
        }

        // Mark trade as completed
        $trade->setStatus(TradeOffer::STATUS_ACCEPTED);
        $trade->setCompletedAt(new \DateTime());

        // Log transactions
        $descItems = array_map(fn (PlayerItem $pi) => $pi->getGenericItem()->getName(), $initiatorPlayerItems);
        $descItemsReceiver = array_map(fn (PlayerItem $pi) => $pi->getGenericItem()->getName(), $receiverPlayerItems);

        $logDescription = sprintf(
            'Echange avec %s: donne [%s]%s, recu [%s]%s',
            $receiver->getName(),
            implode(', ', $descItems) ?: 'rien',
            $trade->getInitiatorGils() > 0 ? " + {$trade->getInitiatorGils()} G" : '',
            implode(', ', $descItemsReceiver) ?: 'rien',
            $trade->getReceiverGils() > 0 ? " + {$trade->getReceiverGils()} G" : ''
        );

        $log = new TransactionLog();
        $log->setType(TransactionLog::TYPE_TRADE);
        $log->setPlayer($initiator);
        $log->setOtherPlayer($receiver);
        $log->setGilsAmount($trade->getInitiatorGils());
        $log->setDescription($logDescription);
        $log->setCreatedAt(new \DateTime());
        $log->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($log);

        $this->entityManager->persist($trade);
        $this->entityManager->persist($initiator);
        $this->entityManager->persist($receiver);
        $this->entityManager->flush();

        return null;
    }
}
