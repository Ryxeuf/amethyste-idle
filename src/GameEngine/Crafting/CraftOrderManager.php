<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\CraftOrder;
use App\Entity\App\Guild;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Recipe;
use App\Enum\CraftOrderStatus;
use App\GameEngine\Auction\AuctionAntiExploit;
use App\GameEngine\Auction\AuctionSettlement;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\RegionBonusProvider;
use App\GameEngine\Guild\TownControlManager;
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

    /**
     * Delai de livraison accorde a un artisan a partir de sa prise en charge.
     *
     * Sans lui, l'echeance d'une commande prise resterait celle du tableau : un
     * artisan qui prend une commande a sa 71e heure aurait une heure pour
     * livrer, et serait sanctionne pour un delai qu'il n'a pas choisi.
     */
    public const DELIVERY_WINDOW_HOURS = 24;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CraftOrderRepository $orderRepository,
        private readonly PlayerRegionResolver $regionResolver,
        private readonly CraftingManager $craftingManager,
        private readonly AuctionAntiExploit $antiExploit,
        private readonly CrafterReputationManager $reputationManager,
        private readonly TownControlManager $townControlManager,
        private readonly GuildManager $guildManager,
        private readonly PlayerItemGenerator $playerItemGenerator,
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
        ?Player $targetCrafter = null,
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

        if (null !== $targetCrafter) {
            $this->assertTargetIsAcceptable($requester, $targetCrafter);
        }

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
        $order->setTargetCrafter($targetCrafter);
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
            'target_crafter_id' => $targetCrafter?->getId(),
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

        // ECO-07b : une commande directe est **adressee**, pas publiee. Le
        // controle vit ici et pas seulement dans la requete du tableau : sans
        // lui, une requete forgee avec l'identifiant d'une commande directe la
        // detournerait entierement.
        $target = $order->getTargetCrafter();
        if (null !== $target && $target->getId() !== $crafter->getId()) {
            throw new \InvalidArgumentException('Cette commande est adressee a un artisan en particulier.');
        }

        // ECO-09 : le plafond par couple mord ici et non a la livraison. Une
        // commande prise immobilise le tableau ; refuser au dernier moment
        // aurait laisse l'artisan travailler pour rien.
        if ($this->antiExploit->isCraftOrderPairCapReached($order->getRequester(), $crafter)) {
            throw new \InvalidArgumentException('Vous avez trop travaille pour ce commanditaire recemment.');
        }

        $this->assertSameMarket($crafter, $order);
        $this->assertQualified($crafter, $order);

        $now = new \DateTimeImmutable();

        $order->setCrafter($crafter);
        $order->setStatus(CraftOrderStatus::Claimed);
        $order->setClaimedAt($now);
        // ECO-07 : le `craftingTime` de la recette devient une attente reelle.
        $order->setReadyAt($now->modify(sprintf('+%d seconds', max(1, $order->getRecipe()->getCraftingTime()))));

        // ECO-09 : l'echeance cesse d'etre celle du tableau pour devenir celle
        // de la **livraison**, comptee depuis la prise en charge.
        $deadline = $now->modify(sprintf('+%d hours', self::DELIVERY_WINDOW_HOURS));
        if ($deadline > $order->getExpiresAt()) {
            $order->setExpiresAt($deadline);
        }

        $this->entityManager->flush();

        $this->logger->info('Craft order claimed', [
            'order_id' => $order->getId(),
            'crafter_id' => $crafter->getId(),
            'recipe' => $order->getRecipe()->getSlug(),
        ]);
    }

    /**
     * Destinataires acceptables d'une commande directe (ECO-07b).
     *
     * Les memes refus qu'a la prise en charge, appliques **au depot** : sans
     * cela, un commanditaire immobiliserait son escrow pour une commande que
     * l'artisan vise ne pourra jamais prendre, jusqu'a l'expiration.
     */
    private function assertTargetIsAcceptable(Player $requester, Player $target): void
    {
        if ($target->getId() === $requester->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez pas vous adresser une commande a vous-meme.');
        }

        // ECO-16a : le commerce entre personnages d'un meme compte n'a pas plus
        // de sens ici qu'a l'hotel des ventes.
        if ($this->antiExploit->isSameAccount($requester, $target)) {
            throw new \InvalidArgumentException('Vous ne pouvez pas adresser une commande a un autre de vos personnages.');
        }

        if ($target->isTradeSuspended()) {
            throw new \InvalidArgumentException('Cet artisan ne peut pas recevoir de commande pour le moment.');
        }

        if (!$this->regionResolver->isSameMarket($this->regionResolver->resolve($requester), $this->regionResolver->resolve($target))) {
            throw new \InvalidArgumentException('Cet artisan ne se trouve pas dans votre region.');
        }
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
     * applique (`CraftingManager::isRecipeUnlocked()`) : niveau de metier,
     * specialisation et plan appris. Pouvoir prendre une commande qu'on ne
     * saurait pas realiser a son etabli n'aurait aucun sens.
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

        // ECO-20 : le « plan possede » existe enfin comme gardien. ECO-06 avait
        // du s'aligner sur le niveau de metier seul, faute de quoi s'appuyer.
        if (!$this->craftingManager->isRecipeUnlocked($crafter, $recipe)) {
            throw new \InvalidArgumentException('Vous n\'avez pas appris cette recette.');
        }
    }

    /**
     * Livraison de la commande par l'artisan qui l'a prise (ECO-07).
     *
     * Trois choses se passent en meme temps, et aucune ne peut manquer sans
     * leser quelqu'un : l'escrow de materiaux est **consomme** (pas rendu), le
     * resultat va au commanditaire, la commission va a l'artisan moins la taxe
     * de region.
     *
     * L'artisan ne fournit **aucun** materiau : ceux de la commande sont deja
     * immobilises depuis le depot. C'est toute la difference avec l'etabli —
     * ici il vend son plan et son temps, pas sa reserve.
     */
    public function fulfillOrder(Player $crafter, CraftOrder $order): ?AuctionSettlement
    {
        if (!$order->isClaimed()) {
            throw new \InvalidArgumentException('Cette commande n\'est pas en cours de realisation.');
        }

        if ($order->getCrafter()?->getId() !== $crafter->getId()) {
            throw new \InvalidArgumentException('Cette commande a ete prise en charge par un autre artisan.');
        }

        if (!$order->isReady()) {
            throw new \InvalidArgumentException(sprintf('Le travail n\'est pas termine : encore %d seconde(s).', $order->getRemainingWorkSeconds()));
        }

        // L'expiration ne s'applique **pas** a une commande deja prise : le delai
        // d'affichage protege le commanditaire d'une commande qui dort, pas
        // l'artisan qui travaille. Sanctionner la non-livraison est un sujet
        // distinct (ECO-09).

        $requester = $order->getRequester();
        $recipe = $order->getRecipe();

        // ECO-20 : la qualite existe enfin sur l'objet, donc `minQuality` cesse
        // d'etre decoratif. Une piece en dessous du seuil est **retravaillee**,
        // pas refusee : refuser piegerait la commande, et l'artisan vend
        // precisement du temps.
        $quality = $this->craftingManager->computeQuality($crafter, $recipe);
        if (!$this->satisfiesMinQuality($order, $quality)) {
            $order->setReadyAt(new \DateTimeImmutable(sprintf('+%d seconds', max(1, $recipe->getCraftingTime()))));
            $this->entityManager->flush();

            $this->logger->info('Craft order reworked (below requested quality)', [
                'order_id' => $order->getId(),
                'crafter_id' => $crafter->getId(),
                'rolled' => $quality,
                'required' => $order->getMinQuality(),
            ]);

            return null;
        }

        $this->consumeEscrowMaterials($order);
        $this->deliverResult($order, $requester, $quality);

        // La guilde controlante est resolue **une seule fois** : la repartition
        // et le versement en ont tous deux besoin, et deux lectures laisseraient
        // une bascule de controle entre les deux produire une incoherence.
        $region = $order->getRegion();
        $ruler = null !== $region ? $this->townControlManager->getControllingGuild($region) : null;

        $settlement = $this->settleCommission($order, $ruler);
        $this->grantCommission($order, $crafter, $settlement, $ruler);

        // L'artisan progresse : c'est du travail d'atelier comme un autre.
        $grantedXp = $this->craftingManager->grantCraftingXp($crafter, $recipe->getCraft(), $recipe->getXpReward());

        // ECO-08b : l'objet part chez le client et n'y revient jamais ; ce que
        // l'artisan capitalise, c'est sa reputation.
        $reputation = $this->reputationManager->recordDelivery($crafter, $order);

        $order->setStatus(CraftOrderStatus::Fulfilled);
        $order->setFulfilledAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Craft order fulfilled', [
            'order_id' => $order->getId(),
            'crafter_id' => $crafter->getId(),
            'requester_id' => $requester->getId(),
            'recipe' => $recipe->getSlug(),
            'commission' => $order->getCommission(),
            'crafter_revenue' => $settlement->sellerRevenue,
            'tax' => $settlement->taxAmount,
            'burned' => $settlement->burnedAmount,
            'xp' => $grantedXp,
            'quality' => $quality,
            'reputation' => $reputation->getPoints(),
        ]);

        return $settlement;
    }

    /**
     * Les materiaux en escrow **disparaissent** : ils ont ete transformes.
     *
     * On les detruit plutot que de les rendre a qui que ce soit — les rendre au
     * commanditaire lui offrirait l'objet **et** sa matiere, et les donner a
     * l'artisan ferait de chaque commande une source de materiaux gratuits.
     */
    private function consumeEscrowMaterials(CraftOrder $order): void
    {
        foreach ($order->getMaterials() as $material) {
            $material->setCraftOrder(null);
            $this->entityManager->remove($material);
        }
    }

    /**
     * L'objet fabrique va directement dans le sac du **commanditaire**.
     *
     * Il ne transite jamais par l'inventaire de l'artisan : ce detour ouvrirait
     * la porte a une commande honoree puis gardee.
     *
     * **La liaison est posee ici, explicitement** (ECO-08). `InventoryHelper`
     * lie normalement les objets `bind_on_pickup` au joueur de la session — or
     * la session, au moment de la livraison, est celle de l'**artisan**. Passer
     * par lui aurait lie l'objet a celui qui le fabrique au lieu de celui qui
     * l'a commande : exactement l'inverse de ce que ce canal doit produire.
     */
    private function deliverResult(CraftOrder $order, Player $requester, string $quality): void
    {
        $recipe = $order->getRecipe();
        $result = $recipe->getResult();
        $bag = $this->getBagInventory($requester);

        for ($i = 0; $i < max(1, $recipe->getResultQuantity()); ++$i) {
            $playerItem = $this->playerItemGenerator->generateFromItemId($result->getId());
            $playerItem->setInventory($bag);
            $playerItem->setCraftQuality($quality);

            if ($result->isBoundOnPickup()) {
                $playerItem->setBoundToPlayerId($requester->getId());
            }

            $this->entityManager->persist($playerItem);
        }
    }

    /**
     * La piece atteint-elle la qualite demandee par le commanditaire ?
     *
     * Une commande sans exigence accepte tout — c'est le cas courant. Un seuil
     * inconnu de l'echelle est traite comme absent plutot que comme
     * infranchissable : une donnee erronee ne doit pas rendre une commande
     * impossible a honorer.
     */
    private function satisfiesMinQuality(CraftOrder $order, string $quality): bool
    {
        $required = $order->getMinQuality();
        if (null === $required) {
            return true;
        }

        $requiredIndex = array_search($required, QualityCalculator::QUALITY_TIERS, true);
        $rolledIndex = array_search($quality, QualityCalculator::QUALITY_TIERS, true);

        if (false === $requiredIndex || false === $rolledIndex) {
            return true;
        }

        return $rolledIndex >= $requiredIndex;
    }

    /**
     * Repartition de la commission, avec **exactement** les regles de l'hotel
     * des ventes (ECO-04).
     *
     * Un canal d'echange qui taxerait differemment deviendrait le canal ou l'on
     * evite la taxe de l'autre. `AuctionSettlement` est reutilise tel quel : la
     * commission joue le role du prix, l'artisan celui du vendeur.
     *
     * La ristourne membre revient au **commanditaire**, qui a paye au depot.
     * L'invariant tient : l'artisan touche `commission - taxe`, quelle que soit
     * l'appartenance de guilde de son client.
     */
    private function settleCommission(CraftOrder $order, ?Guild $ruler): AuctionSettlement
    {
        $requesterGuild = null !== $ruler ? $this->guildManager->getPlayerGuild($order->getRequester()) : null;
        $requesterIsMember = null !== $ruler && null !== $requesterGuild && $requesterGuild->getId() === $ruler->getId();

        return AuctionSettlement::compute(
            $order->getCommission(),
            $order->getRegion()?->getTaxRateFloat() ?? 0.0,
            null !== $ruler,
            $requesterIsMember,
            RegionBonusProvider::MEMBER_DISCOUNT,
        );
    }

    /**
     * Verse les parts : artisan, tresor de guilde — ou le neant.
     *
     * Quand la region n'a pas de maitre, la taxe est **detruite**. Ce n'est pas
     * un oubli : c'est le gold sink du canal, identique a celui de l'hotel des
     * ventes, et on le journalise pour qu'une refonte ne le rende pas a
     * l'artisan en croyant colmater une fuite.
     */
    private function grantCommission(CraftOrder $order, Player $crafter, AuctionSettlement $settlement, ?Guild $ruler): void
    {
        $crafter->addGils($settlement->sellerRevenue);

        if ($settlement->memberRebate > 0) {
            $order->getRequester()->addGils($settlement->memberRebate);
        }

        if ($settlement->burnedAmount > 0) {
            $this->logger->info('Craft order tax burned (region has no ruling guild)', [
                'region' => $order->getRegion()?->getSlug(),
                'amount' => $settlement->burnedAmount,
            ]);

            return;
        }

        if (null !== $ruler && $settlement->treasuryAmount > 0) {
            $ruler->addGilsTreasury($settlement->treasuryAmount);
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
     * Restitue l'escrow des commandes echues (ECO-09).
     *
     * `findExpirable()` et `releaseEscrow()` existaient depuis ECO-05 sans que
     * rien ne les appelle : une commande que personne ne prenait immobilisait
     * materiaux et Gils **indefiniment**. C'est le seul chemin de sortie
     * automatique de l'escrow, et il manquait.
     *
     * @return array{released: int, penalised: int}
     */
    public function expireOrders(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $released = 0;
        $penalised = 0;

        foreach ($this->orderRepository->findExpirable($now) as $order) {
            $crafter = $order->getCrafter();

            // Une commande **prise** et non livree n'est pas la meme faute
            // qu'une commande que personne n'a voulue : l'artisan s'etait
            // engage, et il a bloque le tableau pour les autres.
            if ($order->isClaimed() && null !== $crafter) {
                $this->reputationManager->recordFailure($crafter, $order);
                ++$penalised;

                $this->logger->info('Craft order not delivered in time', [
                    'order_id' => $order->getId(),
                    'crafter_id' => $crafter->getId(),
                    'recipe' => $order->getRecipe()->getSlug(),
                ]);
            }

            // Dans les deux cas le commanditaire recupere tout : il n'a commis
            // aucune faute, et lui faire payer l'inaction d'un tiers serait la
            // pire lecon a tirer d'une commande non honoree.
            $this->releaseEscrow($order, CraftOrderStatus::Expired);
            ++$released;
        }

        if ($released > 0) {
            $this->entityManager->flush();
        }

        return ['released' => $released, 'penalised' => $penalised];
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
