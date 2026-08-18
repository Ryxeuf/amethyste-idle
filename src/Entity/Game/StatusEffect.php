<?php

namespace App\Entity\Game;

use App\Enum\Element;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_status_effects')]
class StatusEffect
{
    use TimestampableEntity;

    public const TYPE_POISON = 'poison';
    public const TYPE_PARALYSIS = 'paralysis';
    public const TYPE_BURN = 'burn';
    public const TYPE_FREEZE = 'freeze';
    public const TYPE_SILENCE = 'silence';
    public const TYPE_REGENERATION = 'regeneration';
    public const TYPE_SHIELD = 'shield';
    public const TYPE_BERSERK = 'berserk';

    /**
     * La marque d'un element (ARC-13).
     *
     * Elle ne dit pas *ce que l'effet fait* comme les huit autres, mais *par
     * quoi il agit* : une marque pure modifie une statistique et sert de prise
     * aux passifs d'arbre. La Brulure garde `TYPE_BURN` — elle est un DOT qui
     * se **trouve** etre la marque du feu, et c'est `ElementalMark` qui le dit.
     */
    public const TYPE_MARK = 'mark';

    /**
     * ARC-18a — la riposte : *etre frappe est une action*.
     *
     * Un depot sur soi qui rend des degats a qui vous touche, **et seulement
     * quand le coup a reellement retire des points de vie** (`RiposteLaw`). Le
     * type existe a part parce que la forme n'est ni une protection — elle
     * n'empeche rien — ni une entrave : elle **repond**.
     */
    public const TYPE_RIPOSTE = 'riposte';

    /**
     * ARC-18b — la posture : *un choix durable qu'on remplace*.
     *
     * Un depot sur soi **sans duree** et **exclusif** : elle ne finit pas en
     * se decomptant, mais parce qu'on en pose une autre ou parce que la
     * rencontre s'acheve. Le type existe a part parce qu'elle n'ameliore pas
     * comme le berserk — elle **deplace** : ce qu'elle donne sur un levier,
     * elle le retire sur un autre (`StanceLaw`).
     */
    public const TYPE_STANCE = 'stance';

    /**
     * ARC-18d — le transfert : *une part des degats des allies vous revient*.
     *
     * Un depot pose sur le groupe, qui ne fait ni bien ni mal a celui qui le
     * porte : il **deplace** qui paie. Le type existe a part parce que la forme
     * n'est ni une protection — elle n'empeche aucun degat, le total de la
     * rencontre est inchange — ni une amelioration : elle rend le porteur
     * strictement plus fragile.
     */
    public const TYPE_TRANSFER = 'transfer';

    /**
     * ARC-18h — le familier : *une creature qui frappe a votre place*.
     *
     * L'arbitrage du § 13.3 en fait un **depot offensif et non un acteur** :
     * retirez le ciblage, il ne reste qu'une chose qui inflige des degats a
     * chaque tour pendant une duree, posee en un tour. Le type existe a part
     * d'un DOT ordinaire parce que sa valeur ne s'ecrit pas — *elle se derive
     * d'un tour d'attaque de son invocateur* (`FamiliarLaw`) —, et parce qu'il
     * n'y en a **qu'un a la fois**.
     */
    public const TYPE_FAMILIAR = 'familiar';

    public const TYPES = [
        self::TYPE_POISON,
        self::TYPE_PARALYSIS,
        self::TYPE_BURN,
        self::TYPE_FREEZE,
        self::TYPE_SILENCE,
        self::TYPE_REGENERATION,
        self::TYPE_SHIELD,
        self::TYPE_BERSERK,
        self::TYPE_MARK,
        self::TYPE_RIPOSTE,
        self::TYPE_STANCE,
        self::TYPE_TRANSFER,
        self::TYPE_FAMILIAR,
    ];

    public const CATEGORY_BUFF = 'buff';
    public const CATEGORY_DEBUFF = 'debuff';
    public const CATEGORY_HOT = 'hot';
    public const CATEGORY_DOT = 'dot';

    public const CATEGORIES = [
        self::CATEGORY_BUFF,
        self::CATEGORY_DEBUFF,
        self::CATEGORY_HOT,
        self::CATEGORY_DOT,
    ];

