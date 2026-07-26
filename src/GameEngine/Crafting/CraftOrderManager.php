<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\CraftOrder;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Recipe;
use App\Enum\CraftOrderStatus;
use App\GameEngine\Auction\AuctionAntiExploit;
use App\GameEngine\Region\PlayerRegionResolver;
use App\Repository\CraftOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cycle de vie des commandes de craft (ECO-05).
 *
 * Le troisieme canal d'echange : le commanditaire fournit les materiaux et la
 * commission, l'artisan fournit le plan et le savoir-faire.
 *
 * **L'escrow est pose des deux cotes a la creation.** Sans cela, un artisan
 * pourrait prendre une commande, la travailler — le temps de craft etant reel —
 * et decouvrir a la livraison que le client a revendu les materiaux entre-temps.
 * La fenetre d'abus serait exactement la duree du craft.
 */
class CraftOrderManager
{
    /** Duree de vie d'une commande non prise en charge. */
    public const DEFAULT_DURATION_HOURS = 72;

    /**
     * Commandes vivantes simultanees par commanditaire.
     *
     * Le plafond n'est pas cosmetique : chaque commande immobilise des materiaux
     * et des Gils. Sans limite, un joueur pourrait assecher le marche en ouvrant
     * des centaines de commandes qu'il annulerait ensuite.
     */
    public const MAX_ACTIVE_ORDERS = 10;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CraftOrderRepository $orderRepository,
        private readonly PlayerRegionResolver $regionResolver,
        private readonly CraftingManager $craftingManager,
        private readonly AuctionAntiExploit $antiExploit,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Ouvre une commande, materiaux et commission bloques.
     *
     * @param list<PlayerItem> $materials materiaux preleves dans le sac du commanditaire
     */
    public function createOrder(
        Player $requester,
        Recipe $recipe,
        array $materials,
        int $commission,
        ?string $minQuality = null,
        int $durationHours = self::DEFAULT_DURATION_HOURS,
    ): CraftOrder {
        if ($commission < 1) {
            throw new \InvalidArgumentException('La commission doit etre superieure a 0.');
        }

        if ([] === $materials) {
            throw new \InvalidArgumentException('Une commande doit fournir des materiaux.');
        }

        if ($this->orderRepository->countActiveByRequester($requester) >= self::MAX_ACTIVE_ORDERS) {
            throw new \InvalidArgumentException(sprintf('Vous avez deja %d commandes en cours.', self::MAX_ACTIVE_ORDERS));
        }

        $this->assertMaterialsBelongTo($requester, $materials);
        $this->assertMaterialsCoverRecipe($recipe, $materials);

        // La commission part **avant** la creation : si la bourse ne suit pas,
        // rien n'est engage et les materiaux restent en place.
        if (!$requester->removeGils($commission)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour la commission.');
        }

        $order = new CraftOrder();
        $order->setRequester($requester);
        $order->setRecipe($recipe);
        $order->setCommission($commission);
        $order->setMinQuality($minQuality);
        $order->setRegion($this->regionResolver->resolve($requester));
        $order->setStatus(CraftOrderStatus::Open);
        $order->setExpiresAt(new \DateTimeImmutable(sprintf('+%d hours', max(1, $durationHours))));

        foreach ($materials as $material) {
            // L'objet quitte l'inventaire : c'est ce qui rend l'escrow reel.
            $material->setInventory(null);
            $order->addMaterial($material);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->logger->info('Craft order created', [
            'order_id' => $order->getId(),
            'requester_id' => $requester->getId(),
            'recipe' => $recipe->getSlug(),
            'commission' => $commission,
            'materials' => \count($materials),
            'region' => $order->getRegion()?->getSlug(),
        ]);

        return $order;
    }


    /**
     * Reserve dans le sac du commanditaire les objets couvrant la recette.
     *
     * Le client dit « je veux cet objet, voila ma commission » — c'est au jeu de
     * prelever les bons materiaux. Lui faire cocher chaque minerai un a un
     * n'ajouterait qu'une occasion de se tromper.
     *
     * @return list<PlayerItem> vide si le sac ne couvre pas la recette
     */
    public function collectMaterials(Player $requester, Recipe $recipe): array
    {
        $bag = $this->getBagInventory($requester);

        $needed = [];
        foreach ($recipe->getIngredients() as $ingredient) {
            if (\is_array($ingredient) && isset($ingredient['slug'])) {
                $needed[(string) $ingredient['slug']] = (int) ($ingredient['quantity'] ?? 1);
            }
        }

        $collected = [];
        foreach ($bag->getItems() as $playerItem) {
            $slug = $playerItem->getGenericItem()->getSlug();
            if (($needed[$slug] ?? 0) <= 0 || !$playerItem->isExchangeable()) {
                continue;
            }
            --$needed[$slug];
            $collected[] = $playerItem;
        }

        foreach ($needed as $remaining) {
            if ($remaining > 0) {
                return [];
            }
        }

        return $collected;
    }

    /**
     * Prise en charge par un artisan (ECO-06).
     *
     * Le verrou anti-double-prise repose sur le statut : la commande passe a
     * `claimed` et n'est plus servie par le tableau. Deux artisans qui cliquent
     * a la meme milliseconde restent theoriquement possibles — la parade
     * definitive est un verrou pessimiste, disproportionne ici : le perdant
     * recoit un refus explicite et n'a rien engage.
     */
    public function claimOrder(Player $crafter, CraftOrder $order): void
    {
        if (!$order->isOpen()) {
            throw new \InvalidArgumentException('Cette commande a deja ete prise en charge.');
        }

        if ($order->isExpired()) {
            throw new \InvalidArgumentException('Cette commande a expire.');
        }

        if ($order->getRequester()->getId() === $crafter->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez pas prendre en charge votre propre commande.');
        }

        // ECO-16b : la suspension ferme les canaux d'echange, celui-ci compris.
        if ($crafter->isTradeSuspended()) {
            throw new \InvalidArgumentException('Votre acces au marche est suspendu.');
        }

        // ECO-16a : le commerce entre personnages d'un meme compte n'a pas plus
        // de sens ici qu'a l'hotel des ventes — se commander a soi-meme du stuff
        // lie contournerait tout l'interet du canal.
        if ($this->antiExploit->isSameAccount($crafter, $order->getRequester())) {
            throw new \InvalidArgumentException('Vous ne pouvez pas honorer la commande d\'un autre de vos personnages.');
        }

        $this->assertSameMarket($crafter, $order);
        $this->assertQualified($crafter, $order);

        $order->setCrafter($crafter);
        $order->setStatus(CraftOrderStatus::Claimed);
        $order->setClaimedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Craft order claimed', [
            'order_id' => $order->getId(),
            'crafter_id' => $crafter->getId(),
            'recipe' => $order->getRecipe()->getSlug(),
        ]);
    }

    /**
     * Un artisan ne voit et ne prend que les commandes de la region ou il se
     * trouve — meme regle que l'hotel des ventes (ECO-03). Le filtre de l'ecran
     * n'est pas une regle metier.
     */
    private function assertSameMarket(Player $crafter, CraftOrder $order): void
    {
        if (!$this->regionResolver->isSameMarket($this->regionResolver->resolve($crafter), $order->getRegion())) {
            throw new \InvalidArgumentException('Cette commande appartient au tableau d\'une autre region : rendez-vous sur place.');
        }
    }

    /**
     * L'artisan sait-il faire ?
     *
     * Le controle reprend **exactement** les regles que l'ecran d'artisanat
     * applique (`CraftingManager`) : niveau de metier et specialisation. Pouvoir
     * prendre une commande qu'on ne saurait pas realiser a son etabli n'aurait
     * aucun sens.
     */
    private function assertQualified(Player $crafter, CraftOrder $order): void
    {
        $recipe = $order->getRecipe();

        $level = $this->craftingManager->getCraftingLevel($crafter, $recipe->getCraft());
        if ($level < $recipe->getRequiredLevel()) {
            throw new \InvalidArgumentException(sprintf('Niveau de %s insuffisant : %d requis, vous avez %d.', $recipe->getCraft(), $recipe->getRequiredLevel(), $level));
        }

        $required = $recipe->getRequiredSpecialization();
        if (null !== $required && $required !== $crafter->getCraftSpecialization()) {
            throw new \InvalidArgumentException('Cette recette exige une specialisation que vous n\'avez pas.');
        }
    }

    /**
     * Annulation par le commanditaire, possible tant que **personne n'a pris**
     * la commande : une fois un artisan engage, l'annuler unilateralement
     * reviendrait a lui faire perdre le travail deja fourni.
     */
    public function cancelOrder(Player $player, CraftOrder $order): void
    {
        if ($order->getRequester()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez annuler que vos propres commandes.');
        }

        if (!$order->isOpen()) {
            throw new \InvalidArgumentException($order->isClaimed() ? 'Un artisan a deja pris cette commande en charge.' : 'Cette commande n\'est plus active.');
        }

        $this->releaseEscrow($order, CraftOrderStatus::Cancelled);
        $this->entityManager->flush();

        $this->logger->info('Craft order cancelled', [
            'order_id' => $order->getId(),
            'requester_id' => $player->getId(),
        ]);
    }

    /**
     * Rend l'escrow au commanditaire et clot la commande.
     *
     * Materiaux **et** commission repartent ensemble : une restitution partielle
     * serait une spoliation silencieuse, et c'est le genre de bug qu'on ne voit
     * qu'en lisant les plaintes des joueurs.
     */
    public function releaseEscrow(CraftOrder $order, CraftOrderStatus $status): void
    {
        if (!$status->refundsEscrow()) {
            throw new \InvalidArgumentException('Cet etat ne restitue pas l\'escrow.');
        }

        $requester = $order->getRequester();
        $bag = $this->getBagInventory($requester);

        foreach ($order->getMaterials() as $material) {
            $material->setInventory($bag);
            $material->setCraftOrder(null);
        }

        $requester->addGils($order->getCommission());
        $order->setStatus($status);
    }

    /**
     * @param list<PlayerItem> $materials
     */
    private function assertMaterialsBelongTo(Player $requester, array $materials): void
    {
        foreach ($materials as $material) {
            if ($material->getInventory()?->getPlayer()?->getId() !== $requester->getId()) {
                throw new \InvalidArgumentException('Un materiau ne provient pas de votre inventaire.');
            }

            // ECO-01 : un objet lie ne circule pas, meme via une commande.
            if (!$material->isExchangeable()) {
                throw new \InvalidArgumentException('Un objet lie a son proprietaire ne peut pas etre confie a une commande.');
            }
        }
    }

    /**
     * Les materiaux fournis couvrent-ils la recette ?
     *
     * Le controle vit ici et non a l'execution : un artisan qui prend une
     * commande doit pouvoir la realiser. Decouvrir a la livraison qu'il manque
     * un minerai ferait perdre a l'artisan le temps de craft, pour une faute qui
     * n'est pas la sienne.
     *
     * @param list<PlayerItem> $materials
     */
    private function assertMaterialsCoverRecipe(Recipe $recipe, array $materials): void
    {
        $provided = [];
        foreach ($materials as $material) {
            $slug = $material->getGenericItem()->getSlug();
            $provided[$slug] = ($provided[$slug] ?? 0) + 1;
        }

        $missing = [];
        foreach ($recipe->getIngredients() as $ingredient) {
            if (!\is_array($ingredient) || !isset($ingredient['slug'])) {
                continue;
            }
            $slug = (string) $ingredient['slug'];
            $required = (int) ($ingredient['quantity'] ?? 1);
            $have = $provided[$slug] ?? 0;
            if ($have < $required) {
                $missing[] = sprintf('%s (%d/%d)', $slug, $have, $required);
            }
        }

        if ([] !== $missing) {
            throw new \InvalidArgumentException(sprintf('Materiaux insuffisants pour cette recette : %s.', implode(', ', $missing)));
        }
    }

    private function getBagInventory(Player $player): Inventory
    {
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->getType() === Inventory::TYPE_BAG) {
                return $inventory;
            }
        }

        throw new \RuntimeException('Le joueur n\'a pas d\'inventaire sac.');
    }
}
