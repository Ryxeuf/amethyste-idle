<?php

namespace App\GameEngine\Map;

use App\DataStorage\MapStorage;
use App\Entity\App\ObjectLayer;
use App\Exception\PathCoordinatesNotFoundException;
use App\Exception\PathNotFoundException;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;

class MovementCalculator
{
    private int $mapId;
    private array $map;

    public function __construct(
        private readonly MapStorage $mapStorage,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function loadMap(int $mapId): void
    {
        $tag = $this->mapStorage->getMapTag($mapId);
        if (!\is_array($tag)) {
            throw new \InvalidArgumentException(\sprintf(
                'Données de navigation (fichier tag) introuvables pour la carte %d (data/map/tag_%d_*).',
                $mapId,
                $mapId
            ));
        }

        $this->mapId = $mapId;
        $this->map = $tag;
    }

    /**
     * @throws PathCoordinatesNotFoundException
     * @throws PathNotFoundException
     * @throws \Exception
     */
    public function calculateMovement(int $x, int $y, int $targetX, int $targetY, int $abilityMask = 0b1): array
    {
        if (!isset($this->mapId)) {
            throw new \Exception('loadMap must be called before calculating movement');
        }

        if (!isset($this->map[CellHelper::stringifyCoordinates($x, $y)]) || !isset($this->map[CellHelper::stringifyCoordinates($targetX, $targetY)])) {
            throw new PathCoordinatesNotFoundException();
        }

        // Retrieve all map objects to update map paths
        $mapObjectLayers = $this->entityManager->getRepository(ObjectLayer::class)->findBy(['map' => $this->mapId]);
        /** @var bool[] $objectLayers */
        $objectLayers = [];
        foreach ($mapObjectLayers as $objectLayer) {
            $objectLayers[$objectLayer->getCoordinates()] = $objectLayer->getMovement();
        }

        foreach ($this->map as $coords => $targets) {
            // If the cell is a source cell, but becomes unreachable due to the object, we remove it from tree
            if (isset($objectLayers[$coords]) && $objectLayers[$coords] === CellHelper::MOVE_UNREACHABLE) {
                unset($this->map[$coords]);
            } else {
                foreach ($targets as $targetCoords => $target) {
                    if (isset($objectLayers[$targetCoords])) {
                        // If the cell is a target cell, but becomes unreachable due to the object, we remove it from tree
                        if ($objectLayers[$targetCoords] === CellHelper::MOVE_UNREACHABLE) {
                            unset($this->map[$coords]);
                        } else {
                            // Update target movement considering the object movement property
                            $this->map[$coords] = $objectLayers[$targetCoords] === 0 ? 1 : $objectLayers[$targetCoords];
                        }
                    }
                }
            }
        }

        $dijkstra = new Dijkstra($this->map, CellHelper::stringifyCoordinates($x, $y), $abilityMask);
        $nodes = $dijkstra->shortestPathTo(CellHelper::stringifyCoordinates($targetX, $targetY));

        $mouvementCells = [];

        if (count($nodes) === 0) {
            throw new PathNotFoundException();
        }
        foreach ($nodes as $node) {
            [$x, $y] = explode('.', $node['node_identifier']);
            $mouvementCells[] = [
                'map' => $this->mapId,
                'x' => $x,
                'y' => $y,
                'cost' => $node['weight'],
                'totalScore' => $node['accumulated_weight'],
            ];
        }

        return $mouvementCells;
    }

    public function setMap(array $map): void
    {
        $this->map = $map;
    }
}
