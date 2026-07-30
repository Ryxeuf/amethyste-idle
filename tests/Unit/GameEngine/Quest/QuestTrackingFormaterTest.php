<?php

namespace App\Tests\Unit\GameEngine\Quest;

use App\Entity\Game\Quest;
use App\GameEngine\Quest\QuestTrackingFormater;
use PHPUnit\Framework\TestCase;

class QuestTrackingFormaterTest extends TestCase
{
    private QuestTrackingFormater $formater;

    protected function setUp(): void
    {
        $this->formater = new QuestTrackingFormater();
    }

    public function testFormatDeliverCreatesTrackingEntries(): void
    {
        $requirements = [
            'deliver' => [
                [
                    'item_slug' => 'mushroom',
                    'pnj_id' => 5,
                    'quantity' => 3,
                    'name' => 'Champignon',
                ],
            ],
        ];

        $result = $this->formater->formatDeliver($requirements);

        $this->assertCount(1, $result);
        $this->assertEquals(0, $result[0]['count']);
        $this->assertEquals(3, $result[0]['necessary']);
        $this->assertEquals('mushroom', $result[0]['item_slug']);
        $this->assertEquals(5, $result[0]['pnj_id']);
        $this->assertEquals('Champignon', $result[0]['name']);
    }

    public function testFormatExploreCreatesTrackingEntries(): void
    {
        $requirements = [
            'explore' => [
                [
                    'map_id' => 1,
                    'coordinates' => '15.20',
                    'name' => 'Clairière',
                ],
            ],
        ];

        $result = $this->formater->formatExplore($requirements);

        $this->assertCount(1, $result);
        $this->assertEquals(0, $result[0]['count']);
        $this->assertEquals(1, $result[0]['necessary']);
        $this->assertEquals(1, $result[0]['map_id']);
        $this->assertEquals('15.20', $result[0]['coordinates']);
        $this->assertEquals('Clairière', $result[0]['name']);
    }

    public function testFormatExploreWithNullCoordinates(): void
    {
        $requirements = [
            'explore' => [
                [
                    'map_id' => 2,
                    'name' => 'Toute la carte',
                ],
            ],
        ];

        $result = $this->formater->formatExplore($requirements);

        $this->assertCount(1, $result);
        $this->assertNull($result[0]['coordinates']);
    }

    public function testFormatTrackingIncludesDeliverAndExplore(): void
    {
        $quest = new Quest();
        $quest->setRequirements([
            'deliver' => [
                [
                    'item_slug' => 'mushroom',
                    'pnj_id' => 5,
                    'quantity' => 3,
                    'name' => 'Champignon',
                ],
            ],
            'explore' => [
                [
                    'map_id' => 1,
                    'coordinates' => '10.10',
                    'name' => 'Zone test',
                ],
            ],
        ]);

        $tracking = $this->formater->formatTracking($quest);

        $this->assertArrayHasKey('deliver', $tracking);
        $this->assertArrayHasKey('explore', $tracking);
        $this->assertCount(1, $tracking['deliver']);
        $this->assertCount(1, $tracking['explore']);
    }

    public function testFormatDeliverEmptyWhenNoDeliverRequirements(): void
    {
        $result = $this->formater->formatDeliver(['monsters' => []]);

        $this->assertEmpty($result);
    }

    public function testFormatExploreEmptyWhenNoExploreRequirements(): void
    {
        $result = $this->formater->formatExplore(['monsters' => []]);

        $this->assertEmpty($result);
    }

    public function testFormatExploreAcceptsZoneSlug(): void
    {
        // Forme cible depuis ZON-22 : la cible d'exploration est une zone.
        $result = $this->formater->formatExplore([
            'explore' => [
                ['zone_slug' => 'foret-des-murmures', 'name' => 'Forêt des Murmures'],
            ],
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('foret-des-murmures', $result[0]['zone_slug']);
        $this->assertNull($result[0]['map_id']);
        $this->assertNull($result[0]['coordinates']);
        $this->assertEquals('Forêt des Murmures', $result[0]['name']);
    }

    public function testFormatEscortAcceptsDestinationZoneSlug(): void
    {
        $result = $this->formater->formatEscort([
            'escort' => [
                ['destination_zone_slug' => 'village-de-lumiere', 'name' => 'Amener le marchand'],
            ],
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('village-de-lumiere', $result[0]['destination_zone_slug']);
        $this->assertNull($result[0]['destination_map_id']);
        $this->assertNull($result[0]['destination_coordinates']);
    }

    public function testFormatEscortKeepsLegacyMapForm(): void
    {
        $result = $this->formater->formatEscort([
            'escort' => [
                ['destination_map_id' => 2, 'destination_coordinates' => '10.10', 'name' => 'Escorte'],
            ],
        ]);

        $this->assertCount(1, $result);
        $this->assertNull($result[0]['destination_zone_slug']);
        $this->assertEquals(2, $result[0]['destination_map_id']);
        $this->assertEquals('10.10', $result[0]['destination_coordinates']);
    }
}
