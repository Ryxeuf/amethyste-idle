<?php

namespace App\Entity\Game;

use App\Enum\BindType;
use App\Enum\Element;
use App\Enum\ItemRarity;
use App\Enum\MateriaSlotType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_items')]
class Item
{
    use TimestampableEntity;

    public const TYPE_STUFF = 'stuff';
    public const TYPE_GEAR_PIECE = 'gear';
    public const TYPE_MATERIA = 'materia';
    public const TYPE_RESOURCE = 'resource';
    public const TYPE_TOOL = 'tool';

    public const TOOL_TYPE_PICKAXE = 'pickaxe';
    public const TOOL_TYPE_SICKLE = 'sickle';
    public const TOOL_TYPE_FISHING_ROD = 'fishing_rod';
    public const TOOL_TYPE_SKINNING_KNIFE = 'skinning_knife';
    public const TOOL_TYPE_HAMMER = 'hammer';
    public const TOOL_TYPE_TANNING_KIT = 'tanning_kit';
    public const TOOL_TYPE_MORTAR = 'mortar';
    public const TOOL_TYPE_CHISEL = 'chisel';
    // DOM-05 : la hache, promise par ZON-34 et differee faute de metier a
    // servir. Le charpentier existe depuis ECO-30 ; l'outil arrive avec celui a
    // qui il sert, comme la note de ZON-34 l'annoncait.
    public const TOOL_TYPE_AXE = 'axe';
    // OBJ-06 : les trois metiers sans outil en recoivent un (GAME_ITEMS §4.2).
    // La marmite du cuisinier, la varlope du charpentier et l'aiguille du
    // tailleur — le type, le bit et l'emplacement qu'ECO-29/30/31 avaient
    // differes comme « un changement de mecanisme pour un jalon de contenu ».
    public const TOOL_TYPE_COOKPOT = 'cookpot';
    public const TOOL_TYPE_PLANE = 'plane';
    public const TOOL_TYPE_NEEDLE = 'needle';

    public const TOOL_GEAR_LOCATIONS = [
        self::TOOL_TYPE_PICKAXE => 'tool_pickaxe',
        self::TOOL_TYPE_SICKLE => 'tool_sickle',
        self::TOOL_TYPE_FISHING_ROD => 'tool_fishing_rod',
        self::TOOL_TYPE_SKINNING_KNIFE => 'tool_skinning_knife',
        self::TOOL_TYPE_HAMMER => 'tool_hammer',
        self::TOOL_TYPE_TANNING_KIT => 'tool_tanning_kit',
        self::TOOL_TYPE_MORTAR => 'tool_mortar',
        self::TOOL_TYPE_CHISEL => 'tool_chisel',
        self::TOOL_TYPE_AXE => 'tool_axe',
        self::TOOL_TYPE_COOKPOT => 'tool_cookpot',
        self::TOOL_TYPE_PLANE => 'tool_plane',
        self::TOOL_TYPE_NEEDLE => 'tool_needle',
    ];

    public const CRAFT_TOOL_TYPES = [
        'forgeron' => self::TOOL_TYPE_HAMMER,
        'tanneur' => self::TOOL_TYPE_TANNING_KIT,
        'alchimiste' => self::TOOL_TYPE_MORTAR,
        'joaillier' => self::TOOL_TYPE_CHISEL,
        // OBJ-06 : les sept metiers d'artisanat exigent desormais leur outil.
        'cuisinier' => self::TOOL_TYPE_COOKPOT,
        'charpentier' => self::TOOL_TYPE_PLANE,
        'tailleur' => self::TOOL_TYPE_NEEDLE,
    ];

    // OBJ-05 : l'outil que chaque profession de filon exige (GAME_ITEMS §4).
    // Le depecage (`skinning`) n'a pas de filon — il passe par le depecage de
    // carcasse — mais la cle est declaree pour que la regle soit la meme le
    // jour ou un filon de depeçage existera.
    public const GATHER_TOOL_TYPES = [
        'mining' => self::TOOL_TYPE_PICKAXE,
        'herbalism' => self::TOOL_TYPE_SICKLE,
        'fishing' => self::TOOL_TYPE_FISHING_ROD,
        'woodcutting' => self::TOOL_TYPE_AXE,
        'skinning' => self::TOOL_TYPE_SKINNING_KNIFE,
    ];

