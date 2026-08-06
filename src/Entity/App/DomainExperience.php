<?php

namespace App\Entity\App;

use App\Entity\Game\Domain;
use App\GameEngine\Progression\DomainPointYield;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'domain_experience')]
#[ORM\Index(columns: ['player_id'], name: 'idx_domain_experience_player')]
#[ORM\Index(columns: ['domain_id'], name: 'idx_domain_experience_domain')]
#[ORM\Entity(repositoryClass: \App\Repository\DomainExperienceRepository::class)]
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
     * Le reste, en quarts de point (ARC-06b).
     *
     * La table du gain descend a 0,25 (`DomainPointYield`), et un compteur qui
     * perd ses restes est un compteur qui ment : un joueur de palier 1
     * gagnerait **zero** point par rencontre, arrondi apres arrondi. Ce champ
     * porte ce qui n'a pas encore fait un point entier — il vaut donc toujours
     * 0, 1, 2 ou 3, et `addQuarters()` est le seul a l'ecrire.
     *
     * Il n'est jamais montre au joueur : ce qui se lit est le point, le quart
     * n'est que la facon dont il se gagne.
     */
    #[ORM\Column(name: 'experience_quarters', type: 'integer', options: ['default' => 0])]
    private int $experienceQuarters = 0;

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

    public function getExperienceQuarters(): int
    {
        return $this->experienceQuarters;
    }

    public function setExperienceQuarters(int $experienceQuarters): void
    {
        $this->experienceQuarters = $experienceQuarters;
    }

    /**
     * Crediter un gain exprime en quarts de point, et retenir le reste.
     *
     * Retourne les points **entiers** effectivement gagnes — zero est une
     * reponse normale, et c'est tout l'interet : quatre rencontres de palier 1
     * font un point, aucune n'en fait un quart de point perdu.
     */
    public function addQuarters(int $quarters): int
    {
        if ($quarters <= 0) {
            return 0;
        }

        $carried = $this->experienceQuarters + $quarters;
        $points = intdiv($carried, DomainPointYield::QUARTERS_PER_POINT);

        $this->experienceQuarters = $carried % DomainPointYield::QUARTERS_PER_POINT;
        $this->totalExperience += $points;

        return $points;
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
