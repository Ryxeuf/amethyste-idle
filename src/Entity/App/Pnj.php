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
    #[ORM\GeneratedValue(strategy: 'AUTO')]
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

    /**
     * Coordonnées du player sur la carte
     */
    #[ORM\Column(name: 'coordinates', type: 'string')]
    protected string $coordinates;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set life
     *
     * @param integer $life
     *
     * @return Pnj
     */
    public function setLife($life)
    {
        $this->life = $life;

        return $this;
    }

    /**
     * Get life
     *
     * @return integer
     */
    public function getLife()
    {
        return $this->life;
    }

    /**
     * Set maxLife
     *
     * @param integer $maxLife
     *
     * @return Pnj
     */
    public function setMaxLife($maxLife)
    {
        $this->maxLife = $maxLife;

        return $this;
    }

    /**
     * Get maxLife
     *
     * @return integer
     */
    public function getMaxLife()
    {
        return $this->maxLife;
    }

    /**
     * @return string
     */
    public function getClassType(): string
    {
        return $this->class_type;
    }

    /**
     * @param string $class_type
     */
    public function setClassType(string $class_type): void
    {
        $this->class_type = $class_type;
    }

    /**
     * @return array
     */
    public function getDialog(): array
    {
        return $this->dialog;
    }

    /**
     * @param array $dialog
     */
    public function setDialog(array $dialog = []): void
    {
        $this->dialog = $dialog;
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

    /**
     * @return string
     */
    public function getCoordinates(): string
    {
        return $this->coordinates;
    }

    /**
     * @param string $coordinates
     */
    public function setCoordinates(string $coordinates): void
    {
        $this->coordinates = $coordinates;
    }
}
