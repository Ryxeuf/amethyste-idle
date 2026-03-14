<?php

namespace App\Tests\Unit\GameEngine\Item;

use App\GameEngine\Item\ItemEffectEncoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ItemEffectEncoderTest extends TestCase
{
    private ItemEffectEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new ItemEffectEncoder();
    }

    public function testEncodeUseSpellEffect(): void
    {
        $effect = [
            'action' => ItemEffectEncoder::ACTION_USE_SPELL,
            'id' => 42,
        ];

        $json = $this->encoder->encodeItemEffect($effect);
        $decoded = json_decode($json, true);

        $this->assertSame('use_spell', $decoded['action']);
        $this->assertSame(42, $decoded['id']);
    }

    public function testEncodeLearnSkillEffect(): void
    {
        $effect = [
            'action' => ItemEffectEncoder::ACTION_LEARN_SKILL,
            'id' => 7,
        ];

        $json = $this->encoder->encodeItemEffect($effect);
        $decoded = json_decode($json, true);

        $this->assertSame('learn_skill', $decoded['action']);
        $this->assertSame(7, $decoded['id']);
    }

    public function testEncodeBuildItemEffect(): void
    {
        $effect = [
            'action' => ItemEffectEncoder::ACTION_BUILD_ITEM,
            'id' => 1,
            'components' => [10, 20, 30],
        ];

        $json = $this->encoder->encodeItemEffect($effect);
        $decoded = json_decode($json, true);

        $this->assertSame('build_item', $decoded['action']);
        $this->assertSame([10, 20, 30], $decoded['components']);
    }

    public function testEncodeMissingActionThrows(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->encoder->encodeItemEffect(['id' => 1]);
    }

    public function testEncodeMissingIdThrows(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->encoder->encodeItemEffect(['action' => 'use_spell']);
    }

    public function testEncodeInvalidActionThrows(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->encoder->encodeItemEffect([
            'action' => 'invalid_action',
            'id' => 1,
        ]);
    }

    public function testConstants(): void
    {
        $this->assertSame('use_spell', ItemEffectEncoder::ACTION_USE_SPELL);
        $this->assertSame('learn_skill', ItemEffectEncoder::ACTION_LEARN_SKILL);
        $this->assertSame('build_item', ItemEffectEncoder::ACTION_BUILD_ITEM);
    }
}
