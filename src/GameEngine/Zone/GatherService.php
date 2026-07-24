<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Zone;
use App\Entity\App\ZoneVein;
use App\Entity\Game\Item;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\InventoryHelper;
use App\Repository\PlayerJournalEntryRepository;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Action Recolter (pivot PBBG, ZON-10).
 *
 * Coute de l'energie d'action puis puise dans un filon partage de la zone :
 * un stock collectif par ressource, commun a tous les joueurs presents, qui
 * s'epuise a mesure qu'on recolte puis respawn apres un delai (fenetre de
 * tension cooperative). Les ressources gagnees reutilisent les items existants
 * (minerais, plantes, poissons) et l'inventaire existant — comme Explorer et
 * Chasser, l'energie gate l'acces, jamais le combat.
 *
 * Definition declarative par zone via `Zone::gatherConfig` ; l'etat runtime du
 * stock partage vit dans `ZoneVein`, cree paresseusement a la premiere recolte.
 * Ajouter une ressource = ajouter de la donnee, pas du code.
 */
class GatherService
{
    public const DEFAULT_COST = 3;
    public const PARAM_COST = 'zone.energy.cost.gather';

    public const DEFAULT_CAPACITY = 20;
    public const DEFAULT_RESPAWN_SECONDS = 1800;
    public const DEFAULT_YIELD_MIN = 1;
    public const DEFAULT_YIELD_MAX = 2;

