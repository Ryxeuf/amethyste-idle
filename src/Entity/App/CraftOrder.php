<?php

namespace App\Entity\App;

use App\Entity\Game\Recipe;
use App\Enum\CraftOrderStatus;
use App\Enum\Purity;
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
#[ORM\Index(name: 'idx_craft_order_workshop', columns: ['crafter_id', 'status'])]
#[ORM\Index(name: 'idx_craft_order_target', columns: ['target_crafter_id', 'status'])]
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
    /**
     * Artisan cible d'une **commande directe** (ECO-07b).
     *
     * Quand il est renseigne, la commande ne parait pas au tableau public et
     * lui seul peut la prendre. C'est le canal inverse du tableau : le client
     * choisit son artisan au lieu de choisir son prix.
     */
    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'target_crafter_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Player $targetCrafter = null;

    /**
     * Guilde commanditaire (RET-03), ou `null` pour une commande ordinaire.
     *
     * Une commande de guilde n'est **pas** une commande ordinaire mieux payee :
     * elle est invisible au tableau regional et n'est prenable que par un
     * membre. C'est ce qui en fait un rendez-vous interne — « on compte sur
     * moi » a cadence fixe — plutot qu'une enchere de plus.
     */
    #[ORM\ManyToOne(targetEntity: Guild::class)]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Guild $guild = null;

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

    /**
     * Bande de purete minimale exigee des materiaux (ECO-23).
     *
     * Repond a la question laissee ouverte par GAME_PRINCIPLES § 6 : un client
     * peut-il exiger une matiere d'une certaine purete ? Oui — et c'est ce qui
     * donne au prospecteur un **client**, pas seulement un marche. Sans cette
     * exigence, la bande n'aurait de valeur qu'a la revente, et le savoir du
     * prospecteur resterait speculatif.
     *
     * A ne pas confondre avec `minQuality`, qui porte sur l'objet **produit** :
     * l'un exige de l'artisan, l'autre de la matiere.
     */
    #[ORM\Column(name: 'min_purity', type: 'string', length: 20, nullable: true, enumType: Purity::class)]
    private ?Purity $minPurity = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20, enumType: CraftOrderStatus::class)]
    private CraftOrderStatus $status = CraftOrderStatus::Open;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'claimed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $claimedAt = null;

    #[ORM\Column(name: 'fulfilled_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fulfilledAt = null;

    /**
     * Fin du travail d'atelier, posee a la prise en charge (ECO-07).
     *
     * C'est le `craftingTime` de la recette rendu **reel** : jusqu'ici il etait
     * affiche au joueur (« Temps : 5s ») sans que rien ne l'applique. Sur ce
     * canal, l'attente est ce qui distingue une commande d'un achat — sans elle,
     * prendre et livrer seraient le meme clic.
     */
    #[ORM\Column(name: 'ready_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readyAt = null;

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

    public function getTargetCrafter(): ?Player
    {
        return $this->targetCrafter;
    }

    public function setTargetCrafter(?Player $targetCrafter): self
    {
        $this->targetCrafter = $targetCrafter;

        return $this;
    }

    public function isDirect(): bool
    {
        return null !== $this->targetCrafter;
    }

    public function getGuild(): ?Guild
    {
        return $this->guild;
    }

    public function setGuild(?Guild $guild): self
    {
        $this->guild = $guild;

        return $this;
    }

    public function isGuildOrder(): bool
    {
        return $this->guild !== null;
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

    public function getMinPurity(): ?Purity
    {
        return $this->minPurity;
    }

    public function setMinPurity(?Purity $minPurity): self
    {
        $this->minPurity = $minPurity;

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

    public function getReadyAt(): ?\DateTimeImmutable
    {
        return $this->readyAt;
    }

    public function setReadyAt(?\DateTimeImmutable $readyAt): self
    {
        $this->readyAt = $readyAt;

        return $this;
    }

    /**
     * Le travail d'atelier est-il termine ?
     *
     * Une commande sans `readyAt` est prete : les commandes prises avant ECO-07
     * n'ont pas d'echeance de travail, et les bloquer indefiniment punirait les
     * artisans pour une migration.
     */
    public function isReady(?\DateTimeImmutable $now = null): bool
    {
        return null === $this->readyAt || $this->readyAt <= ($now ?? new \DateTimeImmutable());
    }

    /**
     * Secondes restantes avant la fin du travail (0 si termine).
     */
    public function getRemainingWorkSeconds(?\DateTimeImmutable $now = null): int
    {
        if (null === $this->readyAt) {
            return 0;
        }

        return max(0, $this->readyAt->getTimestamp() - ($now ?? new \DateTimeImmutable())->getTimestamp());
    }
}
