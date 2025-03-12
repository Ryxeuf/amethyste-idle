<?php

namespace App\Dto\Cell;

use App\Dto\Mob\MobModelLight;
use App\Dto\Player\PlayerModelLight;
use App\Dto\Pnj\PnjModelLight;
use App\Entity\App\Map;
use App\Entity\App\Area;
use App\Dto\Area\AreaModel;

class CellModel extends CellModelLight
{
    public string            $slug;
    public array             $layers       = [];
    public array             $actions      = [];
    public ?ObjectLayerModel $object       = null;
    public ?ObjectLayerModel $usableObject = null;
    /** @var PlayerModelLight[] */
    public array          $players = [];
    public ?MobModelLight $mob     = null;
    public ?PnjModelLight $pnj     = null;  
    public ?AreaModel $area     = null;

    public function __construct(array $data, ?Map $map = null, ?Area $area = null)
    {
        parent::__construct($data, $map, $area);

        // Vérifier si les clés nécessaires existent
        $this->slug = $data['slug'] ?? 'unknown';
        $this->layers = $data['layers'] ?? [];
        $this->area = $area ? new AreaModel($area) : null;
    }
}
