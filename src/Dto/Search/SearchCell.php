<?php

namespace App\Dto\Search;

class SearchCell
{
    public readonly string $id;

    public function __construct(
        public int $x,
        public int $y,
        public int $movementCost,
        public int $upCost,
        public int $downCost,
        public int $leftCost,
        public int $rightCost,
        public string $world,
        public array $map,
        public array $area,
        public string $slug,
        public string $displayClass,
        public array $layers,
        public array $debug = [],
    ) {
        $this->id = $this->area['slug'] . '#' . $this->x . '#' . $this->y;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'x' => $this->x,
            'y' => $this->y,
            'movementCost' => $this->movementCost,
            'upCost' => $this->upCost,
            'downCost' => $this->downCost,
            'leftCost' => $this->leftCost,
            'rightCost' => $this->rightCost,
            'world' => $this->world,
            'map' => $this->map,
            'area' => $this->area,
            'slug' => $this->slug,
            'displayClass' => $this->displayClass,
            'layers' => $this->layers,
            'debug' => $this->debug,
        ];
    }

    public static function fromArray(array $data): SearchCell
    {
        return new SearchCell(
            x: $data['x'],
            y: $data['y'],
            movementCost: $data['movementCost'],
            upCost: $data['upCost'],
            downCost: $data['downCost'],
            leftCost: $data['leftCost'],
            rightCost: $data['rightCost'],
            world: $data['world'],
            map: $data['map'],
            area: $data['area'],
            slug: $data['slug'],
            displayClass: $data['displayClass'],
            layers: $data['layers'],
            debug: $data['debug'] ?? [],
        );
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
