<?php

namespace App\Entity\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_skills')]
class Skill
{
    use TimestampableEntity;

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->requirements = new ArrayCollection();
        $this->achievements = new ArrayCollection();
        $this->items = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, unique: true)]
    private $slug;

    #[ORM\Column(type: 'string', length: 128)]
    private $title;

    #[ORM\Column(type: 'text')]
    private $description;

    #[ORM\Column(name: 'actions', type: 'json', nullable: true)]
    private $actions;

    #[ORM\Column(name: 'required_points', type: 'integer')]
    private $requiredPoints;

    #[ORM\Column(name: 'damage', type: 'integer', options: ['default' => 0])]
    private $damage = 0;

    #[ORM\Column(name: 'heal', type: 'integer', options: ['default' => 0])]
    private $heal = 0;

    #[ORM\Column(name: 'hit', type: 'integer', options: ['default' => 0])]
    private $hit = 0;

    #[ORM\Column(name: 'critical', type: 'integer', options: ['default' => 0])]
    private $critical = 0;

    #[ORM\Column(name: 'life', type: 'integer', options: ['default' => 0])]
    private $life = 0;

    #[ORM\ManyToMany(targetEntity: Skill::class, mappedBy: 'requirements')]
    private $achievements;

    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'achievements')]
    #[ORM\JoinTable(name: 'skill_requirement',
        joinColumns: [new ORM\JoinColumn(name: 'requirement_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'achievement_id', referencedColumnName: 'id')]
    )]
    private $requirements;

    #[ORM\ManyToMany(targetEntity: Item::class, mappedBy: 'requirements')]
    private $items;

    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'domain_id', referencedColumnName: 'id')]
    private $domain;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * Set title.
     *
     * @param string $title
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Add requirement.
     */
    public function addRequirement(Skill $requirement): self
    {
        $this->requirements[] = $requirement;

        return $this;
    }

    /**
     * Remove requirement.
     */
    public function removeRequirement(Skill $requirement): void
    {
        $this->requirements->removeElement($requirement);
    }

    /**
     * Get requirements.
     *
     * @return Collection|Skill[]
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * Add achievement.
     */
    public function addAchievement(Skill $achievement): self
    {
        $this->achievements[] = $achievement;

        return $this;
    }

    /**
     * Remove achievement.
     */
    public function removeAchievement(Skill $achievement): void
    {
        $this->achievements->removeElement($achievement);
    }

    /**
     * Get achievements.
     *
     * @return Collection|Skill[]
     */
    public function getAchievements()
    {
        return $this->achievements;
    }

    /**
     * Set domain.
     */
    public function setDomain(?Domain $domain = null): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain.
     */
    public function getDomain(): Domain
    {
        return $this->domain;
    }

    /**
     * Add item.
     */
    public function addItem(Item $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove item.
     */
    public function removeItem(Item $item): void
    {
        $this->items->removeElement($item);
    }

    /**
     * Get items.
     *
     * @return Collection|Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set requiredPoints.
     *
     * @param int $requiredPoints
     */
    public function setRequiredPoints($requiredPoints): self
    {
        $this->requiredPoints = $requiredPoints;

        return $this;
    }

    /**
     * Get requiredPoints.
     */
    public function getRequiredPoints(): int
    {
        return $this->requiredPoints;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->description;
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

    public function getLife(): int
    {
        return $this->life;
    }

    public function setLife(int $life): void
    {
        $this->life = $life;
    }

    public function getHeal(): int
    {
        return $this->heal;
    }

    public function setHeal(int $heal): void
    {
        $this->heal = $heal;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getActions(): ?array
    {
        return $this->actions;
    }

    public function setActions(?array $actions): void
    {
        $this->actions = $actions;
    }
}
