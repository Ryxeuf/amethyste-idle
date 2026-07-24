<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Repository\ZoneRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

/**
 * Rattache automatiquement les entites de monde (Mob, Pnj, ObjectLayer) a leur
 * zone au moment de la persistence (pivot PBBG, ZON-04).
 *
 * Couvre tous les chemins de creation (fixtures, admin, invasions, invocations,
 * terrain sync) sans devoir cabler chaque appelant. La zone est derivee de la
 * carte via Zone::sourceMap ; les cartes hors graphe (donjons instancies,
 * carte de test) laissent la zone a null. Une zone posee explicitement par
 * l'appelant n'est jamais ecrasee.
 */
#[AsDoctrineListener(event: Events::prePersist)]
class WorldEntityZoneListener
{
    /** @var array<int, Zone|null> */
    private array $zoneByMap = [];

    public function __construct(
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Mob && !$entity instanceof Pnj && !$entity instanceof ObjectLayer) {
            return;
        }
        if ($entity->getZone() !== null) {
            return;
        }

        $map = $entity->getMap();
        if ($map === null) {
            return;
        }

        $entity->setZone($this->resolveZone($map));
    }

    private function resolveZone(Map $map): ?Zone
    {
        // spl_object_id : cle sure meme pour une carte pas encore flushee (id non initialise).
        $key = spl_object_id($map);
        if (!\array_key_exists($key, $this->zoneByMap)) {
            $this->zoneByMap[$key] = $this->zoneRepository->findEnabledBySourceMap($map);
        }

        return $this->zoneByMap[$key];
    }
}
