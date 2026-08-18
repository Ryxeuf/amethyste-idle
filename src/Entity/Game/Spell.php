<?php

namespace App\Entity\Game;

use App\Enum\CombatRegister;
use App\Enum\Element;
use App\Enum\SpellIntent;
use App\Enum\SpellScope;
use App\GameEngine\Fight\SpellIntentDeriver;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_spells')]
class Spell
{
    use TimestampableEntity;

    public function __toString(): string
    {
        return $this->getName();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, unique: true)]
    private $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'name_translations', type: 'json', nullable: true)]
    private ?array $nameTranslations = null;

    #[ORM\Column(name: 'description', type: 'text')]
    private $description;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    #[ORM\Column(name: 'damage', type: 'integer', nullable: true)]
    private $damage;

    #[ORM\Column(name: 'heal', type: 'integer', nullable: true)]
    private $heal;

    #[ORM\Column(name: 'hit', type: 'integer', options: ['default' => 75])]
    private $hit = 75;

    #[ORM\Column(name: 'critical', type: 'integer', options: ['default' => 5])]
    private $critical = 5;

    #[ORM\Column(name: 'spell_range', type: 'integer', nullable: true)]
    private $spellRange;

    #[ORM\Column(name: 'element', type: 'string', length: 25, enumType: Element::class)]
    private Element $element = Element::None;

    #[ORM\Column(name: 'cooldown', type: 'integer', nullable: true)]
    private ?int $cooldown = null;

    /**
     * Ce que le geste coute en munitions (ARC-04b).
     *
     * La ressource du registre **distance**, comme les PM sont celle des sorts
     * et la reprise celle de la melee (GAME_MATERIA § 2.3 bis). Elle se compte
     * **dans la rencontre** : le carquois se vide en combat et se ramasse
     * apres, si bien qu'un archer ne paie jamais un gil pour tirer — arbitrage
     * du § 9 septies, *aucun archetype ne porte un cout recurrent en gils que
     * les autres n'ont pas*.
     *
     * `0` pour tout ce qui n'est pas un geste de tir : un sort ne consomme pas
     * de fleche, et un geste ne facture que la ressource de son registre.
     */
    #[ORM\Column(name: 'ammo_cost', type: 'integer', options: ['default' => 0])]
    private int $ammoCost = 0;

    #[ORM\Column(name: 'energy_cost', type: 'integer', options: ['default' => 0])]
    private int $energyCost = 0;

    /**
     * Ce que ce geste coute en points de vie (ARC-18c).
     *
     * La forme **conversion** (GAME_ARCHETYPES § 13.1, n° 6) : *echanger une
     * ressource contre une autre*. `0` sur tous les gestes livres — un geste
     * qui coute de la vie est une decision d'auteur, jamais un defaut.
     *
     * Elle n'est pas une quatrieme ressource de registre : un geste de
     * conversion **facture toujours celle de son registre**, et cette colonne
     * dit seulement qu'il en achete une autre au passage. Ce qu'un point de vie
     * rend est `ConversionLaw`, jamais un second chiffre ecrit ici — *deux
     * chiffres a la main divergent, une derivation ne peut pas*.
     */
    #[ORM\Column(name: 'life_cost', type: 'integer', options: ['default' => 0])]
    private int $lifeCost = 0;

    #[ORM\Column(name: 'status_effect_slug', type: 'string', length: 255, nullable: true)]
    private ?string $statusEffectSlug = null;

    #[ORM\Column(name: 'aoe_targets', type: 'integer', options: ['default' => 1])]
    private int $aoeTargets = 1;

    #[ORM\Column(name: 'level', type: 'integer', options: ['default' => 1])]
    private int $level = 1;

    #[ORM\Column(name: 'value_type', type: 'string', length: 10, options: ['default' => 'fixed'])]
    private string $valueType = 'fixed';

    public const VALUE_TYPE_FIXED = 'fixed';
    public const VALUE_TYPE_PERCENT = 'percent';

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set name.
     */
    public function setName(string $name): Spell
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
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
     * @param array<string, mixed>|null $translations
     */
    public function setNameTranslations(?array $translations): Spell
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->nameTranslations = $normalized === [] ? null : $normalized;

        return $this;
    }

    /**
     * Set description.
     */
    public function setDescription(string $description): Spell
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

    /**
     * Get the description translated for the requested locale, or fall back to the base `description` column.
     */
    public function getLocalizedDescription(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->descriptionTranslations === null) {
            return $this->description;
        }
        $translation = $this->descriptionTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->description;
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptionTranslations(): array
    {
        return $this->descriptionTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setDescriptionTranslations(?array $translations): self
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->descriptionTranslations = $normalized === [] ? null : $normalized;

        return $this;
    }

    /**
     * Set damage.
     */
    public function setDamage(?int $damage): Spell
    {
        $this->damage = $damage;

        return $this;
    }

    /**
     * Get damage.
     */
    public function getDamage(): ?int
    {
        return $this->damage;
    }

    /**
     * Set heal.
     */
    public function setHeal(?int $heal): Spell
    {
        $this->heal = $heal;

        return $this;
    }

    public function getHeal(): ?int
    {
        return $this->heal;
    }

    /**
     * Set spellRange.
     */
    public function setSpellRange(?int $spellRange): Spell
    {
        $this->spellRange = $spellRange;

        return $this;
    }

    public function getSpellRange(): ?int
    {
        return $this->spellRange;
    }

    public function setElement(Element $element): Spell
    {
        $this->element = $element;

        return $this;
    }

    public function getElement(): Element
    {
        return $this->element;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getCooldown(): ?int
    {
        return $this->cooldown;
    }

    public function setCooldown(?int $cooldown): void
    {
        $this->cooldown = $cooldown;
    }

    public function getAmmoCost(): int
    {
        return $this->ammoCost;
    }

    public function setAmmoCost(int $ammoCost): void
    {
        $this->ammoCost = max(0, $ammoCost);
    }

    public function getEnergyCost(): int
    {
        return $this->energyCost;
    }

    public function getLifeCost(): int
    {
        return $this->lifeCost;
    }

    public function setLifeCost(int $lifeCost): void
    {
        $this->lifeCost = max(0, $lifeCost);
    }

    /**
     * Ce geste echange-t-il de la vie contre de la magie ?
     *
     * La forme se **derive du champ** plutot que de se declarer a cote de lui :
     * une colonne `form` qui pourrait dire « conversion » sur un geste sans
     * cout en vie serait un mensonge que rien ne rattraperait.
     */
    public function isConversion(): bool
    {
        return $this->lifeCost > 0;
    }

    public function setEnergyCost(int $energyCost): void
    {
        $this->energyCost = $energyCost;
    }

    public function getStatusEffectSlug(): ?string
    {
        return $this->statusEffectSlug;
    }

    public function setStatusEffectSlug(?string $statusEffectSlug): void
    {
        $this->statusEffectSlug = $statusEffectSlug;
    }

    public function getAoeTargets(): int
    {
        return $this->aoeTargets;
    }

    public function setAoeTargets(int $aoeTargets): void
    {
        $this->aoeTargets = $aoeTargets;
    }

    public function isAoe(): bool
    {
        return $this->aoeTargets === 0 || $this->aoeTargets > 1;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * Le registre du geste — sorts, melee ou distance (ARC-02).
     *
     * GAME_ARCHETYPES § 3 : **le geste d'arme est une materia**. Avant ce
     * jalon, les 200 accords promis par les arbres n'ouvraient que des
     * **sorts** — un arbre de melee ou de distance n'ouvrait donc que des
     * sorts, et ses passifs, bornes a son registre, ne s'appliquaient a aucune
     * action existante : *un arbre d'archer etait un arbre de mage avec un arc*.
     *
     * Le registre se declare ici et la materia en **herite**, exactement comme
     * elle herite deja de l'element. C'est ce qui fait qu'un emplacement de
     * technique cesse d'etre un mur sans porte (DOM-03).
     *
     * `Spell` par defaut : les gestes livres avant ce jalon sont tous des
     * sorts, et la colonne le dit sans qu'on ait a les reprendre un par un.
     */
    #[ORM\Column(name: 'combat_register', type: 'string', length: 20, options: ['default' => 'spell'], enumType: CombatRegister::class)]
    private CombatRegister $register = CombatRegister::Spell;

    public function getRegister(): CombatRegister
    {
        return $this->register;
    }

    public function setRegister(CombatRegister $register): self
    {
        $this->register = $register;

        return $this;
    }

    /**
     * Ce geste est-il une **technique** — un geste d'arme plutot qu'un sort ?
     *
     * La question se pose telle quelle a plusieurs endroits (le genre de la
     * materia qui le porte, l'emplacement qui l'accepte), et la reponse derive
     * du registre : tout ce qui n'est pas du registre des sorts est un geste
     * d'arme, qu'on l'abatte ou qu'on le decoche.
     */
    public function isTechnique(): bool
    {
        return $this->register !== CombatRegister::Spell;
    }

    public function getValueType(): string
    {
        return $this->valueType;
    }

    public function setValueType(string $valueType): void
    {
        $this->valueType = $valueType;
    }

    public function isPercent(): bool
    {
        return $this->valueType === self::VALUE_TYPE_PERCENT;
    }

    /**
     * Ce que le geste fait (ARC-11a, GAME_ARCHETYPES § 3.1).
     *
     * **Nullable, et derivable** : la colonne porte la decision d'auteur quand
     * il y en a une, et reste vide quand la donnee suffit a la dire. C'est
     * `SpellIntentDeriver` qui repond alors — la meme facon de faire que la
     * materia derivee de son sort (MAT-02) ou les stats derivees du gabarit
     * (BES-02). Ecrire 253 valeurs a la main aurait ete 253 occasions de se
     * tromper.
     */
    #[ORM\Column(name: 'intent', type: 'string', length: 20, nullable: true, enumType: SpellIntent::class)]
    private ?SpellIntent $intent = null;

    /**
     * Qui le geste touche (ARC-11a).
     *
     * La portee `Group` ne se derive **jamais** : elle est une decision
     * d'auteur, et c'est ici qu'elle s'ecrit.
     */
    #[ORM\Column(name: 'scope', type: 'string', length: 20, nullable: true, enumType: SpellScope::class)]
    private ?SpellScope $scope = null;

    /**
     * L'intention declaree, sans repli — `null` dit « rien n'a ete decide ».
     */
    public function getIntent(): ?SpellIntent
    {
        return $this->intent;
    }

    public function setIntent(?SpellIntent $intent): self
    {
        $this->intent = $intent;

        return $this;
    }

    public function getScope(): ?SpellScope
    {
        return $this->scope;
    }

    public function setScope(?SpellScope $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * L'intention du geste : celle qu'on a declaree, sinon celle qu'il montre.
     *
     * `$statusEffectType` est passe par l'appelant plutot que resolu ici :
     * l'entite ne connait de son effet que le slug, et lui donner un depot
     * pour aller chercher le type ferait d'un objet de donnees un service.
     */
    public function resolveIntent(?string $statusEffectType = null): ?SpellIntent
    {
        return $this->intent ?? SpellIntentDeriver::deriveIntent(
            $this->damage,
            $this->heal,
            $statusEffectType,
        );
    }

    /**
     * La portee du geste : celle qu'on a declaree, sinon celle qu'il montre.
     */
    public function resolveScope(?string $statusEffectType = null): ?SpellScope
    {
        return $this->scope ?? SpellIntentDeriver::deriveScope(
            $this->resolveIntent($statusEffectType),
            $this->aoeTargets,
        );
    }
}
