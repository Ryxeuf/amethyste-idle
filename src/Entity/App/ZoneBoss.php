<?php

namespace App\Entity\App;

use App\Entity\Game\Monster;
use App\Repository\ZoneBossRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Boss de zone asynchrone (pivot PBBG, ZON-18).
 *
 * Un boss a large pool de PV partage, rattache a un evenement de zone
 * (`GameEvent`, ZON-15) et donc a une zone et une fenetre temporelle. Chaque
 * joueur present depense de l'energie pour lancer ses assauts quand il le
 * souhaite (aucune presence simultanee requise) ; les degats s'accumulent sur
 * le pool partage et alimentent la contribution
 * (`PlayerZoneEventParticipation.contribution`). A 0 PV, le loot est distribue
 * a la contribution — generalisation de `WorldBossLootDistributor` au modele
 * zone, sans combat tour par tour.
 */
#[ORM\Entity(repositoryClass: ZoneBossRepository::class)]
#[ORM\Table(name: 'zone_boss')]
#[ORM\UniqueConstraint(name: 'uniq_zone_boss_event', columns: ['game_event_id'])]
class ZoneBoss
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: GameEvent::class)]
    #[ORM\JoinColumn(name: 'game_event_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private GameEvent $gameEvent;

    #[ORM\ManyToOne(targetEntity: Monster::class)]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\Column(name: 'hp_max', type: 'integer')]
    private int $hpMax;

    #[ORM\Column(name: 'hp_current', type: 'integer')]
    private int $hpCurrent;

    #[ORM\Column(name: 'defeated', type: 'boolean', options: ['default' => false])]
    private bool $defeated = false;

    #[ORM\Column(name: 'defeated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $defeatedAt = null;

    public function __construct(GameEvent $gameEvent, Monster $monster, int $hpMax)
    {
        $this->gameEvent = $gameEvent;
        $this->monster = $monster;
        $this->hpMax = max(1, $hpMax);
        $this->hpCurrent = $this->hpMax;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGameEvent(): GameEvent
    {
        return $this->gameEvent;
    }

    public function getMonster(): Monster
    {
        return $this->monster;
    }

    public function getHpMax(): int
    {
        return $this->hpMax;
    }

    public function getHpCurrent(): int
    {
        return $this->hpCurrent;
    }

    public function isDefeated(): bool
    {
        return $this->defeated;
    }

    public function getDefeatedAt(): ?\DateTimeImmutable
    {
        return $this->defeatedAt;
    }

    /**
     * Applique des degats au pool partage. Retourne les degats reellement
     * infliges (bornes par les PV restants). Marque le boss vaincu a 0 PV.
     */
    public function applyDamage(int $damage): int
    {
        $damage = max(0, $damage);
        $dealt = min($damage, $this->hpCurrent);
        $this->hpCurrent -= $dealt;

        if ($this->hpCurrent <= 0 && !$this->defeated) {
            $this->hpCurrent = 0;
            $this->defeated = true;
            $this->defeatedAt = new \DateTimeImmutable();
        }

        return $dealt;
    }

    public function getHpPercent(): int
    {
        if ($this->hpMax <= 0) {
            return 0;
        }

        return (int) round(($this->hpCurrent / $this->hpMax) * 100);
    }
}
