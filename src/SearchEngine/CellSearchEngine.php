<?php

namespace App\SearchEngine;

use Typesense\Collection;
use App\Dto\Search\SearchCell;

class CellSearchEngine extends SearchEngine
{
    public function getCollection() : Collection
    {
        if (!$this->client->collections()[self::CELL_INDEX_NAME]->exists()) {
            $this->client->collections()->create(self::CELL_INDEX_CONFIGURATION);
        }

        return $this->client->collections()[self::CELL_INDEX_NAME];
    }

    public function getObject(string $id): array
    {
        return $this->getCollection()->documents[$id]->retrieve();
    }

    public function find(array $query): array
    {
        return $this->getCollection()->documents->search($query);
    }

    /**
     * @return SearchCell[]
     */
    public function getMapCells(int $x, int $y, int $mapId): array
    {
        $founds = $this->getCollection()->documents->search([
            'q' => '*',
            'filter_by' => 'x:['.($x-10).'..'.($x+10).'] && y:['.($y-10).'..'.($y+10).'] && map.id:'.$mapId,
            'per_page' => 250,
            'page' => 1,
        ]);
        $found = $founds['found'];
        $cells = [];
        for($i=0; $i<=floor($found/250); $i++) {
            $result = $this->getCollection()->documents->search([
                'q' => '*',
                'filter_by' => 'x:['.($x-10).'..'.($x+10).'] && y:['.($y-10).'..'.($y+10).'] && map.id:'.$mapId,
                'per_page' => 250,
                'page' => $i+1,
            ]);
            foreach($result['hits'] as $hit) {
                $cells[] = SearchCell::fromArray($hit['document']);
            }
        }

        return $cells;
    }
}
