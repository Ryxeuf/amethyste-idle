<?php

namespace App\Entity\App;

use App\Entity\Game\Dungeon;
use App\Entity\Game\Monster;
use App\Repository\GroupDungeonRunRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Donjon de groupe semi-synchrone (pivot PBBG, ZON-19).
 *
 * Un leader forme un groupe (systeme `Party` existant) parmi les joueurs
 * presents dans une zone, puis lance le donjon : une sequence de combats tour
 * par tour partagee. Ce run agrege le donjon, la zone d'origine, le leader et
 * un instantane des membres (fige a la formation, decouple de la `Party`
 * mutable).
 *
 * ZON-19 est decoupe en sous-jalons : cette premiere livraison pose le modele
 * et la formation du groupe ; la boucle de combat tour par tour (delai par
 * tour, action par defaut) et l'experience temps reel Mercure arrivent ensuite.
 */
#[ORM\Entity(repositoryClass: GroupDungeonRunRepository::class)]
#[ORM\Table(name: 'group_dungeon_run')]
#[ORM\Index(name: 'idx_group_dungeon_run_leader', columns: ['leader_id'])]
#[ORM\Index(name: 'idx_group_dungeon_run_status', columns: ['status'])]
class GroupDungeonRun
{
    public const STATUS_FORMING = 'forming';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';
    // DON-02 : la rencontre riposte — un donjon peut desormais etre perdu.
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Dungeon::class)]
    #[ORM\JoinColumn(name: 'dungeon_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Dungeon $dungeon;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Zone $zone = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'leader_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $leader;

    #[ORM\Column(name: 'status', type: 'string', length: 20, options: ['default' => self::STATUS_IN_PROGRESS])]
    private string $status = self::STATUS_IN_PROGRESS;

    /** Etape courante de la sequence de combats (0 = premiere), alimentee par la boucle de combat. */
    #[ORM\Column(name: 'current_step', type: 'integer', options: ['default' => 0])]
    private int $currentStep = 0;

    // ── Etat de combat tour par tour partage (ZON-19 sous-jalon 2) ──

    /**
     * Le monstre que l'etape courante incarne (DON-03).
     *
     * La rencontre n'est plus un sac de PV abstrait : elle est un `Monster`
     * du palier de la zone, dont la vie dimensionne la barre et dont le
     * coup nourrit la riposte. `SET NULL` en cas de suppression : le run
     * retombe alors sur les curseurs historiques, il ne casse pas.
     */
    #[ORM\ManyToOne(targetEntity: Monster::class)]
    #[ORM\JoinColumn(name: 'encounter_monster_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Monster $encounterMonster = null;

    #[ORM\Column(name: 'encounter_hp_max', type: 'integer', options: ['default' => 0])]
    private int $encounterHpMax = 0;

    #[ORM\Column(name: 'encounter_hp_current', type: 'integer', options: ['default' => 0])]
    private int $encounterHpCurrent = 0;

    /**
     * Ordre de tour : liste des ids joueurs (instantane a l'initialisation du
     * combat). Vide tant que le combat n'est pas initialise.
     *
     * @var list<int>
     */
    #[ORM\Column(name: 'turn_order', type: 'json')]
    private array $turnOrder = [];

    #[ORM\Column(name: 'active_turn_index', type: 'integer', options: ['default' => 0])]
    private int $activeTurnIndex = 0;

    /** Echeance du tour courant : au-dela, action par defaut (attaque de base). */
    #[ORM\Column(name: 'turn_deadline', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $turnDeadline = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'ended_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    /** @var Collection<int, GroupDungeonMember> */
    #[ORM\OneToMany(targetEntity: GroupDungeonMember::class, mappedBy: 'run', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $members;

    public function __construct(Dungeon $dungeon, Player $leader, ?Zone $zone)
    {
        $this->dungeon = $dungeon;
        $this->leader = $leader;
        $this->zone = $zone;
        $this->createdAt = new \DateTimeImmutable();
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function getLeader(): Player
    {
        return $this->leader;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        if (\in_array($status, [self::STATUS_COMPLETED, self::STATUS_ABANDONED, self::STATUS_FAILED], true) && null === $this->endedAt) {
            $this->endedAt = new \DateTimeImmutable();
        }
    }

    public function isActive(): bool
    {
        return \in_array($this->status, [self::STATUS_FORMING, self::STATUS_IN_PROGRESS], true);
    }

    public function getEncounterMonster(): ?Monster
    {
        return $this->encounterMonster;
    }

    public function setEncounterMonster(?Monster $encounterMonster): void
    {
        $this->encounterMonster = $encounterMonster;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function setCurrentStep(int $currentStep): void
    {
        $this->currentStep = max(0, $currentStep);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function getEncounterHpMax(): int
    {
        return $this->encounterHpMax;
    }

    public function getEncounterHpCurrent(): int
    {
        return $this->encounterHpCurrent;
    }

    public function setEncounterHp(int $max, int $current): void
    {
        $this->encounterHpMax = max(0, $max);
        $this->encounterHpCurrent = max(0, min($current, $this->encounterHpMax));
    }

    public function damageEncounter(int $damage): int
    {
        $dealt = min(max(0, $damage), $this->encounterHpCurrent);
        $this->encounterHpCurrent -= $dealt;

        return $dealt;
    }

    public function getEncounterHpPercent(): int
    {
        return $this->encounterHpMax > 0 ? (int) round(($this->encounterHpCurrent / $this->encounterHpMax) * 100) : 0;
    }

    /** @return list<int> */
    public function getTurnOrder(): array
    {
        return $this->turnOrder;
    }

    /** @param int[] $turnOrder */
    public function setTurnOrder(array $turnOrder): void
    {
        $this->turnOrder = array_values($turnOrder);
    }

    public function isCombatInitialized(): bool
    {
        return [] !== $this->turnOrder;
    }

    public function getActiveTurnIndex(): int
    {
        return $this->activeTurnIndex;
    }

    public function getActivePlayerId(): ?int
    {
        return $this->turnOrder[$this->activeTurnIndex] ?? null;
    }

    public function advanceTurn(): void
    {
        if ([] === $this->turnOrder) {
            return;
        }
        $this->activeTurnIndex = ($this->activeTurnIndex + 1) % \count($this->turnOrder);
    }

    public function getTurnDeadline(): ?\DateTimeImmutable
    {
        return $this->turnDeadline;
    }

    public function setTurnDeadline(?\DateTimeImmutable $turnDeadline): void
    {
        $this->turnDeadline = $turnDeadline;
    }

    /** @return Collection<int, GroupDungeonMember> */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(GroupDungeonMember $member): void
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }
    }

    /** @return list<Player> */
    public function getMemberPlayers(): array
    {
        return array_map(static fn (GroupDungeonMember $m): Player => $m->getPlayer(), $this->members->toArray());
    }
}
