<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Avatar;

use App\Service\Avatar\AvatarHashGenerator;
use PHPUnit\Framework\TestCase;

class AvatarHashGeneratorTest extends TestCase
{
    private AvatarHashGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new AvatarHashGenerator();
    }

    public function testGenerateReturnsSha256Hash(): void
    {
        $hash = $this->generator->generate(
            ['body' => 'human_m_light'],
            ['/avatar/hair/short_01.png'],
        );

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function testGenerateIsDeterministic(): void
    {
        $appearance = ['body' => 'human_m_light', 'hair' => 'short_01', 'hairColor' => '#d6b25e'];
        $layers = ['/avatar/hair/short_01.png', '/avatar/gear/chest/iron_armor.png'];

        $hash1 = $this->generator->generate($appearance, $layers);
        $hash2 = $this->generator->generate($appearance, $layers);

        $this->assertSame($hash1, $hash2);
    }

    public function testHashChangesWhenAppearanceChanges(): void
    {
        $layers = ['/avatar/hair/short_01.png'];

        $hash1 = $this->generator->generate(['body' => 'human_m_light'], $layers);
        $hash2 = $this->generator->generate(['body' => 'human_f_dark'], $layers);

        $this->assertNotSame($hash1, $hash2);
    }

    public function testHashChangesWhenLayersChange(): void
    {
        $appearance = ['body' => 'human_m_light'];

        $hash1 = $this->generator->generate($appearance, ['/avatar/hair/short_01.png']);
        $hash2 = $this->generator->generate($appearance, ['/avatar/hair/long_02.png']);

        $this->assertNotSame($hash1, $hash2);
    }

    public function testHashIsInsensitiveToAppearanceKeyOrder(): void
    {
        $layers = ['/avatar/hair/short_01.png'];

        $hash1 = $this->generator->generate(['body' => 'human_m_light', 'hair' => 'short_01'], $layers);
        $hash2 = $this->generator->generate(['hair' => 'short_01', 'body' => 'human_m_light'], $layers);

        $this->assertSame($hash1, $hash2);
    }

    public function testHashIsInsensitiveToLayerOrder(): void
    {
        $appearance = ['body' => 'human_m_light'];

        $hash1 = $this->generator->generate($appearance, ['/avatar/hair/short_01.png', '/avatar/gear/chest/armor.png']);
        $hash2 = $this->generator->generate($appearance, ['/avatar/gear/chest/armor.png', '/avatar/hair/short_01.png']);

        $this->assertSame($hash1, $hash2);
    }

    public function testHashChangesWhenFormatVersionChanges(): void
    {
        $appearance = ['body' => 'human_m_light'];
        $layers = ['/avatar/hair/short_01.png'];

        $hash1 = $this->generator->generate($appearance, $layers, 'avatar-v1');
        $hash2 = $this->generator->generate($appearance, $layers, 'avatar-v2');

        $this->assertNotSame($hash1, $hash2);
    }

    public function testHashWithEmptyAppearanceAndLayers(): void
    {
        $hash = $this->generator->generate([], []);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }
}
