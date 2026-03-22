<?php

namespace App\Entity\App;

use App\Enum\Element;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: \App\Repository\FightRepository::class)]
#[ORM\Table(name: 'fight')]
class Fight
{
    use TimestampableEntity;

    public function __construct()
    {
        $this->mobs = new ArrayCollection();
        $this->players = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'step', type: 'integer', options: ['default' => 0])]
    private int $step = 0;

    /** @var Collection<int, Player> */
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'fight')]
    private Collection $players;

    /** @var Collection<int, Mob> */
    #[ORM\OneToMany(targetEntity: Mob::class, mappedBy: 'fight')]
    private Collection $mobs;

    #[ORM\Column(name: 'in_progress', type: 'boolean', options: ['default' => 0])]
    private bool $inProgress = false;

    #[ORM\Column(name: 'last_element_used', type: 'string', length: 25, nullable: true, enumType: Element::class)]
    private ?Element $lastElementUsed = null;

    #[ORM\Column(name: 'cooldowns', type: 'json', nullable: true)]
    private ?array $cooldowns = null;

    public function getId(): int
    {
        return $this->id;
    }

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
     * Get the first mob in the fight (convenience for single-mob fights).
     */
    public function getMob(): ?Mob
    {
        return $this->mobs->first() ?: null;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function setStep(int $step): void
    {
        $this->step = $step;
    }

    public function isInProgress(): bool
    {
        return $this->inProgress;
    }

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

    public function getLastElementUsed(): ?Element
    {
        return $this->lastElementUsed;
    }

    public function setLastElementUsed(?Element $lastElementUsed): void
    {
        $this->lastElementUsed = $lastElementUsed;
    }

    public function getCooldowns(): ?array
    {
        return $this->cooldowns;
    }

    public function setCooldowns(?array $cooldowns): void
    {
        $this->cooldowns = $cooldowns;
    }

    public function getSpellCooldown(string $entityKey, string $spellSlug): int
    {
        return $this->cooldowns[$entityKey][$spellSlug] ?? 0;
    }

    public function setSpellCooldown(string $entityKey, string $spellSlug, int $turns): void
    {
        $cooldowns = $this->cooldowns ?? [];
        $cooldowns[$entityKey][$spellSlug] = $turns;
        $this->cooldowns = $cooldowns;
    }

    public function isSpellOnCooldown(string $entityKey, string $spellSlug): bool
    {
        return $this->getSpellCooldown($entityKey, $spellSlug) > 0;
    }

    public function decrementAllCooldowns(): void
    {
        if ($this->cooldowns === null) {
            return;
        }

        $cooldowns = $this->cooldowns;
        foreach ($cooldowns as $entityKey => $spells) {
            foreach ($spells as $spellSlug => $turns) {
                $cooldowns[$entityKey][$spellSlug] = max(0, $turns - 1);
            }
        }
        $this->cooldowns = $cooldowns;
    }
}
