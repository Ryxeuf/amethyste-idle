<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\CraftOrder;
use App\Entity\App\Guild;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Recipe;
use App\Enum\CraftOrderStatus;
use App\Enum\Purity;
use App\GameEngine\Auction\AuctionAntiExploit;
use App\GameEngine\Auction\AuctionSettlement;
use App\GameEngine\Economy\PurityChain;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\RegionBonusProvider;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\GameEngine\Reputation\CrystalBuybackFloor;
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

    /**
     * ECO-28 — le sertissage. Le travail d'un service n'a pas de recette pour
     * porter son temps : dix minutes d'etabli, et deux amethystites Pures
     * fournies par le client — la bande est la matiere du geste, c'est elle
     * qui donne enfin un debouche d'usage au « pur » (ECO-23).
     */
    public const SERVICE_WORK_SECONDS = 600;
    public const SERVICE_CRYSTAL_COST = 2;

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
        private readonly PurityChain $purityChain,
        private readonly GameMasterPolicy $gameMasterPolicy,
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
        ?Purity $minPurity = null,
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
        $this->assertMaterialsMeetPurity($materials, $minPurity);

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
        $order->setMinPurity($minPurity);
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
     * Ouvre une commande de **service** : le joaillier travaille l'objet du
     * client au lieu d'en produire un neuf (ECO-28).
     *
     * Le premier service est le sertissage — ouvrir un emplacement de materia
     * sur une piece, jusqu'au nombre que sa forme declare (OBJ-04). L'objet du
     * client part en escrow **sans que sa liaison soit touchee** : c'est tout
     * le point du canal — sans lui, aucun artisanat de service sur le stuff
     * lie n'est possible (GAME_WORLD § 2.1). Le client fournit l'amethystite
     * **Pure** ; le refus d'une bande insuffisante arrive avant l'escrow,
     * quand il ne coute encore rien (ECO-23).
     */
    public function createServiceOrder(
        Player $requester,
        PlayerItem $target,
        int $commission,
        ?Player $targetCrafter = null,
        int $durationHours = self::DEFAULT_DURATION_HOURS,
    ): CraftOrder {
        if ($commission < 1) {
            throw new \InvalidArgumentException('La commission doit etre superieure a 0.');
        }

        if ($this->orderRepository->countActiveByRequester($requester) >= self::MAX_ACTIVE_ORDERS) {
            throw new \InvalidArgumentException(sprintf('Vous avez deja %d commandes en cours.', self::MAX_ACTIVE_ORDERS));
        }

        if ($target->getInventory()?->getPlayer()?->getId() !== $requester->getId()) {
            throw new \InvalidArgumentException('Cet objet ne provient pas de votre inventaire.');
        }

        // La piece peut etre liee — c'est precisement le canal fait pour ca —
        // mais pas portee : on ne travaille pas une piece sur le dos du client.
        if (0 !== $target->getGear()) {
            throw new \InvalidArgumentException('Retirez la piece avant de la confier : on ne sertit pas un equipement porte.');
        }

        // FAC-07 : la commande passe l'objet entre les mains d'un autre joueur
        // — une contrefacon n'y entre jamais.
        if ($target->isCounterfeit()) {
            throw new \InvalidArgumentException('Une contrefacon ne se confie pas a un artisan.');
        }

        if (!$target->getGenericItem()->isGear()) {
            throw new \InvalidArgumentException('Le sertissage ne travaille que les pieces d\'equipement.');
        }

        if ($target->getSlots()->count() >= $target->getGenericItem()->getMateriaSlots()) {
            throw new \InvalidArgumentException('Cette piece porte deja tous les emplacements que sa forme permet.');
        }

        $crystals = $this->collectServiceCrystals($requester);
        if ([] === $crystals) {
            // ECO-23 : le refus de bande arrive avant l'escrow.
            throw new \InvalidArgumentException(sprintf('Le sertissage exige %d amethystite(s) de bande « %s » au moins.', self::SERVICE_CRYSTAL_COST, Purity::Pur->value));
        }

        if (null !== $targetCrafter) {
            $this->assertTargetIsAcceptable($requester, $targetCrafter);
        }

        if (!$requester->removeGils($commission)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour la commission.');
        }

        $order = new CraftOrder();
        $order->setRequester($requester);
        $order->setServiceKind(CraftOrder::SERVICE_SOCKET);
        $order->setCommission($commission);
        $order->setMinPurity(Purity::Pur);
        $order->setTargetCrafter($targetCrafter);
        $order->setRegion($this->regionResolver->resolve($requester));
        $order->setStatus(CraftOrderStatus::Open);
        $order->setExpiresAt(new \DateTimeImmutable(sprintf('+%d hours', max(1, $durationHours))));

        foreach ($crystals as $crystal) {
            $crystal->setInventory(null);
            $order->addMaterial($crystal);
        }

        // L'objet du client quitte l'inventaire comme les materiaux — mais par
        // sa propre place : les materiaux se consomment, lui revient toujours.
        $target->setInventory(null);
        $order->setTargetItem($target);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->logger->info('Service order created', [
            'order_id' => $order->getId(),
            'requester_id' => $requester->getId(),
            'service' => CraftOrder::SERVICE_SOCKET,
            'target_item_id' => $target->getId(),
            'commission' => $commission,
            'region' => $order->getRegion()?->getSlug(),
            'target_crafter_id' => $targetCrafter?->getId(),
        ]);

        return $order;
    }

    /**
     * L'amethystite Pure du sac du client — la matiere du sertissage.
     *
     * Une bande sous « pur » ne convient pas, et une amethystite liee ou
     * portee n'est pas prelevable. Rend `[]` si le compte n'y est pas : le
     * refus se joue avant tout escrow.
     *
     * @return list<PlayerItem>
     */
    private function collectServiceCrystals(Player $requester): array
    {
        $collected = [];
        foreach ($this->getBagInventory($requester)->getItems() as $playerItem) {
            if (\count($collected) >= self::SERVICE_CRYSTAL_COST) {
                break;
            }
            if (CrystalBuybackFloor::CRYSTAL_SLUG !== $playerItem->getGenericItem()->getSlug()) {
                continue;
            }
            if (!$playerItem->isExchangeable()) {
                continue;
            }
            $purity = $playerItem->getPurity();
            if (null === $purity || !$purity->isAtLeast(Purity::Pur)) {
                continue;
            }
            $collected[] = $playerItem;
        }

        return \count($collected) >= self::SERVICE_CRYSTAL_COST ? $collected : [];
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

        // La commande de craft est un canal d'echange : un MJ ne l'honore pas.
        $this->gameMasterPolicy->assertMayTrade($crafter);

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

        // RET-03 : meme raisonnement pour la commande de la semaine. Le tableau
        // regional ne la montre pas, mais un identifiant devine ou reste d'un
        // ancien affichage suffirait a la prendre — la visibilite n'est pas une
        // autorisation, et une regle qui ne vit que dans une requete de lecture
        // ne protege rien.
        $orderGuild = $order->getGuild();
        if (null !== $orderGuild && $this->guildManager->getPlayerGuild($crafter) !== $orderGuild) {
            throw new \InvalidArgumentException('Cette commande est reservee aux membres de la guilde qui l\'a posee.');
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
        // Un service n'a pas de recette : son temps d'etabli est le sien.
        $workSeconds = $order->isService()
            ? self::SERVICE_WORK_SECONDS
            : max(1, $order->getRecipe()?->getCraftingTime() ?? 1);
        $order->setReadyAt($now->modify(sprintf('+%d seconds', $workSeconds)));

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
            'recipe' => $order->getRecipe()?->getSlug(),
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

        // Ni comme donneur d'ordre, ni comme destinataire : un MJ ne passe pas
        // commande, et une commande ne lui est pas adressee.
        $this->gameMasterPolicy->assertMayTrade($requester);

        if (!$this->gameMasterPolicy->canTrade($target)) {
            throw new \InvalidArgumentException('Cet artisan ne prend pas de commande.');
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
        // ECO-28 : un service n'a pas de recette — le metier et le niveau
        // vivent sur la commande. Le sertissage est l'affaire du joaillier.
        if ($order->isService()) {
            $level = $this->craftingManager->getCraftingLevel($crafter, CraftOrder::SERVICE_CRAFT);
            if ($level < CraftOrder::SERVICE_LEVEL) {
                throw new \InvalidArgumentException(sprintf('Niveau de %s insuffisant : %d requis, vous avez %d.', CraftOrder::SERVICE_CRAFT, CraftOrder::SERVICE_LEVEL, $level));
            }

            return;
        }

        $recipe = $order->getRecipe();
        if (null === $recipe) {
            throw new \InvalidArgumentException('Cette commande ne porte ni recette ni service.');
        }

        $level = $this->craftingManager->getCraftingLevel($crafter, $recipe->getCraft());
        if ($level < $recipe->getRequiredLevel()) {
            throw new \InvalidArgumentException(sprintf('Niveau de %s insuffisant : %d requis, vous avez %d.', $recipe->getCraft(), $recipe->getRequiredLevel(), $level));
        }

        $required = $recipe->getRequiredSpecialization();
        if (null !== $required && !$crafter->isSpecializedIn($required->craftSlug())) {
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

        // ECO-28 : un service suit son propre chemin de livraison — l'objet du
        // client est travaille, jamais produit.
        if ($order->isService()) {
            return $this->fulfillServiceOrder($crafter, $order);
        }

        $requester = $order->getRequester();
        $recipe = $order->getRecipe();
        if (null === $recipe) {
            throw new \InvalidArgumentException('Cette commande ne porte ni recette ni service.');
        }

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

        // ECO-26 — la bande se lit **avant** que l'escrow ne parte : les
        // materiaux sont des `PlayerItem`, et les detruire d'abord effacerait
        // ce dont l'objet doit heriter. La commande est le canal de l'endgame ;
        // y perdre la purete casserait la chaine haute exactement la ou elle
        // compte le plus.
        $purity = $this->purityChain->weakestOf($order->getMaterials());

        $this->consumeEscrowMaterials($order);
        $this->deliverResult($order, $requester, $quality, $purity);

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
     * Livraison d'un service : l'objet du client revient travaille (ECO-28).
     *
     * Le sertissage ouvre un emplacement de materia sur la piece, consomme
     * l'amethystite Pure de l'escrow, et rend la piece **au commanditaire** —
     * jamais a l'artisan, et **sans toucher sa liaison** : `boundToPlayerId`
     * n'est ecrit nulle part sur ce chemin, c'est l'invariant du canal.
     */
    private function fulfillServiceOrder(Player $crafter, CraftOrder $order): AuctionSettlement
    {
        $requester = $order->getRequester();
        $target = $order->getTargetItem();
        if (null === $target) {
            throw new \InvalidArgumentException('Cette commande de service a perdu son objet.');
        }

        // Les cristaux ont ete transformes : ils disparaissent, comme les
        // materiaux d'une commande classique.
        $this->consumeEscrowMaterials($order);

        // Le geste : un emplacement de plus sur la piece. C'est la premiere
        // mecanique du jeu qui cree un `Slot` hors fixtures.
        $slot = new Slot();
        $slot->setItem($target);
        $slot->setCreatedAt(new \DateTime());
        $slot->setUpdatedAt(new \DateTime());
        $target->getSlots()->add($slot);
        $this->entityManager->persist($slot);

        // La piece rentre chez son proprietaire — ameliore, liaison intacte.
        $target->setInventory($this->getBagInventory($requester));

        $region = $order->getRegion();
        $ruler = null !== $region ? $this->townControlManager->getControllingGuild($region) : null;
        $settlement = $this->settleCommission($order, $ruler);
        $this->grantCommission($order, $crafter, $settlement, $ruler);

        $reputation = $this->reputationManager->recordDelivery($crafter, $order);

        $order->setStatus(CraftOrderStatus::Fulfilled);
        $order->setFulfilledAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Service order fulfilled', [
            'order_id' => $order->getId(),
            'crafter_id' => $crafter->getId(),
            'requester_id' => $requester->getId(),
            'service' => $order->getServiceKind(),
            'target_item_id' => $target->getId(),
            'commission' => $order->getCommission(),
            'crafter_revenue' => $settlement->sellerRevenue,
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
    private function deliverResult(CraftOrder $order, Player $requester, string $quality, ?Purity $purity = null): void
    {
        $recipe = $order->getRecipe();
        if (null === $recipe) {
            // Jamais atteint : le chemin de service livre par
            // fulfillServiceOrder(). La garde vaut contrat.
            throw new \InvalidArgumentException('Cette commande ne porte ni recette ni service.');
        }
        $result = $recipe->getResult();
        $bag = $this->getBagInventory($requester);

        for ($i = 0; $i < max(1, $recipe->getResultQuantity()); ++$i) {
            $playerItem = $this->playerItemGenerator->generateFromItemId($result->getId());
            $playerItem->setInventory($bag);
            $playerItem->setCraftQuality($quality);
            $playerItem->setPurity($purity);

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
                    'recipe' => $order->getRecipe()?->getSlug(),
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

        // ECO-28 : l'objet d'un service revient a son proprietaire, intact —
        // et sa liaison n'est pas touchee : elle n'est ecrite nulle part sur
        // ce chemin, quel que soit le statut de sortie.
        $target = $order->getTargetItem();
        if (null !== $target) {
            $target->setInventory($bag);
        }

        $requester->addGils($order->getCommission());
        $order->setStatus($status);
    }

    /**
     * La bande exigee est verifiee **a la creation**, sur les materiaux confies
     * (ECO-23).
     *
     * La verifier plus tard reviendrait a laisser un client immobiliser sa
     * matiere et sa commission dans une commande qu'aucun artisan ne pourrait
     * honorer sans faute de sa part. Le refus arrive donc avant l'escrow, quand
     * il ne coute encore rien.
     *
     * Une matiere **hors perimetre** ne peut pas satisfaire une exigence de
     * bande : elle n'en a pas. Le dire explicitement vaut mieux que de la
     * laisser passer, ce qui reviendrait a offrir un contournement a qui
     * fournirait des herbes.
     *
     * @param list<PlayerItem> $materials
     */
    private function assertMaterialsMeetPurity(array $materials, ?Purity $minPurity): void
    {
        if (null === $minPurity) {
            return;
        }

        foreach ($materials as $material) {
            $purity = $material->getPurity();
            if (null === $purity || !$purity->isAtLeast($minPurity)) {
                $supplied = null === $purity ? 'sans bande' : $purity->value;

                throw new \InvalidArgumentException(sprintf('Cette commande exige de la matiere « %s » : « %s » ne convient pas.', $minPurity->value, $supplied));
            }
        }
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
