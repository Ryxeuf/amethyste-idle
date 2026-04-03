<?php

namespace App\Twig\Components;

use App\Entity\App\Fight;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class FightTimeline
{
    use DefaultActionTrait;

    public Fight $fight;

    public array $timeline = [];

    public function mount(Fight $fight)
    {
        $this->fight = $fight;
    }
}
