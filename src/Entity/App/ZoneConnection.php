<?php

namespace App\Entity\App;

use App\Repository\ZoneConnectionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Arete orientee du graphe de zones (pivot PBBG, ZON-02).
 *
 * Une liaison A → B avec une duree de voyage en temps reel. Les liaisons
 * bidirectionnelles sont representees par deux aretes (une par sens), ce qui
 * permet des durees asymetriques (descente plus rapide que l'ascension...).
 */
#[ORM\Entity(repositoryClass: ZoneConnectionRepository::class)]
#[ORM\Table(name: 'zone_connection')]
#[ORM\UniqueConstraint(name: 'uniq_zone_connection_from_to', columns: ['from_zone_id', 'to_zone_id'])]
class ZoneConnection
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Zone::class, inversedBy: 'connections')]
    #[ORM\JoinColumn(name: 'from_zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $fromZone;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'to_zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $toZone;

    /**
     * Duree de voyage en secondes reelles (0 = passage instantane, ex. entrer dans un batiment).
     */
    #[ORM\Column(name: 'travel_seconds', type: 'integer', options: ['default' => 60])]
    private int $travelSeconds = 60;

    /**
     * Liaison rapide : deverrouillee uniquement apres avoir decouvert la zone cible
     * (transposition du fast travel existant, cf. ZON-06).
     */
    #[ORM\Column(name: 'requires_discovery', type: 'boolean', options: ['default' => false])]
    private bool $requiresDiscovery = false;

    #[ORM\Column(name: 'enabled', type: 'boolean', options: ['default' => true])]
    private bool $enabled = true;

    public function __construct(Zone $fromZone, Zone $toZone, int $travelSeconds = 60)
    {
        if ($fromZone === $toZone) {
            throw new \InvalidArgumentException('A zone connection cannot loop on itself.');
        }
        $this->fromZone = $fromZone;
        $this->toZone = $toZone;
        $this->setTravelSeconds($travelSeconds);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFromZone(): Zone
    {
        return $this->fromZone;
    }

    public function getToZone(): Zone
    {
        return $this->toZone;
    }

    public function getTravelSeconds(): int
    {
        return $this->travelSeconds;
    }

    public function setTravelSeconds(int $travelSeconds): self
    {
        if ($travelSeconds < 0) {
            throw new \InvalidArgumentException('Travel duration cannot be negative.');
        }
        $this->travelSeconds = $travelSeconds;

        return $this;
    }

    public function requiresDiscovery(): bool
    {
        return $this->requiresDiscovery;
    }

    public function setRequiresDiscovery(bool $requiresDiscovery): self
    {
        $this->requiresDiscovery = $requiresDiscovery;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
