<?php

namespace App\Entity\App;

use App\Entity\Game\Recipe;
use App\Enum\CraftOrderStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Commande de craft (ECO-05).
 *
 * Le troisieme canal d'echange, et le seul qui produise du stuff **lie** : le
 * commanditaire fournit les materiaux et la commission, l'artisan fournit le
 * plan et le savoir-faire. L'objet nait entre leurs mains a tous les deux.
 *
 * L'escrow est **des deux cotes des la creation** : les materiaux quittent
 * l'inventaire du commanditaire et la commission quitte sa bourse. Sans cela,
 * un artisan pourrait prendre une commande, la travailler, et decouvrir a la
 * livraison que le client a revendu les materiaux entre-temps — le temps de
 * craft etant reel (time-gating), la fenetre d'abus serait large.
 */
#[ORM\Entity(repositoryClass: \App\Repository\CraftOrderRepository::class)]
#[ORM\Table(name: 'craft_order')]
#[ORM\Index(name: 'idx_craft_order_status', columns: ['status'])]
#[ORM\Index(name: 'idx_craft_order_board', columns: ['region_id', 'status', 'expires_at'])]
#[ORM\Index(name: 'idx_craft_order_requester', columns: ['requester_id'])]
#[ORM\Index(name: 'idx_craft_order_crafter', columns: ['crafter_id'])]
class CraftOrder
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'requester_id', referencedColumnName: 'id', nullable: false)]
    private Player $requester;

    #[ORM\ManyToOne(targetEntity: Recipe::class)]
    #[ORM\JoinColumn(name: 'recipe_id', referencedColumnName: 'id', nullable: false)]
    private Recipe $recipe;

    /**
     * Artisan ayant pris la commande. Nul tant qu'elle est ouverte.
     */
    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'crafter_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Player $crafter = null;

    /**
     * Marche regional ou la commande est publiee, fige au depot — meme regle que
     * l'hotel des ventes (ECO-03). Un artisan ne voit que les commandes de la
     * region ou il se trouve.
     */
    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(name: 'region_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Region $region = null;

    /**
     * Materiaux en escrow : ils ont quitte l'inventaire du commanditaire et
     * n'appartiennent plus a personne tant que la commande vit.
     *
     * @var Collection<int, PlayerItem>
     */
    #[ORM\OneToMany(mappedBy: 'craftOrder', targetEntity: PlayerItem::class)]
    private Collection $materials;

    /**
     * Commission en escrow, prelevee sur la bourse du commanditaire a la
     * creation.
     */
    #[ORM\Column(name: 'commission', type: 'integer')]
    private int $commission = 0;

    /**
     * Qualite minimale exigee, ou null si le commanditaire n'en impose aucune.
     */
    #[ORM\Column(name: 'min_quality', type: 'string', length: 20, nullable: true)]
    private ?string $minQuality = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20, enumType: CraftOrderStatus::class)]
    private CraftOrderStatus $status = CraftOrderStatus::Open;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'claimed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $claimedAt = null;

    #[ORM\Column(name: 'fulfilled_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fulfilledAt = null;

    public function __construct()
    {
        $this->materials = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequester(): Player
    {
        return $this->requester;
    }

    public function setRequester(Player $requester): self
    {
        $this->requester = $requester;

        return $this;
    }

    public function getRecipe(): Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(Recipe $recipe): self
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getCrafter(): ?Player
    {
        return $this->crafter;
    }

    public function setCrafter(?Player $crafter): self
    {
        $this->crafter = $crafter;

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): self
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return Collection<int, PlayerItem>
     */
    public function getMaterials(): Collection
    {
        return $this->materials;
    }

    public function addMaterial(PlayerItem $material): self
    {
        if (!$this->materials->contains($material)) {
            $this->materials->add($material);
            $material->setCraftOrder($this);
        }

        return $this;
    }

    public function removeMaterial(PlayerItem $material): self
    {
        if ($this->materials->removeElement($material) && $material->getCraftOrder() === $this) {
            $material->setCraftOrder(null);
        }

        return $this;
    }

    public function getCommission(): int
    {
        return $this->commission;
    }

    public function setCommission(int $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function getMinQuality(): ?string
    {
        return $this->minQuality;
    }

    public function setMinQuality(?string $minQuality): self
    {
        $this->minQuality = $minQuality;

        return $this;
    }

    public function getStatus(): CraftOrderStatus
    {
        return $this->status;
    }

    public function setStatus(CraftOrderStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getClaimedAt(): ?\DateTimeImmutable
    {
        return $this->claimedAt;
    }

    public function setClaimedAt(?\DateTimeImmutable $claimedAt): self
    {
        $this->claimedAt = $claimedAt;

        return $this;
    }

    public function getFulfilledAt(): ?\DateTimeImmutable
    {
        return $this->fulfilledAt;
    }

    public function setFulfilledAt(?\DateTimeImmutable $fulfilledAt): self
    {
        $this->fulfilledAt = $fulfilledAt;

        return $this;
    }

    public function isOpen(): bool
    {
        return CraftOrderStatus::Open === $this->status;
    }

    public function isClaimed(): bool
    {
        return CraftOrderStatus::Claimed === $this->status;
    }

    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        return $this->expiresAt <= ($now ?? new \DateTimeImmutable());
    }
}
