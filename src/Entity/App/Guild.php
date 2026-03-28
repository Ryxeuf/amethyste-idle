<?php

namespace App\Entity\App;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'guild')]
class Guild
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 50, unique: true)]
    private string $name;

    #[ORM\Column(name: 'tag', type: 'string', length: 5, unique: true)]
    private string $tag;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'points', type: 'integer', options: ['default' => 0])]
    private int $points = 0;

    #[ORM\Column(name: 'gils_treasury', type: 'integer', options: ['default' => 0])]
    private int $gilsTreasury = 0;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'leader_id', referencedColumnName: 'id', nullable: false)]
    private Player $leader;

    /** @var Collection<int, GuildMember> */
    #[ORM\OneToMany(targetEntity: GuildMember::class, mappedBy: 'guild', cascade: ['persist', 'remove'])]
    private Collection $members;

    #[ORM\OneToOne(targetEntity: GuildVault::class, mappedBy: 'guild', cascade: ['persist', 'remove'])]
    private ?GuildVault $vault = null;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): self
    {
        $this->tag = mb_strtoupper($tag);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLeader(): Player
    {
        return $this->leader;
    }

    public function setLeader(Player $leader): self
    {
        $this->leader = $leader;

        return $this;
    }

    /**
     * @return Collection<int, GuildMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(GuildMember $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setGuild($this);
        }

        return $this;
    }

    public function removeMember(GuildMember $member): self
    {
        $this->members->removeElement($member);

        return $this;
    }

    public function getMemberCount(): int
    {
        return $this->members->count();
    }

    public function getVault(): ?GuildVault
    {
        return $this->vault;
    }

    public function setVault(?GuildVault $vault): self
    {
        $this->vault = $vault;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }

    public function addPoints(int $points): self
    {
        $this->points += $points;

        return $this;
    }

    public function getGilsTreasury(): int
    {
        return $this->gilsTreasury;
    }

    public function setGilsTreasury(int $gilsTreasury): self
    {
        $this->gilsTreasury = $gilsTreasury;

        return $this;
    }

    public function addGilsTreasury(int $amount): self
    {
        $this->gilsTreasury += $amount;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
