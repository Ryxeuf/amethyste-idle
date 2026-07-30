<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerExpedition;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Enum\QuestGesture;
use App\Event\Game\PlayerGestureEvent;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Notification\NotificationService;
use App\Helper\InventoryHelper;
use App\Repository\PlayerExpeditionRepository;
use App\Repository\PlayerJournalEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Expeditions time-gated (pivot PBBG, ZON-13).
 *
 * Le joueur envoie son personnage explorer une zone pendant N heures reelles.
 * L'expedition est un etat exclusif : pendant sa duree, plus de voyage, ni
 * d'exploration/chasse/recolte, ni de combat (garde dans ZoneController). Au
 * retour (heure passee), un butin l'attend, a recuperer via claim().
 *
 * Time-gated en temps reel, resolu paresseusement au chargement de l'ecran de
 * zone (settle) — aucun cron par joueur, comme le voyage (ZON-06) et les
 * regens (ZON-07/12).
 *
 * Recompenses **derivees des tables declaratives de la zone** (ZON-11), mises a
 * l'echelle par la duree : les gils reprennent la fourchette « coffre » de
 * `exploreConfig`, les objets sont tires des filons de `gatherConfig`. Ajouter
 * du contenu d'expedition = enrichir la donnee de zone, pas le code.
 *
 * Durees pilotables via la table `parameter` :
 *  - `zone.expedition.duration.short`  (defaut 3600 s = 1 h)
 *  - `zone.expedition.duration.medium` (defaut 14400 s = 4 h)
 *  - `zone.expedition.duration.long`   (defaut 43200 s = 12 h)
 */
class ExpeditionService
{
    public const DURATION_SHORT = 'short';
    public const DURATION_MEDIUM = 'medium';
    public const DURATION_LONG = 'long';

    public const NOTIFICATION_TYPE = 'expedition';

