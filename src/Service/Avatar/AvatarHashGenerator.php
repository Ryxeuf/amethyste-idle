<?php

declare(strict_types=1);

namespace App\Service\Avatar;

final class AvatarHashGenerator
{
    /**
     * Generate a deterministic hash for an avatar composition.
     *
     * The hash uniquely identifies a visual appearance so the frontend
     * can cache the composed spritesheet (AvatarSpriteSheetCache).
     *
     * @param array<string, mixed> $appearance    Player appearance attributes (body, hair, hairColor, etc.)
     * @param array<int, string>   $visibleLayers Ordered list of layer sheet paths
     * @param string               $formatVersion Format version for cache invalidation
     */
    public function generate(array $appearance, array $visibleLayers, string $formatVersion = 'avatar-v1'): string
    {
        ksort($appearance);
        sort($visibleLayers);

        return hash('sha256', json_encode([
            'format' => $formatVersion,
            'appearance' => $appearance,
            'layers' => $visibleLayers,
        ], \JSON_THROW_ON_ERROR));
    }
}
