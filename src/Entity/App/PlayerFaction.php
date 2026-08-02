<?php

namespace App\Entity\App;

use App\Entity\Game\Faction;
use App\Enum\ReputationTier;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'player_factions')]
#[ORM\UniqueConstraint(name: 'player_faction_unique', columns: ['player_id', 'faction_id'])]
class PlayerFaction
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: 'faction_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Faction $faction;

    #[ORM\Column(name: 'reputation', type: 'integer', options: ['default' => 0])]
    private int $reputation = 0;

    /**
     * FAC-02 : le plafond journalier des gestes, materialise sur la ligne.
     *
     * Meme doctrine que le plafond d'influence de guilde (`InfluenceAntiExploit`),
     * mais sans table de log : la reputation n'a pas de journal, et un couple
     * (jour, cumul) sur la ligne suffit — le compteur repart de zero des que la
     * cle de jour change, sans cron ni fenetre glissante.
     */
    #[ORM\Column(name: 'daily_gesture_gained', type: 'integer', options: ['default' => 0])]
    private int $dailyGestureGained = 0;

    #[ORM\Column(name: 'daily_gesture_key', type: 'string', length: 10, nullable: true)]
    private ?string $dailyGestureKey = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getFaction(): Faction
    {
        return $this->faction;
    }

    public function setFaction(Faction $faction): self
    {
        $this->faction = $faction;

        return $this;
    }

    public function getReputation(): int
    {
        return $this->reputation;
    }

    public function setReputation(int $reputation): self
    {
        $this->reputation = $reputation;

        return $this;
    }

    public function addReputation(int $amount): self
    {
        $this->reputation += $amount;

        return $this;
    }

    /**
     * Ce que les gestes ont deja rapporte sur cette faction pour la cle de
     * jour donnee. Une cle differente = un autre jour = compteur a zero.
     */
    public function gestureGainedOn(string $dayKey): int
    {
        return $this->dailyGestureKey === $dayKey ? $this->dailyGestureGained : 0;
    }

    public function recordGestureGain(string $dayKey, int $amount): self
    {
        if ($this->dailyGestureKey !== $dayKey) {
            $this->dailyGestureKey = $dayKey;
            $this->dailyGestureGained = 0;
        }
        $this->dailyGestureGained += $amount;

        return $this;
    }

    public function getTier(): ReputationTier
    {
        return ReputationTier::fromReputation($this->reputation);
    }

    public function getProgressPercent(): int
    {
        $tier = $this->getTier();
        $nextTier = $tier->nextTier();

        if (null === $nextTier) {
            return 100;
        }

        $currentThreshold = $tier->threshold();
        $nextThreshold = $nextTier->threshold();
        $range = $nextThreshold - $currentThreshold;

        if ($range <= 0) {
            return 100;
        }

        $progress = $this->reputation - $currentThreshold;

        return (int) min(100, floor(($progress / $range) * 100));
    }
}