    private ?int $costCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActionEnergyManager $actionEnergyManager,
        private readonly ZoneTravelService $zoneTravelService,
        private readonly ZoneVeinRepository $veinRepository,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly PlayerJournalEntryRepository $journalRepository,
    ) {
    }

    /**
     * Filons recoltables de la zone, avec l'etat du stock partage resolu a la
     * lecture (respawn applique en memoire, sans effet de bord).
     *
     * @return list<GatherableResource>
     */
    public function getGatherables(Zone $zone): array
    {
        $now = $this->now();
        $gatherables = [];

        foreach ($zone->getGatherResources() as $resource) {
            $normalized = $this->normalize($resource);
            if (null === $normalized) {
                continue;
            }

            $item = $this->findItem($normalized['item']);
            if (null === $item) {
                continue;
            }

            $vein = $this->veinRepository->findOneByZoneAndSlug($zone, $normalized['slug']);
            $stock = $this->effectiveStock($vein, $normalized['capacity'], $normalized['respawn_seconds'], $now);

            $gatherables[] = new GatherableResource(
                $normalized['slug'],
                $item->getName(),
                $normalized['item'],
                $normalized['profession'],
                $stock,
                $normalized['capacity'],
                $stock > 0 ? 0 : $this->respawnRemaining($vein, $normalized['respawn_seconds'], $now),
            );
        }

        return $gatherables;
    }

    /**
     * Recolte une ressource ciblee dans la zone courante.
     *
     * @throws ZoneActionException            si la recolte est refusee (cle de traduction en message)
     * @throws NotEnoughActionEnergyException si l'energie est insuffisante
     */
    public function gather(Player $player, string $slug): GatherResult
    {
        $this->zoneTravelService->settleArrival($player, false);

        if ($player->isTraveling()) {
            throw new ZoneActionException('game.zone.gather.error.traveling');
        }
        if (null !== $player->getFight()) {
            throw new ZoneActionException('game.zone.gather.error.in_fight');
        }
        $zone = $player->getCurrentZone();
        if (null === $zone) {
            throw new ZoneActionException('game.zone.gather.error.no_zone');
        }

        $resource = $this->findResource($zone, $slug);
        if (null === $resource) {
            throw new ZoneActionException('game.zone.gather.error.unknown_resource');
        }

        $item = $this->findItem($resource['item']);
        if (null === $item) {
            // Config de zone incoherente (item inexistant) : refus sans cout.
            throw new ZoneActionException('game.zone.gather.error.unknown_resource');
        }

        $now = $this->now();
        $vein = $this->resolveVein($zone, $resource, $now);
        if ($vein->getStock() <= 0) {
            // Filon epuise : refus sans depenser d'energie (respawn en attente).
            throw new ZoneActionException('game.zone.gather.error.depleted');
        }

        // L'energie n'est prelevee qu'une fois la recolte garantie possible.
        $this->actionEnergyManager->spend($player, $this->getGatherCost(), false);

        $quantity = $this->computeYield($resource, $vein->getStock());
        $remaining = $vein->getStock() - $quantity;
        $vein->setStock($remaining);
        if ($remaining <= 0) {
            $vein->setDepletedAt($now);
        }

        for ($i = 0; $i < $quantity; ++$i) {
            $playerItem = $this->playerItemGenerator->generateFromItemId($item->getId());
            $this->inventoryHelper->addItem($playerItem, false);
        }

        $entry = new PlayerJournalEntry();
        $entry->setPlayer($player);
        $entry->setType(PlayerJournalEntry::TYPE_GATHERING);
        $entry->setMessage(sprintf('Recolte : %dx %s (%s)', $quantity, $item->getName(), $zone->getName()));
        $entry->setMetadata([
            'zone' => $zone->getSlug(),
            'action' => 'gather',
            'vein' => $resource['slug'],
            'item' => $resource['item'],
            'quantity' => $quantity,
        ]);
        $this->entityManager->persist($entry);

        $this->entityManager->flush();
        $this->journalRepository->enforceEntryLimit($player);

        return new GatherResult(
            $resource['slug'],
            $item->getName(),
            $quantity,
            max(0, $remaining),
            'game.zone.gather.result.success',
            ['%count%' => $quantity, '%item%' => $item->getName()],
        );
    }

    public function getGatherCost(): int
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
     * Charge (ou cree) le filon partage et applique un respawn eventuel.
     *
     * @param array{slug: string, item: string, profession: string, capacity: int, respawn_seconds: int, yield_min: int, yield_max: int} $resource
     */
    private function resolveVein(Zone $zone, array $resource, \DateTimeImmutable $now): ZoneVein
    {
        $vein = $this->veinRepository->findOneByZoneAndSlug($zone, $resource['slug']);
        if (null === $vein) {
            $vein = new ZoneVein($zone, $resource['slug'], $resource['capacity']);
            $this->entityManager->persist($vein);

            return $vein;
        }

        if ($this->hasRespawned($vein, $resource['respawn_seconds'], $now)) {
            $vein->setStock($resource['capacity']);
            $vein->setDepletedAt(null);
        }

        return $vein;
    }

    private function effectiveStock(?ZoneVein $vein, int $capacity, int $respawnSeconds, \DateTimeImmutable $now): int
    {
        if (null === $vein) {
            return $capacity;
        }
        if ($vein->getStock() > 0) {
            return min($vein->getStock(), $capacity);
        }

        return $this->hasRespawned($vein, $respawnSeconds, $now) ? $capacity : 0;
    }

    private function respawnRemaining(?ZoneVein $vein, int $respawnSeconds, \DateTimeImmutable $now): int
    {
        if (null === $vein) {
            return 0;
        }
        $depletedAt = $vein->getDepletedAt();
        if (null === $depletedAt) {
            return 0;
        }

        $respawnAt = $depletedAt->getTimestamp() + $respawnSeconds;

        return max(0, $respawnAt - $now->getTimestamp());
    }

    private function hasRespawned(ZoneVein $vein, int $respawnSeconds, \DateTimeImmutable $now): bool
    {
        if ($vein->getStock() > 0) {
            return false;
        }
        $depletedAt = $vein->getDepletedAt();
        if (null === $depletedAt) {
            return false;
        }

        return $now->getTimestamp() >= $depletedAt->getTimestamp() + $respawnSeconds;
    }

    /**
     * @param array{slug: string, item: string, profession: string, capacity: int, respawn_seconds: int, yield_min: int, yield_max: int} $resource
     */
    private function computeYield(array $resource, int $stock): int
    {
        $min = $resource['yield_min'];
        $max = $resource['yield_max'];
        $span = $max - $min;
        $yield = $min + ($span > 0 ? $this->roll($span + 1) - 1 : 0);

        return max(1, min($yield, $stock));
    }

    /**
     * @return array{slug: string, item: string, profession: string, capacity: int, respawn_seconds: int, yield_min: int, yield_max: int}|null
     */
    private function findResource(Zone $zone, string $slug): ?array
    {
        foreach ($zone->getGatherResources() as $resource) {
            $normalized = $this->normalize($resource);
            if (null !== $normalized && $normalized['slug'] === $slug) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $resource
     *
     * @return array{slug: string, item: string, profession: string, capacity: int, respawn_seconds: int, yield_min: int, yield_max: int}|null
     */
    private function normalize(array $resource): ?array
    {
        $slug = isset($resource['slug']) ? (string) $resource['slug'] : '';
        $item = isset($resource['item']) ? (string) $resource['item'] : '';
        if ('' === $slug || '' === $item) {
            return null;
        }

        $capacity = max(1, (int) ($resource['capacity'] ?? self::DEFAULT_CAPACITY));
        $respawn = max(0, (int) ($resource['respawn_seconds'] ?? self::DEFAULT_RESPAWN_SECONDS));
        $yieldMin = max(1, (int) ($resource['yield_min'] ?? self::DEFAULT_YIELD_MIN));
        $yieldMax = max($yieldMin, (int) ($resource['yield_max'] ?? self::DEFAULT_YIELD_MAX));

        return [
            'slug' => $slug,
            'item' => $item,
            'profession' => isset($resource['profession']) ? (string) $resource['profession'] : 'gathering',
            'capacity' => $capacity,
            'respawn_seconds' => $respawn,
            'yield_min' => $yieldMin,
            'yield_max' => $yieldMax,
        ];
    }

    private function findItem(string $slug): ?Item
    {
        return $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $slug]);
    }

    /**
     * Instant courant — surchargeable en test pour un respawn deterministe.
     */
    protected function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    /**
     * Tirage aleatoire 1..max — surchargeable en test pour un rendement deterministe.
     */
    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