    // OBJ-05 : le palier de l'outil module le rendement, jamais l'acces
    // (GAME_ITEMS §4.2). Bareme de GAME_TRADES §7 — bronze est la reference,
    // et le bonus ne passe PAS par `gather_percent` : ce curseur decale aussi
    // la bande de purete (PurityDrawer), l'outil ne doit toucher que la
    // quantite.
    public const TOOL_TIER_YIELD_PERCENT = [
        self::TOOL_TIER_BRONZE => 0,
        self::TOOL_TIER_IRON => 8,
        self::TOOL_TIER_STEEL => 18,
        self::TOOL_TIER_MITHRIL => 30,
    ];

    public const TOOL_TYPE_LABELS = [
        self::TOOL_TYPE_PICKAXE => 'une pioche',
        self::TOOL_TYPE_SICKLE => 'une faucille',
        self::TOOL_TYPE_FISHING_ROD => 'une canne à pêche',
        self::TOOL_TYPE_SKINNING_KNIFE => 'un couteau de dépeçage',
        self::TOOL_TYPE_HAMMER => 'un marteau de forge',
        self::TOOL_TYPE_TANNING_KIT => 'un kit de tannage',
        self::TOOL_TYPE_MORTAR => 'un mortier d\'alchimie',
        self::TOOL_TYPE_CHISEL => 'un burin de joaillier',
        self::TOOL_TYPE_AXE => 'une hache',
        self::TOOL_TYPE_COOKPOT => 'une marmite de cuisine',
        self::TOOL_TYPE_PLANE => 'une varlope de charpentier',
        self::TOOL_TYPE_NEEDLE => 'une aiguille de tailleur',
    ];

    public const TOOL_TIER_BRONZE = 1;
    public const TOOL_TIER_IRON = 2;
    public const TOOL_TIER_STEEL = 3;
    public const TOOL_TIER_MITHRIL = 4;

    public const GEAR_LOCATION_HEAD = 'head';
    public const GEAR_LOCATION_NECK = 'neck';
    public const GEAR_LOCATION_CHEST = 'chest';
    public const GEAR_LOCATION_HAND = 'hand';
    public const GEAR_LOCATION_FINGER = 'finger';
    public const GEAR_LOCATION_LEGS = 'legs';
    public const GEAR_LOCATION_FEET = 'feet';
    public const GEAR_LOCATION_MAIN_HAND = 'main_hand';
    public const GEAR_LOCATION_OFF_HAND = 'off_hand';
    public const GEAR_LOCATION_TWO_HAND = 'two_hand';
    public const GEAR_LOCATION_RANGED = 'ranged';
    public const GEAR_LOCATION_AMMO = 'ammo';

    public const GEAR_LOCATION_MAIN_WEAPON = 'main_weapon';
    public const GEAR_LOCATION_SIDE_WEAPON = 'side_weapon';
    public const GEAR_LOCATION_BELT = 'belt';
    public const GEAR_LOCATION_LEG = 'leg';
    public const GEAR_LOCATION_FOOT = 'foot';
    public const GEAR_LOCATION_RING_1 = 'ring_1';
    public const GEAR_LOCATION_RING_2 = 'ring_2';
    public const GEAR_LOCATION_SHOULDER = 'shoulder';

    public const GEAR_LOCATIONS = [
        self::GEAR_LOCATION_HEAD,
        self::GEAR_LOCATION_NECK,
        self::GEAR_LOCATION_CHEST,
        self::GEAR_LOCATION_HAND,
        self::GEAR_LOCATION_MAIN_WEAPON,
        self::GEAR_LOCATION_SIDE_WEAPON,
        self::GEAR_LOCATION_BELT,
        self::GEAR_LOCATION_LEG,
        self::GEAR_LOCATION_FOOT,
        self::GEAR_LOCATION_RING_1,
        self::GEAR_LOCATION_RING_2,
        self::GEAR_LOCATION_SHOULDER,
    ];

    public function __toString(): string
    {
        return $this->getName();
    }

    public function isObject(): bool
    {
        return $this->getType() === self::TYPE_STUFF;
    }

    public function isMateria(): bool
    {
        return $this->getType() === self::TYPE_MATERIA;
    }

    public function isGear(): bool
    {
        return $this->getType() === self::TYPE_GEAR_PIECE;
    }

    public function isResource(): bool
    {
        return $this->getType() === self::TYPE_RESOURCE;
    }

