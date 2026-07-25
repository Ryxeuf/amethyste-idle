<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Map;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Import declaratif du graphe de zones (ZON-11).
 *
 * Consomme la structure normalisee produite par ZoneDefinitionLoader et applique
 * un upsert idempotent : chaque Zone est identifiee par son slug, chaque
 * ZoneConnection par le couple (from, to). Non destructif — les zones et
 * liaisons absentes du fichier ne sont jamais supprimees (l'etat runtime, ex.
 * ZoneVein, n'est pas touche). Utilise a la fois par la commande
 * `app:zone:import` et par les fixtures, garantissant une source de verite
 * unique.
 */
class ZoneImporter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array{zones: list<array<string, mixed>>, connections: list<array<string, mixed>>} $definition
     */
    public function import(array $definition, bool $dryRun = false): ZoneImportReport
    {
        $report = new ZoneImportReport();

        /** @var array<string, Zone> $bySlug */
        $bySlug = [];
        /** @var array<string, true> $newSlugs */
        $newSlugs = [];
        foreach ($definition['zones'] as $zoneData) {
            [$zone, $isNew] = $this->upsertZone($zoneData, $dryRun, $report);
            $bySlug[$zoneData['slug']] = $zone;
            if ($isNew) {
                $newSlugs[$zoneData['slug']] = true;
            }
        }

        // Les zones doivent exister (id genere) avant de creer les liaisons.
        if (!$dryRun) {
            $this->entityManager->flush();
        }

        foreach ($definition['connections'] as $connectionData) {
            $this->upsertConnectionEdges($connectionData, $bySlug, $newSlugs, $dryRun, $report);
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        return $report;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{0: Zone, 1: bool} la zone et si elle vient d'etre creee
     */
    private function upsertZone(array $data, bool $dryRun, ZoneImportReport $report): array
    {
        /** @var string $slug */
        $slug = $data['slug'];
        $zone = $this->entityManager->getRepository(Zone::class)->findOneBy(['slug' => $slug]);

        if (null === $zone) {
            $isNew = true;
            $zone = new Zone();
            $zone->setSlug($slug);
            $zone->setCreatedAt($this->now());
            $zone->setUpdatedAt($this->now());
            ++$report->zonesCreated;
        } else {
            $isNew = false;
            ++$report->zonesUpdated;
        }

        $zone->setName((string) $data['name']);
        $zone->setNameTranslations(null !== $data['name_en'] ? ['en' => (string) $data['name_en']] : null);
        $zone->setDescription(null !== $data['description'] ? (string) $data['description'] : null);
        $zone->setDescriptionTranslations(null !== $data['description_en'] ? ['en' => (string) $data['description_en']] : null);
        $zone->setType((string) $data['type']);
        $zone->setIsSafe((bool) $data['safe']);
        $zone->setEnabled((bool) $data['enabled']);
        $zone->setMapX(isset($data['map_x']) ? (int) $data['map_x'] : null);
        $zone->setMapY(isset($data['map_y']) ? (int) $data['map_y'] : null);

        /** @var array<string, mixed>|null $explore */
        $explore = \is_array($data['explore'] ?? null) ? $data['explore'] : null;
        $zone->setExploreConfig($explore);

        /** @var array<string, mixed>|null $gather */
        $gather = \is_array($data['gather'] ?? null) ? ['resources' => $data['gather']] : null;
        $zone->setGatherConfig($gather);

        $zone->setSourceMap($this->resolveSourceMap($data['source_map'] ?? null, $slug, $report));

        if (!$dryRun) {
            $this->entityManager->persist($zone);
        }

        return [$zone, $isNew];
    }

    private function resolveSourceMap(mixed $name, string $zoneSlug, ZoneImportReport $report): ?Map
    {
        if (!\is_string($name) || '' === trim($name)) {
            return null;
        }

        $map = $this->entityManager->getRepository(Map::class)->findOneBy(['name' => $name]);
        if (null === $map) {
            $report->addWarning(sprintf('Zone "%s": source map "%s" introuvable, ignoree.', $zoneSlug, $name));

            return null;
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, Zone>  $bySlug
     * @param array<string, true>  $newSlugs
     */
    private function upsertConnectionEdges(array $data, array $bySlug, array $newSlugs, bool $dryRun, ZoneImportReport $report): void
    {
        /** @var string $from */
        $from = $data['from'];
        /** @var string $to */
        $to = $data['to'];

        $edges = [[$from, $to]];
        if (true === ($data['bidirectional'] ?? false)) {
            $edges[] = [$to, $from];
        }

        foreach ($edges as [$fromSlug, $toSlug]) {
            // Une liaison impliquant une zone tout juste creee est forcement
            // nouvelle : on evite un findOneBy sur une entite encore transiente.
            $mustBeNew = isset($newSlugs[$fromSlug]) || isset($newSlugs[$toSlug]);
            $this->upsertConnection($bySlug[$fromSlug], $bySlug[$toSlug], $data, $mustBeNew, $dryRun, $report);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function upsertConnection(Zone $from, Zone $to, array $data, bool $mustBeNew, bool $dryRun, ZoneImportReport $report): void
    {
        $travelSeconds = (int) $data['travel_seconds'];
        $requiresDiscovery = (bool) $data['requires_discovery'];
        $enabled = (bool) $data['enabled'];

        $connection = $mustBeNew ? null : $this->entityManager->getRepository(ZoneConnection::class)
            ->findOneBy(['fromZone' => $from, 'toZone' => $to]);

        if (null === $connection) {
            $connection = new ZoneConnection($from, $to, $travelSeconds);
            $connection->setCreatedAt($this->now());
            $connection->setUpdatedAt($this->now());
            ++$report->connectionsCreated;
        } else {
            $connection->setTravelSeconds($travelSeconds);
            ++$report->connectionsUpdated;
        }

        $connection->setRequiresDiscovery($requiresDiscovery);
        $connection->setEnabled($enabled);

        if (!$dryRun) {
            $this->entityManager->persist($connection);
        }
    }

    /**
     * Surchargable en test pour un horodatage deterministe.
     */
    protected function now(): \DateTime
    {
        return new \DateTime();
    }
}
