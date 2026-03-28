<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'region_upgrade')]
#[ORM\UniqueConstraint(name: 'uniq_region_upgrade_control_slug', columns: ['region_control_id', 'upgrade_slug'])]
class RegionUpgrade
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RegionControl::class)]
    #[ORM\JoinColumn(name: 'region_control_id', referencedColumnName: 'id', nullable: false)]
    private RegionControl $regionControl;

    #[ORM\Column(name: 'upgrade_slug', type: 'string', length: 50)]
    private string $upgradeSlug;

    #[ORM\Column(name: 'level', type: 'integer', options: ['default' => 1])]
    private int $level = 1;

    #[ORM\Column(name: 'cost_gils', type: 'integer')]
    private int $costGils;

    #[ORM\Column(name: 'activated_at', type: 'datetime')]
    private \DateTimeInterface $activatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegionControl(): RegionControl
    {
        return $this->regionControl;
    }

    public function setRegionControl(RegionControl $regionControl): self
    {
        $this->regionControl = $regionControl;

        return $this;
    }

    public function getUpgradeSlug(): string
    {
        return $this->upgradeSlug;
    }

    public function setUpgradeSlug(string $upgradeSlug): self
    {
        $this->upgradeSlug = $upgradeSlug;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getCostGils(): int
    {
        return $this->costGils;
    }

    public function setCostGils(int $costGils): self
    {
        $this->costGils = $costGils;

        return $this;
    }

    public function getActivatedAt(): \DateTimeInterface
    {
        return $this->activatedAt;
    }

    public function setActivatedAt(\DateTimeInterface $activatedAt): self
    {
        $this->activatedAt = $activatedAt;

        return $this;
    }
}
