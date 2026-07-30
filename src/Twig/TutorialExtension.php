<?php

namespace App\Twig;

use App\Enum\TutorialStep;
use App\GameEngine\Tutorial\TutorialManager;
use App\Helper\PlayerHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TutorialExtension extends AbstractExtension
{
    /** @var array{step: ?TutorialStep, resolved: bool} */
    private array $cache = ['step' => null, 'resolved' => false];

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly TutorialManager $tutorialManager,
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

        $this->cache['step'] = $this->tutorialManager->getCurrentStep($player);

        return $this->cache['step'];
    }
}
