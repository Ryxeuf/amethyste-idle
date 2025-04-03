<?php

namespace App\Entity\App;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
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
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "step", type: "integer", options: ["default" => 0])]
    private int $step = 0;

    /**
     * @var Player[]|array|ArrayCollection|PersistentCollection
     */
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: "fight")]
    private $players;

    /**
     * @var Mob[]|array|ArrayCollection|PersistentCollection
     */
    #[ORM\OneToMany(targetEntity: Mob::class, mappedBy: "fight")]
    private $mobs;

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

    /**
     * @return Player[]|ArrayCollection|PersistentCollection
     */
    public function getPlayers(): array|ArrayCollection|PersistentCollection
    {
        return $this->players;
    }

    /**
     * @param Player[] $players
     */
    public function setPlayers(array|ArrayCollection $players): void
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

    /**
     * @return Mob[]|array|ArrayCollection|PersistentCollection
     */
    public function getMobs(): ArrayCollection|array|PersistentCollection
    {
        return $this->mobs;
    }

    /**
     * @param Mob[]|array|ArrayCollection|PersistentCollection $mobs
     */
    public function setMobs(ArrayCollection|array|PersistentCollection $mobs): void
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
