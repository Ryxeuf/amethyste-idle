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

    public function testEncodeUseSpellEffectWithId(): void
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

    public function testEncodeUseSpellEffectWithSlug(): void
    {
        $effect = [
            'action' => ItemEffectEncoder::ACTION_USE_SPELL,
            'slug' => 'fire-ball',
        ];

        $json = $this->encoder->encodeItemEffect($effect);
        $decoded = json_decode($json, true);

        $this->assertSame('use_spell', $decoded['action']);
        $this->assertSame('fire-ball', $decoded['slug']);
        $this->assertArrayNotHasKey('id', $decoded);
    }

    public function testEncodeUseSpellEffectWithIdAndSlug(): void
    {
        $effect = [
            'action' => ItemEffectEncoder::ACTION_USE_SPELL,
            'id' => 42,
            'slug' => 'fire-ball',
        ];

        $json = $this->encoder->encodeItemEffect($effect);
        $decoded = json_decode($json, true);

        $this->assertSame('use_spell', $decoded['action']);
        $this->assertSame(42, $decoded['id']);
        $this->assertSame('fire-ball', $decoded['slug']);
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

    public function testEncodeLearnSkillEffectWithSlug(): void
    {
        $effect = [
            'action' => ItemEffectEncoder::ACTION_LEARN_SKILL,
            'slug' => 'healer-materia-1',
        ];

        $json = $this->encoder->encodeItemEffect($effect);
        $decoded = json_decode($json, true);

        $this->assertSame('learn_skill', $decoded['action']);
        $this->assertSame('healer-materia-1', $decoded['slug']);
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
        $this->assertSame('slug', ItemEffectEncoder::KEY_SLUG);
    }

    public function testNullValuesAreStrippedFromJson(): void
    {
        $effect = [
            'action' => ItemEffectEncoder::ACTION_USE_SPELL,
            'slug' => 'fire-ball',
        ];

        $json = $this->encoder->encodeItemEffect($effect);
        $decoded = json_decode($json, true);

        $this->assertArrayNotHasKey('id', $decoded);
        $this->assertArrayNotHasKey('components', $decoded);
    }
}
