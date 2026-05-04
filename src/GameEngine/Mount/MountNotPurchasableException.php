<?php

namespace App\GameEngine\Mount;

use App\Entity\Game\Mount;

class MountNotPurchasableException extends \RuntimeException
{
    public function __construct(public readonly Mount $mount)
    {
        parent::__construct(sprintf('La monture "%s" n\'est pas achetable (aucun cout en gils).', $mount->getSlug()));
    }
}
