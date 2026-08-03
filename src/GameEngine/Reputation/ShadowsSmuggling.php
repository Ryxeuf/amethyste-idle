<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Pnj;
use App\Entity\App\SmugglingContract;
use App\Entity\App\Zone;
use App\Entity\Game\Faction;
use App\Enum\SettlementType;
use App\GameEngine\Retention\WeekKey;
use App\GameEngine\World\GameTimeService;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les contrats de contrebande des Ruelles (FAC-08).
 *
 * GAME_WORLD § 12.4 d : « livrer discretement une cargaison de nuit, moins de
 * capacite, pas d'escorte » — un systeme propre a la Confrerie, pas un derive
 * des caravanes. Le ballot se prend a un guichet, se livre a l'autre, **la
 * nuit** — et la cargaison vit dans le contrat, jamais dans l'inventaire : la
 * fouille aux portes d'un Bastion confisque le ballot, jamais le sac.
 *
 * Se faire prendre decote les Chevaliers, immediatement et fortement
 * (§ 12.5, la rigueur) — c'est le prix du canal, et il est plus lourd que la
 * prime. Crochet inerte au premier jour : aucun foyer n'est Bastion tant que
 * les joueurs n'ont pas sedimenté la Guerre — le jour ou l'un bascule, la
 * fouille mord sans qu'on revienne ici.
 */
