<?php

namespace App\Entity\App;

use App\Repository\PlayerHouseRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Demeure d'un personnage (tache 129, HOU-01).
 *
 * Une maison appartient a un **personnage**, pas a un compte : la regle projet
 * #12 autorise plusieurs personnages par compte, et un logement partage entre
 * eux effacerait le cout de s'en offrir un second.
 *
 * Elle est **rattachee a une zone** — la position d'un joueur etant sa zone
 * depuis le pivot (regle #7), une demeure sans zone ne serait ni visitable ni
 * situable sur la carte du monde.
 */
#[ORM\Entity(repositoryClass: PlayerHouseRepository::class)]
#[ORM\Table(name: 'player_house')]
#[ORM\UniqueConstraint(name: 'uniq_player_house_owner', columns: ['owner_id'])]
#[ORM\Index(name: 'idx_player_house_zone', columns: ['zone_id'])]
class PlayerHouse
{
    use TimestampableEntity;

    /**
     * Prix du terrain, en Gils.
     *
     * Jalon economique assume : au bareme de `BALANCE.md`, c'est l'ordre de
     * grandeur de plusieurs equipements de palier 3. Le housing est un
     * **gold sink** (GAME_PRINCIPLES §4.7), pas une commodite d'inventaire.
     */
    public const LAND_PRICE = 25_000;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $owner;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: false)]
    private Zone $zone;

    /**
     * Nom donne par le proprietaire.
     *
     * Le seul element de personnalisation de ce jalon : les meubles viendront
     * plus tard, mais une demeure sans nom ne se distingue pas de celle du
     * voisin quand on la visite.
     */
    #[ORM\Column(name: 'name', type: 'string', length: 60)]
    private string $name;

    #[ORM\Column(name: 'purchased_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $purchasedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): Player
    {
        return $this->owner;
    }

    public function setOwner(Player $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function setZone(Zone $zone): self
    {
        $this->zone = $zone;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = mb_substr(trim($name), 0, 60);

        return $this;
    }

    public function getPurchasedAt(): \DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    public function setPurchasedAt(\DateTimeImmutable $purchasedAt): self
    {
        $this->purchasedAt = $purchasedAt;

        return $this;
    }
}
