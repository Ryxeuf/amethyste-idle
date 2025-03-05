<?php

namespace App\Dto\Cell;

use App\Dto\Mob\MobModelLight;
use App\Dto\Player\PlayerModelLight;
use App\Dto\Pnj\PnjModelLight;
use App\Entity\App\Map;

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

    public function __construct(array $data, ?Map $map = null)
    {
        parent::__construct($data, $map);

        $this->layers = $data['layers'];
        $this->slug   = $data['slug'];
    }
}
