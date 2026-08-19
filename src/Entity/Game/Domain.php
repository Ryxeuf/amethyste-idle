<?php

namespace App\Entity\Game;

use App\Entity\App\DomainExperience;
use App\Enum\CombatRegister;
use App\Enum\DomainRole;
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

    /**
     * Le registre de combat du domaine — la seconde borne (DOM-01).
     *
     * Avec `element`, il fait du domaine une **case** : le pyromancien est
     * `feu x sorts`, le berserker `feu x melee`. C'est le domaine qui porte la
     * borne, jamais le nœud : « le domaine *est* la case, aucune decision
     * manuelle par nœud » (PLAN_DOMAINS, DOM-01).
     *
     * `null` veut dire **hors combat**, pas « registre inconnu » : la recolte et
     * l'artisanat n'ont pas de registre (GAME_DOMAINS § 2), leurs passifs etant
     * bornes a leur metier — c'est-a-dire au domaine lui-meme.
     */
    #[ORM\Column(name: 'combat_register', type: 'string', length: 20, nullable: true, enumType: CombatRegister::class)]
    private ?CombatRegister $register = null;

    /**
     * La fonction du domaine — le **troisieme axe** (ARC-01).
     *
     * GAME_ARCHETYPES § 1 : element et registre font une case, mais trois
     * arbres d'eau x sorts l'occupaient sans que rien ne dise en quoi ils
     * different. La fonction dit ce que l'arbre **fait** au combat, donc quels
     * leviers il a le droit d'acheter (§ 5) — c'est une contrainte d'auteur,
     * jamais une classe affichee au joueur.
     *
     * `null` suit exactement `register` : un domaine hors combat n'a pas de
     * fonction. Les deux vont ensemble, et un test le verifie.
     */
    #[ORM\Column(name: 'combat_role', type: 'string', length: 20, nullable: true, enumType: DomainRole::class)]
    private ?DomainRole $role = null;

    /**
     * Un arbre **hors registre** (DOM-10).
     *
     * Il n'apparait pas au catalogue public : le joueur ne peut pas decider de
     * l'ouvrir, parce qu'il ignore qu'il existe. Il ne s'ouvre que par un
     * **parchemin retrouve**, lie, remis par une rencontre qu'un accomplissement
     * declenche.
     *
     * *Ce n'est pas un arbre cache : c'est un arbre qui n'a pas de vendeur.*
     */
    #[ORM\Column(name: 'off_register', type: 'boolean', options: ['default' => false])]
    private bool $offRegister = false;

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

    public function getRegister(): ?CombatRegister
    {
        return $this->register;
    }

    public function setRegister(?CombatRegister $register): self
    {
        $this->register = $register;

        return $this;
    }

    public function getRole(): ?DomainRole
    {
        return $this->role;
    }

    public function setRole(?DomainRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Le domaine s'exprime-t-il en combat ?
     *
     * La question se pose telle quelle a plusieurs endroits, et la reponse
     * derive du registre : un domaine de recolte ou d'artisanat n'en a pas.
     */
    public function isCombatDomain(): bool
    {
        return $this->register !== null;
    }

    /**
     * Cet arbre est-il hors du registre public (DOM-10) ?
     */
    public function isOffRegister(): bool
    {
        return $this->offRegister;
    }

    public function setOffRegister(bool $offRegister): self
    {
        $this->offRegister = $offRegister;

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
