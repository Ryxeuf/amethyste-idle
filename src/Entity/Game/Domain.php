<?php

namespace App\Entity\Game;

use App\Entity\App\DomainExperience;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: "game_domains")]
class Domain
{
    use TimestampableEntity;

    function __toString()
    {
        return $this->getTitle();
    }

    public function getSlug(): string
    {
        return strtolower(str_replace(' ', '-', $this->getTitle()));
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->skills = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id", type: "integer")]
    private $id;

    #[ORM\Column(name: "title", type: "string", length: 255)]
    private $title;

    #[ORM\OneToMany(targetEntity: Skill::class, mappedBy: "domain")]
    private $skills;

    #[ORM\Column(name: "random_seed", type: "integer")]
    private $randomSeed;

    #[ORM\Column(name: "graph_height", type: "integer")]
    private $graphHeight;

    #[ORM\OneToMany(targetEntity: DomainExperience::class, mappedBy: "domain")]
    private $playerExperiences;

    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: "domain")]
    private $items;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Domain
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Add skill
     *
     * @param Skill $skill
     *
     * @return Domain
     */
    public function addSkill(Skill $skill)
    {
        $this->skills[] = $skill;

        return $this;
    }

    /**
     * Remove skill
     *
     * @param Skill $skill
     */
    public function removeSkill(Skill $skill)
    {
        $this->skills->removeElement($skill);
    }

    /**
     * Get skills
     *
     * @return Collection
     */
    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * Set randomSeed
     *
     * @param string $randomSeed
     *
     * @return Domain
     */
    public function setRandomSeed($randomSeed)
    {
        $this->randomSeed = $randomSeed;

        return $this;
    }

    /**
     * Get randomSeed
     *
     * @return string
     */
    public function getRandomSeed()
    {
        return $this->randomSeed;
    }

    /**
     * Set graphHeight
     *
     * @param integer $graphHeight
     *
     * @return Domain
     */
    public function setGraphHeight($graphHeight)
    {
        $this->graphHeight = $graphHeight;

        return $this;
    }

    /**
     * Get graphHeight
     *
     * @return integer
     */
    public function getGraphHeight()
    {
        return $this->graphHeight;
    }

    /**
     * @return DomainExperience[]|ArrayCollection
     */
    public function getPlayerExperiences()
    {
        return $this->playerExperiences;
    }

    /**
     * @param DomainExperience[]|ArrayCollection $playerExperiences
     */
    public function setPlayerExperiences($playerExperiences)
    {
        $this->playerExperiences = $playerExperiences;
    }

    /**
     * Add playerExperience
     *
     * @param DomainExperience $playerExperience
     *
     * @return Domain
     */
    public function addPlayerExperience(DomainExperience $playerExperience)
    {
        $this->playerExperiences[] = $playerExperience;

        return $this;
    }

    /**
     * Remove playerExperience
     *
     * @param DomainExperience $playerExperience
     */
    public function removePlayerExperience(DomainExperience $playerExperience)
    {
        $this->playerExperiences->removeElement($playerExperience);
    }

    /**
     * @return Item[]|ArrayCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param Item[]|ArrayCollection $items
     */
    public function setItems($items): void
    {
        $this->items = $items;
    }
}
