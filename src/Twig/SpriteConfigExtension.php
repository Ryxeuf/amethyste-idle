<?php

namespace App\Twig;

use App\GameEngine\Map\SpriteConfigProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SpriteConfigExtension extends AbstractExtension
{
    public function __construct(
        private readonly SpriteConfigProvider $spriteConfigProvider,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sprite_config', $this->getSpriteConfig(...)),
        ];
    }

    /**
     * Returns sprite config for a given spriteKey.
     *
     * @return array{sheet: string, type: string, charIndex?: int, category: string}|null
     */
    public function getSpriteConfig(string $spriteKey): ?array
    {
        $config = $this->spriteConfigProvider->getFullConfig();

        return $config[$spriteKey] ?? null;
    }
}
