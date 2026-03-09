<?php

namespace App\Entity\App;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: "fight")]
class Fight
{
    use TimestampableEntity;

    public function __construct()
    {
        $this->mobs = new ArrayCollection();
        $this->players = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "step", type: "integer", options: ["default" => 0])]
    private int $step = 0;

    /** @var Collection<int, Player> */
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: "fight")]
    private Collection $players;

    /** @var Collection<int, Mob> */
    #[ORM\OneToMany(targetEntity: Mob::class, mappedBy: "fight")]
    private Collection $mobs;

    #[ORM\Column(name: "in_progress", type: "boolean", options: ["default" => 0])]
    private bool $inProgress = false;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /** @return Collection<int, Player> */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function setPlayers(Collection $players): void
    {
        $this->players = $players;
    }

    public function addPlayer(Player $player): void
    {
        $this->players->add($player);
    }

    public function removePlayer(Player $player): void
    {
        $this->players->removeElement($player);
    }

    public function addMob(Mob $mob): void
    {
        $this->mobs->add($mob);
    }

    /** @return Collection<int, Mob> */
    public function getMobs(): Collection
    {
        return $this->mobs;
    }

    public function setMobs(Collection $mobs): void
    {
        $this->mobs = $mobs;
    }

    /**
     * @return int
     */
    public function getStep(): int
    {
        return $this->step;
    }

    /**
     * @param int $step
     */
    public function setStep(int $step): void
    {
        $this->step = $step;
    }

    /**
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->inProgress;
    }

    /**
     * @param bool $inProgress
     */
    public function setInProgress(bool $inProgress): void
    {
        $this->inProgress = $inProgress;
    }

    public function isTerminated(): bool
    {
        $allPlayersDead = $this->players->filter(function (Player $player) {
            return $player->isDead();
        })->count() === $this->players->count();

        $allMobsDead = $this->mobs->filter(function (Mob $mob) {
            return $mob->isDead();
        })->count() === $this->mobs->count();

        return $allPlayersDead || $allMobsDead;
        }

    public function isVictory(): bool
    {
        return $this->mobs->filter(function (Mob $mob) {
            return $mob->isDead();
        })->count() === $this->mobs->count();
    }

    public function isDefeat(): bool
    {
        return $this->players->filter(function (Player $player) {
            return $player->isDead();
        })->count() === $this->players->count();
    }
}
