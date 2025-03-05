<?php

namespace App\Entity\App;

use App\Entity\Game\Domain;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'domain_experience')]
#[ORM\Entity(repositoryClass: 'App\Repository\App\DomainExperienceRepository')]
class DomainExperience
{
    use TimestampableEntity;
    
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * Expérience totale accumulée
     */
    #[ORM\Column(name: 'total_experience', type: 'integer', options: ['default' => 0])]
    private $totalExperience = 0;

    /**
     * Expérience utilisée
     */
    #[ORM\Column(name: 'used_experience', type: 'integer', options: ['default' => 0])]
    private $usedExperience = 0;

    /**
     * Dégâts additionnels pour le domain
     */
    #[ORM\Column(name: 'damage', type: 'integer', options: ['default' => 0])]
    private $damage = 0;

    /**
     * Dégâts additionnels pour le domain
     */
    #[ORM\Column(name: 'heal', type: 'integer', options: ['default' => 0])]
    private $heal = 0;

    /**
     * Chances de toucher : X/100
     */
    #[ORM\Column(name: 'hit', type: 'integer', options: ['default' => 0])]
    private $hit = 0;

    /**
     * Chances de critique : X/100
     */
    #[ORM\Column(name: 'critical', type: 'integer', options: ['default' => 0])]
    private $critical = 0;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'domain_experiences')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id')]
    private $player;

    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(name: 'domain_id', referencedColumnName: 'id')]
    private $domain;

    /**
     * @return int
     */
    public function getAvailableExperience(): int
    {
        return $this->getTotalExperience() - $this->getUsedExperience();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getTotalExperience(): int
    {
        return $this->totalExperience;
    }

    /**
     * @param int $totalExperience
     */
    public function setTotalExperience(int $totalExperience): void
    {
        $this->totalExperience = $totalExperience;
    }

    /**
     * @return int
     */
    public function getUsedExperience(): int
    {
        return $this->usedExperience;
    }

    /**
     * @param int $usedExperience
     */
    public function setUsedExperience(int $usedExperience): void
    {
        $this->usedExperience = $usedExperience;
    }

    /**
     * @return int
     */
    public function getDamage(): int
    {
        return $this->damage;
    }

    /**
     * @param int $damage
     */
    public function setDamage(int $damage): void
    {
        $this->damage = $damage;
    }

    /**
     * @return int
     */
    public function getHit(): int
    {
        return $this->hit;
    }

    /**
     * @param int $hit
     */
    public function setHit(int $hit): void
    {
        $this->hit = $hit;
    }

    /**
     * @return int
     */
    public function getCritical(): int
    {
        return $this->critical;
    }

    /**
     * @param int $critical
     */
    public function setCritical(int $critical): void
    {
        $this->critical = $critical;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @param Player $player
     */
    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    /**
     * @return Domain
     */
    public function getDomain(): Domain
    {
        return $this->domain;
    }

    /**
     * @param Domain $domain
     */
    public function setDomain(Domain $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return int
     */
    public function getHeal(): int
    {
        return $this->heal;
    }

    /**
     * @param int $heal
     */
    public function setHeal(int $heal): void
    {
        $this->heal = $heal;
    }
}
