<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\Entity\Game\Monster;
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
    /**
     * Racine publique servie statiquement, pour verifier qu'un bandeau declare
     * existe bel et bien (ZON-41).
     *
     * Injectee plutot que deduite : un service qui devine sa propre racine se
     * trompe le jour ou on le lance depuis un autre repertoire.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $publicDir = '',
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

        // ZON-26b : la population declaree, apres le flush des zones (les mobs
        // referencent une zone qui doit avoir un id).
        foreach ($definition['zones'] as $zoneData) {
            $this->syncMobs($bySlug[$zoneData['slug']], $zoneData['mobs'] ?? null, $dryRun, $report);
            $this->syncPnjs($bySlug[$zoneData['slug']], $zoneData['pnjs'] ?? null, $dryRun, $report);
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
        $zone->setTier((int) $data['tier']);
        $zone->setEnabled((bool) $data['enabled']);
        $zone->setMapX(isset($data['map_x']) ? (int) $data['map_x'] : null);
        $zone->setMapY(isset($data['map_y']) ? (int) $data['map_y'] : null);
        $zone->setMapShape(isset($data['map_shape']) ? (string) $data['map_shape'] : null);

        // ZON-41 : le YAML est la source de verite, et il **ecrase**. Une valeur
        // saisie en administration est un apercu, remis a plat au prochain
        // import — l'alternative (« ne pas ecraser si la cle est absente »)
        // reconduirait le defaut que ce jalon repare : une donnee qui differe
        // d'un environnement a l'autre sans que rien ne le dise.
        $illustration = isset($data['illustration']) ? (string) $data['illustration'] : null;
        $zone->setIllustrationPath($illustration);

        // Un bandeau annonce mais absent du disque se voit — sans casser
        // l'import. Les douze images arrivent une par une, et un import qui
        // tomberait parce qu'une zone n'est pas encore peinte serait une
        // regression pour tout le monde.
        if (null !== $illustration && '' !== $this->publicDir && !is_file($this->publicDir . '/images/' . $illustration)) {
            $report->addWarning(sprintf('Zone "%s": illustration "%s" declared but missing from disk.', $slug, $illustration));
        }

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

    /**
     * Materialise la population declaree d'une zone (ZON-26b).
     *
     * Un `Mob` n'atteignait sa zone que **par une carte**
     * (`WorldEntityZoneListener` derive `Mob.zone` de `Mob.map`). Une zone sans
     * carte d'origine — toute zone nouvelle depuis le pivot — ne pouvait donc
     * avoir aucune rencontre. Ici, la zone est posee **directement**.
     *
     * L'import est **idempotent et non destructif** : on complete jusqu'au
     * compte declare, on ne supprime jamais. Un mob peut etre en combat ou
     * blesse au moment de l'import, et le rejouer ne doit ni voler une
     * rencontre en cours ni ressusciter la faune d'un coup.
     *
     * @param list<array<string, mixed>>|null $mobs
     */
    private function syncMobs(Zone $zone, ?array $mobs, bool $dryRun, ZoneImportReport $report): void
    {
        if (null === $mobs || [] === $mobs) {
            return;
        }

        foreach ($mobs as $entry) {
            $slug = (string) $entry['monster'];
            $monster = $this->entityManager->getRepository(Monster::class)->findOneBy(['slug' => $slug]);

            if (null === $monster) {
                $report->addWarning(sprintf('Zone "%s": unknown monster "%s", population skipped.', $zone->getSlug(), $slug));
                continue;
            }

            $existing = $this->countZoneMobs($zone, $monster);
            $missing = max(0, (int) $entry['count'] - $existing);

            for ($i = 0; $i < $missing; ++$i) {
                if (!$dryRun) {
                    $this->entityManager->persist($this->spawn($zone, $monster, $entry));
                }
                ++$report->mobsCreated;
            }
        }
    }

    /**
     * Effectif deja present d'une espece dans une zone.
     *
     * Le test porte sur le **suivi Doctrine** et non sur `getId()` : `Zone::$id`
     * est declare `int` non nullable et **leve** sur une zone pas encore
     * persistee — cas normal en dry-run, ou aucune zone neuve n'est ecrite.
     * Une zone non suivie n'a par definition aucune creature en base.
     */
    private function countZoneMobs(Zone $zone, Monster $monster): int
    {
        if (!$this->entityManager->contains($zone)) {
            return 0;
        }

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(Mob::class, 'm')
            ->where('m.zone = :zone')
            ->andWhere('m.monster = :monster')
            ->setParameter('zone', $zone)
            ->setParameter('monster', $monster)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function spawn(Zone $zone, Monster $monster, array $entry): Mob
    {
        $mob = new Mob();
        $mob->setMonster($monster);
        // La zone est posee explicitement : `WorldEntityZoneListener` respecte
        // une zone deja fixee et n'ira pas chercher de carte.
        $mob->setZone($zone);
        // Champ herite de l'ere carte (regle #7), sans usage depuis ZON-21
        // mais non nullable en base.
        $mob->setCoordinates('0.0');
        $mob->setLife($monster->getLife());
        $mob->setTier($monster->getTier());
        $mob->setNocturnal((bool) $entry['nocturnal']);

        if (null !== $entry['group_tag']) {
            $mob->setGroupTag((string) $entry['group_tag']);
        }

        return $mob;
    }

    /**
     * Habitants declares d'une zone (ZON-26b-b).
     *
     * Un PNJ est un **individu**, la ou une creature est un effectif : l'upsert
     * porte sur le slug, et re-jouer l'import met a jour au lieu de dupliquer.
     * C'est aussi ce qui permet de corriger un horaire de boutique en editant
     * le YAML, sans repasser par une migration.
     *
     * @param list<array<string, mixed>>|null $pnjs
     */
    private function syncPnjs(Zone $zone, ?array $pnjs, bool $dryRun, ZoneImportReport $report): void
    {
        if (null === $pnjs || [] === $pnjs) {
            return;
        }

        foreach ($pnjs as $entry) {
            $slug = (string) $entry['slug'];
            $pnj = $this->entityManager->getRepository(Pnj::class)->findOneBy(['slug' => $slug]);

            if (null === $pnj) {
                $pnj = new Pnj();
                $pnj->setSlug($slug);
                // La zone est posee explicitement : `WorldEntityZoneListener`
                // respecte une zone deja fixee et n'ira pas chercher de carte.
                // C'est ce qui leve le verrou — une zone sans carte d'origine
                // ne pouvait avoir aucun habitant.
                $pnj->setZone($zone);
                // Champ herite de l'ere carte (regle #7), sans usage depuis
                // ZON-21 mais non nullable en base.
                $pnj->setCoordinates('0.0');
                ++$report->pnjsCreated;
            } else {
                $pnj->setZone($zone);
                ++$report->pnjsUpdated;
            }

            $this->applyPnj($pnj, $entry);

            if (!$dryRun) {
                $this->entityManager->persist($pnj);
            }
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function applyPnj(Pnj $pnj, array $entry): void
    {
        $pnj->setName((string) $entry['name']);
        $pnj->setNameTranslations(null !== $entry['name_en'] ? ['en' => (string) $entry['name_en']] : null);
        $pnj->setClassType((string) $entry['class_type']);
        $pnj->setLife((int) $entry['life']);
        $pnj->setMaxLife((int) $entry['life']);
        $pnj->setPortrait(null !== $entry['portrait'] ? (string) $entry['portrait'] : null);
        $pnj->setShopItems(\is_array($entry['shop_items']) ? $entry['shop_items'] : null);
        $pnj->setOpensAt(null !== $entry['opens_at'] ? (int) $entry['opens_at'] : null);
        $pnj->setClosesAt(null !== $entry['closes_at'] ? (int) $entry['closes_at'] : null);

        // Une replique d'accueil suffit a rendre un habitant vivant. Les arbres
        // de dialogue restent aux fixtures : les decrire en YAML demanderait un
        // second langage pour un gain nul sur le verrou leve ici.
        $pnj->setDialog(null !== $entry['greeting'] ? ['greeting' => (string) $entry['greeting']] : []);
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
