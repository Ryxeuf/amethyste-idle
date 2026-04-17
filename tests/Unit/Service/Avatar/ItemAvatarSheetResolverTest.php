<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Avatar;

use App\Entity\Game\Item;
use App\Service\Avatar\ItemAvatarSheetResolver;
use PHPUnit\Framework\TestCase;

class ItemAvatarSheetResolverTest extends TestCase
{
    private ItemAvatarSheetResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ItemAvatarSheetResolver();
    }

    public function testExplicitAvatarSheetOverridesConvention(): void
    {
        $item = $this->buildGearItem(
            slug: 'iron_chestplate',
            location: Item::GEAR_LOCATION_CHEST,
            explicit: '/custom/path/unique_plate.png',
        );

        $this->assertSame('/custom/path/unique_plate.png', $this->resolver->resolve($item));
    }

    public function testEmptyExplicitSheetFallsBackToConvention(): void
    {
        $item = $this->buildGearItem(
            slug: 'leather_boots',
            location: Item::GEAR_LOCATION_FOOT,
            explicit: '',
        );

        $this->assertSame(
            '/assets/styles/images/avatar/foot/leather_boots.png',
            $this->resolver->resolve($item),
        );
    }

    /**
     * @dataProvider gearLocationProvider
     */
    public function testConventionMapsEachVisibleGearLocation(string $location, string $directory): void
    {
        $item = $this->buildGearItem(slug: 'ashen_gear', location: $location);

        $this->assertSame(
            sprintf('/assets/styles/images/avatar/%s/ashen_gear.png', $directory),
            $this->resolver->resolve($item),
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function gearLocationProvider(): array
    {
        return [
            'head' => [Item::GEAR_LOCATION_HEAD, 'head'],
            'chest' => [Item::GEAR_LOCATION_CHEST, 'chest'],
            'leg' => [Item::GEAR_LOCATION_LEG, 'leg'],
            'foot' => [Item::GEAR_LOCATION_FOOT, 'foot'],
            'hand' => [Item::GEAR_LOCATION_HAND, 'hand'],
            'belt' => [Item::GEAR_LOCATION_BELT, 'belt'],
            'shoulder' => [Item::GEAR_LOCATION_SHOULDER, 'shoulder'],
            'main_weapon' => [Item::GEAR_LOCATION_MAIN_WEAPON, 'weapon_main'],
            'side_weapon' => [Item::GEAR_LOCATION_SIDE_WEAPON, 'weapon_side'],
        ];
    }

    /**
     * @dataProvider nonVisualLocationProvider
     */
    public function testNonVisualGearLocationsReturnNull(string $location): void
    {
        $item = $this->buildGearItem(slug: 'silver_band', location: $location);

        $this->assertNull($this->resolver->resolve($item));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonVisualLocationProvider(): array
    {
        return [
            'neck' => [Item::GEAR_LOCATION_NECK],
            'ring_1' => [Item::GEAR_LOCATION_RING_1],
            'ring_2' => [Item::GEAR_LOCATION_RING_2],
        ];
    }

    public function testNonGearItemReturnsNull(): void
    {
        $item = new Item();
        $item->setType(Item::TYPE_MATERIA);
        $item->setSlug('m1-fire');
        $item->setGearLocation(Item::GEAR_LOCATION_CHEST);

        $this->assertNull($this->resolver->resolve($item));
    }

    public function testGearWithoutLocationReturnsNull(): void
    {
        $item = new Item();
        $item->setType(Item::TYPE_GEAR_PIECE);
        $item->setSlug('mystery_piece');

        $this->assertNull($this->resolver->resolve($item));
    }

    public function testGearWithEmptySlugReturnsNull(): void
    {
        $item = new Item();
        $item->setType(Item::TYPE_GEAR_PIECE);
        $item->setGearLocation(Item::GEAR_LOCATION_CHEST);
        $item->setSlug('');

        $this->assertNull($this->resolver->resolve($item));
    }

    private function buildGearItem(string $slug, string $location, ?string $explicit = null): Item
    {
        $item = new Item();
        $item->setType(Item::TYPE_GEAR_PIECE);
        $item->setSlug($slug);
        $item->setGearLocation($location);
        $item->setAvatarSheet($explicit);

        return $item;
    }
}
