<?php

declare(strict_types=1);

namespace App\Service\Mount;

use App\Entity\App\Player;

class MountMapPayloadBuilder
{
    /**
     * @return array{slug: string, name: string, iconPath: string|null, spriteSheet: string|null, speedBonus: int}|null
     */
    public function build(Player $player): ?array
    {
        $mount = $player->getActiveMount();
        if ($mount === null) {
            return null;
        }

        return [
            'slug' => $mount->getSlug(),
            'name' => $mount->getName(),
            'iconPath' => $mount->getIconPath(),
            'spriteSheet' => $mount->getSpriteSheet(),
            'speedBonus' => $mount->getSpeedBonus(),
        ];
    }
}
