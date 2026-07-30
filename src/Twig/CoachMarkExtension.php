<?php

namespace App\Twig;

use App\Enum\CoachMark;
use App\GameEngine\Onboarding\CoachMarkResolver;
use App\Helper\PlayerHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `coach_mark('zone')` — l'encart de cet ecran, ou `null` (ONB-17).
 *
 * Passer par une fonction Twig plutot que par le tableau de rendu de chaque
 * controleur tient **C3** : l'encart se decide au rendu, la ou l'ecran est, et
 * pas au moment ou un controleur assemble ses variables. C'est aussi ce qui
 * rend les sept ecrans restants (ONB-17b) branchables en une ligne chacun,
 * sans toucher a leur controleur — l'ecran de zone en a deux, et il aurait
 * fallu penser aux deux.
 */
final class CoachMarkExtension extends AbstractExtension
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly CoachMarkResolver $resolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('coach_mark', $this->forScreen(...)),
        ];
    }

    /**
     * @param bool $condition ce que seul l'ecran sait — voir `CoachMark::needsCallerCondition()`
     */
    public function forScreen(string $slug, bool $condition = true): ?CoachMark
    {
        $mark = CoachMark::tryFrom($slug);
        if (null === $mark) {
            return null;
        }

        return $this->resolver->forScreen($this->playerHelper->getPlayer(), $mark, $condition);
    }
}
