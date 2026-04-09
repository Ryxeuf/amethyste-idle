<?php

namespace App\Twig;

use App\Enum\TutorialStep;
use App\Helper\PlayerHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TutorialExtension extends AbstractExtension
{
    /** @var array{step: ?TutorialStep, resolved: bool} */
    private array $cache = ['step' => null, 'resolved' => false];

    public function __construct(
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tutorial_current_step', $this->getCurrentStep(...)),
        ];
    }

    public function getCurrentStep(): ?TutorialStep
    {
        if ($this->cache['resolved']) {
            return $this->cache['step'];
        }

        $this->cache['resolved'] = true;

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return null;
        }

        $stepValue = $player->getTutorialStep();
        $this->cache['step'] = null !== $stepValue ? TutorialStep::tryFrom($stepValue) : null;

        return $this->cache['step'];
    }
}
