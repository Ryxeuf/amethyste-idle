<?php

namespace App\Entity\Game;

use App\Enum\Element;
use App\Enum\MonsterRank;
use App\Enum\TrainingMode;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_monsters')]
class Monster
{
    use TimestampableEntity;

    public function __construct()
    {
        $this->monsterItems = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'name_translations', type: 'json', nullable: true)]
    private ?array $nameTranslations = null;

    #[ORM\Column(name: 'slug', type: 'string', length: 255)]
    private $slug;

    #[ORM\Column(name: 'life', type: 'integer')]
    private $life;

    #[ORM\OneToMany(targetEntity: MonsterItem::class, mappedBy: 'monster')]
    private $monsterItems;

    #[ORM\ManyToOne(targetEntity: Spell::class)]
    #[ORM\JoinColumn(name: 'attack_id', referencedColumnName: 'id')]
    private $attack;

    #[ORM\ManyToMany(targetEntity: Spell::class)]
    #[ORM\JoinTable(name: 'monster_spells')]
    private $spells;

    #[ORM\Column(name: 'speed', type: 'integer', options: ['default' => 10])]
    private $speed = 10;

    #[ORM\Column(name: 'hit', type: 'integer', options: ['default' => 20])]
    private $hit = 20;

    /**
     * Ou vit la creature (BES-01) : T0 (sur) a T4, repris du palier de la
     * zone qui la place — jamais invente. Remplace l'echelle `level` 1-40,
     * qui ne se comparait a rien : le joueur n'a pas de niveau (regle 6).
     */
    #[ORM\Column(name: 'tier', type: 'integer', options: ['default' => 1])]
    private int $tier = 1;

    /**
     * Qu'est-ce que c'est (BES-01) : le tout-venant, la rencontre qui fait
     * hesiter, ou l'evenement. Absorbe `difficulty` et `isBoss`.
     */
    #[ORM\Column(name: 'rank', type: 'string', length: 20, enumType: MonsterRank::class, options: ['default' => 'common'])]
    private MonsterRank $rank = MonsterRank::Common;

    #[ORM\Column(name: 'ai_pattern', type: 'json', nullable: true)]
    private ?array $aiPattern = null;

    #[ORM\Column(name: 'elemental_resistances', type: 'json', nullable: true)]
    private ?array $elementalResistances = null;

    /**
     * Le flux dont la creature releve (MAT-01).
     *
     * C'est le prerequis du butin de materia derive (un monstre lache des
     * materia de son element) et ce que le flair de l'Orc lit des la premiere
     * rencontre. `None` est reserve a ce qui ne releve d'aucun flux — les deux
     * mannequins d'entrainement.
     */
    #[ORM\Column(name: 'element', type: 'string', length: 20, enumType: Element::class, options: ['default' => 'none'])]
    private Element $element = Element::None;

    /**
     * Mannequin d'entrainement, et lequel (ONB-11).
     *
     * `null` designe **un vrai monstre**, et c'est la valeur de tout ce qui vit
     * dans le monde. Un mannequin n'est pas un monstre affaibli pour les
     * debutants : c'est un mannequin, ce qui evite au monde de raconter que ses
     * monstres sont inoffensifs.
     *
     * Un mannequin n'entre **jamais** dans un tirage de rencontre : il se
     * declenche par une quete. C'est ce qui permet d'enseigner le combat au
     * Fanal sans toucher a son `safe: true` — « ici, rien ne mord » reste vrai.
     */
    #[ORM\Column(name: 'training_mode', type: 'string', length: 20, nullable: true, enumType: TrainingMode::class)]
    private ?TrainingMode $trainingMode = null;

    #[ORM\Column(name: 'boss_phases', type: 'json', nullable: true)]
    private ?array $bossPhases = null;

    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: 'faction_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Faction $faction = null;

    public function getSpeed(): int
    {
        return $this->speed;
    }

    public function setSpeed(int $speed): void
    {
        $this->speed = $speed;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Monster
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the name translated for the requested locale, or fall back to the base `name` column.
     */
    public function getLocalizedName(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->nameTranslations === null) {
            return $this->name;
        }
        $translation = $this->nameTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getNameTranslations(): array
    {
        return $this->nameTranslations ?? [];
    }

    /**
     * @param array<string, string>|null $translations
     */
    public function setNameTranslations(?array $translations): self
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->nameTranslations = $normalized === [] ? null : $normalized;

        return $this;
    }

    /**
     * Set life.
     *
     * @param int $life
     *
     * @return Monster
     */
    public function setLife($life)
    {
        $this->life = $life;

        return $this;
    }

    /**
     * Get life.
     *
     * @return int
     */
    public function getLife()
    {
        return $this->life;
    }

    /**
     * Add monsterItem.
     *
     * @return Monster
     */
    public function addMonsterItem(MonsterItem $monsterItem)
    {
        $this->monsterItems[] = $monsterItem;

        return $this;
    }

    /**
     * Remove monsterItem.
     */
    public function removeMonsterItem(MonsterItem $monsterItem)
    {
        $this->monsterItems->removeElement($monsterItem);
    }

    /**
     * Get monsterItems.
     *
     * @return Collection|MonsterItem[]
     */
    public function getMonsterItems()
    {
        return $this->monsterItems;
    }

    public function getAttack(): Spell
    {
        return $this->attack;
    }

    public function setAttack(Spell $attack): void
    {
        $this->attack = $attack;
    }

    /**
     * @return Spell[]|ArrayCollection
     */
    public function getSpells()
    {
        return $this->spells;
    }

    /**
     * @param Spell[]|ArrayCollection $spells
     */
    public function setSpells($spells): void
    {
        $this->spells = $spells;
    }

    public function getHit(): int
    {
        return $this->hit;
    }

    public function setHit(int $hit): void
    {
        $this->hit = $hit;
    }

    public function getTier(): int
    {
        return $this->tier;
    }

    public function setTier(int $tier): void
    {
        $this->tier = max(0, min(4, $tier));
    }

    public function getRank(): MonsterRank
    {
        return $this->rank;
    }

    public function setRank(MonsterRank $rank): void
    {
        $this->rank = $rank;
    }

    public function getAiPattern(): ?array
    {
        return $this->aiPattern;
    }

    public function setAiPattern(?array $aiPattern): void
    {
        $this->aiPattern = $aiPattern;
    }

    public function getElementalResistances(): ?array
    {
        return $this->elementalResistances;
    }

    public function setElementalResistances(?array $elementalResistances): void
    {
        $this->elementalResistances = $elementalResistances;
    }

    public function getElementalResistance(string $element): float
    {
        if ($this->elementalResistances === null) {
            return 0.0;
        }

        return $this->elementalResistances[$element] ?? 0.0;
    }

    /**
     * Derive du rang (BES-01) : `Boss` reste le porteur de `bossPhases`, du
     * multiplicateur d'XP et de l'interdiction de fuite. Le booleen n'est
     * plus une colonne — le rang est la seule echelle.
     */
    public function isBoss(): bool
    {
        return $this->rank === MonsterRank::Boss;
    }

    public function getElement(): Element
    {
        return $this->element;
    }

    public function setElement(Element $element): void
    {
        $this->element = $element;
    }

    public function getTrainingMode(): ?TrainingMode
    {
        return $this->trainingMode;
    }

    public function setTrainingMode(?TrainingMode $trainingMode): void
    {
        $this->trainingMode = $trainingMode;
    }

    /**
     * Est-ce un mannequin ? (ONB-11).
     *
     * Le seul predicat que les tirages de rencontre doivent consulter : un
     * mannequin ne s'y presente jamais.
     */
    public function isTrainingDummy(): bool
    {
        return $this->trainingMode !== null;
    }

    public function getBossPhases(): ?array
    {
        return $this->bossPhases;
    }

    public function setBossPhases(?array $bossPhases): void
    {
        $this->bossPhases = $bossPhases;
    }

    public function getFaction(): ?Faction
    {
        return $this->faction;
    }

    public function setFaction(?Faction $faction): void
    {
        $this->faction = $faction;
    }

    public function getCurrentBossPhase(int $currentHpPercent): ?array
    {
        if ($this->bossPhases === null) {
            return null;
        }

        $activePhase = null;
        foreach ($this->bossPhases as $phase) {
            $threshold = $phase['hpThreshold'] ?? 100;
            if ($currentHpPercent <= $threshold) {
                $activePhase = $phase;
            }
        }

        return $activePhase;
    }
}