    public function isTool(): bool
    {
        return $this->getType() === self::TYPE_TOOL;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->requirements = new ArrayCollection();
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

    #[ORM\Column(name: 'price', type: 'integer', nullable: true)]
    private $price;

    #[ORM\Column(name: 'description', type: 'text')]
    private $description;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    #[ORM\Column(name: 'protection', type: 'integer', nullable: true)]
    private $protection;

    #[ORM\Column(name: 'energy_cost', type: 'integer', nullable: true)]
    private $energyCost;

    #[ORM\Column(name: 'type', type: 'string', length: 50, options: ['default' => 'stuff'])]
    private $type = self::TYPE_STUFF;

    #[ORM\Column(name: 'space', type: 'integer')]
    private $space = 1;

    #[ORM\Column(name: 'element', type: 'string', length: 25, enumType: Element::class)]
    private Element $element = Element::None;

    /**
     * L'affinite elementaire de la matiere — la loi 10 (ZON-36).
     *
     * Distincte de `element`, qui dit le flux qu'une arme **projette**. Celle-ci
     * dit le flux dont une ressource est faite : le mithril est Air parce que le
     * vent l'a mis a nu, pas parce qu'il frappe au vent.
     *
     * Nullable, et le `null` porte deux sens que `Element::None` ne saurait
     * separer : « pas une ressource » et « l'amethyste, qui est le substrat et
     * non un flux ». `ResourceAffinityCatalog::covers()` tranche entre les deux
     * — et reste la source de verite : cette colonne n'en est que la projection,
     * posee au chargement des fixtures pour que la donnee soit interrogeable.
     */
    #[ORM\Column(name: 'affinity', type: 'string', length: 25, nullable: true, enumType: Element::class)]
    private ?Element $affinity = null;

    #[ORM\Column(name: 'gear_location', type: 'string', nullable: true)]
    private $gearLocation;

    #[ORM\Column(name: 'slug', type: 'string', length: 255)]
    private $slug;

    #[ORM\Column(name: 'effect', type: 'text', nullable: true)]
    private $effect;

    #[ORM\ManyToOne(targetEntity: Spell::class)]
    #[ORM\JoinColumn(name: 'spell_id', referencedColumnName: 'id')]
    private $spell;

    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'items')]
    #[ORM\JoinTable(name: 'item_skill_requirement')]
    private $requirements;

    #[ORM\Column(name: 'level', type: 'integer', nullable: true)]
    private $level;

    #[ORM\Column(name: 'nb_usages', type: 'integer', options: ['default' => -1])]
    private $nbUsages = -1;

    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'domain_id', referencedColumnName: 'id')]
    private $domain;

    #[ORM\Column(name: 'tool_type', type: 'string', length: 50, nullable: true)]
    private ?string $toolType = null;

    #[ORM\Column(name: 'tool_tier', type: 'integer', nullable: true)]
    private ?int $toolTier = null;

    #[ORM\Column(name: 'durability', type: 'integer', nullable: true)]
    private ?int $durability = null;

    #[ORM\Column(name: 'value', type: 'integer', nullable: true)]
    private ?int $value = null;

    #[ORM\Column(name: 'rarity', type: 'string', length: 50, nullable: true, enumType: ItemRarity::class)]
    private ?ItemRarity $rarity = null;

    /**
     * Type de liaison (ECO-01). Remplace l'ancien booleen `bound_to_player`,
     * qui ne savait exprimer que « lie des l'obtention ».
     */
    #[ORM\Column(name: 'bind_type', type: 'string', length: 20, options: ['default' => 'none'], enumType: BindType::class)]
    private BindType $bindType = BindType::None;

    #[ORM\Column(name: 'materia_slots', type: 'integer', options: ['default' => 0])]
    private int $materiaSlots = 0;

    #[ORM\Column(name: 'materia_slot_config', type: 'json', nullable: true)]
    private ?array $materiaSlotConfig = null;

    /**
     * Ce que les emplacements de cette piece acceptent — DOM-03.
     *
     * **Un type par piece, pas un par emplacement**, et c'est la lettre du canon :
     * « la robe porte des emplacements de sort ; la plaque, des emplacements de
     * technique ». Une piece est d'une famille, ses emplacements en heritent.
     * Panacher les types au sein d'une meme piece aurait demande d'ordonner les
     * emplacements, qui n'ont pas d'indice — et aurait fait dependre le
     * sertissage de l'ordre des identifiants en base.
     *
     * `null` vaut `Free` : une piece qui ne dit rien accepte tout. Le typage est
     * donc **additif**, et le plancher jour 1 tient sans qu'on l'ecrive piece
     * par piece.
     */
    #[ORM\Column(name: 'materia_slot_type', type: 'string', length: 20, nullable: true, enumType: MateriaSlotType::class)]
    private ?MateriaSlotType $materiaSlotType = null;

    #[ORM\Column(name: 'is_cosmetic', type: 'boolean', options: ['default' => false])]
    private bool $isCosmetic = false;

    #[ORM\Column(name: 'avatar_sheet', type: 'string', length: 255, nullable: true)]
    private ?string $avatarSheet = null;

    #[ORM\ManyToOne(targetEntity: EquipmentSet::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'equipment_set_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?EquipmentSet $equipmentSet = null;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): Item
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
     * @param array<string, string>|null $translations
     */
    public function setNameTranslations(?array $translations): Item
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
     * Set type.
     *
     * @param int $type
     */
    public function setType($type): Item
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set space.
     *
     * @param int $space
     */
    public function setSpace($space): Item
    {
        $this->space = $space;

        return $this;
    }

    /**
     * Get space.
     */
    public function getSpace(): int
    {
        return $this->space;
    }

    /**
     * Set protection.
     *
     * @param int $protection
     */
    public function setProtection($protection): Item
    {
        $this->protection = $protection;

        return $this;
    }

    /**
     * Get protection.
     */
    public function getProtection(): int
    {
        return $this->protection ?? 0;
    }

    /**
     * Set energyCost.
     *
     * @param int $energyCost
     */
    public function setEnergyCost($energyCost): Item
    {
        $this->energyCost = $energyCost;

        return $this;
    }

    /**
     * Get energyCost.
     */
    public function getEnergyCost(): ?int
    {
        return $this->energyCost;
    }

    public function setElement(Element $element): Item
    {
        $this->element = $element;

        return $this;
    }

    public function getElement(): Element
    {
        return $this->element;
    }

    public function setAffinity(?Element $affinity): Item
    {
        $this->affinity = $affinity;

        return $this;
    }

    public function getAffinity(): ?Element
    {
        return $this->affinity;
    }

    /**
     * Set gearLocation.
     *
     * @param string|null $gearLocation
     */
    public function setGearLocation($gearLocation): Item
    {
        $this->gearLocation = $gearLocation;

        return $this;
    }

    /**
     * Get gearLocation.
     */
    public function getGearLocation(): ?string
    {
        return $this->gearLocation;
    }

    /**
     * Set spell.
     */
    public function setSpell(?Spell $spell = null): Item
    {
        $this->spell = $spell;

        return $this;
    }

    /**
     * Get spell.
     */
    public function getSpell(): ?Spell
    {
        return $this->spell;
    }

    /**
     * Set price.
     *
     * @param int $price
     */
    public function setPrice($price): Item
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     */
    public function getPrice(): ?int
    {
        return $this->price;
    }

    /**
     * Add requirement.
     */
    public function addRequirement(Skill $requirement): Item
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
     * @return Collection<Skill>
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * @param array<Skill> $requirements
     */
    public function setRequirements(array $requirements): void
    {
        $this->requirements = new ArrayCollection($requirements);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
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
     * @param array<string, string>|null $translations
     */
    public function setDescriptionTranslations(?array $translations): Item
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->descriptionTranslations = $normalized === [] ? null : $normalized;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain = null): void
    {
        $this->domain = $domain;
    }

    public function getNbUsages(): int
    {
        return $this->nbUsages;
    }

    public function setNbUsages(int $nbUsages): void
    {
        $this->nbUsages = $nbUsages;
    }

    public function getEffect(): ?string
    {
        return $this->effect;
    }

    public function setEffect(string $effect): void
    {
        $this->effect = $effect;
    }

    public function getToolType(): ?string
    {
        return $this->toolType;
    }

    public function setToolType(?string $toolType): void
    {
        $this->toolType = $toolType;
    }

    public function getToolTier(): ?int
    {
        return $this->toolTier;
    }

    public function setToolTier(?int $toolTier): void
    {
        $this->toolTier = $toolTier;
    }

    public function getDurability(): ?int
    {
        return $this->durability;
    }

    public function setDurability(?int $durability): void
    {
        $this->durability = $durability;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): void
    {
        $this->value = $value;
    }

    public function getRarity(): ?string
    {
        return $this->rarity?->value;
    }

    public function getRarityEnum(): ?ItemRarity
    {
        return $this->rarity;
    }

    public function setRarity(ItemRarity|string|null $rarity): void
    {
        if (\is_string($rarity)) {
            $rarity = ItemRarity::tryFrom($rarity);
        }
        $this->rarity = $rarity;
    }

    public function getBindType(): BindType
    {
        return $this->bindType;
    }

    public function setBindType(BindType $bindType): void
    {
        $this->bindType = $bindType;
    }

    /**
     * L'objet se lie-t-il des l'obtention ? Equivalent de l'ancien booleen
     * `boundToPlayer` : conserve car c'est la question que posent les points
     * d'entree du butin et de la boutique.
     */
    public function isBoundOnPickup(): bool
    {
        return BindType::BindOnPickup === $this->bindType;
    }

    /** L'objet se lie-t-il au premier equipement ? */
    public function isBoundOnEquip(): bool
    {
        return BindType::BindOnEquip === $this->bindType;
    }

    public function getMateriaSlots(): int
    {
        return $this->materiaSlots;
    }

    public function setMateriaSlots(int $materiaSlots): void
    {
        $this->materiaSlots = $materiaSlots;
    }

    /**
     * Ce que les emplacements de cette piece acceptent (DOM-03).
     *
     * Jamais `null` en sortie : l'absence de declaration **est** une reponse, et
     * c'est « libre ». Laisser fuir le `null` obligerait chaque appelant a
     * refaire ce choix, et l'un d'eux finirait par le refaire autrement.
     */
    public function getMateriaSlotType(): MateriaSlotType
    {
        return $this->materiaSlotType ?? MateriaSlotType::Free;
    }

    public function setMateriaSlotType(?MateriaSlotType $type): void
    {
        $this->materiaSlotType = $type;
    }

    /**
     * Le genre de materia que cet objet **est**, s'il en est une.
     *
     * Derive plutot que declare : une materia qui accorde un sort est une
     * materia de sort. Ajouter une colonne aurait permis de la contredire — une
     * materia declaree « technique » et porteuse d'un sort n'aurait eu aucun
     * comportement defini.
     *
     * **ARC-02 — le genre suit le registre du geste porte.** La derivation
     * lisait auparavant la seule presence d'un sort, donc toute materia sans
     * geste tombait en « technique » par defaut — ce qui etait faux dans les
     * deux sens : une materia vide n'est pas une technique, et une materia
     * portant un geste de melee etait annoncee « sort ». Le registre repond
     * maintenant a la question, et la materia en **herite** comme elle herite
     * de l'element.
     *
     * Une materia sans geste reste `Free` : elle n'exige rien de la piece qui
     * l'accueille, ce qui est le comportement d'avant le jalon pour tout ce
     * qui n'a pas d'accord.
     */
    public function getMateriaKind(): MateriaSlotType
    {
        if ($this->spell === null) {
            return MateriaSlotType::Free;
        }

        return $this->spell->isTechnique() ? MateriaSlotType::Technique : MateriaSlotType::Spell;
    }

    public function getMateriaSlotConfig(): array
    {
        return $this->materiaSlotConfig ?? [];
    }

    public function setMateriaSlotConfig(?array $config): void
    {
        $this->materiaSlotConfig = $config;

        if ($config !== null) {
            $this->materiaSlots = \count($config);
        }
    }

    public function isCosmetic(): bool
    {
        return $this->isCosmetic;
    }

    public function setIsCosmetic(bool $isCosmetic): void
    {
        $this->isCosmetic = $isCosmetic;
    }

    public function getEquipmentSet(): ?EquipmentSet
    {
        return $this->equipmentSet;
    }

    public function setEquipmentSet(?EquipmentSet $equipmentSet): void
    {
        $this->equipmentSet = $equipmentSet;
    }

    public function hasEquipmentSet(): bool
    {
        return $this->equipmentSet !== null;
    }

    public function getAvatarSheet(): ?string
    {
        return $this->avatarSheet;
    }

    public function setAvatarSheet(?string $avatarSheet): void
    {
        $this->avatarSheet = $avatarSheet;
    }
}
