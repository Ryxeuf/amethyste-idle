<?php

namespace App\Entity\App;

use App\Enum\HouseStyle;
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

    /**
     * Loyer d'entretien, en Gils, et sa periode.
     *
     * 500 Gils par semaine, soit 2 % du terrain : assez pour etre un **gold
     * sink recurrent** (GAME_PRINCIPLES §4.7), assez peu pour qu'oublier une
     * echeance ne ruine personne.
     */
    public const RENT_AMOUNT = 500;
    public const RENT_PERIOD_DAYS = 7;

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

    /**
     * Echeance du prochain loyer.
     *
     * L'arriere se **deduit** de cette date plutot que d'un drapeau : un etat
     * de plus serait un etat de plus a garder coherent, et la date suffit a
     * repondre aux deux questions (doit-on ? depuis quand ?).
     */
    #[ORM\Column(name: 'rent_due_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $rentDueAt;

    /** Ameublement, visible des visiteurs (HOU-05). */
    #[ORM\Column(name: 'style', type: 'string', length: 20, enumType: HouseStyle::class, options: ['default' => 'bare'])]
    private HouseStyle $style = HouseStyle::Bare;

    /**
     * Devise gravee au fronton, libre et gratuite.
     *
     * Le style se paie, la devise non : on ne fait pas payer un joueur pour
     * ecrire une phrase chez lui.
     */
    #[ORM\Column(name: 'motto', type: 'string', length: 140, nullable: true)]
    private ?string $motto = null;

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

    public function getRentDueAt(): \DateTimeImmutable
    {
        return $this->rentDueAt;
    }

    public function setRentDueAt(\DateTimeImmutable $rentDueAt): self
    {
        $this->rentDueAt = $rentDueAt;

        return $this;
    }

    /**
     * Le loyer est-il en retard ?
     *
     * Une demeure en arriere **ne se perd pas** : elle dort. Rien n'est
     * confisque, rien n'est detruit — elle cesse simplement de rendre service
     * jusqu'a ce que le loyer soit paye.
     */
    public function isInArrears(?\DateTimeImmutable $now = null): bool
    {
        return $this->rentDueAt < ($now ?? new \DateTimeImmutable());
    }

    /**
     * Reporte l'echeance d'une periode a partir de la precedente.
     *
     * A partir de l'echeance et non de « maintenant » : payer en retard ne doit
     * pas offrir une periode pleine, sinon attendre serait rentable.
     */
    public function getStyle(): HouseStyle
    {
        return $this->style;
    }

    public function setStyle(HouseStyle $style): self
    {
        $this->style = $style;

        return $this;
    }

    public function getMotto(): ?string
    {
        return $this->motto;
    }

    public function setMotto(?string $motto): self
    {
        $motto = null === $motto ? null : trim($motto);
        $this->motto = ('' === $motto || null === $motto) ? null : mb_substr($motto, 0, 140);

        return $this;
    }

    public function extendRent(): self
    {
        $this->rentDueAt = $this->rentDueAt->modify(sprintf('+%d days', self::RENT_PERIOD_DAYS));

        return $this;
    }
}
