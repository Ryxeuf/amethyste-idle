<?php

namespace App\Entity\App;

use App\Enum\RankingTab;
use App\Repository\PlayerRankingBaselineRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Total cumule d'un joueur fige a la cloture d'une saison (tache 132).
 *
 * Les trois classements reposent sur des compteurs **cumulatifs** que rien
 * n'horodate : `PlayerBestiary::killCount`, le nombre de quetes achevees,
 * `DomainExperience::totalExperience`. Impossible, donc, de sommer « ce qui a
 * ete fait pendant la saison » par une fenetre temporelle.
 *
 * La reference resout le probleme sans toucher aux compteurs : le classement de
 * la saison en cours est `cumul actuel - reference`, et la reference est
 * reecrite a chaque cloture. Une ligne par (joueur, onglet) — la table ne
 * grossit pas avec les saisons, seulement avec la population.
 */
#[ORM\Entity(repositoryClass: PlayerRankingBaselineRepository::class)]
#[ORM\Table(name: 'player_ranking_baseline')]
#[ORM\UniqueConstraint(name: 'uniq_ranking_baseline_player_tab', columns: ['player_id', 'tab'])]
class PlayerRankingBaseline
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(name: 'tab', type: 'string', length: 20, enumType: RankingTab::class)]
    private RankingTab $tab;

    #[ORM\Column(name: 'baseline_value', type: 'bigint')]
    private string $value;

    /**
     * Numero de la saison a la cloture de laquelle la reference a ete prise.
     *
     * Purement diagnostique : il permet de reconnaitre une reference restee en
     * arriere (cloture interrompue) sans avoir a relire les journaux.
     */
    #[ORM\Column(name: 'season_number', type: 'integer')]
    private int $seasonNumber;

    #[ORM\Column(name: 'captured_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $capturedAt;

    public function __construct(Player $player, RankingTab $tab, int $value, int $seasonNumber)
    {
        $this->player = $player;
        $this->tab = $tab;
        $this->seasonNumber = $seasonNumber;
        $this->capturedAt = new \DateTimeImmutable();
        $this->setValue($value, $seasonNumber);
    }

    /**
     * Reecrit la reference a la cloture suivante.
     *
     * Les compteurs sources ne decroissent jamais ; une valeur en recul
     * signalerait une corruption, et la laisser passer transformerait
     * silencieusement le classement suivant en cadeau.
     */
    public function setValue(int $value, int $seasonNumber): void
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Une reference de classement ne peut pas etre negative.');
        }

        $this->value = (string) $value;
        $this->seasonNumber = $seasonNumber;
        $this->capturedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getTab(): RankingTab
    {
        return $this->tab;
    }

    public function getValue(): int
    {
        return (int) $this->value;
    }

    public function getSeasonNumber(): int
    {
        return $this->seasonNumber;
    }

    public function getCapturedAt(): \DateTimeImmutable
    {
        return $this->capturedAt;
    }
}
