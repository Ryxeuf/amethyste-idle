<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'tileset')]
#[ORM\UniqueConstraint(name: 'tileset_name_uniq', columns: ['name'])]
class Tileset
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 100)]
    private string $name;

    /** Chemin relatif de l'image depuis assets/styles/images/ (ex: "terrain/terrain.png") */
    #[ORM\Column(name: 'image_path', type: 'string', length: 500)]
    private string $imagePath;

    #[ORM\Column(name: 'columns_count', type: 'integer')]
    private int $columnsCount;

    #[ORM\Column(name: 'tile_count', type: 'integer')]
    private int $tileCount;

    #[ORM\Column(name: 'tile_width', type: 'integer', options: ['default' => 32])]
    private int $tileWidth = 32;

    #[ORM\Column(name: 'tile_height', type: 'integer', options: ['default' => 32])]
    private int $tileHeight = 32;

    #[ORM\Column(name: 'first_gid', type: 'integer')]
    private int $firstGid;

    /** Les tilesets built-in (terrain, forest, etc.) ne sont pas supprimables */
    #[ORM\Column(name: 'is_builtin', type: 'boolean', options: ['default' => false])]
    private bool $isBuiltin = false;

    /** Peut-on peindre avec ce tileset dans l'editeur ? */
    #[ORM\Column(name: 'is_editable', type: 'boolean', options: ['default' => true])]
    private bool $isEditable = true;

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $imagePath): self
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getColumnsCount(): int
    {
        return $this->columnsCount;
    }

    public function setColumnsCount(int $columnsCount): self
    {
        $this->columnsCount = $columnsCount;

        return $this;
    }

    public function getTileCount(): int
    {
        return $this->tileCount;
    }

    public function setTileCount(int $tileCount): self
    {
        $this->tileCount = $tileCount;

        return $this;
    }

    public function getTileWidth(): int
    {
        return $this->tileWidth;
    }

    public function setTileWidth(int $tileWidth): self
    {
        $this->tileWidth = $tileWidth;

        return $this;
    }

    public function getTileHeight(): int
    {
        return $this->tileHeight;
    }

    public function setTileHeight(int $tileHeight): self
    {
        $this->tileHeight = $tileHeight;

        return $this;
    }

    public function getFirstGid(): int
    {
        return $this->firstGid;
    }

    public function setFirstGid(int $firstGid): self
    {
        $this->firstGid = $firstGid;

        return $this;
    }

    public function isBuiltin(): bool
    {
        return $this->isBuiltin;
    }

    public function setIsBuiltin(bool $isBuiltin): self
    {
        $this->isBuiltin = $isBuiltin;

        return $this;
    }

    public function isEditable(): bool
    {
        return $this->isEditable;
    }

    public function setIsEditable(bool $isEditable): self
    {
        $this->isEditable = $isEditable;

        return $this;
    }
}
