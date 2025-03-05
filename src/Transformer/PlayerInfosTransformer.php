<?php

namespace App\Transformer;

use App\Dto\Player\PlayerModel;
use App\Entity\App\Player;

class PlayerInfosTransformer
{
    public function transform(Player $player, bool $self = false): PlayerModel
    {
        $model = new PlayerModel($player);
        $fight = $player->getFight();
        $model->inFight = $fight !== null;
        $model->fightId = $fight?->getId();
        $model->dead = $player->getLife() === 0;
        $model->self = $self;

        return $model;
    }
}