<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'pnj')]
#[ORM\Index(columns: ['map_id'], name: 'idx_pnj_map')]
#[ORM\Entity()]
class Pnj
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'name_translations', type: 'json', nullable: true)]
    private ?array $nameTranslations = null;

    #[ORM\Column(name: 'class_type', type: 'string', length: 255)]
    private $class_type;

    #[ORM\Column(name: 'life', type: 'integer')]
    private $life;

    #[ORM\Column(name: 'max_life', type: 'integer')]
    private $maxLife;

    #[ORM\Column(name: 'dialog', type: 'json', nullable: true)]
    private $dialog;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'pnjs')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map;

    #[ORM\Column(name: 'shop_items', type: 'json', nullable: true)]
    private ?array $shopItems = null;

    /**
     * Coordonnées du player sur la carte.
     */
    #[ORM\Column(name: 'coordinates', type: 'string')]
    protected string $coordinates;

    /**
     * Heure d'ouverture de la boutique (0-23 in-game). Null = toujours ouvert.
     */
    #[ORM\Column(name: 'opens_at', type: 'integer', nullable: true)]
    private ?int $opensAt = null;

    /**
     * Heure de fermeture de la boutique (0-23 in-game). Null = toujours ouvert.
     */
    #[ORM\Column(name: 'closes_at', type: 'integer', nullable: true)]
    private ?int $closesAt = null;

    #[ORM\Column(name: 'portrait', type: 'string', length: 255, nullable: true)]
    private ?string $portrait = null;

    /**
     * Stock par item slug : {"sword": {"stock": 5, "maxStock": 10, "restockInterval": 3600}}.
     * Null = stock illimité (comportement par défaut).
     */
    #[ORM\Column(name: 'shop_stock', type: 'json', nullable: true)]
    private ?array $shopStock = null;

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
     * Set name.
     *
     * @param string $name
     *
     * @return Pnj
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
            if (!\is_string($value)) {
                continue;
            }
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
     * @return Pnj
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
     * Set maxLife.
     *
     * @param int $maxLife
     *
     * @return Pnj
     */
    public function setMaxLife($maxLife)
    {
        $this->maxLife = $maxLife;

        return $this;
    }

    /**
     * Get maxLife.
     *
     * @return int
     */
    public function getMaxLife()
    {
        return $this->maxLife;
    }

    public function getClassType(): string
    {
        return $this->class_type;
    }

    public function setClassType(string $class_type): void
    {
        $this->class_type = $class_type;
    }

    public function getDialog(): array
    {
        return $this->dialog ?? [];
    }

    public function setDialog(array $dialog = []): void
    {
        $this->dialog = $dialog;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }

    public function getCoordinates(): string
    {
        return $this->coordinates;
    }

    public function setCoordinates(string $coordinates): void
    {
        $this->coordinates = $coordinates;
    }

    public function getShopItems(): ?array
    {
        return $this->shopItems;
    }

    public function setShopItems(?array $shopItems): void
    {
        $this->shopItems = $shopItems;
    }

    public function isMerchant(): bool
    {
        return $this->shopItems !== null && count($this->shopItems) > 0;
    }

    public function getOpensAt(): ?int
    {
        return $this->opensAt;
    }

    public function setOpensAt(?int $opensAt): void
    {
        $this->opensAt = $opensAt;
    }

    public function getClosesAt(): ?int
    {
        return $this->closesAt;
    }

    public function setClosesAt(?int $closesAt): void
    {
        $this->closesAt = $closesAt;
    }

    public function getPortrait(): ?string
    {
        return $this->portrait;
    }

    public function setPortrait(?string $portrait): void
    {
        $this->portrait = $portrait;
    }

    public function getShopStock(): ?array
    {
        return $this->shopStock;
    }

    public function setShopStock(?array $shopStock): void
    {
        $this->shopStock = $shopStock;
    }

    /**
     * Retourne le stock restant pour un item. Null = illimité.
     */
    public function getItemStock(string $slug): ?int
    {
        if ($this->shopStock === null || !isset($this->shopStock[$slug])) {
            return null;
        }

        return (int) ($this->shopStock[$slug]['stock'] ?? 0);
    }

    /**
     * Décrémente le stock d'un item. Ne fait rien si stock illimité.
     */
    public function decrementStock(string $slug, int $quantity = 1): void
    {
        if ($this->shopStock === null || !isset($this->shopStock[$slug])) {
            return;
        }

        $this->shopStock[$slug]['stock'] = max(0, ($this->shopStock[$slug]['stock'] ?? 0) - $quantity);
    }

    /**
     * Restock tous les items dont le restockInterval est écoulé.
     */
    public function restockItems(\DateTimeInterface $now): int
    {
        if ($this->shopStock === null) {
            return 0;
        }

        $restocked = 0;
        foreach ($this->shopStock as $slug => &$data) {
            $maxStock = (int) ($data['maxStock'] ?? 0);
            $currentStock = (int) ($data['stock'] ?? 0);
            $interval = (int) ($data['restockInterval'] ?? 0);

            if ($currentStock >= $maxStock || $interval <= 0) {
                continue;
            }

            $lastRestock = isset($data['lastRestockedAt']) ? new \DateTimeImmutable($data['lastRestockedAt']) : null;
            if ($lastRestock !== null && ($now->getTimestamp() - $lastRestock->getTimestamp()) < $interval) {
                continue;
            }

            $data['stock'] = $maxStock;
            $data['lastRestockedAt'] = $now->format('c');
            ++$restocked;
        }

        return $restocked;
    }

    /**
     * Vérifie si la boutique est ouverte à l'heure in-game donnée.
     * Si opensAt/closesAt sont null, la boutique est toujours ouverte.
     */
    public function isShopOpen(int $gameHour): bool
    {
        if ($this->opensAt === null || $this->closesAt === null) {
            return true;
        }

        // Cas normal : opensAt < closesAt (ex: 8h-18h)
        if ($this->opensAt < $this->closesAt) {
            return $gameHour >= $this->opensAt && $gameHour < $this->closesAt;
        }

        // Cas nuit : opensAt > closesAt (ex: 20h-6h)
        return $gameHour >= $this->opensAt || $gameHour < $this->closesAt;
    }
}
