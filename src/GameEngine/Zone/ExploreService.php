<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\GameEngine\Fight\Handler\FightHandler;
use App\GameEngine\World\GameTimeService;
use App\Repository\MobRepository;
use App\Repository\PlayerJournalEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Action Explorer (pivot PBBG, ZON-08).
 *
 * Coute de l'energie d'action, tire un evenement selon la table de la zone
 * (mob, coffre, filon, PNJ, rien) et le resout avec les donnees deja
 * rattachees a la zone (ZON-04) : les rencontres reutilisent les mobs et le
 * combat tour par tour existants — le combat lui-meme reste gratuit.
 *
 * Table declarative par zone via Zone::exploreConfig (defauts ci-dessous) :
 * ajouter du contenu = ajuster de la donnee, pas du code.
 */
class ExploreService
{
    public const DEFAULT_COST = 5;
    public const PARAM_COST = 'zone.energy.cost.explore';

    public const DEFAULT_WEIGHTS = [
        ExploreResult::EVENT_MOB => 50,
        ExploreResult::EVENT_CHEST => 10,
        ExploreResult::EVENT_HARVEST => 10,
        ExploreResult::EVENT_PNJ => 10,
        ExploreResult::EVENT_NOTHING => 20,
    ];

    public const DEFAULT_CHEST_GILS_MIN = 5;
    public const DEFAULT_CHEST_GILS_MAX = 30;

