<?php

namespace App\Twig\Components;

use App\Entity\App\Fight;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class FightNotification
{
    use DefaultActionTrait;

    public Fight $fight;

    public array $notifications = [];

    public function mount(Fight $fight)
    {
        $this->fight = $fight;
    }
}
