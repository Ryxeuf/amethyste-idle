<?php

namespace App\Entity\App;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'party')]
class Party
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'leader_id', referencedColumnName: 'id', nullable: false)]
    private Player $leader;

    #[ORM\Column(name: 'max_size', type: 'integer', options: ['default' => 4])]
    private int $maxSize = 4;

    /** @var Collection<int, PartyMember> */
    #[ORM\OneToMany(targetEntity: PartyMember::class, mappedBy: 'party', cascade: ['persist', 'remove'])]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    public function setMaxSize(int $maxSize): self
    {
        $this->maxSize = $maxSize;

        return $this;
    }

    /**
     * @return Collection<int, PartyMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(PartyMember $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setParty($this);
        }

        return $this;
    }

    public function removeMember(PartyMember $member): self
    {
        $this->members->removeElement($member);

        return $this;
    }

    public function getMemberCount(): int
    {
        return $this->members->count();
    }

    public function isFull(): bool
    {
        return $this->members->count() >= $this->maxSize;
    }

    public function __toString(): string
    {
        return sprintf('Groupe de %s', $this->leader->getName());
    }
}
