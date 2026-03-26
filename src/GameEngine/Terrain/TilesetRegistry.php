<?php

namespace App\GameEngine\Terrain;

use Symfony\Component\Asset\Packages;

/**
 * Registre centralise des tilesets du projet.
 * Remplace la lecture des fichiers .tsx de Tiled et les constantes TILESET_COLUMNS dupliquees.
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

    // --- GID cles : collision ---
    public const GID_COLLISION_WALL = 5162;  // 5161 + 1 = mur impassable

    /**
     * Definitions statiques des 4 tilesets, triees par firstGid.
     *
     * @var array<int, array{name: string, firstGid: int, columns: int, tileCount: int, tileWidth: int, tileHeight: int, imageFile: string}>
     */
    private const TILESETS = [
        self::FIRST_GID_TERRAIN => [
            'name' => 'terrain',
            'firstGid' => self::FIRST_GID_TERRAIN,
            'columns' => 32,
            'tileCount' => 1024,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'terrain.png',
        ],
        self::FIRST_GID_FOREST => [
            'name' => 'forest',
            'firstGid' => self::FIRST_GID_FOREST,
            'columns' => 16,
            'tileCount' => 3072,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'forest.png',
        ],
        self::FIRST_GID_BASECHIP_PIPO => [
            'name' => 'BaseChip_pipo',
            'firstGid' => self::FIRST_GID_BASECHIP_PIPO,
            'columns' => 8,
            'tileCount' => 1064,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'BaseChip_pipo.png',
        ],
        self::FIRST_GID_COLLISIONS => [
            'name' => 'collisions',
            'firstGid' => self::FIRST_GID_COLLISIONS,
            'columns' => 6,
            'tileCount' => 18,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'imageFile' => 'collisions.png',
        ],
    ];

    /**
     * Tableau inverse : firstGid tries par ordre decroissant pour la resolution rapide.
     *
     * @var list<int>
     */
    private array $sortedFirstGids;

    public function __construct(
        private readonly Packages $packages,
    ) {
        $gids = array_keys(self::TILESETS);
        rsort($gids);
        $this->sortedFirstGids = $gids;
    }

    /**
     * Retourne tous les tilesets avec leur URL publique d'image.
     *
     * @return list<array{name: string, firstGid: int, columns: int, tileCount: int, tileWidth: int, tileHeight: int, imageFile: string, imagePath: string}>
     */
    public function getTilesets(): array
    {
        $result = [];
        foreach (self::TILESETS as $tileset) {
            $result[] = [
                ...$tileset,
                'imagePath' => $this->packages->getUrl('styles/images/terrain/' . $tileset['imageFile']),
            ];
        }

        return $result;
    }

    /**
     * Resout le tileset auquel appartient un GID global.
     *
     * @return array{name: string, firstGid: int, columns: int, tileCount: int, tileWidth: int, tileHeight: int, imageFile: string}|null
     */
    public function getTilesetForGid(int $gid): ?array
    {
        foreach ($this->sortedFirstGids as $firstGid) {
            if ($gid >= $firstGid) {
                $tileset = self::TILESETS[$firstGid];
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
        foreach ($this->sortedFirstGids as $firstGid) {
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
        foreach (self::TILESETS as $tileset) {
            if (strcasecmp($tileset['name'], $name) === 0) {
                return $tileset['columns'];
            }
        }

        return 32;
    }

    /**
     * Retourne les tilesets formates pour l'API frontend (compatibilite existante).
     *
     * @return list<array{name: string, image: string, columns: int, tileWidth: int, tileHeight: int, firstGid: int}>
     */
    public function getTilesetsForApi(): array
    {
        $result = [];
        foreach (self::TILESETS as $tileset) {
            $result[] = [
                'name' => $tileset['name'],
                'image' => $this->packages->getUrl('styles/images/terrain/' . $tileset['imageFile']),
                'columns' => $tileset['columns'],
                'tileWidth' => $tileset['tileWidth'],
                'tileHeight' => $tileset['tileHeight'],
                'firstGid' => $tileset['firstGid'],
            ];
        }

        return $result;
    }
}
