<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'pnj')]
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
}
