<?php

namespace App\Entity\App;

use App\Enum\InfluenceActivityType;
use App\Repository\SettlementWeeklyWorkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Le chantier de la semaine d'un foyer (RET-05).
 *
 * A la maniere de la Restauration d'Ishgard : la maree dit **ou va** la ville,
 * le chantier dit **ce qu'elle attend cette semaine**. C'est ce qui donne au
 * foyer une voix — jusqu'ici il encaissait le sediment en silence, sans rien
 * demander a personne.
 *
 * Les besoins vivent dans une colonne JSON plutot que dans une table : ils sont
 * **lus ensemble et ecrits ensemble**, ne se referencent nulle part ailleurs, et
 * meurent avec la semaine. Les contributions, elles, ont leur table — parce
 * qu'elles sont nominatives et que c'est le point : un chantier rempli doit
 * pouvoir dire **qui** l'a rempli.
 *
 * L'unicite porte sur le couple foyer/semaine : un foyer n'a qu'un chantier a la
 * fois, et le rejouer ne le duplique pas.
 */
#[ORM\Entity(repositoryClass: SettlementWeeklyWorkRepository::class)]
#[ORM\Table(name: 'settlement_weekly_work')]
#[ORM\UniqueConstraint(name: 'uq_settlement_weekly_work', columns: ['settlement_id', 'week_key'])]
class SettlementWeeklyWork
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Settlement::class)]
    #[ORM\JoinColumn(name: 'settlement_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Settlement $settlement;

    /**
     * Semaine ISO (`2026-W31`) — la meme clef que les defis et les commissions,
     * pour que tous les rendez-vous tombent le meme lundi (contrat RET-07).
     */
    #[ORM\Column(name: 'week_key', type: 'string', length: 10)]
    private string $weekKey;

    /**
     * Besoins de la semaine.
     *
     * @var list<array{activity: string, target: int, progress: int}>
     */
    #[ORM\Column(name: 'needs', type: 'json')]
    private array $needs = [];

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    /**
     * @var Collection<int, SettlementWeeklyWorkContribution>
     */
    #[ORM\OneToMany(targetEntity: SettlementWeeklyWorkContribution::class, mappedBy: 'work', cascade: ['persist'])]
    private Collection $contributions;

    /**
     * @param list<array{activity: string, target: int, progress: int}> $needs
     */
    public function __construct(Settlement $settlement, string $weekKey, array $needs = [])
    {
        $this->settlement = $settlement;
        $this->weekKey = $weekKey;
        $this->needs = $needs;
        $this->contributions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettlement(): Settlement
    {
        return $this->settlement;
    }

    public function getWeekKey(): string
    {
        return $this->weekKey;
    }

    /**
     * @return list<array{activity: string, target: int, progress: int}>
     */
    public function getNeeds(): array
    {
        return $this->needs;
    }

    /**
     * Avance le besoin correspondant a cette activite.
     *
     * L'avancement **s'ecrete a la cible**, contrairement a la commission
     * personnelle : ici le compteur est collectif, et laisser vingt joueurs
     * empiler du depassement sur un besoin rempli masquerait ceux qui restent a
     * remplir — or c'est exactement ce que le chantier existe pour montrer.
     *
     * @return int unites reellement retenues
     */
    public function contribute(InfluenceActivityType $activity, int $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $retained = 0;
        foreach ($this->needs as $index => $need) {
            if ($need['activity'] !== $activity->value) {
                continue;
            }

            $room = max(0, $need['target'] - $need['progress']);
            $retained = min($amount, $room);
            if ($retained > 0) {
                $this->needs[$index]['progress'] = $need['progress'] + $retained;
            }

            break;
        }

        return $retained;
    }

    public function isComplete(): bool
    {
        foreach ($this->needs as $need) {
            if ($need['progress'] < $need['target']) {
                return false;
            }
        }

        return $this->needs !== [];
    }

    /**
     * Avancement global, en pourcentage, pondere par les cibles.
     *
     * Un besoin de 200 unites et un besoin de 15 ne pesent pas pareil dans
     * l'effort ; les moyenner a poids egal donnerait une jauge qui bondit quand
     * le petit besoin se remplit et stagne ensuite.
     */
    public function getProgressPercent(): int
    {
        $target = 0;
        $progress = 0;
        foreach ($this->needs as $need) {
            $target += $need['target'];
            $progress += min($need['progress'], $need['target']);
        }

        return $target > 0 ? (int) round($progress * 100 / $target) : 0;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    /**
     * @return Collection<int, SettlementWeeklyWorkContribution>
     */
    public function getContributions(): Collection
    {
        return $this->contributions;
    }

    public function addContribution(SettlementWeeklyWorkContribution $contribution): self
    {
        if (!$this->contributions->contains($contribution)) {
            $this->contributions->add($contribution);
        }

        return $this;
    }
}
