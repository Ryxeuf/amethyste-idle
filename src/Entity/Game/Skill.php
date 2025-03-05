<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: "App\Repository\Game\SkillRepository")]
#[ORM\Table(name: "game_skills")]
class Skill
{
    use TimestampableEntity;

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requirements = new ArrayCollection();
        $this->achievements = new ArrayCollection();
        $this->items = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(name: "slug", type: "string", length: 255, unique: true)]
    private $slug;

    #[ORM\Column(type: "string", length: 128)]
    private $title;

    #[ORM\Column(type: "text")]
    private $description;

    #[ORM\Column(name: "actions", type: "json", nullable: true)]
    private $actions;

    #[ORM\Column(name: "required_points", type: "integer")]
    private $requiredPoints;

    #[ORM\Column(name: "damage", type: "integer", options: ["default" => 0])]
    private $damage = 0;

    #[ORM\Column(name: "heal", type: "integer", options: ["default" => 0])]
    private $heal = 0;

    #[ORM\Column(name: "hit", type: "integer", options: ["default" => 0])]
    private $hit = 0;

    #[ORM\Column(name: "critical", type: "integer", options: ["default" => 0])]
    private $critical = 0;

    #[ORM\Column(name: "life", type: "integer", options: ["default" => 0])]
    private $life = 0;

    #[ORM\ManyToMany(targetEntity: Skill::class, mappedBy: "requirements")]
    private $achievements;

    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: "achievements")]
    #[ORM\JoinTable(name: "skill_requirement",
        joinColumns: [new ORM\JoinColumn(name: "requirement_id", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "achievement_id", referencedColumnName: "id")]
    )]
    private $requirements;

    #[ORM\ManyToMany(targetEntity: Item::class, mappedBy: "requirements")]
    private $items;

    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: "skills")]
    #[ORM\JoinColumn(name: "domain_id", referencedColumnName: "id")]
    private $domain;

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Skill
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Add requirement
     *
     * @param Skill $requirement
     *
     * @return Skill
     */
    public function addRequirement(Skill $requirement): self
    {
        $this->requirements[] = $requirement;

        return $this;
    }

    /**
     * Remove requirement
     *
     * @param Skill $requirement
     */
    public function removeRequirement(Skill $requirement): void
    {
        $this->requirements->removeElement($requirement);
    }

    /**
     * Get requirements
     *
     * @return Collection|Skill[]
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * Add achievement
     *
     * @param Skill $achievement
     *
     * @return Skill
     */
    public function addAchievement(Skill $achievement): self
    {
        $this->achievements[] = $achievement;

        return $this;
    }

    /**
     * Remove achievement
     *
     * @param Skill $achievement
     */
    public function removeAchievement(Skill $achievement): void
    {
        $this->achievements->removeElement($achievement);
    }

    /**
     * Get achievements
     *
     * @return Collection|Skill[]
     */
    public function getAchievements()
    {
        return $this->achievements;
    }

    /**
     * Set domain
     *
     * @param Domain $domain
     *
     * @return Skill
     */
    public function setDomain(Domain $domain = null): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return Domain
     */
    public function getDomain(): Domain
    {
        return $this->domain;
    }

    /**
     * Add item
     *
     * @param Item $item
     *
     * @return Skill
     */
    public function addItem(Item $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove item
     *
     * @param Item $item
     */
    public function removeItem(Item $item): void
    {
        $this->items->removeElement($item);
    }

    /**
     * Get items
     *
     * @return Collection|Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set requiredPoints
     *
     * @param integer $requiredPoints
     *
     * @return Skill
     */
    public function setRequiredPoints($requiredPoints): self
    {
        $this->requiredPoints = $requiredPoints;

        return $this;
    }

    /**
     * Get requiredPoints
     *
     * @return int
     */
    public function getRequiredPoints(): int
    {
        return $this->requiredPoints;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Skill
     */
    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
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
     * @return int
     */
    public function getLife(): int
    {
        return $this->life;
    }

    /**
     * @param int $life
     */
    public function setLife(int $life): void
    {
        $this->life = $life;
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

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return array|null
     */
    public function getActions(): ?array
    {
        return $this->actions;
    }

    /**
     * @param array|null $actions
     */
    public function setActions(?array $actions): void
    {
        $this->actions = $actions;
    }
}
