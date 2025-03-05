<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Counter
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
} 