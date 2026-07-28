<?php

namespace App\Entity\App;

use App\Enum\SettlementIndex;
use App\Repository\SettlementContributionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Ce qu'un joueur a depose dans un foyer (FOY-02).
 *
 * Une ligne par couple joueur/foyer, pas une par jour : le compteur journalier
 * se remet a zero tout seul en changeant de date. Un joueur qui frequente une
 * zone pendant six mois n'y laisse donc qu'une ligne, pas cent-quatre-vingts.
 *
 * **Le report fractionnaire est la raison d'etre de cette table.** Traverser une
 * zone y depose 0,2 grain (BALANCE § 23.1) : arrondi a chaque evenement, ce
 * serait zero, et la ligne du tableau serait morte sans que rien ne le dise —
 * exactement le defaut muet que ce pilier passe son temps a fermer. Le reste est
 * donc conserve, **par indice**, jusqu'a former un grain entier. Vingt
 * traversees dans la journee valent bien les quatre grains annonces.
 *
 * Meme parti pris que `GatherService::regenerate()` : ce qui n'atteint pas
 * l'unite n'est pas perdu, il attend.
 */
#[ORM\Entity(repositoryClass: SettlementContributionRepository::class)]
#[ORM\Table(name: 'settlement_contribution')]
#[ORM\UniqueConstraint(name: 'uq_settlement_contribution', columns: ['settlement_id', 'player_id'])]
#[ORM\Index(name: 'idx_settlement_contribution_player', columns: ['player_id'])]
class SettlementContribution
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Settlement::class)]
    #[ORM\JoinColumn(name: 'settlement_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Settlement $settlement;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    /**
     * Grains entiers deposes depuis toujours — ce que l'ecran de zone montrera
     * comme « votre contribution » (FOY-04).
     */
    #[ORM\Column(name: 'grains', type: 'integer', options: ['default' => 0])]
    private int $grains = 0;

    #[ORM\Column(name: 'carry_trade', type: 'float', options: ['default' => 0])]
    private float $carryTrade = 0.0;

    #[ORM\Column(name: 'carry_war', type: 'float', options: ['default' => 0])]
    private float $carryWar = 0.0;

    #[ORM\Column(name: 'carry_lore', type: 'float', options: ['default' => 0])]
    private float $carryLore = 0.0;

    #[ORM\Column(name: 'carry_rite', type: 'float', options: ['default' => 0])]
    private float $carryRite = 0.0;

    /**
     * Grains deposes le jour de `dailyDate` — l'assiette du plafond anti-exploit.
     */
    #[ORM\Column(name: 'daily_grains', type: 'integer', options: ['default' => 0])]
    private int $dailyGrains = 0;

    #[ORM\Column(name: 'daily_date', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dailyDate = null;

    public function __construct(Settlement $settlement, Player $player)
    {
        $this->settlement = $settlement;
        $this->player = $player;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettlement(): Settlement
    {
        return $this->settlement;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getGrains(): int
    {
        return $this->grains;
    }

    public function addGrains(int $grains): self
    {
        $this->grains += $grains;

        return $this;
    }

    public function getCarry(SettlementIndex $index): float
    {
        return match ($index) {
            SettlementIndex::Trade => $this->carryTrade,
            SettlementIndex::War => $this->carryWar,
            SettlementIndex::Lore => $this->carryLore,
            SettlementIndex::Rite => $this->carryRite,
        };
    }

    public function setCarry(SettlementIndex $index, float $carry): self
    {
        $carry = max(0.0, $carry);

        match ($index) {
            SettlementIndex::Trade => $this->carryTrade = $carry,
            SettlementIndex::War => $this->carryWar = $carry,
            SettlementIndex::Lore => $this->carryLore = $carry,
            SettlementIndex::Rite => $this->carryRite = $carry,
        };

        return $this;
    }

    /**
     * Grains deja deposes aujourd'hui.
     *
     * Le compteur appartient a une date : consulte un autre jour, il vaut zero
     * sans qu'aucune tache planifiee n'ait eu a le remettre a zero. Un plafond
     * journalier qui dependrait d'un cron serait un plafond qui saute la nuit ou
     * le cron ne tourne pas.
     */
    public function getDailyGrains(\DateTimeImmutable $today): int
    {
        if ($this->dailyDate === null || $this->dailyDate->format('Y-m-d') !== $today->format('Y-m-d')) {
            return 0;
        }

        return $this->dailyGrains;
    }

    public function addDailyGrains(\DateTimeImmutable $today, int $grains): self
    {
        $this->dailyGrains = $this->getDailyGrains($today) + $grains;
        $this->dailyDate = $today;

        return $this;
    }

    public function getDailyDate(): ?\DateTimeImmutable
    {
        return $this->dailyDate;
    }
}