    /** @var array<string, array{param: string, default: int}> */
    private const DURATIONS = [
        self::DURATION_SHORT => ['param' => 'zone.expedition.duration.short', 'default' => 3600],
        self::DURATION_MEDIUM => ['param' => 'zone.expedition.duration.medium', 'default' => 14400],
        self::DURATION_LONG => ['param' => 'zone.expedition.duration.long', 'default' => 43200],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerExpeditionRepository $expeditionRepository,
        private readonly ZoneTravelService $zoneTravelService,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly PlayerJournalEntryRepository $journalRepository,
        private readonly NotificationService $notificationService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Duree (en secondes) de chaque palier, curseur `parameter` applique.
     *
     * @return array<string, int>
     */
    public function getDurations(): array
    {
        $durations = [];
        foreach (self::DURATIONS as $key => $config) {
            $parameter = $this->entityManager->getRepository(Parameter::class)
                ->findOneBy(['name' => $config['param']]);
            $value = null !== $parameter ? (int) $parameter->getValue() : $config['default'];
            $durations[$key] = $value > 0 ? $value : $config['default'];
        }

        return $durations;
    }

    public function getActive(Player $player): ?PlayerExpedition
    {
        return $this->expeditionRepository->findForPlayer($player);
    }

    /**
     * Demarre une expedition dans la zone courante.
     *
     * @throws ZoneActionException si l'expedition est refusee (cle de traduction en message)
     */
    public function start(Player $player, string $durationKey): PlayerExpedition
    {
        $durations = $this->getDurations();
        if (!isset($durations[$durationKey])) {
            throw new ZoneActionException('game.zone.expedition.error.unknown_duration');
        }

        $this->zoneTravelService->settleArrival($player, false);

        if (null !== $this->getActive($player)) {
            throw new ZoneActionException('game.zone.expedition.error.already_active');
        }
        if ($player->isTraveling()) {
            throw new ZoneActionException('game.zone.expedition.error.traveling');
        }
        if (null !== $player->getFight()) {
            throw new ZoneActionException('game.zone.expedition.error.in_fight');
        }

        $zone = $player->getCurrentZone();
        if (null === $zone) {
            throw new ZoneActionException('game.zone.expedition.error.no_zone');
        }
        if (!$this->isEligibleZone($zone)) {
            throw new ZoneActionException('game.zone.expedition.error.zone_ineligible');
        }

        $now = $this->now();
        $endsAt = $now->modify(sprintf('+%d seconds', $durations[$durationKey]));

        $expedition = new PlayerExpedition($player, $zone, $durationKey, $now, $endsAt);
        $this->entityManager->persist($expedition);
        $this->entityManager->flush();

        // ONB-12a : la derniere lecon de l'acte I — quitter le jeu en le
        // laissant travailler. La cible est le palier choisi : la quete
        // d'introduction accepte le plus court, elle n'impose pas d'attendre
        // douze heures pour finir le tutoriel.
        $this->eventDispatcher->dispatch(
            new PlayerGestureEvent(QuestGesture::StartExpedition, [$durationKey]),
            PlayerGestureEvent::NAME,
        );

        return $expedition;
    }

    /**
     * Resolution paresseuse : si l'expedition active vient de se terminer et
     * n'a pas encore ete notifiee, emet la notification de fin (in-game +
     * Mercure si connecte) une seule fois. A appeler au chargement de l'ecran
     * de zone.
     */
    public function settle(Player $player, bool $flush = true): void
    {
        $expedition = $this->getActive($player);
        if (null === $expedition) {
            return;
        }

        if (!$expedition->isComplete($this->now()) || null !== $expedition->getNotifiedAt()) {
            return;
        }

        $expedition->setNotifiedAt($this->now());
        if ($flush) {
            $this->entityManager->flush();
        }

        $this->notificationService->notify(
            $player,
            self::NOTIFICATION_TYPE,
            'game.zone.expedition.notification.title',
            'game.zone.expedition.notification.message',
            '🧭',
            '/game/zone',
        );
    }

    /**
     * Recupere le butin d'une expedition terminee et libere le joueur.
     *
     * @throws ZoneActionException si aucune expedition terminee n'est a recuperer
     */
    public function claim(Player $player): ExpeditionClaimResult
    {
        $expedition = $this->getActive($player);
        if (null === $expedition) {
            throw new ZoneActionException('game.zone.expedition.error.none');
        }
        if (!$expedition->isComplete($this->now())) {
            throw new ZoneActionException('game.zone.expedition.error.not_complete');
        }

        $zone = $expedition->getZone();
        $hours = max(1, (int) round($this->durationSeconds($expedition) / 3600));

        $gils = $this->rollGils($zone, $hours);
        $player->addGils($gils);

        $items = $this->rollItems($zone, $hours);
        foreach ($items as $itemSlug => $quantity) {
            $item = $this->findItem($itemSlug);
            if (null === $item) {
                continue;
            }
            for ($i = 0; $i < $quantity; ++$i) {
                $playerItem = $this->playerItemGenerator->generateFromItemId($item->getId());
                $this->inventoryHelper->addItem($playerItem, false);
            }
        }

        $itemNames = $this->resolveItemNames($items);

        $entry = new PlayerJournalEntry();
        $entry->setPlayer($player);
        $entry->setType(PlayerJournalEntry::TYPE_EXPLORATION);
        $entry->setMessage(sprintf('Expedition (%s) : +%d gils, %d objet(s) rapporte(s)', $zone->getName(), $gils, array_sum($items)));
        $entry->setMetadata([
            'zone' => $zone->getSlug(),
            'action' => 'expedition',
            'duration' => $expedition->getDurationKey(),
            'gils' => $gils,
            'items' => $items,
        ]);
        $this->entityManager->persist($entry);

        $this->entityManager->remove($expedition);
        $this->entityManager->flush();
        $this->journalRepository->enforceEntryLimit($player);

        return new ExpeditionClaimResult($zone->getName(), $gils, $itemNames);
    }

    /**
     * Une zone est eligible aux expeditions si elle n'est pas sure (cite/hub)
     * et offre un butin declaratif (coffre ou filons).
     */
    public function isEligibleZone(Zone $zone): bool
    {
        if ($zone->isSafe()) {
            return false;
        }

        return true;
    }

    private function durationSeconds(PlayerExpedition $expedition): int
    {
        return max(0, $expedition->getEndsAt()->getTimestamp() - $expedition->getStartedAt()->getTimestamp());
    }

    /**
     * Gils = une fourchette « coffre » de la zone tiree par heure d'expedition.
     */
    private function rollGils(Zone $zone, int $hours): int
    {
        $config = $zone->getExploreConfig() ?? [];
        $min = max(0, (int) ($config['chest_gils_min'] ?? ExploreService::DEFAULT_CHEST_GILS_MIN));
        $max = max($min, (int) ($config['chest_gils_max'] ?? ExploreService::DEFAULT_CHEST_GILS_MAX));

        $total = 0;
        for ($i = 0; $i < $hours; ++$i) {
            $total += $min + ($max > $min ? $this->roll($max - $min + 1) - 1 : 0);
        }

        return $total;
    }

    /**
     * Objets = un filon de la zone tire par heure, avec son rendement declare.
     *
     * @return array<string, int> item slug => quantite
     */
    private function rollItems(Zone $zone, int $hours): array
    {
        $resources = [];
        foreach ($zone->getGatherResources() as $resource) {
            $slug = isset($resource['item']) ? (string) $resource['item'] : '';
            if ('' === $slug) {
                continue;
            }
            $resources[] = [
                'item' => $slug,
                'yield_min' => max(1, (int) ($resource['yield_min'] ?? GatherService::DEFAULT_YIELD_MIN)),
                'yield_max' => max((int) ($resource['yield_min'] ?? GatherService::DEFAULT_YIELD_MIN), (int) ($resource['yield_max'] ?? GatherService::DEFAULT_YIELD_MAX)),
            ];
        }

        if ([] === $resources) {
            return [];
        }

        $items = [];
        for ($i = 0; $i < $hours; ++$i) {
            $resource = $resources[$this->roll(\count($resources)) - 1];
            $span = $resource['yield_max'] - $resource['yield_min'];
            $quantity = $resource['yield_min'] + ($span > 0 ? $this->roll($span + 1) - 1 : 0);
            $items[$resource['item']] = ($items[$resource['item']] ?? 0) + $quantity;
        }

        return $items;
    }

    /**
     * @param array<string, int> $items
     *
     * @return list<array{name: string, quantity: int}>
     */
    private function resolveItemNames(array $items): array
    {
        $resolved = [];
        foreach ($items as $slug => $quantity) {
            $item = $this->findItem($slug);
            $resolved[] = ['name' => null !== $item ? $item->getName() : $slug, 'quantity' => $quantity];
        }

        return $resolved;
    }

    private function findItem(string $slug): ?Item
    {
        return $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $slug]);
    }

    /**
     * Instant courant — surchargeable en test.
     */
    protected function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    /**
     * Tirage aleatoire 1..max — surchargeable en test pour un butin deterministe.
     */
    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
