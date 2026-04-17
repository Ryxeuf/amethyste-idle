<?php

namespace App\Twig;

use App\Service\FeatureFlagManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FeatureFlagExtension extends AbstractExtension
{
    public function __construct(
        private readonly FeatureFlagManager $manager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('feature_enabled', [$this->manager, 'isEnabled']),
        ];
    }
}
