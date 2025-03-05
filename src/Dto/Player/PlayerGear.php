<?php

namespace App\Dto\Player;

use App\Dto\Item\ItemModel;

class PlayerGear
{
    public ?ItemModel $head = null;
    public ?ItemModel $chest = null;
    public ?ItemModel $weapon = null;
    public ?ItemModel $foot = null;
}
