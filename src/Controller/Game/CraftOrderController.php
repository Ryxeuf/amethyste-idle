<?php

namespace App\Controller\Game;

use App\Entity\App\CraftOrder;
use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Enum\Purity;
use App\GameEngine\Crafting\CraftOrderManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\Helper\PlayerHelper;
use App\Repository\CrafterReputationRepository;
use App\Repository\CraftOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Tableau de commandes de craft (ECO-06).
 *
 * Canal **anonyme** : n'importe quel artisan qualifie de la region peut prendre
 * une commande ouverte. C'est ce qui le distingue de la commande directe
 * (ECO-07) — ici, le commanditaire ne choisit pas son artisan, il choisit son
 * prix.
 */
#[Route('/game/craft-order')]
class CraftOrderController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly CraftOrderRepository $orderRepository,
        private readonly CrafterReputationRepository $reputationRepository,
        private readonly CraftOrderManager $orderManager,
        private readonly PlayerRegionResolver $regionResolver,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_craft_order', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        $craft = trim((string) $request->query->get('craft', ''));
        // Le tableau est local, comme le marche : on ne prend pas une commande
        // d'une region ou l'on ne se trouve pas (ECO-03).
        $region = $this->regionResolver->resolve($player);

        return $this->render('game/craft_order/index.html.twig', [
            'player' => $player,
            'region' => $region,
            'craft' => $craft,
            'crafts' => array_keys(Item::CRAFT_TOOL_TYPES),
            'orders' => $this->orderRepository->findOpenInRegion($region, '' !== $craft ? $craft : null),
            // ECO-28 : la section des services, a part — la piece du client
            // ne se filtre pas par metier de recette.
            'serviceOrders' => $this->orderRepository->findOpenServiceInRegion($region),
        ]);
    }

    #[Route('/mine', name: 'app_game_craft_order_mine', methods: ['GET'])]
    public function mine(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        return $this->render('game/craft_order/mine.html.twig', [
            'player' => $player,
            'orders' => $this->orderRepository->findActiveByRequester($player),
        ]);
    }

    /**
     * L'atelier : les commandes que j'ai prises et dois livrer (ECO-07).
     */
    #[Route('/workshop', name: 'app_game_craft_order_workshop', methods: ['GET'])]
    public function workshop(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        return $this->render('game/craft_order/workshop.html.twig', [
            'player' => $player,
            'orders' => $this->orderRepository->findClaimedByCrafter($player),
            // ECO-07b : les commandes qui m'ont ete adressees nommement.
            'directOrders' => $this->orderRepository->findOpenDirectFor($player),
        ]);
    }

    #[Route('/{id}/fulfill', name: 'app_game_craft_order_fulfill', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function fulfill(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('craft_order_fulfill_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_craft_order_workshop');
        }

        $order = $this->orderRepository->find($id);
        if (!$order instanceof CraftOrder) {
            $this->addFlash('error', 'Commande introuvable.');

            return $this->redirectToRoute('app_game_craft_order_workshop');
        }

        try {
            $settlement = $this->orderManager->fulfillOrder($player, $order);

            // null = la piece n'atteignait pas la qualite demandee : l'artisan la
            // retravaille. Ce n'est pas un echec, c'est le metier.
            if (null === $settlement) {
                $this->addFlash('warning', sprintf(
                    'La piece n\'atteint pas la qualite demandee (%s) : vous la retravaillez.',
                    $order->getMinQuality() ?? '',
                ));

                return $this->redirectToRoute('app_game_craft_order_workshop');
            }

            // ECO-28 : un service rend la piece du client, pas un objet neuf.
            $delivered = $order->isService()
                ? $order->getTargetItem()?->getGenericItem()->getName() ?? 'piece'
                : $order->getRecipe()?->getResult()->getName() ?? '';
            $this->addFlash('success', sprintf(
                'Commande livree : %s remis a %s, %d Gils encaisses (%d de taxe de region).',
                $delivered,
                $order->getRequester()->getName(),
                $settlement->sellerRevenue,
                $settlement->taxAmount,
            ));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_craft_order_workshop');
    }

    /**
     * Classement des artisans par metier (ECO-08b).
     *
     * C'est l'ecran qui donne son sens a la commande directe : sans lui, nommer
     * un artisan supposerait de le connaitre deja.
     */
    #[Route('/artisans', name: 'app_game_craft_order_artisans', methods: ['GET'])]
    public function artisans(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        $craft = trim((string) $request->query->get('craft', ''));

        return $this->render('game/craft_order/artisans.html.twig', [
            'player' => $player,
            'craft' => $craft,
            'crafts' => array_keys(Item::CRAFT_TOOL_TYPES),
            'reputations' => $this->reputationRepository->findTopByCraft('' !== $craft ? $craft : null),
            'mine' => $this->reputationRepository->findForPlayer($player),
        ]);
    }

    #[Route('/new', name: 'app_game_craft_order_new', methods: ['GET'])]
    public function newForm(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        // Le commanditaire commande ce qu'il ne sait pas faire : toutes les
        // recettes sont proposees, pas seulement les siennes.
        $recipes = $this->entityManager->getRepository(Recipe::class)->findBy([], ['craft' => 'ASC', 'requiredLevel' => 'ASC']);

        return $this->render('game/craft_order/new.html.twig', [
            'player' => $player,
            'recipes' => $recipes,
            'region' => $this->regionResolver->resolve($player),
            // ECO-12b : la vitrine d'un artisan renvoie ici avec son nom. Le
            // champ reste editable — c'est une suggestion, pas un verrou.
            'targetCrafter' => trim((string) $request->query->get('crafter', '')),
            // ECO-23 : exiger une bande donne au prospecteur un **client**, pas
            // seulement un marche.
            'purities' => Purity::ordered(),
            // ECO-28 : les pieces du sac qu'un sertissage peut encore ouvrir,
            // et le compte d'amethystite Pure disponible.
            'serviceablePieces' => $this->serviceablePieces(),
            'pureCrystals' => $this->countPureCrystals(),
            'serviceCrystalCost' => CraftOrderManager::SERVICE_CRYSTAL_COST,
        ]);
    }

    #[Route('/new', name: 'app_game_craft_order_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('craft_order_create', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_craft_order_new');
        }

        $recipe = $this->entityManager->getRepository(Recipe::class)->find($request->request->getInt('recipe_id'));
        if (!$recipe instanceof Recipe) {
            $this->addFlash('error', 'Recette introuvable.');

            return $this->redirectToRoute('app_game_craft_order_new');
        }

        $materials = $this->orderManager->collectMaterials($player, $recipe);
        if ([] === $materials) {
            $this->addFlash('error', 'Votre sac ne contient pas les materiaux de cette recette.');

            return $this->redirectToRoute('app_game_craft_order_new');
        }

        // ECO-07b : un nom d'artisan transforme la commande en commande directe.
        $targetName = trim((string) $request->request->get('target_crafter', ''));
        $targetCrafter = null;
        if ('' !== $targetName) {
            $targetCrafter = $this->entityManager->getRepository(Player::class)->findOneBy(['name' => $targetName]);
            if (!$targetCrafter instanceof Player) {
                $this->addFlash('error', sprintf('Aucun personnage nomme « %s ».', $targetName));

                return $this->redirectToRoute('app_game_craft_order_new');
            }
        }

        try {
            $minQuality = trim((string) $request->request->get('min_quality', ''));

            $this->orderManager->createOrder(
                $player,
                $recipe,
                $materials,
                $request->request->getInt('commission'),
                '' !== $minQuality ? $minQuality : null,
                targetCrafter: $targetCrafter,
                minPurity: Purity::tryFrom((string) $request->request->get('min_purity', '')),
            );
            $this->addFlash('success', null !== $targetCrafter
                ? sprintf('Commande adressee a %s : materiaux et commission sont bloques jusqu\'a la livraison.', $targetCrafter->getName())
                : 'Commande publiee : materiaux et commission sont bloques jusqu\'a la livraison.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_game_craft_order_new');
        }

        return $this->redirectToRoute('app_game_craft_order_mine');
    }

    /**
     * ECO-28 : deposer une commande de **service** — le sertissage d'une piece.
     */
    #[Route('/service/new', name: 'app_game_craft_order_create_service', methods: ['POST'])]
    public function createService(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('craft_order_create_service', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_craft_order_new');
        }

        $target = null;
        $targetId = $request->request->getInt('target_item_id');
        foreach ($this->playerHelper->getBagInventory()->getItems() as $playerItem) {
            if ($playerItem->getId() === $targetId) {
                $target = $playerItem;
                break;
            }
        }
        if (null === $target) {
            $this->addFlash('error', 'Cette piece est introuvable dans votre sac.');

            return $this->redirectToRoute('app_game_craft_order_new');
        }

        $targetName = trim((string) $request->request->get('target_crafter', ''));
        $targetCrafter = null;
        if ('' !== $targetName) {
            $targetCrafter = $this->entityManager->getRepository(Player::class)->findOneBy(['name' => $targetName]);
            if (!$targetCrafter instanceof Player) {
                $this->addFlash('error', sprintf('Aucun personnage nomme « %s ».', $targetName));

                return $this->redirectToRoute('app_game_craft_order_new');
            }
        }

        try {
            $this->orderManager->createServiceOrder(
                $player,
                $target,
                $request->request->getInt('commission'),
                $targetCrafter,
            );
            $this->addFlash('success', 'Commande de sertissage deposee : la piece, l\'amethystite et la commission sont bloquees jusqu\'a la livraison.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_game_craft_order_new');
        }

        return $this->redirectToRoute('app_game_craft_order_mine');
    }

    /**
     * Les pieces du sac qu'un sertissage peut encore ouvrir : equipement
     * desequipe dont la forme declare plus d'emplacements que la piece n'en
     * porte. La liaison n'exclut rien — c'est le canal fait pour elle.
     *
     * @return list<\App\Entity\App\PlayerItem>
     */
    private function serviceablePieces(): array
    {
        $pieces = [];
        foreach ($this->playerHelper->getBagInventory()->getItems() as $playerItem) {
            $generic = $playerItem->getGenericItem();
            if (!$generic->isGear() || 0 !== $playerItem->getGear()) {
                continue;
            }
            if ($playerItem->getSlots()->count() >= $generic->getMateriaSlots()) {
                continue;
            }
            $pieces[] = $playerItem;
        }

        return $pieces;
    }

    private function countPureCrystals(): int
    {
        $count = 0;
        foreach ($this->playerHelper->getBagInventory()->getItems() as $playerItem) {
            if ('ore-amethyst-crystal' !== $playerItem->getGenericItem()->getSlug() || !$playerItem->isExchangeable()) {
                continue;
            }
            $purity = $playerItem->getPurity();
            if (null !== $purity && $purity->isAtLeast(Purity::Pur)) {
                ++$count;
            }
        }

        return $count;
    }

    #[Route('/{id}/claim', name: 'app_game_craft_order_claim', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function claim(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('craft_order_claim_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_craft_order');
        }

        $order = $this->orderRepository->find($id);
        if (!$order instanceof CraftOrder) {
            $this->addFlash('error', 'Commande introuvable.');

            return $this->redirectToRoute('app_game_craft_order');
        }

        // Une commande directe se prend depuis l'atelier : y renvoyer evite de
        // faire atterrir l'artisan sur un tableau ou elle ne figure pas.
        $back = $order->isDirect() ? 'app_game_craft_order_workshop' : 'app_game_craft_order';

        try {
            $this->orderManager->claimOrder($player, $order);
            $this->addFlash('success', 'Commande prise en charge : elle vous est desormais reservee.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute($back);
    }

    #[Route('/{id}/cancel', name: 'app_game_craft_order_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('craft_order_cancel_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_craft_order_mine');
        }

        $order = $this->orderRepository->find($id);
        if (!$order instanceof CraftOrder) {
            $this->addFlash('error', 'Commande introuvable.');

            return $this->redirectToRoute('app_game_craft_order_mine');
        }

        try {
            $this->orderManager->cancelOrder($player, $order);
            $this->addFlash('success', 'Commande annulee : materiaux et commission vous sont rendus.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_craft_order_mine');
    }
}
