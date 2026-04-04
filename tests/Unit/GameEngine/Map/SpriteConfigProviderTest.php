<?php

namespace App\Tests\Unit\GameEngine\Map;

use App\GameEngine\Map\SpriteConfigProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

class SpriteConfigProviderTest extends TestCase
{
    private SpriteConfigProvider $provider;

    protected function setUp(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')->willReturnCallback(
            fn (string $path) => '/assets/' . $path
        );

        $this->provider = new SpriteConfigProvider($packages);
    }

    public function testAllMonsterFixturesHaveSpriteConfig(): void
    {
        $monsterSlugs = [
            'zombie', 'skeleton', 'ochu', 'taiju',
            'goblin', 'troll', 'dragon', 'werewolf',
            'banshee', 'griffin', 'minotaur', 'gargoyle',
            'slime', 'giant_rat', 'bat', 'spider',
            'venom_snake', 'specter', 'fire_elemental', 'stone_golem',
            'wolf', 'scorpion', 'beetle', 'mushroom_golem', 'ghost',
        ];

        $mobSprites = $this->provider->getMobSprites();

        foreach ($monsterSlugs as $slug) {
            $spriteKey = 'mob_' . $slug;
            $this->assertArrayHasKey(
                $spriteKey,
                $mobSprites,
                sprintf('Le monstre "%s" (spriteKey: %s) n\'a pas de sprite configuré', $slug, $spriteKey)
            );
        }
    }

    public function testMobSpritesHaveRequiredFields(): void
    {
        $mobSprites = $this->provider->getMobSprites();

        foreach ($mobSprites as $key => $sprite) {
            $this->assertArrayHasKey('sheet', $sprite, "$key manque le champ 'sheet'");
            $this->assertArrayHasKey('type', $sprite, "$key manque le champ 'type'");
            $this->assertArrayHasKey('category', $sprite, "$key manque le champ 'category'");
            $this->assertArrayHasKey('animations', $sprite, "$key manque le champ 'animations'");
            $this->assertSame('mob', $sprite['category'], "$key devrait avoir la catégorie 'mob'");
            $this->assertContains($sprite['type'], ['single', 'multi'], "$key a un type invalide");
        }
    }

    public function testMultiSheetSpritesHaveCharIndex(): void
    {
        $mobSprites = $this->provider->getMobSprites();

        foreach ($mobSprites as $key => $sprite) {
            if ($sprite['type'] === 'multi') {
                $this->assertArrayHasKey('charIndex', $sprite, "$key (multi) manque le champ 'charIndex'");
            }
        }
    }

    public function testGetKeysByCategoryReturnsMobs(): void
    {
        $keys = $this->provider->getKeysByCategory('mob');

        $this->assertNotEmpty($keys);
        foreach ($keys as $key) {
            $this->assertStringStartsWith('mob_', $key);
        }
    }

    public function testGetFullConfigContainsAllCategories(): void
    {
        $config = $this->provider->getFullConfig();

        $categories = [];
        foreach ($config as $sprite) {
            $categories[$sprite['category']] = true;
        }

        $this->assertArrayHasKey('player', $categories);
        $this->assertArrayHasKey('mob', $categories);
        $this->assertArrayHasKey('pnj', $categories);
    }
}
