<?php

namespace App\Entity\Game;

use App\Entity\App\DomainExperience;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_domains')]
class Domain
{
    use TimestampableEntity;

    public function __toString()
    {
        return $this->getTitle();
    }

    public function getSlug(): string
    {
        return strtolower(str_replace(' ', '-', $this->getTitle()));
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->skills = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'title_translations', type: 'json', nullable: true)]
    private ?array $titleTranslations = null;

    #[ORM\ManyToMany(targetEntity: Skill::class, mappedBy: 'domains')]
    private Collection $skills;

    #[ORM\Column(name: 'random_seed', type: 'integer')]
    private $randomSeed;

    #[ORM\Column(name: 'graph_height', type: 'integer')]
    private $graphHeight;

    #[ORM\Column(name: 'element', type: 'string', length: 25, nullable: true)]
    private ?string $element = null;

    #[ORM\OneToMany(targetEntity: DomainExperience::class, mappedBy: 'domain')]
    private $playerExperiences;

    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'domain')]
    private $items;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title.
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
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the title translated for the requested locale, or fall back to the base `title` column.
     */
    public function getLocalizedTitle(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->titleTranslations === null) {
            return $this->title;
        }
        $translation = $this->titleTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->title;
    }

    /**
     * @return array<string, string>
     */
    public function getTitleTranslations(): array
    {
        return $this->titleTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setTitleTranslations(?array $translations): self
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->titleTranslations = $normalized === [] ? null : $normalized;

        return $this;
    }

    public function addSkill(Skill $skill): self
    {
        if (!$this->skills->contains($skill)) {
            $this->skills[] = $skill;
            $skill->addDomain($this);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): self
    {
        if ($this->skills->removeElement($skill)) {
            $skill->removeDomain($this);
        }

        return $this;
    }

    /**
     * Get skills.
     *
     * @return Collection
     */
    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * Set randomSeed.
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
     * Get randomSeed.
     *
     * @return string
     */
    public function getRandomSeed()
    {
        return $this->randomSeed;
    }

    /**
     * Set graphHeight.
     *
     * @param int $graphHeight
     *
     * @return Domain
     */
    public function setGraphHeight($graphHeight)
    {
        $this->graphHeight = $graphHeight;

        return $this;
    }

    /**
     * Get graphHeight.
     *
     * @return int
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
     * Add playerExperience.
     *
     * @return Domain
     */
    public function addPlayerExperience(DomainExperience $playerExperience)
    {
        $this->playerExperiences[] = $playerExperience;

        return $this;
    }

    /**
     * Remove playerExperience.
     */
    public function removePlayerExperience(DomainExperience $playerExperience)
    {
        $this->playerExperiences->removeElement($playerExperience);
    }

    public function getElement(): ?string
    {
        return $this->element;
    }

    public function setElement(?string $element): self
    {
        $this->element = $element;

        return $this;
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
