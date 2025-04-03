<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use App\Entity\App\Fight;
use App\GameEngine\Fight\FightNotificationHandler;

#[AsLiveComponent]
class FightNotification
{
    use DefaultActionTrait;

    public Fight $fight;

    public array $notifications = [];

    public function __construct(private readonly FightNotificationHandler $fightNotificationHandler)
    {
    }

    public function mount(Fight $fight)
    {
        $this->fight = $fight;
    }

}
