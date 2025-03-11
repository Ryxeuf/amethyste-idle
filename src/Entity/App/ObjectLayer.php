<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CoordinatesTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'object_layer')]
#[ORM\Entity()]
class ObjectLayer
{
    use TimestampableEntity;
    use CoordinatesTrait;

    const TYPE_OTHER = 'other';
    const TYPE_CHEST = 'chest';
    const TYPE_SPOT = 'spot';

    function __toString(): string
    {
        return $this->getName();
    }

    public function isDynamic(): bool
    {
        return $this->isUsable();
    }

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'slug', type: 'string', length: 255)]
    private string $slug;

    /**
     * @var string
     */
    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    private string $type = self::TYPE_OTHER;

    /**
     * Date d'utilisation
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $usedAt;

    /**
     * Liste des items que l'objet peut contenir
     */
    #[ORM\Column(name: 'items', type: 'json', nullable: true)]
    private ?array $items;

    /**
     * Modificateur de mouvement
     * Si la valeur est -1, la case devient impénétrable
     * @var int
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $movement = 0;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'actions', type: 'json', nullable: true)]
    private ?array $actions;

    #[ORM\Column(name: 'usable', type: 'boolean', options: ['default' => 0])]
    private bool $usable = false;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'objectLayers', fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
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
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return DateTime
     */
    public function getUsedAt(): ?DateTime
    {
        return $this->usedAt;
    }

    /**
     * @param DateTime|null $usedAt
     */
    public function setUsedAt(?DateTime $usedAt): void
    {
        $this->usedAt = $usedAt;
    }

    /**
     * @return array|null
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    /**
     * @param array|null $items
     */
    public function setItems(?array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return int
     */
    public function getMovement(): int
    {
        return $this->movement;
    }

    /**
     * @param int $movement
     */
    public function setMovement(int $movement): void
    {
        $this->movement = $movement;
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

    public function isUsable(): bool
    {
        return $this->usable;
    }

    public function setUsable(bool $usable): void
    {
        $this->usable = $usable;
    }

    /**
     * @return null|Map
     */
    public function getMap(): ?Map
    {
        return $this->map;
    }

    /**
     * @param null|Map $map
     */
    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }
}
