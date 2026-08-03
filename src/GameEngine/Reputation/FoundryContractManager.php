<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\FoundryContract;
use App\Entity\App\FoundryContractFulfillment;
use App\Entity\App\Player;
use App\Entity\Game\Faction;
use App\Entity\Game\Item;
use App\Enum\ReputationTier;
use App\GameEngine\Retention\WeekKey;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use App\Repository\AuctionTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les contrats d'approvisionnement de la Fonderie (FAC-05).
 *
 * GAME_WORLD § 12.2 : chaque semaine, la Fonderie publie un contrat — gros
 * volume d'une matiere commune, a prix **garanti mais toujours sous le
 * marche**. Le garde-fou est l'inverse exact du receleur : lui prend plus que
 * la taxe max pour ne jamais renverser le HV, elle paie moins que le marche
 * pour ne jamais le remplacer. Zero friction, regularite, paiement mixte
 * gils + essence — et un plancher de demande permanent pour les ressources
 * du milieu.
 *
 * **Le tirage est deterministe et n'est jamais un reroll.** La cle de semaine
 * (`WeekKey`, le point de rotation unique de RET-01) seme `crc32` ; la ligne
 * de la semaine, une fois ecrite, est la verite — rejouer la rotation la
 * retrouve. Le garde-fou de prix se verifie **au tirage** : la reference du
 * marche (mediane HV des ventes conclues sur sept jours, ou prix d'item si
 * le marche est muet) est lue, le prix unitaire y est plafonne strictement
 * en dessous, et la reference est figee sur la ligne — verifiable apres coup.
 */
class FoundryContractManager
{
    /**
     * La fenetre de lecture du marche au tirage, en jours.
     */
    public const MARKET_WINDOW_DAYS = 7;

    /**
     * Le palier d'acces : « Ami — contrats d'approvisionnement » (§ 12.2).
     */
    public const REQUIRED_TIER = ReputationTier::Ami;

    /**
     * La zone du guichet : le contrat se remet au comptoir des Mines, la ou
     * la maison siege — comme la commission se livre au foyer (RET-02b).
     */
    public const COUNTER_ZONE_SLUG = 'mines-profondes';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FoundryContractCatalog $catalog,
        private readonly ReputationManager $reputationManager,
        private readonly HostileConsequenceResolver $hostileConsequences,
        private readonly AuctionTransactionRepository $transactionRepository,
        private readonly InventoryHelper $inventoryHelper,
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    /**
     * Le contrat de la semaine — tire paresseusement s'il n'existe pas
     * encore : le calendrier declaratif n'a pas de worker (voir
     * `DefaultScheduleProvider`), et une affiche absente un lundi matin
     * serait un silence. La commande de rotation reste le chemin explicite.
     */
    public function current(?\DateTimeImmutable $now = null): FoundryContract
    {
        return $this->rotate($now);
    }

    /**
     * Tire (ou retrouve) le contrat de la semaine. Idempotent : une ligne
     * existante est rendue telle quelle, jamais retiree.
     */
    public function rotate(?\DateTimeImmutable $now = null): FoundryContract
    {
        $weekKey = WeekKey::of($now ?? new \DateTimeImmutable());

        $existing = $this->entityManager->getRepository(FoundryContract::class)
            ->findOneBy(['weekKey' => $weekKey]);
        if (null !== $existing) {
            return $existing;
        }

        $pool = $this->catalog->contracts();
        $template = $pool[abs(crc32($weekKey)) % \count($pool)];

        $reference = $this->marketReference($template['item']);
        // Le garde-fou du plan : le prix contractuel est strictement sous la
        // reference, verifie au tirage. Le plancher a 1 gil garde un contrat
        // payant meme sur une matiere qui ne vaut presque rien.
        $gilsPerUnit = min($template['gils_per_unit'], max(1, $reference - 1));

        $contract = new FoundryContract();
        $contract->setWeekKey($weekKey);
        $contract->setItemSlug($template['item']);
        $contract->setVolume($template['volume']);
        $contract->setGilsPerUnit($gilsPerUnit);
        $contract->setEssence($template['essence']);
        $contract->setReferencePrice($reference);

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        return $contract;
    }

