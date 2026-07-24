<?php

namespace App\Entity\App;

use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Filon partage d'une zone (pivot PBBG, ZON-10).
 *
 * Etat runtime mutable du stock collectif d'une ressource recoltable d'une
 * zone : plusieurs joueurs presents dans la meme zone puisent dans le meme
 * stock, qui s'epuise puis respawn (fenetre de tension cooperative). La
 * definition declarative (item, capacite, respawn, rendement) vit dans
 * `Zone::gatherConfig` ; cette entite ne porte que l'etat variable (stock
 * courant, instant d'epuisement), cree paresseusement a la premiere recolte.
 *
 * Unicite (zone, slug) : un seul filon partage par ressource et par zone.
 */
#[ORM\Entity(repositoryClass: ZoneVeinRepository::class)]
#[ORM\Table(name: 'zone_vein')]
#[ORM\UniqueConstraint(name: 'uniq_zone_vein_zone_slug', columns: ['zone_id', 'slug'])]
#[ORM\Index(columns: ['zone_id'], name: 'idx_zone_vein_zone')]
class ZoneVein
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $zone;

    /**
     * Identifiant de la ressource au sein de la zone (cle `slug` d'une entree
     * de `Zone::gatherConfig`).
     */
    #[ORM\Column(name: 'slug', type: 'string', length: 64)]
    private string $slug;

    /**
     * Stock collectif restant. 0 = epuise (respawn en attente).
     */
    #[ORM\Column(name: 'stock', type: 'integer')]
    private int $stock = 0;

    /**
     * Instant d'epuisement (stock tombe a 0). Null tant que le filon n'a
     * jamais ete vide ; sert de base au calcul du respawn.
     */
    #[ORM\Column(name: 'depleted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $depletedAt = null;

    public function __construct(Zone $zone, string $slug, int $stock)
    {
        $this->zone = $zone;
        $this->slug = $slug;
        $this->stock = max(0, $stock);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = max(0, $stock);

        return $this;
    }

    public function getDepletedAt(): ?\DateTimeImmutable
    {
        return $this->depletedAt;
    }

    public function setDepletedAt(?\DateTimeImmutable $depletedAt): self
    {
        $this->depletedAt = $depletedAt;

        return $this;
    }
}
