<?php

namespace App\Entity\App;

use App\Enum\ShopStatus;
use App\Repository\PlayerShopRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Echoppe d'un artisan (ECO-10).
 *
 * Vitrine **persistante** : contrairement a l'hotel des ventes, qui est un
 * carnet d'ordres anonyme, l'echoppe porte un nom, une enseigne et une adresse.
 * Elle vend pendant que son proprietaire est deconnecte — c'est le pilier
 * asynchrone de l'economie joueur (GAME_PRINCIPLES §4.5).
 *
 * L'echoppe **suit la demeure** : elle s'ouvre dans la zone ou le joueur
 * possede un terrain. Le housing (tache 129) cesse ainsi d'etre un pur gold
 * sink cosmetique — il conditionne l'acces au commerce de detail.
 */
#[ORM\Entity(repositoryClass: PlayerShopRepository::class)]
#[ORM\Table(name: 'player_shop')]
#[ORM\Index(name: 'idx_player_shop_zone_status', columns: ['zone_id', 'status'])]
class PlayerShop
{
    /**
     * Emplacements de vente offerts a l'ouverture.
     *
     * Volontairement etroit : une echoppe doit obliger a **choisir** ce qu'on
     * expose. Sans contrainte de place, elle deviendrait un second hotel des
     * ventes, en moins lisible.
     */
    public const DEFAULT_SLOTS = 6;

    public const MAX_SLOTS = 24;

    /**
     * Niveau de metier minimal, dans n'importe quel craft, pour ouvrir.
     *
     * Les recettes livrees plafonnent au niveau 10 : exiger 5 place l'echoppe a
     * mi-parcours, quand un artisan produit deja au-dela du T1 que les PNJ
     * vendent. Plus bas, l'echoppe ferait doublon avec le plancher PNJ
     * (ECO-02) ; plus haut, elle resterait un privilege de fin de course.
     */
    public const MIN_CRAFT_LEVEL = 5;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    /**
     * Une echoppe par artisan : c'est une enseigne, pas une chaine.
     */
    #[ORM\OneToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private Player $owner;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $zone;

    #[ORM\Column(name: 'name', type: 'string', length: 60)]
    private string $name;

    /**
     * Enseigne : la phrase peinte au-dessus de la porte.
     */
    #[ORM\Column(name: 'sign', type: 'string', length: 140, nullable: true)]
    private ?string $sign = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20, enumType: ShopStatus::class)]
    private ShopStatus $status = ShopStatus::Open;

    #[ORM\Column(name: 'slot_count', type: 'integer', options: ['default' => self::DEFAULT_SLOTS])]
    private int $slotCount = self::DEFAULT_SLOTS;

    /**
     * Caisse : recettes encaissables a la reconnexion (ECO-11).
     *
     * Un entier sur l'echoppe plutot qu'une entite dediee : une caisse n'a
     * qu'un solde, et lui donner une table ajouterait une jointure a chaque
     * vente pour ne rien exprimer de plus. Le detail vit dans `ShopSaleLog`.
     */
    #[ORM\Column(name: 'vault_gils', type: 'integer', options: ['default' => 0])]
    private int $vaultGils = 0;

    #[ORM\Column(name: 'opened_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $openedAt;

    #[ORM\Column(name: 'rent_due_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentDueAt = null;

    public function __construct()
    {
        $this->openedAt = new \DateTimeImmutable();
    }

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
        $this->name = $name;

        return $this;
    }

    public function getSign(): ?string
    {
        return $this->sign;
    }

    public function setSign(?string $sign): self
    {
        $sign = null === $sign ? null : trim($sign);
        $this->sign = '' === $sign ? null : $sign;

        return $this;
    }

    public function getStatus(): ShopStatus
    {
        return $this->status;
    }

    public function setStatus(ShopStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function sells(): bool
    {
        return $this->status->sells();
    }

    public function getSlotCount(): int
    {
        return $this->slotCount;
    }

    public function setSlotCount(int $slotCount): self
    {
        $this->slotCount = max(1, min(self::MAX_SLOTS, $slotCount));

        return $this;
    }

    public function getVaultGils(): int
    {
        return $this->vaultGils;
    }

    public function creditVault(int $gils): void
    {
        if ($gils < 0) {
            throw new \InvalidArgumentException('Une recette ne peut pas etre negative.');
        }

        $this->vaultGils += $gils;
    }

    /**
     * Vide la caisse et retourne ce qu'elle contenait.
     */
    public function emptyVault(): int
    {
        $amount = $this->vaultGils;
        $this->vaultGils = 0;

        return $amount;
    }

    public function getOpenedAt(): \DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function getRentDueAt(): ?\DateTimeImmutable
    {
        return $this->rentDueAt;
    }

    public function setRentDueAt(?\DateTimeImmutable $rentDueAt): self
    {
        $this->rentDueAt = $rentDueAt;

        return $this;
    }
}
