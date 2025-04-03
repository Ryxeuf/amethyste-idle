<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use App\Entity\App\Fight;
use App\Helper\FightTimelineHelper;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class FightTimeline
{
    use DefaultActionTrait;

    public Fight $fight;

    public array $timeline = [];

    public function __construct(private readonly FightTimelineHelper $timeLineHelper)
    {
    }

    public function mount(Fight $fight)
    {
        $this->fight = $fight;
    }

}