class ShadowsSmuggling
{
    public const CHEVALIERS_SLUG = 'chevaliers';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShadowsMarketCatalog $catalog,
        private readonly ShadowsApproach $approach,
        private readonly GameTimeService $gameTimeService,
        private readonly SettlementRepository $settlementRepository,
        private readonly ReputationManager $reputationManager,
    ) {
    }

    public function activeContract(Player $player): ?SmugglingContract
    {
        return $this->entityManager->getRepository(SmugglingContract::class)->findOneBy([
            'player' => $player,
            'status' => SmugglingContract::STATUS_IN_TRANSIT,
        ]);
    }

    /**
     * Le guichet parle-t-il contrebande a ce joueur, ici, maintenant ?
     */
    public function isAvailableFor(Player $player, ?Pnj $pnj): bool
    {
        return null !== $pnj
            && $this->catalog->isCounter($pnj->getSlug())
            && $this->approach->hasMet($player)
            && GameTimeService::PHASE_NIGHT === $this->gameTimeService->getPhase();
    }

    /**
     * Ce qui empeche d'accepter un ballot, ou `null` si la voie est libre.
     * Cascade de cles de traduction — le refus n'est jamais muet.
     */
    public function acceptBlocker(Player $player, ?Pnj $pnj): ?string
    {
        if (null === $pnj || !$this->catalog->isCounter($pnj->getSlug()) || !$this->approach->hasMet($player)) {
            // Le meme refus neutre qu'un mauvais guichet : ne rien reveler.
            return 'game.shadows.smuggling.error.counter';
        }
        if (GameTimeService::PHASE_NIGHT !== $this->gameTimeService->getPhase()) {
            return 'game.shadows.smuggling.error.daylight';
        }
        if (!$this->tierAtLeast($player, $this->catalog->smugglingRequiredTier())) {
            return 'game.shadows.smuggling.error.tier';
        }
        if (null !== $this->activeContract($player)) {
            // Moins de capacite : un seul ballot a la fois, c'est la
            // definition du canal.
            return 'game.shadows.smuggling.error.active';
        }
        if ($this->contractsThisWeek($player) >= $this->catalog->smugglingWeeklyCap()) {
            return 'game.shadows.smuggling.error.cap';
        }

        return null;
    }

    /**
     * Accepte un ballot au guichet : la destination est l'autre guichet de la
     * Confrerie — la contrebande relie ses deux portes, jamais ailleurs.
     *
     * @throws ShadowsMarketException si un blocage subsiste (cle en message)
     */
    public function accept(Player $player, ?Pnj $pnj): SmugglingContract
    {
        $blocker = $this->acceptBlocker($player, $pnj);
        if (null !== $blocker) {
            throw new ShadowsMarketException($blocker);
        }

        $destination = $this->otherCounterZoneSlug($pnj);
        if (null === $destination) {
            throw new ShadowsMarketException('game.shadows.smuggling.error.counter');
        }

        $labels = $this->catalog->smugglingCargoLabels();
        $contract = new SmugglingContract();
        $contract->setPlayer($player);
        $contract->setWeekKey(WeekKey::of(new \DateTimeImmutable()));
        $contract->setCargoLabel($labels[($this->roll(\count($labels)) - 1) % \count($labels)]);
        $contract->setOriginZoneSlug((string) $pnj->getZone()?->getSlug());
        $contract->setDestinationZoneSlug($destination);
        $contract->setRewardGils($this->catalog->smugglingRewardGils());

        $this->entityManager->persist($contract);
        $this->addJournalEntry($player, sprintf('Un ballot discret vous attend : %s, a livrer sans bruit.', $contract->getCargoLabel()), [
            'action' => 'smuggling_accept',
            'destination' => $destination,
        ]);
        $this->entityManager->flush();

        return $contract;
    }

    /**
     * Ce qui empeche de livrer, ou `null` si le guichet tend la main.
     */
    public function deliverBlocker(Player $player, ?Pnj $pnj): ?string
    {
        if (null === $pnj || !$this->catalog->isCounter($pnj->getSlug()) || !$this->approach->hasMet($player)) {
            return 'game.shadows.smuggling.error.counter';
        }

        $contract = $this->activeContract($player);
        if (null === $contract) {
            return 'game.shadows.smuggling.error.none';
        }
        if (GameTimeService::PHASE_NIGHT !== $this->gameTimeService->getPhase()) {
            return 'game.shadows.smuggling.error.daylight';
        }
        if ($player->getCurrentZone()?->getSlug() !== $contract->getDestinationZoneSlug()
            || $pnj->getZone()?->getSlug() !== $contract->getDestinationZoneSlug()) {
            return 'game.shadows.smuggling.error.elsewhere';
        }

        return null;
    }

    /**
     * Livre le ballot : la prime figee au contrat tombe dans la bourse, et le
     * geste nourrit la Confrerie (route grey_market_sale, FAC-02).
     *
     * @throws ShadowsMarketException si un blocage subsiste (cle en message)
     */
    public function deliver(Player $player, ?Pnj $pnj): int
    {
        $blocker = $this->deliverBlocker($player, $pnj);
        if (null !== $blocker) {
            throw new ShadowsMarketException($blocker);
        }

        $contract = $this->activeContract($player);
        \assert(null !== $contract);

        $contract->setStatus(SmugglingContract::STATUS_DELIVERED);
        $player->addGils($contract->getRewardGils());
        $this->entityManager->persist($player);
        $this->addJournalEntry($player, 'Le ballot a change de mains sans un mot. La Confrerie s\'en souviendra.', [
            'action' => 'smuggling_delivered',
            'gils' => $contract->getRewardGils(),
        ]);
        $this->entityManager->flush();

        $this->reputationManager->grantGestureReputation($player, 'grey_market_sale');

        return $contract->getRewardGils();
    }

    /**
     * La fouille aux portes (FAC-03 § les Chevaliers) : a l'entree d'une zone
     * a foyer **Bastion**, un ballot en transit peut etre confisque — le
     * contrat, jamais l'inventaire. Rend le contrat confisque, ou `null` si
     * rien ne s'est passe. L'appelant flushe (le voyage flushe deja).
     */
    public function inspectAtGates(Player $player, Zone $destination): ?SmugglingContract
    {
        $contract = $this->activeContract($player);
        if (null === $contract) {
            return null;
        }

        $settlement = $this->settlementRepository->findOneByZone($destination);
        if (SettlementType::Bastion !== $settlement?->getType()) {
            return null;
        }

        if ($this->roll(100) > $this->catalog->smugglingSearchChancePercent()) {
            return null;
        }

        $contract->setStatus(SmugglingContract::STATUS_CONFISCATED);
        $this->penalizeChevaliers($player, $this->catalog->smugglingCaughtPenalty());
        $this->addJournalEntry($player, 'Fouille aux portes : le ballot est confisque. L\'Ordre vous regarde autrement, desormais.', [
            'action' => 'smuggling_confiscated',
            'zone' => (string) $destination->getSlug(),
        ]);

        return $contract;
    }

    /**
     * La prime affichee au guichet — celle qui sera figee au contrat.
     */
    public function rewardGils(): int
    {
        return $this->catalog->smugglingRewardGils();
    }

    public function contractsThisWeek(Player $player, ?\DateTimeImmutable $now = null): int
    {
        return \count($this->entityManager->getRepository(SmugglingContract::class)->findBy([
            'player' => $player,
            'weekKey' => WeekKey::of($now ?? new \DateTimeImmutable()),
        ]));
    }

    /**
     * La decote des Chevaliers — immediate et forte, et elle peut faire
     * basculer en Hostile : se faire prendre EST le geste oppose, celui qui
     * gagne l'hostilite (jamais un defaut). Crochet inerte si l'Ordre n'est
     * pas seme.
     */
    private function penalizeChevaliers(Player $player, int $penalty): void
    {
        $chevaliers = $this->entityManager->getRepository(Faction::class)
            ->findOneBy(['slug' => self::CHEVALIERS_SLUG]);
        if (null === $chevaliers) {
            return;
        }

        $this->reputationManager->addReputation($player, $chevaliers, -$penalty);
    }

    private function tierAtLeast(Player $player, \App\Enum\ReputationTier $tier): bool
    {
        $faction = $this->entityManager->getRepository(Faction::class)
            ->findOneBy(['slug' => ShadowsApproach::FACTION_SLUG]);
        if (null === $faction) {
            return false;
        }

        $line = $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);

        return null !== $line && $line->getReputation() >= $tier->threshold();
    }

    /**
     * L'autre guichet de la Confrerie : la contrebande relie ses deux portes.
     */
    private function otherCounterZoneSlug(Pnj $pnj): ?string
    {
        foreach ($this->catalog->counterPnjSlugs() as $slug) {
            if ($slug === $pnj->getSlug()) {
                continue;
            }
            $other = $this->entityManager->getRepository(Pnj::class)->findOneBy(['slug' => $slug]);
            $zoneSlug = $other?->getZone()?->getSlug();
            if (null !== $zoneSlug) {
                return $zoneSlug;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function addJournalEntry(Player $player, string $message, array $metadata): void
    {
        $entry = new PlayerJournalEntry();
        $entry->setPlayer($player);
        $entry->setType(PlayerJournalEntry::TYPE_EXPLORATION);
        $entry->setMessage($message);
        $entry->setMetadata($metadata);
        $this->entityManager->persist($entry);
    }

    /**
     * Protegee pour que les tests fixent le tirage.
     */
    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
