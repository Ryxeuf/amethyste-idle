<?php

namespace App\GameEngine\Map;

use Symfony\Component\Asset\Packages;

/**
 * Centralise la configuration des sprites pour le rendu de la carte.
 * Chaque clé correspond à un spriteKey utilisé côté frontend par SpriteAnimator.
 *
 * Métadonnées par sprite :
 *   - sheet: URL de la sprite sheet
 *   - type: 'single' (3×4) ou 'multi' (12×8)
 *   - charIndex: index du personnage dans une multi-sheet (0-7)
 *   - category: classification (player, mob, pnj)
 *   - animations: liste des animations supportées (walk, idle, breathe)
 *   - frameSize: taille d'un frame en pixels [w, h] (32×48 standard RPG Maker VX)
 */
class SpriteConfigProvider
{
    /** Standard RPG Maker VX frame dimensions */
    private const FRAME_W = 32;
    private const FRAME_H = 48;

    public function __construct(
        private readonly Packages $packages,
    ) {
    }

    public function getFullConfig(): array
    {
        return array_merge(
            $this->getPlayerSprites(),
            $this->getMobSprites(),
            $this->getPnjSprites(),
        );
    }

    /**
     * Returns sprite registry with metadata for the asset management system.
     *
     * @return array<string, array{sheet: string, type: string, category: string, animations: string[], frameSize: int[]}>
     */
    public function getRegistry(): array
    {
        $config = $this->getFullConfig();
        $registry = [];

        foreach ($config as $key => $sprite) {
            $registry[$key] = array_merge($sprite, [
                'key' => $key,
                'frameSize' => [self::FRAME_W, self::FRAME_H],
            ]);
        }

        return $registry;
    }

    /**
     * Get sprite keys by category.
     */
    public function getKeysByCategory(string $category): array
    {
        return array_keys(array_filter(
            $this->getFullConfig(),
            fn (array $sprite) => ($sprite['category'] ?? '') === $category
        ));
    }

    public function getPlayerSprites(): array
    {
        return [
            'player_default' => $this->single('character/Male/Male 01-2.png', 'player'),
            'player_male_01' => $this->single('character/Male/Male 01-2.png', 'player'),
            'player_male_02' => $this->single('character/Male/Male 02-1.png', 'player'),
            'player_male_03' => $this->single('character/Male/Male 03-2.png', 'player'),
            'player_female_01' => $this->single('character/Female/Female 01-1.png', 'player'),
            'player_female_02' => $this->single('character/Female/Female 02-1.png', 'player'),
            'player_soldier_01' => $this->single('character/Soldier/Soldier 01-1.png', 'player'),
        ];
    }

    public function getMobSprites(): array
    {
        return [
            // Multi-sheet : demons.png contient 8 personnages (index 0-7)
            'mob_zombie' => $this->multi('demons.png', 0, 'mob'),
            'mob_taiju' => $this->multi('demons.png', 1, 'mob'),
            'mob_ochu' => $this->multi('demons.png', 2, 'mob'),
            'mob_skeleton' => $this->multi('demons.png', 3, 'mob'),
            // Sprites single-sheet pour les nouveaux monstres
            'mob_goblin' => $this->single('monster/Enemy 01-1.png', 'mob'),
            'mob_troll' => $this->single('monster/Enemy 03-1.png', 'mob'),
            'mob_dragon' => $this->single('monster/Enemy 05-1.png', 'mob'),
            'mob_werewolf' => $this->single('monster/Enemy 07-1.png', 'mob'),
            'mob_banshee' => $this->single('monster/Enemy 09-1.png', 'mob'),
            'mob_griffin' => $this->single('monster/Enemy 11-1.png', 'mob'),
            'mob_minotaur' => $this->single('monster/Enemy 13-1.png', 'mob'),
            'mob_gargoyle' => $this->single('monster/Enemy 02-1.png', 'mob'),
        ];
    }

    public function getPnjSprites(): array
    {
        return [
            'pnj_default' => $this->single('character/Male/Male 03-1.png', 'pnj'),
            // PNJ par type de classe
            'pnj_villager' => $this->single('character/Male/Male 04-1.png', 'pnj'),
            'pnj_merchant' => $this->single('character/Male/Male 05-1.png', 'pnj'),
            'pnj_guard' => $this->single('character/Soldier/Soldier 01-1.png', 'pnj'),
            'pnj_noble' => $this->single('character/Male/Male 07-1.png', 'pnj'),
            'pnj_warrior' => $this->single('character/Soldier/Soldier 02-1.png', 'pnj'),
            'pnj_mage' => $this->single('character/Male/Male 09-1.png', 'pnj'),
            'pnj_healer' => $this->single('character/Female/Female 01-1.png', 'pnj'),
            'pnj_blacksmith' => $this->single('character/Male/Male 11-1.png', 'pnj'),
            'pnj_farmer' => $this->single('character/Male/Male 13-1.png', 'pnj'),
            'pnj_hunter' => $this->single('character/Male/Male 15-1.png', 'pnj'),
        ];
    }

    private function single(string $path, string $category = 'player'): array
    {
        return [
            'sheet' => $this->packages->getUrl('styles/images/' . $path),
            'type' => 'single',
            'category' => $category,
            'animations' => ['walk', 'idle', 'breathe'],
        ];
    }

    private function multi(string $path, int $charIndex, string $category = 'mob'): array
    {
        return [
            'sheet' => $this->packages->getUrl('styles/images/' . $path),
            'type' => 'multi',
            'charIndex' => $charIndex,
            'category' => $category,
            'animations' => ['walk', 'idle', 'breathe'],
        ];
    }
}
