<?php

namespace App\Controller\Game;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\PlayerShop;
use App\Entity\App\ShopListing;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Housing\HousingManager;
use App\GameEngine\Shop\ShopManager;
use App\GameEngine\Shop\ShopRentService;
use App\GameEngine\Shop\ShopSaleService;
use App\GameEngine\Shop\ShopSearchService;
use App\GameEngine\Shop\ShopStallService;
use App\Helper\PlayerHelper;
use App\Repository\CrafterReputationRepository;
use App\Repository\ShopListingRepository;
use App\Repository\ShopSaleLogRepository;
use App\Security\Attribute\RequiresVerifiedEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Echoppes joueur — gestion et vitrine (ECO-12).
 *
 * A ne pas confondre avec {@see ShopController}, qui sert les boutiques **PNJ**
 * sur `/game/shop`. Celles-ci sont tenues par des joueurs et vivent sur
 * `/game/shops`.
 *
 * ECO-10 et ECO-11 ont pose les entites, le gardien, l'escrow, la vente et le
 * loyer. Rien de tout cela n'etait **atteignable** : sans ecran, une echoppe
 * est exactement le genre de fonctionnalite declarative et inerte que cette
 * campagne passe son temps a reparer ailleurs.
 */
#[Route('/game/shops')]
#[IsGranted('ROLE_USER')]
class PlayerShopController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly ShopManager $shopManager,
        private readonly ShopSaleService $saleService,
        private readonly ShopRentService $rentService,
        private readonly ShopStallService $stallService,
        private readonly ShopSearchService $searchService,
        private readonly HousingManager $housingManager,
        private readonly ShopListingRepository $listingRepository,
        private readonly ShopSaleLogRepository $saleLogRepository,
        private readonly CrafterReputationRepository $reputationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameMasterPolicy $gameMasterPolicy,
    ) {
    }

    #[Route('', name: 'app_game_player_shop', methods: ['GET'])]
    public function index(): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        $shop = $this->shopManager->getShop($player);

        return $this->render('game/player_shop/index.html.twig', [
            'player' => $player,
            'shop' => $shop,
            'listings' => null !== $shop ? $this->listingRepository->findForShop($shop) : [],
            'sales' => null !== $shop ? $this->saleLogRepository->findRecentForShop($shop) : [],
            // Les deux gardiens d'ouverture, exposes pour que le refus soit
            // comprehensible **avant** d'appuyer sur le bouton.
            'house' => $this->housingManager->getHouse($player),
            'craftLevel' => $this->shopManager->bestCraftLevel($player),
            'minCraftLevel' => PlayerShop::MIN_CRAFT_LEVEL,
            'rentAmount' => ShopRentService::RENT_AMOUNT,
            // La reputation n'est pas dupliquee sur l'echoppe : elle vit par
            // metier depuis ECO-08b (cf. ECO-10).
            'reputations' => $this->reputationRepository->findForPlayer($player),
            'sellables' => $this->sellableItems($player),
            // ECO-13 : les emplacements au-dela des six de base sont loues a
            // la cite. La place du marche est finie — c'est ce qui en fait un
            // actif et non un simple gold sink.
            'stallPrice' => null !== $shop ? $this->stallService->nextStallPrice($shop) : null,
            'stallsLeft' => null !== $shop ? $this->stallService->remainingStalls($shop) : 0,
            'maxSlots' => PlayerShop::MAX_SLOTS,
        ]);
    }

    /**
     * Recherche transversale (ECO-12b).
     *
     * Declaree **avant** `/{id}` : la contrainte `\d+` suffirait a les
     * departager, mais l'ordre de lecture doit refleter l'ordre de matching.
     */
    #[Route('/search', name: 'app_game_player_shop_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        $query = trim((string) $request->query->get('q', ''));

        return $this->render('game/player_shop/search.html.twig', [
            'player' => $player,
            'query' => $query,
            'onSale' => $this->searchService->findOnSale($query),
            // Quand personne ne vend, on renvoie vers ceux qui savent faire :
            // un resultat vide qui n'ouvre sur rien fait cesser la recherche.
            'crafters' => $this->searchService->findCrafters($query),
        ]);
    }

    /**
     * Vitrine d'un artisan, vue par les autres.
     */
    #[Route('/{id}', name: 'app_game_player_shop_visit', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function visit(PlayerShop $shop): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        return $this->render('game/player_shop/visit.html.twig', [
            'player' => $player,
            'shop' => $shop,
            'listings' => $this->listingRepository->findForShop($shop),
            'reputations' => $this->reputationRepository->findForPlayer($shop->getOwner()),
            // Acheter suppose d'etre sur place (regle #7) : le dire avant le
            // clic evite un refus incomprehensible.
            'onSite' => $player->getCurrentZone()?->getSlug() === $shop->getZone()->getSlug(),
            'isOwn' => $shop->getOwner()->getId() === $player->getId(),
        ]);
    }

    #[RequiresVerifiedEmail(channel: 'shop')]
    #[Route('/open', name: 'app_game_player_shop_open', methods: ['POST'])]
    public function open(Request $request): Response
    {
        return $this->act($request, 'shop_open', function (Player $player) use ($request): string {
            $shop = $this->shopManager->open($player, (string) $request->request->get('name', ''));

            return sprintf('« %s » a ouvert ses portes.', $shop->getName());
        });
    }

    #[RequiresVerifiedEmail(channel: 'shop')]
    #[Route('/sign', name: 'app_game_player_shop_sign', methods: ['POST'])]
    public function sign(Request $request): Response
    {
        return $this->act($request, 'shop_sign', function (Player $player) use ($request): string {
            $this->shopManager->setSign($player, $this->requireShop($player), (string) $request->request->get('sign', ''));

            return 'Enseigne repeinte.';
        });
    }

    #[RequiresVerifiedEmail(channel: 'shop')]
    #[Route('/stock', name: 'app_game_player_shop_stock', methods: ['POST'])]
    public function stock(Request $request): Response
    {
        return $this->act($request, 'shop_stock', function (Player $player) use ($request): string {
            $item = $this->entityManager->getRepository(PlayerItem::class)->find($request->request->getInt('player_item_id'));
            if (!$item instanceof PlayerItem) {
                throw new \InvalidArgumentException('Objet introuvable.');
            }

            $this->shopManager->stock(
                $player,
                $this->requireShop($player),
                $item,
                $request->request->getInt('quantity', 1),
                $request->request->getInt('unit_price'),
            );

            return sprintf('%s est en vitrine.', $item->getGenericItem()->getName());
        });
    }

    #[Route('/listing/{id}/unstock', name: 'app_game_player_shop_unstock', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function unstock(ShopListing $listing, Request $request): Response
    {
        return $this->act($request, 'shop_unstock_' . $listing->getId(), function (Player $player) use ($listing): string {
            $this->shopManager->unstock($player, $listing);

            return 'Lot retire de la vitrine.';
        });
    }

    #[RequiresVerifiedEmail(channel: 'shop')]
    #[Route('/listing/{id}/buy', name: 'app_game_player_shop_buy', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function buy(ShopListing $listing, Request $request): Response
    {
        $shopId = $listing->getShop()->getId();

        return $this->act($request, 'shop_buy_' . $listing->getId(), function (Player $player) use ($listing): string {
            $log = $this->saleService->buy($player, $listing);

            return sprintf('%s achete pour %d Gils.', $log->getItemName(), $log->getGrossGils());
        }, 'app_game_player_shop_visit', ['id' => $shopId]);
    }

    #[RequiresVerifiedEmail(channel: 'shop')]
    #[Route('/vault', name: 'app_game_player_shop_vault', methods: ['POST'])]
    public function vault(Request $request): Response
    {
        return $this->act($request, 'shop_vault', function (Player $player): string {
            return sprintf('%d Gils encaisses.', $this->saleService->collectVault($player, $this->requireShop($player)));
        });
    }

    #[RequiresVerifiedEmail(channel: 'shop')]
    #[Route('/rent', name: 'app_game_player_shop_rent', methods: ['POST'])]
    public function rent(Request $request): Response
    {
        return $this->act($request, 'shop_rent', function (Player $player): string {
            $shop = $this->requireShop($player);
            $this->rentService->payRent($player, $shop);

            return sprintf('Loyer regle. Prochaine echeance le %s.', $shop->getRentDueAt()?->format('d/m/Y') ?? '—');
        });
    }

    #[RequiresVerifiedEmail(channel: 'shop')]
    #[Route('/stall', name: 'app_game_player_shop_stall', methods: ['POST'])]
    public function stall(Request $request): Response
    {
        return $this->act($request, 'shop_stall', function (Player $player): string {
            $price = $this->stallService->leaseStall($player, $this->requireShop($player));

            return sprintf('Etal loue pour %s Gils — il revient a la guilde qui tient la cite.', number_format($price, 0, ',', ' '));
        });
    }

    #[RequiresVerifiedEmail(channel: 'shop')]
    #[Route('/shutters', name: 'app_game_player_shop_shutters', methods: ['POST'])]
    public function shutters(Request $request): Response
    {
        return $this->act($request, 'shop_shutters', function (Player $player) use ($request): string {
            $open = $request->request->getBoolean('open');
            $this->shopManager->setOpen($player, $this->requireShop($player), $open);

            return $open ? 'Rideau leve.' : 'Rideau baisse.';
        });
    }

    /**
     * Enveloppe commune : jeton CSRF, joueur present, message de succes ou
     * d'echec, retour a l'ecran.
     *
     * @param callable(Player): string $action
     * @param array<string, mixed>     $routeParams
     */
    private function act(Request $request, string $token, callable $action, string $route = 'app_game_player_shop', array $routeParams = []): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid($token, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute($route, $routeParams);
        }

        // Toutes les ecritures de l'ecran passent par ici : tenir une echoppe,
        // l'approvisionner, y acheter, en encaisser le coffre. Un MJ visite les
        // echoppes — l'index, la recherche et la visite restent ouverts — mais
        // n'y touche a rien. Le controle est donc pose une fois, et non sur
        // chacune des neuf routes.
        if (!$this->gameMasterPolicy->canTrade($player)) {
            $this->addFlash('error', GameMasterPolicy::REASON_TRADE);

            return $this->redirectToRoute($route, $routeParams);
        }

        try {
            $this->addFlash('success', $action($player));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute($route, $routeParams);
    }

    private function requireShop(Player $player): PlayerShop
    {
        $shop = $this->shopManager->getShop($player);
        if (null === $shop) {
            throw new \InvalidArgumentException('Vous ne tenez pas d\'echoppe.');
        }

        return $shop;
    }

    /**
     * Objets exposables : dans le sac, ni lies ni equipes.
     *
     * @return PlayerItem[]
     */
    private function sellableItems(Player $player): array
    {
        $items = [];
        foreach ($player->getInventories() as $inventory) {
            if (Inventory::TYPE_BAG !== $inventory->getType()) {
                continue;
            }
            foreach ($inventory->getItems() as $item) {
                // FAC-07 : l'echoppe vend a des joueurs — une contrefacon ne
                // s'y expose jamais, le verrou metier est dans ShopManager.
                if ($item->isExchangeable() && !$item->isCounterfeit()) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }
}
