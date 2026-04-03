<?php

namespace App\Entity\App;

use App\Entity\Game\Domain;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'domain_experience')]
#[ORM\Entity()]
class DomainExperience
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * Expérience totale accumulée.
     */
    #[ORM\Column(name: 'total_experience', type: 'integer', options: ['default' => 0])]
    private $totalExperience = 0;

    /**
     * Expérience utilisée.
     */
    #[ORM\Column(name: 'used_experience', type: 'integer', options: ['default' => 0])]
    private $usedExperience = 0;

    /**
     * Dégâts additionnels pour le domain.
     */
    #[ORM\Column(name: 'damage', type: 'integer', options: ['default' => 0])]
    private $damage = 0;

    /**
     * Dégâts additionnels pour le domain.
     */
    #[ORM\Column(name: 'heal', type: 'integer', options: ['default' => 0])]
    private $heal = 0;

    /**
     * Chances de toucher : X/100.
     */
    #[ORM\Column(name: 'hit', type: 'integer', options: ['default' => 0])]
    private $hit = 0;

    /**
     * Chances de critique : X/100.
     */
    #[ORM\Column(name: 'critical', type: 'integer', options: ['default' => 0])]
    private $critical = 0;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'domainExperiences')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id')]
    private $player;

    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(name: 'domain_id', referencedColumnName: 'id')]
    private $domain;

    public function getAvailableExperience(): int
    {
        return $this->getTotalExperience() - $this->getUsedExperience();
    }

    /**
     * Calcule le niveau du domaine a partir de l'XP totale.
     * Chaque 100 XP = 1 niveau, niveau de base = 1.
     */
    public function getLevel(): int
    {
        return (int) floor($this->totalExperience / 100) + 1;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTotalExperience(): int
    {
        return $this->totalExperience;
    }

    public function setTotalExperience(int $totalExperience): void
    {
        $this->totalExperience = $totalExperience;
    }

    public function getUsedExperience(): int
    {
        return $this->usedExperience;
    }

    public function setUsedExperience(int $usedExperience): void
    {
        $this->usedExperience = $usedExperience;
    }

    public function getDamage(): int
    {
        return $this->damage;
    }

    public function setDamage(int $damage): void
    {
        $this->damage = $damage;
    }

    public function getHit(): int
    {
        return $this->hit;
    }

    public function setHit(int $hit): void
    {
        $this->hit = $hit;
    }

    public function getCritical(): int
    {
        return $this->critical;
    }

    public function setCritical(int $critical): void
    {
        $this->critical = $critical;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    public function getDomain(): Domain
    {
        return $this->domain;
    }

    public function setDomain(Domain $domain): void
    {
        $this->domain = $domain;
    }

    public function getHeal(): int
    {
        return $this->heal;
    }

    public function setHeal(int $heal): void
    {
        $this->heal = $heal;
    }
}
