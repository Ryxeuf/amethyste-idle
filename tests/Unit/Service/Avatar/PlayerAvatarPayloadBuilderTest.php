<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Avatar;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Helper\GearHelper;
use App\Service\Avatar\AvatarHashGenerator;
use App\Service\Avatar\PlayerAvatarPayloadBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayerAvatarPayloadBuilderTest extends TestCase
{
    private PlayerAvatarPayloadBuilder $builder;
    private GearHelper&MockObject $gearHelper;

    protected function setUp(): void
    {
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->builder = new PlayerAvatarPayloadBuilder(
            new AvatarHashGenerator(),
            $this->gearHelper,
        );
    }

    public function testBuildReturnsNullWhenPlayerHasNoAvatar(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasAvatar')->willReturn(false);

        $this->assertNull($this->builder->build($player));
    }

    public function testBuildReturnsAvatarPayloadWithAppearance(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasAvatar')->willReturn(true);
        $player->method('getAvatarAppearance')->willReturn([
            'body' => 'human_f_dark',
            'hair' => 'long_02',
            'hairColor' => '#c0392b',
        ]);

        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);

        $result = $this->builder->build($player);

        $this->assertNotNull($result);
        $this->assertSame('avatar', $result['renderMode']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['avatarHash']);
        $this->assertSame('/assets/styles/images/avatar/body/human_f_dark.png', $result['avatar']['baseSheet']);

        $hairLayer = $result['avatar']['layers'][0];
        $this->assertSame('/assets/styles/images/avatar/hair/long_02.png', $hairLayer['sheet']);
        $this->assertSame(0xc0392b, $hairLayer['tint']);
    }

    public function testBuildIncludesGearLayers(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasAvatar')->willReturn(true);
        $player->method('getAvatarAppearance')->willReturn([
            'body' => 'human_m_light',
        ]);

        $chestItem = $this->createMock(Item::class);
        $chestItem->method('getAvatarSheet')->willReturn('/assets/styles/images/avatar/gear/chest/iron_armor.png');

        $chestPlayerItem = $this->createMock(PlayerItem::class);
        $chestPlayerItem->method('getGenericItem')->willReturn($chestItem);

        $this->gearHelper->method('getEquippedGearByLocation')
            ->willReturnCallback(function (string $location) use ($chestPlayerItem) {
                return $location === 'chest' ? $chestPlayerItem : null;
            });

        $result = $this->builder->build($player);

        $this->assertNotNull($result);
        $this->assertCount(1, $result['avatar']['layers']);
        $this->assertSame('/assets/styles/images/avatar/gear/chest/iron_armor.png', $result['avatar']['layers'][0]['sheet']);
    }

    public function testGearWithoutAvatarSheetIsSkipped(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasAvatar')->willReturn(true);
        $player->method('getAvatarAppearance')->willReturn([
            'body' => 'human_m_light',
        ]);

        $item = $this->createMock(Item::class);
        $item->method('getAvatarSheet')->willReturn(null);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($item);

        $this->gearHelper->method('getEquippedGearByLocation')
            ->willReturnCallback(function (string $location) use ($playerItem) {
                return $location === 'chest' ? $playerItem : null;
            });

        $result = $this->builder->build($player);

        $this->assertNotNull($result);
        $this->assertCount(0, $result['avatar']['layers']);
    }

    public function testLayerOrderingGearBeforeHairHeadAfter(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasAvatar')->willReturn(true);
        $player->method('getAvatarAppearance')->willReturn([
            'body' => 'human_m_light',
            'hair' => 'short_01',
            'hairColor' => '#d6b25e',
        ]);

        $chestItem = $this->createMock(Item::class);
        $chestItem->method('getAvatarSheet')->willReturn('/gear/chest.png');
        $chestPlayerItem = $this->createMock(PlayerItem::class);
        $chestPlayerItem->method('getGenericItem')->willReturn($chestItem);

        $headItem = $this->createMock(Item::class);
        $headItem->method('getAvatarSheet')->willReturn('/gear/head.png');
        $headPlayerItem = $this->createMock(PlayerItem::class);
        $headPlayerItem->method('getGenericItem')->willReturn($headItem);

        $this->gearHelper->method('getEquippedGearByLocation')
            ->willReturnCallback(function (string $location) use ($chestPlayerItem, $headPlayerItem) {
                return match ($location) {
                    'chest' => $chestPlayerItem,
                    'head' => $headPlayerItem,
                    default => null,
                };
            });

        $result = $this->builder->build($player);
        $layers = $result['avatar']['layers'];

        $this->assertCount(3, $layers);
        $this->assertSame('/gear/chest.png', $layers[0]['sheet']);
        $this->assertSame('/assets/styles/images/avatar/hair/short_01.png', $layers[1]['sheet']);
        $this->assertSame('/gear/head.png', $layers[2]['sheet']);
    }

    public function testBuildWithBeardAndFaceMark(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasAvatar')->willReturn(true);
        $player->method('getAvatarAppearance')->willReturn([
            'body' => 'human_m_light',
            'hair' => 'short_01',
            'beard' => 'full_01',
            'beardColor' => '#8b4513',
            'faceMark' => 'scar_01',
        ]);

        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);

        $result = $this->builder->build($player);
        $layers = $result['avatar']['layers'];

        $this->assertCount(3, $layers);
        $this->assertSame('/assets/styles/images/avatar/hair/short_01.png', $layers[0]['sheet']);
        $this->assertSame('/assets/styles/images/avatar/beard/full_01.png', $layers[1]['sheet']);
        $this->assertSame(0x8b4513, $layers[1]['tint']);
        $this->assertSame('/assets/styles/images/avatar/facemark/scar_01.png', $layers[2]['sheet']);
    }

    public function testHashIsDeterministic(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasAvatar')->willReturn(true);
        $player->method('getAvatarAppearance')->willReturn([
            'body' => 'human_m_light',
            'hair' => 'short_01',
            'hairColor' => '#d6b25e',
        ]);

        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);

        $result1 = $this->builder->build($player);
        $result2 = $this->builder->build($player);

        $this->assertSame($result1['avatarHash'], $result2['avatarHash']);
    }
}