    private ?int $costCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActionEnergyManager $actionEnergyManager,
        private readonly ZoneTravelService $zoneTravelService,
        private readonly MobRepository $mobRepository,
        private readonly FightHandler $fightHandler,
        private readonly PlayerJournalEntryRepository $journalRepository,
        private readonly GameTimeService $gameTimeService,
    ) {
    }

    /**
     * Config d'exploration effective pour la phase courante (ZON-17) : la nuit,
     * le sous-bloc `night` (si present) surcharge `weights`, `chest_gils_*` et
     * peut restreindre le vivier de rencontres a un pool nocturne dedie
     * (`mob_slugs`). Le jour, le bloc racine est utilise tel quel.
     *
     * @return array<string, mixed>
     */
    private function effectiveExploreConfig(Zone $zone): array
    {
        $config = $zone->getExploreConfig() ?? [];
        if (!$this->gameTimeService->isNight()) {
            return $config;
        }

        $night = $config['night'] ?? null;
        if (!\is_array($night)) {
            return $config;
        }

        // Surcharge peu profonde : les cles nuit remplacent les cles jour.
        if (isset($night['weights']) && \is_array($night['weights'])) {
            $config['weights'] = array_merge($config['weights'] ?? [], $night['weights']);
        }
        foreach (['chest_gils_min', 'chest_gils_max', 'mob_slugs'] as $key) {
            if (\array_key_exists($key, $night)) {
                $config[$key] = $night[$key];
            }
        }

        return $config;
    }

    /**
     * @throws ZoneActionException            si l'exploration est refusee (cle de traduction en message)
     * @throws NotEnoughActionEnergyException si l'energie est insuffisante
     */
    public function explore(Player $player): ExploreResult
    {
        $this->zoneTravelService->settleArrival($player, false);

        if ($player->isTraveling()) {
            throw new ZoneActionException('game.zone.explore.error.traveling');
        }
        if (null !== $player->getFight()) {
            throw new ZoneActionException('game.zone.explore.error.in_fight');
        }
        $zone = $player->getCurrentZone();
        if (null === $zone) {
            throw new ZoneActionException('game.zone.explore.error.no_zone');
        }

        $this->actionEnergyManager->spend($player, $this->getExploreCost(), false);

        $result = $this->resolveEvent($player, $zone, $this->drawEvent($zone));

        $this->entityManager->flush();
        $this->journalRepository->enforceEntryLimit($player);

        return $result;
    }

    public function getExploreCost(): int
    {
        if (null !== $this->costCache) {
            return $this->costCache;
        }

        $parameter = $this->entityManager->getRepository(Parameter::class)
            ->findOneBy(['name' => self::PARAM_COST]);
        $value = null !== $parameter ? (int) $parameter->getValue() : self::DEFAULT_COST;

        return $this->costCache = $value >= 0 ? $value : self::DEFAULT_COST;
    }

    /**
     * Tirage pondere de l'evenement. Les zones sures n'ont jamais de rencontre
     * hostile (poids mob force a 0).
     */
    private function drawEvent(Zone $zone): string
    {
        $config = $this->effectiveExploreConfig($zone);
        /** @var array<string, int> $weights */
        $weights = array_merge(self::DEFAULT_WEIGHTS, $config['weights'] ?? []);

        if ($zone->isSafe()) {
            $weights[ExploreResult::EVENT_MOB] = 0;
        }

        $weights = array_filter($weights, static fn (int $weight): bool => $weight > 0);
        $total = array_sum($weights);
        if ($total <= 0) {
            return ExploreResult::EVENT_NOTHING;
        }

        $roll = $this->roll($total);
        foreach ($weights as $event => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $event;
            }
        }

        return ExploreResult::EVENT_NOTHING;
    }

    private function resolveEvent(Player $player, Zone $zone, string $event): ExploreResult
    {
        return match ($event) {
            ExploreResult::EVENT_MOB => $this->resolveMob($player, $zone),
            ExploreResult::EVENT_CHEST => $this->resolveChest($player, $zone),
            ExploreResult::EVENT_HARVEST => $this->resolveHarvest($player, $zone),
            ExploreResult::EVENT_PNJ => $this->resolvePnj($player, $zone),
            default => $this->resolveNothing($player, $zone),
        };
    }

    private function resolveMob(Player $player, Zone $zone): ExploreResult
    {
        $mobs = $this->mobRepository->findAvailableInZone($zone);

        // Pool nocturne dedie (ZON-17) : la nuit, si la zone declare un
        // `night.mob_slugs`, les rencontres se restreignent a ces creatures.
        $config = $this->effectiveExploreConfig($zone);
        $nightPool = $config['mob_slugs'] ?? null;
        if (\is_array($nightPool) && [] !== $nightPool) {
            $filtered = array_values(array_filter(
                $mobs,
                static fn ($mob): bool => \in_array($mob->getMonster()->getSlug(), $nightPool, true),
            ));
            if ([] !== $filtered) {
                $mobs = $filtered;
            }
        }

        if ([] === $mobs) {
            return $this->resolveNothing($player, $zone);
        }

        $mob = $mobs[$this->roll(\count($mobs)) - 1];
        $fight = $this->fightHandler->startFight($player, $mob);
        $monsterName = $mob->getMonster()->getName();

        $this->addJournalEntry($player, sprintf('Exploration : rencontre avec %s (%s)', $monsterName, $zone->getName()), [
            'zone' => $zone->getSlug(),
            'event' => ExploreResult::EVENT_MOB,
            'monster' => $mob->getMonster()->getSlug(),
        ]);

        return new ExploreResult(ExploreResult::EVENT_MOB, 'game.zone.explore.result.mob', ['%monster%' => $monsterName], $fight);
    }

    private function resolveChest(Player $player, Zone $zone): ExploreResult
    {
        $config = $this->effectiveExploreConfig($zone);
        $min = max(0, (int) ($config['chest_gils_min'] ?? self::DEFAULT_CHEST_GILS_MIN));
        $max = max($min, (int) ($config['chest_gils_max'] ?? self::DEFAULT_CHEST_GILS_MAX));
        $gils = $min + ($max > $min ? $this->roll($max - $min + 1) - 1 : 0);

        $player->addGils($gils);

        $this->addJournalEntry($player, sprintf('Exploration : coffre trouve, +%d gils (%s)', $gils, $zone->getName()), [
            'zone' => $zone->getSlug(),
            'event' => ExploreResult::EVENT_CHEST,
            'gils' => $gils,
        ]);

        return new ExploreResult(ExploreResult::EVENT_CHEST, 'game.zone.explore.result.chest', ['%gils%' => $gils]);
    }

    private function resolveHarvest(Player $player, Zone $zone): ExploreResult
    {
        $this->addJournalEntry($player, sprintf('Exploration : filon repere (%s)', $zone->getName()), [
            'zone' => $zone->getSlug(),
            'event' => ExploreResult::EVENT_HARVEST,
        ]);

        return new ExploreResult(ExploreResult::EVENT_HARVEST, 'game.zone.explore.result.harvest');
    }

    private function resolvePnj(Player $player, Zone $zone): ExploreResult
    {
        /** @var list<Pnj> $pnjs */
        $pnjs = $this->entityManager->getRepository(Pnj::class)->findBy(['zone' => $zone]);
        if ([] === $pnjs) {
            return $this->resolveNothing($player, $zone);
        }

        $pnj = $pnjs[$this->roll(\count($pnjs)) - 1];

        $this->addJournalEntry($player, sprintf('Exploration : rencontre avec %s (%s)', $pnj->getName(), $zone->getName()), [
            'zone' => $zone->getSlug(),
            'event' => ExploreResult::EVENT_PNJ,
        ]);

        return new ExploreResult(ExploreResult::EVENT_PNJ, 'game.zone.explore.result.pnj', ['%pnj%' => $pnj->getName()]);
    }

    private function resolveNothing(Player $player, Zone $zone): ExploreResult
    {
        $this->addJournalEntry($player, sprintf('Exploration sans decouverte (%s)', $zone->getName()), [
            'zone' => $zone->getSlug(),
            'event' => ExploreResult::EVENT_NOTHING,
        ]);

        return new ExploreResult(ExploreResult::EVENT_NOTHING, 'game.zone.explore.result.nothing');
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
     * Tirage aleatoire 1..max — surchargable en test pour un tirage deterministe.
     */
    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
