<?php

namespace App\GameEngine\Terrain;

use App\Entity\App\Tileset;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;

/**
 * Registre centralise des tilesets du projet.
 * Charge les tilesets built-in (constantes) + les tilesets custom depuis la base de donnees.
 */
class TilesetRegistry
{
    // --- First GID de chaque tileset (ordre fixe, depuis les fichiers TMX) ---
    public const FIRST_GID_TERRAIN = 1;
    public const FIRST_GID_FOREST = 1025;
    public const FIRST_GID_BASECHIP_PIPO = 4097;
    public const FIRST_GID_COLLISIONS = 5161;

    // --- GID cles : herbe variantes (dans Terrain tileset) ---
    public const GID_GRASS_BASE = 294;   // 1 + 293
    public const GID_GRASS_ALT1 = 354;   // 1 + 353
    public const GID_GRASS_ALT2 = 355;   // 1 + 354
    public const GID_GRASS_ALT3 = 356;   // 1 + 355

    // --- First GID tilesets thematiques (decorations biomes) ---
    public const FIRST_GID_ASHLANDS_B = 6001;
    public const FIRST_GID_BEACH_B = 7001;
    public const FIRST_GID_JUNGLE_B = 8001;
    public const FIRST_GID_DARKDIM_B = 9001;
    public const FIRST_GID_DARKDIM_A5 = 10001;
    public const FIRST_GID_ASHLANDS_A5 = 11001;

    // --- GID cles : collision ---
    public const GID_COLLISION_WALL = 5162;  // 5161 + 1 = mur impassable

    /**
     * Definitions statiques des 4 tilesets built-in, triees par firstGid.
     *
     * @var array<int, array{name: string, firstGid: int, columns: int, tileCount: int, tileWidth: int, tileHeight: int, imageFile: string}>
     */
    private const BUILTIN_TILESETS = [
        self::FIRST_GID_TERRAIN => [
            'name' => 'terrain',
            'firstGid' => self::FIRST_GID_TERRAIN,
            'columns' => 32,
            'tileCount' => 1024,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'terrain.png',
            'imagePrefix' => 'terrain/',
        ],
        self::FIRST_GID_FOREST => [
            'name' => 'forest',
            'firstGid' => self::FIRST_GID_FOREST,
            'columns' => 16,
            'tileCount' => 3072,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'forest.png',
            'imagePrefix' => 'terrain/',
        ],
        self::FIRST_GID_BASECHIP_PIPO => [
            'name' => 'BaseChip_pipo',
            'firstGid' => self::FIRST_GID_BASECHIP_PIPO,
            'columns' => 8,
            'tileCount' => 1064,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'BaseChip_pipo.png',
            'imagePrefix' => 'terrain/',
        ],
        self::FIRST_GID_COLLISIONS => [
            'name' => 'collisions',
            'firstGid' => self::FIRST_GID_COLLISIONS,
            'columns' => 6,
            'tileCount' => 18,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'collisions.png',
            'imagePrefix' => 'terrain/',
        ],
        self::FIRST_GID_ASHLANDS_B => [
            'name' => 'ashlands_b',
            'firstGid' => self::FIRST_GID_ASHLANDS_B,
            'columns' => 16,
            'tileCount' => 256,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'tf_B_ashlands_2.png',
            'imagePrefix' => 'tf_ashlands/2x_RMVX/',
        ],
        self::FIRST_GID_BEACH_B => [
            'name' => 'beach_b',
            'firstGid' => self::FIRST_GID_BEACH_B,
            'columns' => 16,
            'tileCount' => 256,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'tf_beach_tileB.png',
            'imagePrefix' => 'tf_beach_tileset/RMVX/',
        ],
        self::FIRST_GID_JUNGLE_B => [
            'name' => 'jungle_b',
            'firstGid' => self::FIRST_GID_JUNGLE_B,
            'columns' => 16,
            'tileCount' => 256,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'tf_jungle_b.png',
            'imagePrefix' => 'tf_jungle/rpgmaker/RMVX/',
        ],
        self::FIRST_GID_DARKDIM_B => [
            'name' => 'darkdim_b',
            'firstGid' => self::FIRST_GID_DARKDIM_B,
            'columns' => 16,
            'tileCount' => 256,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'tf_dd_B_2.png',
            'imagePrefix' => 'tf_darkdimension/RMVX/',
        ],
        self::FIRST_GID_DARKDIM_A5 => [
            'name' => 'darkdim_a5',
            'firstGid' => self::FIRST_GID_DARKDIM_A5,
            'columns' => 8,
            'tileCount' => 128,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'tf_dd_A5_2.png',
            'imagePrefix' => 'tf_darkdimension/RMVX/',
        ],
        self::FIRST_GID_ASHLANDS_A5 => [
            'name' => 'ashlands_a5',
            'firstGid' => self::FIRST_GID_ASHLANDS_A5,
            'columns' => 8,
            'tileCount' => 128,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'tf_A5_ashlands_2.png',
            'imagePrefix' => 'tf_ashlands/2x_RMVX/',
        ],
    ];