    public function __toString(): string
    {
        return $this->name;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'name_translations', type: 'json', nullable: true)]
    private ?array $nameTranslations = null;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(name: 'duration', type: 'integer')]
    private int $duration;

    #[ORM\Column(name: 'damage_per_turn', type: 'integer', nullable: true)]
    private ?int $damagePerTurn = null;

    #[ORM\Column(name: 'heal_per_turn', type: 'integer', nullable: true)]
    private ?int $healPerTurn = null;

    #[ORM\Column(name: 'stat_modifier', type: 'json', nullable: true)]
    private ?array $statModifier = null;

    /**
     * Les leviers qu'une posture deplace (ARC-18b).
     *
     * `{"power": 6, "guard": -6}` : des points de budget, dans le vocabulaire
     * ferme de `CombatLever`, jamais des effets. La colonne est distincte de
     * `stat_modifier` **et ne la remplace pas** : celle-la porte un vocabulaire
     * ouvert (`damage`, `speed`, `defense`, `shield_absorb`, `max_life`…) que
     * rien ne borne, celle-ci la seule unite que le budget de 50 points sache
     * compter. Elle est `null` sur les quinze statuts livres.
     *
     * @var array<array-key, mixed>|null
     */
    #[ORM\Column(name: 'levers', type: 'json', nullable: true)]
    private ?array $levers = null;

    #[ORM\Column(name: 'chance', type: 'integer', options: ['default' => 100])]
    private int $chance = 100;

    #[ORM\Column(name: 'element', type: 'string', length: 25, enumType: Element::class, options: ['default' => 'none'])]
    private Element $element = Element::None;

    #[ORM\Column(name: 'icon', type: 'string', length: 100, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(name: 'category', type: 'string', length: 20, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(name: 'frequency', type: 'integer', nullable: true)]
    private ?int $frequency = null;

    #[ORM\Column(name: 'real_time_duration', type: 'integer', nullable: true)]
    private ?int $realTimeDuration = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

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
    public function setNameTranslations(?array $translations): self
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getDamagePerTurn(): ?int
    {
        return $this->damagePerTurn;
    }

    public function setDamagePerTurn(?int $damagePerTurn): void
    {
        $this->damagePerTurn = $damagePerTurn;
    }

    public function getHealPerTurn(): ?int
    {
        return $this->healPerTurn;
    }

    public function setHealPerTurn(?int $healPerTurn): void
    {
        $this->healPerTurn = $healPerTurn;
    }

    public function getStatModifier(): ?array
    {
        return $this->statModifier;
    }

    public function setStatModifier(?array $statModifier): void
    {
        $this->statModifier = $statModifier;
    }

    /**
     * Ce que la colonne contient, sans promesse sur sa forme.
     *
     * Le type annonce `mixed` et non `array<string, int>` **exprès** : c'est un
     * JSON, il accepte n'importe quoi, et promettre ici une forme que rien ne
     * garantit ferait taire le seul endroit qui la verifie
     * (`StanceLeverReader`). La meme prudence que `Skill::getLevers()`.
     *
     * @return array<array-key, mixed>
     */
    public function getLevers(): array
    {
        return $this->levers ?? [];
    }

    /**
     * @param array<array-key, mixed>|null $levers
     */
    public function setLevers(?array $levers): void
    {
        $this->levers = $levers;
    }

    public function getChance(): int
    {
        return $this->chance;
    }

    public function setChance(int $chance): void
    {
        $this->chance = $chance;
    }

    public function getElement(): Element
    {
        return $this->element;
    }

    public function setElement(Element $element): void
    {
        $this->element = $element;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getFrequency(): ?int
    {
        return $this->frequency;
    }

    public function setFrequency(?int $frequency): void
    {
        $this->frequency = $frequency;
    }

    public function getRealTimeDuration(): ?int
    {
        return $this->realTimeDuration;
    }

    public function setRealTimeDuration(?int $realTimeDuration): void
    {
        $this->realTimeDuration = $realTimeDuration;
    }

    public function isBuff(): bool
    {
        return $this->category === self::CATEGORY_BUFF;
    }

    public function isDebuff(): bool
    {
        return $this->category === self::CATEGORY_DEBUFF;
    }

    public function isHot(): bool
    {
        return $this->category === self::CATEGORY_HOT;
    }

    public function isDot(): bool
    {
        return $this->category === self::CATEGORY_DOT;
    }

    public function hasPersistentDuration(): bool
    {
        return $this->realTimeDuration !== null && $this->realTimeDuration > 0;
    }

    public function isDamaging(): bool
    {
        return $this->damagePerTurn !== null && $this->damagePerTurn > 0;
    }

    public function isHealing(): bool
    {
        return $this->healPerTurn !== null && $this->healPerTurn > 0;
    }

    public function hasStatModifier(): bool
    {
        return $this->statModifier !== null && count($this->statModifier) > 0;
    }
}
