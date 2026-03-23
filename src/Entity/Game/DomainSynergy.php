<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'game_domain_synergies')]
#[ORM\UniqueConstraint(name: 'uq_synergy_domains', columns: ['domain_a_id', 'domain_b_id'])]
class DomainSynergy
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Domain $domainA;

    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Domain $domainB;

    #[ORM\Column(type: 'string', length: 128)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 32)]
    private string $bonusType;

    #[ORM\Column(type: 'integer')]
    private int $bonusValue;

    #[ORM\Column(type: 'integer', options: ['default' => 50])]
    private int $activationThreshold = 50;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomainA(): Domain
    {
        return $this->domainA;
    }

    public function setDomainA(Domain $domainA): self
    {
        $this->domainA = $domainA;

        return $this;
    }

    public function getDomainB(): Domain
    {
        return $this->domainB;
    }

    public function setDomainB(Domain $domainB): self
    {
        $this->domainB = $domainB;

        return $this;
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getBonusType(): string
    {
        return $this->bonusType;
    }

    public function setBonusType(string $bonusType): self
    {
        $this->bonusType = $bonusType;

        return $this;
    }

    public function getBonusValue(): int
    {
        return $this->bonusValue;
    }

    public function setBonusValue(int $bonusValue): self
    {
        $this->bonusValue = $bonusValue;

        return $this;
    }

    public function getActivationThreshold(): int
    {
        return $this->activationThreshold;
    }

    public function setActivationThreshold(int $activationThreshold): self
    {
        $this->activationThreshold = $activationThreshold;

        return $this;
    }

    /**
     * Vérifie si la synergie concerne un domaine donné (en A ou B).
     */
    public function involvesDomain(Domain $domain): bool
    {
        return $this->domainA === $domain || $this->domainB === $domain;
    }

    /**
     * Retourne l'autre domaine de la synergie par rapport à celui fourni.
     */
    public function getOtherDomain(Domain $domain): Domain
    {
        return $this->domainA === $domain ? $this->domainB : $this->domainA;
    }
}
