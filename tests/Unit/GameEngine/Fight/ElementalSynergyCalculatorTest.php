<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\Game\Spell;
use App\GameEngine\Fight\ElementalSynergyCalculator;
use PHPUnit\Framework\TestCase;

class ElementalSynergyCalculatorTest extends TestCase
{
    private ElementalSynergyCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ElementalSynergyCalculator();
    }

    // --- Tests de synergie eau + feu => vapeur ---

    public function testWaterFireSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_WATER, Spell::ELEMENT_FIRE);

        $this->assertNotNull($synergy);
        $this->assertSame('steam', $synergy['name']);
        $this->assertSame('Vapeur', $synergy['label']);
        $this->assertSame(1.2, $synergy['damageMultiplier']);
    }

    public function testFireWaterSynergyIsBidirectional(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_FIRE, Spell::ELEMENT_WATER);

        $this->assertNotNull($synergy);
        $this->assertSame('steam', $synergy['name']);
    }

    // --- Tests de synergie terre + air => tempete de sable ---

    public function testEarthAirSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_EARTH, Spell::ELEMENT_AIR);

        $this->assertNotNull($synergy);
        $this->assertSame('sandstorm', $synergy['name']);
        $this->assertSame(1.5, $synergy['damageMultiplier']);
    }

    public function testAirEarthSynergyIsBidirectional(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_AIR, Spell::ELEMENT_EARTH);

        $this->assertNotNull($synergy);
        $this->assertSame('sandstorm', $synergy['name']);
    }

    // --- Tests de synergie lumiere + tenebres => eclipse ---

    public function testLightDarkSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_LIGHT, Spell::ELEMENT_DARK);

        $this->assertNotNull($synergy);
        $this->assertSame('eclipse', $synergy['name']);
        $this->assertSame(2.5, $synergy['damageMultiplier']);
        $this->assertSame(10, $synergy['selfDamagePercent']);
    }

    public function testDarkLightSynergyIsBidirectional(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_DARK, Spell::ELEMENT_LIGHT);

        $this->assertNotNull($synergy);
        $this->assertSame('eclipse', $synergy['name']);
    }

    // --- Tests de synergie feu + terre => explosion florale ---

    public function testFireEarthSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_FIRE, Spell::ELEMENT_EARTH);

        $this->assertNotNull($synergy);
        $this->assertSame('floral_explosion', $synergy['name']);
        $this->assertSame(1.3, $synergy['damageMultiplier']);
        $this->assertSame('poison', $synergy['statusEffect']);
    }

    public function testEarthFireSynergyIsBidirectional(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_EARTH, Spell::ELEMENT_FIRE);

        $this->assertNotNull($synergy);
        $this->assertSame('floral_explosion', $synergy['name']);
    }

    // --- Tests d'absence de synergie ---

    public function testNoSynergyForSameElement(): void
    {
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_FIRE, Spell::ELEMENT_FIRE));
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_WATER, Spell::ELEMENT_WATER));
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_EARTH, Spell::ELEMENT_EARTH));
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_DARK, Spell::ELEMENT_DARK));
    }

    public function testNoSynergyForNoneElement(): void
    {
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_NONE, Spell::ELEMENT_FIRE));
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_FIRE, Spell::ELEMENT_NONE));
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_NONE, Spell::ELEMENT_NONE));
    }

    public function testNoSynergyForUnmatchedPair(): void
    {
        // Water + Earth n'a pas de synergie definie
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_WATER, Spell::ELEMENT_EARTH));
        // Air + Fire n'a pas de synergie definie
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_AIR, Spell::ELEMENT_FIRE));
        // Light + Water n'a pas de synergie definie
        $this->assertNull($this->calculator->checkSynergy(Spell::ELEMENT_LIGHT, Spell::ELEMENT_WATER));
    }

    // --- Tests du multiplicateur de degats ---

    public function testApplySynergyDamageWithMultiplier(): void
    {
        $baseDamage = 100;
        $synergyData = ['damageMultiplier' => 1.5];

        $result = $this->calculator->applySynergyDamage($baseDamage, $synergyData);

        $this->assertSame(150, $result);
    }

    public function testApplySynergyDamageWithEclipseMultiplier(): void
    {
        $baseDamage = 100;
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_LIGHT, Spell::ELEMENT_DARK);

        $result = $this->calculator->applySynergyDamage($baseDamage, $synergy);

        // 100 * 2.5 = 250
        $this->assertSame(250, $result);
    }

    public function testApplySynergyDamageWithSteamMultiplier(): void
    {
        $baseDamage = 50;
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_WATER, Spell::ELEMENT_FIRE);

        $result = $this->calculator->applySynergyDamage($baseDamage, $synergy);

        // 50 * 1.2 = 60
        $this->assertSame(60, $result);
    }

    public function testApplySynergyDamageDefaultMultiplierIs1(): void
    {
        $baseDamage = 100;

        $result = $this->calculator->applySynergyDamage($baseDamage, []);

        // Pas de damageMultiplier => defaut 1.0 => 100
        $this->assertSame(100, $result);
    }

    public function testApplySynergyDamageRoundsToInteger(): void
    {
        $baseDamage = 33;
        $synergyData = ['damageMultiplier' => 1.2];

        $result = $this->calculator->applySynergyDamage($baseDamage, $synergyData);

        // 33 * 1.2 = 39.6 => arrondi a 40
        $this->assertSame(40, $result);
    }

    // --- Tests des degats sur soi (eclipse) ---

    public function testGetSelfDamageWithEclipse(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_LIGHT, Spell::ELEMENT_DARK);

        $selfDamage = $this->calculator->getSelfDamage(200, $synergy);

        // 200 * 10 / 100 = 20
        $this->assertSame(20, $selfDamage);
    }

    public function testGetSelfDamageReturnsZeroWhenNoSelfDamagePercent(): void
    {
        $synergy = $this->calculator->checkSynergy(Spell::ELEMENT_WATER, Spell::ELEMENT_FIRE);

        $selfDamage = $this->calculator->getSelfDamage(200, $synergy);

        // steam n'a pas de selfDamagePercent
        $this->assertSame(0, $selfDamage);
    }

    public function testGetSelfDamageReturnsZeroForEmptyData(): void
    {
        $selfDamage = $this->calculator->getSelfDamage(100, []);

        $this->assertSame(0, $selfDamage);
    }

    public function testGetSelfDamageRoundsToInteger(): void
    {
        $synergyData = ['selfDamagePercent' => 10];

        $selfDamage = $this->calculator->getSelfDamage(155, $synergyData);

        // 155 * 10 / 100 = 15.5 => arrondi a 16
        $this->assertSame(16, $selfDamage);
    }

    // --- Test de getAllSynergies ---

    public function testGetAllSynergiesReturnsAllDefined(): void
    {
        $all = $this->calculator->getAllSynergies();

        $this->assertCount(4, $all);
        $this->assertArrayHasKey('water+fire', $all);
        $this->assertArrayHasKey('earth+air', $all);
        $this->assertArrayHasKey('light+dark', $all);
        $this->assertArrayHasKey('fire+earth', $all);
    }

    public function testGetAllSynergiesContainsExpectedData(): void
    {
        $all = $this->calculator->getAllSynergies();

        foreach ($all as $key => $data) {
            $this->assertArrayHasKey('name', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('damageMultiplier', $data);
            $this->assertArrayHasKey('description', $data);
        }
    }
}
