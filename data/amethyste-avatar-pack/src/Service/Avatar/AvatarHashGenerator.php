<?php

declare(strict_types=1);

namespace App\Service\Avatar;

final class AvatarHashGenerator
{
    public function generate(array $appearance, array $visibleLayers, string $formatVersion = 'avatar-v1'): string
    {
        ksort($appearance);
        ksort($visibleLayers);

        return hash('sha256', json_encode([
            'format' => $formatVersion,
            'appearance' => $appearance,
            'layers' => $visibleLayers,
        ], JSON_THROW_ON_ERROR));
    }
}
