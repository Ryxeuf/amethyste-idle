<?php

namespace App\GameEngine\Map;

use Symfony\Component\Asset\Packages;

/**
 * Centralise la configuration des sprites pour le rendu de la carte.
 * Chaque clé correspond à un spriteKey utilisé côté frontend par SpriteAnimator.
 */
class SpriteConfigProvider
{
    public function __construct(
        private readonly Packages $packages,
    ) {}

    public function getFullConfig(): array
    {
        return array_merge(
            $this->getPlayerSprites(),
            $this->getMobSprites(),
            $this->getPnjSprites(),
        );
    }

    public function getPlayerSprites(): array
    {
        return [
            'player_default' => $this->single('character/Male/Male 01-2.png'),
            'player_male_01' => $this->single('character/Male/Male 01-2.png'),
            'player_male_02' => $this->single('character/Male/Male 02-1.png'),
            'player_male_03' => $this->single('character/Male/Male 03-2.png'),
            'player_female_01' => $this->single('character/Female/Female 01-1.png'),
            'player_female_02' => $this->single('character/Female/Female 02-1.png'),
            'player_soldier_01' => $this->single('character/Soldier/Soldier 01-1.png'),
        ];
    }

    public function getMobSprites(): array
    {
        return [
            // Multi-sheet : demons.png contient 8 personnages (index 0-7)
            'mob_zombie' => $this->multi('demons.png', 0),
            'mob_taiju' => $this->multi('demons.png', 1),
            'mob_ochu' => $this->multi('demons.png', 2),
            'mob_skeleton' => $this->multi('demons.png', 3),
            // Sprites single-sheet pour les nouveaux monstres
            'mob_goblin' => $this->single('monster/Enemy 01-1.png'),
            'mob_troll' => $this->single('monster/Enemy 03-1.png'),
            'mob_dragon' => $this->single('monster/Enemy 05-1.png'),
            'mob_werewolf' => $this->single('monster/Enemy 07-1.png'),
            'mob_banshee' => $this->single('monster/Enemy 09-1.png'),
            'mob_griffin' => $this->single('monster/Enemy 11-1.png'),
            'mob_minotaur' => $this->single('monster/Enemy 13-1.png'),
            'mob_gargoyle' => $this->single('monster/Enemy 02-1.png'),
        ];
    }

    public function getPnjSprites(): array
    {
        return [
            'pnj_default' => $this->single('character/Male/Male 03-1.png'),
            // PNJ par type de classe
            'pnj_villager' => $this->single('character/Male/Male 04-1.png'),
            'pnj_merchant' => $this->single('character/Male/Male 05-1.png'),
            'pnj_guard' => $this->single('character/Soldier/Soldier 01-1.png'),
            'pnj_noble' => $this->single('character/Male/Male 07-1.png'),
            'pnj_warrior' => $this->single('character/Soldier/Soldier 02-1.png'),
            'pnj_mage' => $this->single('character/Male/Male 09-1.png'),
            'pnj_healer' => $this->single('character/Female/Female 01-1.png'),
            'pnj_blacksmith' => $this->single('character/Male/Male 11-1.png'),
            'pnj_farmer' => $this->single('character/Male/Male 13-1.png'),
            'pnj_hunter' => $this->single('character/Male/Male 15-1.png'),
        ];
    }

    private function single(string $path): array
    {
        return [
            'sheet' => $this->packages->getUrl('styles/images/' . $path),
            'type' => 'single',
        ];
    }

    private function multi(string $path, int $charIndex): array
    {
        return [
            'sheet' => $this->packages->getUrl('styles/images/' . $path),
            'type' => 'multi',
            'charIndex' => $charIndex,
        ];
    }
}
