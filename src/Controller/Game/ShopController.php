<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Pnj;
use App\Entity\Game\Item;
use App\Enum\PlayerRenownTier;
use App\GameEngine\Economy\PurityPricer;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Guild\RegionBonusProvider;
use App\GameEngine\Renown\PlayerRenownDiscountProvider;
use App\GameEngine\Reputation\CrystalBuybackFloor;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\GameEngine\Reputation\ShadowsMarket;
use App\GameEngine\Reputation\ShadowsMarketException;
use App\GameEngine\Reputation\ShadowsPlacement;
use App\GameEngine\Reputation\ShadowsRumors;
use App\GameEngine\Reputation\ShadowsSmuggling;
use App\GameEngine\World\GameTimeService;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/shop')]
class ShopController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameTimeService $gameTimeService,
        private readonly RegionBonusProvider $regionBonusProvider,
        private readonly PlayerRenownDiscountProvider $renownDiscountProvider,
        private readonly GameMasterPolicy $gameMasterPolicy,
        private readonly HostileConsequenceResolver $hostileConsequences,
        private readonly CrystalBuybackFloor $buybackFloor,
        private readonly PurityPricer $purityPricer,
        private readonly ShadowsMarket $shadowsMarket,
        private readonly ShadowsRumors $shadowsRumors,
        private readonly ShadowsSmuggling $shadowsSmuggling,
        private readonly ShadowsPlacement $shadowsPlacement,
        private readonly \App\GameEngine\Reputation\ReputationManager $reputationManager,
        private readonly \Symfony\Contracts\Translation\TranslatorInterface $translator,
    ) {
    }

    #[Route('/{id}', name: 'app_game_shop', methods: ['GET'])]
    public function index(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj || !$pnj->isMerchant()) {
            throw $this->createNotFoundException('Boutique introuvable');
        }

        $player = $this->playerHelper->getPlayer();
        if (!$this->isReachableFrom($pnj, $player)) {
            throw $this->createNotFoundException('Boutique introuvable');
        }

        $gameHour = $this->gameTimeService->getHour();
        $isOpen = $pnj->isShopOpen($gameHour);

        $shopItems = $this->getShopItems($pnj);

        // Build stock info per slug (null = unlimited)
        $stockInfo = [];
        foreach ($shopItems as $item) {
            $stockInfo[$item->getSlug()] = $pnj->getItemStock($item->getSlug());
        }

        $guildDiscount = $this->regionBonusProvider->getShopDiscount($player, $player->getMap());
        $renownDiscount = $this->renownDiscountProvider->getShopDiscount($player);
        $totalDiscount = $this->renownDiscountProvider->combineDiscount($guildDiscount, $player);
        // FAC-03 — la rancune des Marchands se lit avant de payer : le prix
        // affiche est le prix demande.
        $hostileSurcharge = $this->hostileConsequences->shopSurchargePercent($player);

        return $this->render('game/shop/index.html.twig', [
            'pnj' => $pnj,
            'shopItems' => $shopItems,
            'stockInfo' => $stockInfo,
            'player' => $player,
            'isOpen' => $isOpen,
            'opensAt' => $pnj->getOpensAt(),
            'closesAt' => $pnj->getClosesAt(),
            'guildDiscount' => $guildDiscount,
            'renownDiscount' => $renownDiscount,
            'totalDiscount' => $totalDiscount,
            'hostileSurcharge' => $hostileSurcharge,
            'renownTier' => PlayerRenownTier::fromScore($player->getRenownScore()),
            // FAC-06 : le guichet des Ruelles ne se montre qu'apres le premier
            // contact — avant, l'echoppe est une echoppe.
            'shadowsRumorAvailable' => null !== $player && $this->shadowsRumors->isAvailableFor($player, $pnj),
            'shadowsRumorPrice' => $this->shadowsRumors->priceGils(),
            // FAC-08 : la contrebande et le placement — la nuit, au guichet,
            // apres le premier contact. Le bouton n'apparait que si le geste
            // peut reussir ; le blocker dit pourquoi sinon.
            'shadowsSmugglingAvailable' => null !== $player && $this->shadowsSmuggling->isAvailableFor($player, $pnj),
            'shadowsSmugglingContract' => null !== $player ? $this->shadowsSmuggling->activeContract($player) : null,
            'shadowsSmugglingAcceptBlocker' => null !== $player ? $this->shadowsSmuggling->acceptBlocker($player, $pnj) : 'game.shadows.smuggling.error.counter',
            'shadowsSmugglingDeliverBlocker' => null !== $player ? $this->shadowsSmuggling->deliverBlocker($player, $pnj) : 'game.shadows.smuggling.error.counter',
            'shadowsSmugglingReward' => $this->shadowsSmuggling->rewardGils(),
            'shadowsPlacementItems' => null !== $player ? $this->shadowsPlacement->placeableItems($player, $pnj) : [],
        ]);
    }

    #[Route('/{id}/buy', name: 'app_game_shop_buy', methods: ['POST'])]
    public function buy(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Le MJ entre dans la boutique et lit les prix ; il n'y achete rien.
        if (!$this->gameMasterPolicy->canTrade($this->playerHelper->getPlayer())) {
            return new JsonResponse(['error' => GameMasterPolicy::REASON_TRADE], Response::HTTP_FORBIDDEN);
        }

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj || !$pnj->isMerchant()) {
            return new JsonResponse(['error' => 'Boutique introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (!$pnj->isShopOpen($this->gameTimeService->getHour())) {
            return new JsonResponse(['error' => 'La boutique est fermée.'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $itemSlug = $data['itemSlug'] ?? null;
        $quantity = max(1, (int) ($data['quantity'] ?? 1));

        if (!$itemSlug) {
            return new JsonResponse(['error' => 'Item invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Check item is in this shop
        $shopSlugs = $pnj->getShopItems();
        if (!in_array($itemSlug, $shopSlugs, true)) {
            return new JsonResponse(['error' => 'Cet objet n\'est pas en vente ici'], Response::HTTP_BAD_REQUEST);
        }

        // Check stock
        $stock = $pnj->getItemStock($itemSlug);
        if ($stock !== null && $stock < $quantity) {
            $msg = $stock === 0
                ? 'Cet objet est en rupture de stock.'
                : sprintf('Stock insuffisant (disponible: %d).', $stock);

            return new JsonResponse(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }

        $item = $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $itemSlug]);
        if (!$item) {
            return new JsonResponse(['error' => 'Objet introuvable'], Response::HTTP_NOT_FOUND);
        }

        $player = $this->playerHelper->getPlayer();
        $baseCost = ($item->getPrice() ?? 0) * $quantity;

        // Apply guild region discount (10% if player is in controlling guild)
        $guildDiscount = $this->regionBonusProvider->getShopDiscount($player, $player->getMap());
        $renownDiscount = $this->renownDiscountProvider->getShopDiscount($player);
        // Renown discount stacks additively with guild discount, capped at MAX_COMBINED_DISCOUNT.
        $discount = $this->renownDiscountProvider->combineDiscount($guildDiscount, $player);
        $totalCost = $discount > 0
            ? (int) ceil($baseCost * (1 - $discount))
            : $baseCost;

        // FAC-03 — Hostile chez les Marchands : la boutique vend toujours (le
        // plancher T1 est un droit, jamais gate), mais elle fait payer la
        // rancune. Une surcharge, pas un refus.
        $hostileSurcharge = $this->hostileConsequences->shopSurchargePercent($player);
        if ($hostileSurcharge > 0) {
            // Arithmetique entiere (plafond) : 100 * 1.1 en flottant vaut
            // 110,000...01 et son ceil() facturerait 111.
            $totalCost = intdiv($totalCost * (100 + $hostileSurcharge) + 99, 100);
        }

        if ($player->getGils() < $totalCost) {
            return new JsonResponse([
                'error' => sprintf('Pas assez de Gils ! (requis: %d, possédés: %d)', $totalCost, $player->getGils()),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Debit gils
        $player->removeGils($totalCost);

        // Collect region tax for controlling guild treasury
        $taxAmount = $this->regionBonusProvider->getTaxAmount($baseCost, $player->getMap());
        $controllingGuild = $this->regionBonusProvider->getControllingGuild($player->getMap());
        if ($taxAmount > 0 && $controllingGuild !== null) {
            $controllingGuild->addGilsTreasury($taxAmount);
            $this->entityManager->persist($controllingGuild);
        }

        // Decrement stock
        $pnj->decrementStock($itemSlug, $quantity);

        // Add item to player bag inventory
        $bag = $this->playerHelper->getBagInventory();
        for ($i = 0; $i < $quantity; ++$i) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($item);
            $playerItem->setInventory($bag);
            $playerItem->setGear(0);
            if ($item->isBoundOnPickup()) {
                $playerItem->setBoundToPlayerId($player->getId());
            }
            $this->entityManager->persist($playerItem);
        }

        $this->entityManager->persist($player);
        $this->entityManager->persist($pnj);
        $this->entityManager->flush();

        $remainingStock = $pnj->getItemStock($itemSlug);

        $message = sprintf('Vous avez acheté %dx %s pour %d Gils.', $quantity, $item->getName(), $totalCost);
        if ($discount > 0) {
            $parts = [];
            if ($guildDiscount > 0) {
                $parts[] = sprintf('-%d%% guilde', (int) ($guildDiscount * 100));
            }
            if ($renownDiscount > 0) {
                $parts[] = sprintf('-%d%% renommée', (int) ($renownDiscount * 100));
            }
            if ($parts !== []) {
                $message .= ' (' . implode(', ', $parts) . ')';
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'gils' => $player->getGils(),
            'stock' => $remainingStock,
        ]);
    }

    #[Route('/{id}/sell', name: 'app_game_shop_sell', methods: ['POST'])]
    public function sell(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->gameMasterPolicy->canTrade($this->playerHelper->getPlayer())) {
            return new JsonResponse(['error' => GameMasterPolicy::REASON_TRADE], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $playerItemId = $data['playerItemId'] ?? null;

        if (!$playerItemId) {
            return new JsonResponse(['error' => 'Item invalide'], Response::HTTP_BAD_REQUEST);
        }

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find((int) $playerItemId);
        if (!$playerItem) {
            return new JsonResponse(['error' => 'Objet introuvable'], Response::HTTP_NOT_FOUND);
        }

        $player = $this->playerHelper->getPlayer();
        $item = $playerItem->getGenericItem();

        // Soulbound items cannot be sold
        if ($playerItem->isBound()) {
            return new JsonResponse([
                'error' => 'Cet objet est lié à votre personnage et ne peut pas être vendu.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // MET-01 — le rachat commun (30 % du prix de reference) porte la bande
        // du lot : un parfait vaut 9 fois le trouble de la meme matiere, au
        // guichet comme a l'ecran.
        $sellPrice = $this->purityPricer->buybackValueOf($playerItem);

        // FAC-04a — le plancher d'achat du cristal : au comptoir de la
        // Fonderie, l'amethystite a un prix garanti, jamais inferieur au taux
        // commun. Miroir du plancher T1 (ECO-02), cote vente. La garantie est
        // celle d'un lot trouble ; la bande la porte avec elle (MET-01).
        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        $floor = null !== $pnj ? $this->buybackFloor->floorFor($pnj, $item, $player) : null;
        if (null !== $floor) {
            $sellPrice = max($sellPrice, $this->purityPricer->apply($floor, $playerItem->getPurity()));
        }

        // FAC-06 — le receleur : au guichet des Ruelles, la nuit, un vendeur
        // Ami passe un lot au marche gris — prix moins la coupe de la
        // Confrerie, hors taxe de cite. Le refus (plafond, palier, jour) ne
        // ferme jamais la vente : le repli est le rachat commun. L'objet est
        // echangeable ici : la garde de liaison ci-dessus a deja refuse.
        // La coupe se prend sur le prix de reference ; la bande s'applique
        // ensuite (MET-01), pour que le rapport entre bandes reste exact.
        $fencePrice = $this->shadowsMarket->fencePriceFor($pnj, $item, $player, true);
        if (null !== $fencePrice) {
            $fencePrice = max(1, $this->purityPricer->apply($fencePrice, $playerItem->getPurity()));
        }
        $isFenceSale = null !== $fencePrice && $fencePrice > $sellPrice;
        if ($isFenceSale) {
            $sellPrice = $fencePrice;
        }

        $player->addGils($sellPrice);
        $this->entityManager->remove($playerItem);
        $this->entityManager->persist($player);

        if ($isFenceSale) {
            // Le lot compte apres la vente reussie, jamais avant — et le geste
            // nourrit la Confrerie (route grey_market_sale, FAC-02).
            $this->shadowsMarket->recordFenceSale($player);
        }

        $this->entityManager->flush();

        if ($isFenceSale) {
            $this->reputationManager->grantGestureReputation($player, 'grey_market_sale');
        }

        return new JsonResponse([
            'success' => true,
            'message' => $isFenceSale
                ? $this->translator->trans('game.shadows.fence.sold', ['%gils%' => $sellPrice])
                : sprintf('Vous avez vendu %s pour %d Gils.', $item->getName(), $sellPrice),
            'gils' => $player->getGils(),
        ]);
    }

    /**
     * FAC-06 — acheter une rumeur au guichet des Ruelles. L'information est
     * une marchandise ; a un Hostile de la Confrerie, elle est fausse.
     */
    #[Route('/{id}/rumor', name: 'app_game_shop_rumor', methods: ['POST'])]
    public function rumor(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player || !$this->gameMasterPolicy->canTrade($player)) {
            return new JsonResponse(['error' => GameMasterPolicy::REASON_TRADE], Response::HTTP_FORBIDDEN);
        }

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj || !$this->isReachableFrom($pnj, $player)) {
            return new JsonResponse(['error' => 'Boutique introuvable'], Response::HTTP_NOT_FOUND);
        }

        try {
            $rumor = $this->shadowsRumors->buy($player, $pnj);
            $this->addFlash('success', $this->translator->trans($rumor['key'], $rumor['params']));
        } catch (ShadowsMarketException $e) {
            $this->addFlash('error', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('app_game_shop', ['id' => $id]);
    }

    /**
     * FAC-08 — prendre un ballot de contrebande au guichet. La destination
     * est l'autre guichet de la Confrerie, la cargaison vit dans le contrat.
     */
    #[Route('/{id}/smuggle/accept', name: 'app_game_shop_smuggle_accept', methods: ['POST'])]
    public function smuggleAccept(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player || !$this->gameMasterPolicy->canTrade($player)) {
            return new JsonResponse(['error' => GameMasterPolicy::REASON_TRADE], Response::HTTP_FORBIDDEN);
        }
        if (!$this->isCsrfTokenValid('shadows_smuggle', (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_game_shop', ['id' => $id]);
        }

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj || !$this->isReachableFrom($pnj, $player)) {
            return new JsonResponse(['error' => 'Boutique introuvable'], Response::HTTP_NOT_FOUND);
        }

        try {
            $contract = $this->shadowsSmuggling->accept($player, $pnj);
            $this->addFlash('success', $this->translator->trans('game.shadows.smuggling.accepted', [
                '%cargo%' => $contract->getCargoLabel(),
            ]));
        } catch (ShadowsMarketException $e) {
            $this->addFlash('error', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('app_game_shop', ['id' => $id]);
    }

    /**
     * FAC-08 — livrer le ballot a l'autre guichet, de nuit.
     */
    #[Route('/{id}/smuggle/deliver', name: 'app_game_shop_smuggle_deliver', methods: ['POST'])]
    public function smuggleDeliver(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player || !$this->gameMasterPolicy->canTrade($player)) {
            return new JsonResponse(['error' => GameMasterPolicy::REASON_TRADE], Response::HTTP_FORBIDDEN);
        }
        if (!$this->isCsrfTokenValid('shadows_smuggle', (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_game_shop', ['id' => $id]);
        }

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj || !$this->isReachableFrom($pnj, $player)) {
            return new JsonResponse(['error' => 'Boutique introuvable'], Response::HTTP_NOT_FOUND);
        }

        try {
            $gils = $this->shadowsSmuggling->deliver($player, $pnj);
            $this->addFlash('success', $this->translator->trans('game.shadows.smuggling.delivered', [
                '%gils%' => $gils,
            ]));
        } catch (ShadowsMarketException $e) {
            $this->addFlash('error', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('app_game_shop', ['id' => $id]);
    }

    /**
     * FAC-08 — placer une contrefacon via le contact. Deux issues, jamais de
     * retour : ecoulee, ou saisie (amende + decote Chevaliers).
     */
    #[Route('/{id}/place/{itemId}', name: 'app_game_shop_place', methods: ['POST'])]
    public function placeCounterfeit(int $id, int $itemId, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player || !$this->gameMasterPolicy->canTrade($player)) {
            return new JsonResponse(['error' => GameMasterPolicy::REASON_TRADE], Response::HTTP_FORBIDDEN);
        }
        if (!$this->isCsrfTokenValid('shadows_place', (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_game_shop', ['id' => $id]);
        }

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj || !$this->isReachableFrom($pnj, $player)) {
            return new JsonResponse(['error' => 'Boutique introuvable'], Response::HTTP_NOT_FOUND);
        }

        $item = null;
        foreach ($this->playerHelper->getMateriaInventory()->getItems() as $candidate) {
            if ($candidate->getId() === $itemId) {
                $item = $candidate;
                break;
            }
        }
        if (null === $item) {
            $this->addFlash('error', $this->translator->trans('game.shadows.placement.error.item'));

            return $this->redirectToRoute('app_game_shop', ['id' => $id]);
        }

        try {
            $result = $this->shadowsPlacement->place($player, $pnj, $item);
            if ($result['caught']) {
                $this->addFlash('warning', $this->translator->trans('game.shadows.placement.caught', [
                    '%fine%' => abs($result['gils']),
                ]));
            } else {
                $this->addFlash('success', $this->translator->trans('game.shadows.placement.sold', [
                    '%gils%' => $result['gils'],
                ]));
            }
        } catch (ShadowsMarketException $e) {
            $this->addFlash('error', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('app_game_shop', ['id' => $id]);
    }

    /**
     * @return Item[]
     */
    private function getShopItems(Pnj $pnj): array
    {
        $slugs = $pnj->getShopItems();
        if (!$slugs) {
            return [];
        }

        return $this->entityManager->getRepository(Item::class)->findBy(['slug' => $slugs]);
    }

    /**
     * La boutique d'un PNJ n'est accessible que depuis sa zone (ZON-27).
     *
     * Les PNJ sans zone (donnees heritees, cartes hors graphe) restent
     * joignables : le pivot n'a pas rattache retroactivement tout le monde, et
     * refuser par defaut couperait des boutiques encore valides.
     */
    private function isReachableFrom(Pnj $pnj, ?Player $player): bool
    {
        $pnjZone = $pnj->getZone();
        if (null === $pnjZone) {
            return true;
        }

        return $player?->getCurrentZone()?->getId() === $pnjZone->getId();
    }
}
