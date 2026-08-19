<?php

namespace App\Twig;

use App\Enum\TutorialStep;
use App\GameEngine\Tutorial\TutorialDestination;
use App\GameEngine\Tutorial\TutorialGuide;
use App\GameEngine\Tutorial\TutorialManager;
use App\Helper\PlayerHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TutorialExtension extends AbstractExtension
{
    /** @var array{step: ?TutorialStep, resolved: bool} */
    private array $cache = ['step' => null, 'resolved' => false];

    /** @var array{destination: ?TutorialDestination, resolved: bool} */
    private array $destinationCache = ['destination' => null, 'resolved' => false];

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly TutorialManager $tutorialManager,
        private readonly TutorialGuide $tutorialGuide,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tutorial_current_step', $this->getCurrentStep(...)),
            new TwigFunction('tutorial_destination', $this->getDestination(...)),
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

    /**
     * Ou envoyer le joueur, maintenant.
     *
     * Meme cache que l'etape, et pour la meme raison : le bandeau est rendu une
     * fois par page mais le gabarit l'interroge plusieurs fois.
     */
    public function getDestination(): ?TutorialDestination
    {
        if ($this->destinationCache['resolved']) {
            return $this->destinationCache['destination'];
        }

        $this->destinationCache['resolved'] = true;

        $player = $this->playerHelper->getPlayer();
        if (null === $player || null === $this->getCurrentStep()) {
            return null;
        }

        $this->destinationCache['destination'] = $this->tutorialGuide->destinationFor(
            $player,
            $this->requestStack->getCurrentRequest()?->getLocale(),
        );

        return $this->destinationCache['destination'];
    }
}