    /** @var array<int, array<string, mixed>>|null Merged tilesets cache (built-in + custom) */
    private ?array $allTilesets = null;

    /** @var list<int>|null Sorted firstGids cache */
    private ?array $sortedFirstGids = null;

    public function __construct(
        private readonly Packages $packages,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Retourne tous les tilesets (built-in + custom) tries par firstGid.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAllTilesets(): array
    {
        if ($this->allTilesets !== null) {
            return $this->allTilesets;
        }

        $this->allTilesets = self::BUILTIN_TILESETS;

        try {
            $customTilesets = $this->em->getRepository(Tileset::class)->findBy(['isBuiltin' => false]);
            foreach ($customTilesets as $tileset) {
                $this->allTilesets[$tileset->getFirstGid()] = [
                    'name' => $tileset->getName(),
                    'firstGid' => $tileset->getFirstGid(),
                    'columns' => $tileset->getColumnsCount(),
                    'tileCount' => $tileset->getTileCount(),
                    'tileWidth' => $tileset->getTileWidth(),
                    'tileHeight' => $tileset->getTileHeight(),
                    'imageFile' => basename($tileset->getImagePath()),
                    'imagePrefix' => dirname($tileset->getImagePath()) . '/',
                    'isEditable' => $tileset->isEditable(),
                ];
            }
        } catch (\Exception) {
            // Table may not exist yet during migrations
        }

        ksort($this->allTilesets);

        return $this->allTilesets;
    }

    /**
     * @return list<int>
     */
    private function getSortedFirstGids(): array
    {
        if ($this->sortedFirstGids !== null) {
            return $this->sortedFirstGids;
        }

        $gids = array_keys($this->getAllTilesets());
        rsort($gids);
        $this->sortedFirstGids = $gids;

        return $this->sortedFirstGids;
    }

    /** Invalide le cache (apres ajout/suppression d'un tileset) */
    public function clearCache(): void
    {
        $this->allTilesets = null;
        $this->sortedFirstGids = null;
    }

    /**
     * Retourne tous les tilesets avec leur URL publique d'image.
     *
     * @return list<array<string, mixed>>
     */
    public function getTilesets(): array
    {
        $result = [];
        foreach ($this->getAllTilesets() as $tileset) {
            $prefix = $tileset['imagePrefix'] ?? 'terrain/';
            $result[] = [
                ...$tileset,
                'imagePath' => $this->packages->getUrl('styles/images/' . $prefix . $tileset['imageFile']),
            ];
        }

        return $result;
    }

    /**
     * Resout le tileset auquel appartient un GID global.
     *
     * @return array<string, mixed>|null
     */
    public function getTilesetForGid(int $gid): ?array
    {
        $all = $this->getAllTilesets();
        foreach ($this->getSortedFirstGids() as $firstGid) {
            if ($gid >= $firstGid) {
                $tileset = $all[$firstGid];
                $localId = $gid - $firstGid;
                if ($localId < $tileset['tileCount']) {
                    return $tileset;
                }

                return null;
            }
        }

        return null;
    }

    /**
     * Convertit un GID global en ID local dans son tileset.
     */
    public function getLocalTileId(int $gid): int
    {
        foreach ($this->getSortedFirstGids() as $firstGid) {
            if ($gid >= $firstGid) {
                return $gid - $firstGid;
            }
        }

        return $gid;
    }

    /**
     * Retourne le nombre de colonnes pour un tileset identifie par son nom.
     */
    public function getColumnsForName(string $name): int
    {
        foreach ($this->getAllTilesets() as $tileset) {
            if (strcasecmp($tileset['name'], $name) === 0) {
                return $tileset['columns'];
            }
        }

        return 32;
    }

    /**
     * Retourne les tilesets formates pour l'API frontend (compatibilite existante).
     *
     * @return list<array{name: string, image: string, columns: int, tileCount: int, tileWidth: int, tileHeight: int, firstGid: int}>
     */
    public function getTilesetsForApi(): array
    {
        $result = [];
        foreach ($this->getAllTilesets() as $tileset) {
            $prefix = $tileset['imagePrefix'] ?? 'terrain/';
            $result[] = [
                'name' => $tileset['name'],
                'image' => $this->packages->getUrl('styles/images/' . $prefix . $tileset['imageFile']),
                'columns' => $tileset['columns'],
                'tileCount' => $tileset['tileCount'],
                'tileWidth' => $tileset['tileWidth'],
                'tileHeight' => $tileset['tileHeight'],
                'firstGid' => $tileset['firstGid'],
            ];
        }

        return $result;
    }

    /**
     * Calcule le prochain firstGid disponible pour un nouveau tileset.
     */
    public function getNextAvailableFirstGid(): int
    {
        $maxEnd = 0;
        foreach ($this->getAllTilesets() as $tileset) {
            $end = $tileset['firstGid'] + $tileset['tileCount'];
            if ($end > $maxEnd) {
                $maxEnd = $end;
            }
        }

        // Arrondir au prochain millier pour lisibilite
        return (int) (ceil($maxEnd / 1000) * 1000 + 1);
    }
}