    /**
     * Ce qui empeche ce joueur d'honorer le contrat — une cle de traduction,
     * ou `null` si rien. Le refus n'est jamais muet (doctrine RET-02).
     */
    public function blocker(Player $player, FoundryContract $contract): ?string
    {
        if (!$this->isEligible($player)) {
            return 'game.foundry.contract.error.tier';
        }
        if ($this->hostileConsequences->isHostileToward($player, 'fonderie')) {
            // Un contrat optionnel est un privilege, jamais un droit : la
            // rancune de la maison peut le fermer sans toucher la boucle cœur.
            return 'game.foundry.contract.error.hostile';
        }
        if (null !== $this->fulfillmentOf($player, $contract)) {
            return 'game.foundry.contract.error.delivered';
        }
        if ($player->getCurrentZone()?->getSlug() !== self::COUNTER_ZONE_SLUG) {
            return 'game.foundry.contract.error.elsewhere';
        }
        if ($this->countInBag($contract->getItemSlug()) < $contract->getVolume()) {
            return 'game.foundry.contract.error.missing';
        }

        return null;
    }

    /**
     * Honore le contrat : le volume quitte le sac, gils + essence entrent.
     *
     * @return array{gils: int, essence: int}
     *
     * @throws FoundryContractException si un blocage subsiste (cle en message)
     */
    public function deliver(Player $player, ?\DateTimeImmutable $now = null): array
    {
        $contract = $this->current($now);

        $blocker = $this->blocker($player, $contract);
        if (null !== $blocker) {
            throw new FoundryContractException($blocker);
        }

        $removed = $this->inventoryHelper->removeItemBySlug($contract->getItemSlug(), $contract->getVolume());
        if ($removed < $contract->getVolume()) {
            // Le compte a ete verifie par blocker() : ce chemin dit une course
            // entre deux requetes. On ne paie pas une livraison partielle.
            throw new FoundryContractException('game.foundry.contract.error.missing');
        }

        $gils = $contract->getTotalGils();
        $player->addGils($gils);
        $player->addEssence($contract->getEssence());

        $fulfillment = new FoundryContractFulfillment();
        $fulfillment->setContract($contract);
        $fulfillment->setPlayer($player);
        $this->entityManager->persist($fulfillment);
        $this->entityManager->flush();

        return ['gils' => $gils, 'essence' => $contract->getEssence()];
    }

    public function isEligible(Player $player): bool
    {
        $faction = $this->entityManager->getRepository(Faction::class)->findOneBy(['slug' => 'fonderie']);
        if (null === $faction) {
            return false;
        }

        $playerFaction = $this->reputationManager->getPlayerFaction($player, $faction);

        return null !== $playerFaction
            && $playerFaction->getReputation() >= self::REQUIRED_TIER->threshold();
    }

    public function fulfillmentOf(Player $player, FoundryContract $contract): ?FoundryContractFulfillment
    {
        return $this->entityManager->getRepository(FoundryContractFulfillment::class)->findOneBy([
            'contract' => $contract,
            'player' => $player,
        ]);
    }

    /**
     * La reference du marche : la mediane HV des ventes conclues, ou le prix
     * d'item si le marche est muet — le meme repli assume que le plancher du
     * cristal (un prix de donnee, jamais un hasard).
     */
    private function marketReference(string $itemSlug): int
    {
        $since = (new \DateTimeImmutable())->modify(sprintf('-%d days', self::MARKET_WINDOW_DAYS));
        $median = $this->transactionRepository->medianUnitPriceForSlug($itemSlug, $since);
        if (null !== $median) {
            return $median;
        }

        $item = $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $itemSlug]);

        return max(1, $item?->getPrice() ?? 1);
    }

    private function countInBag(string $slug): int
    {
        $count = 0;
        foreach ($this->playerHelper->getBagInventory()->getItems() as $item) {
            if ($item->getGenericItem()->getSlug() === $slug) {
                ++$count;
            }
        }

        return $count;
    }
}
