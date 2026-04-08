<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\Element;
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
        $synergy = $this->calculator->checkSynergy(Element::Water, Element::Fire);

        $this->assertNotNull($synergy);
        $this->assertSame('steam', $synergy['name']);
        $this->assertSame('Vapeur', $synergy['label']);
        $this->assertSame(1.2, $synergy['damageMultiplier']);
    }

    public function testFireWaterSynergyIsBidirectional(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Fire, Element::Water);

        $this->assertNotNull($synergy);
        $this->assertSame('steam', $synergy['name']);
    }

    // --- Tests de synergie terre + air => tempete de sable ---

    public function testEarthAirSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Earth, Element::Air);

        $this->assertNotNull($synergy);
        $this->assertSame('sandstorm', $synergy['name']);
        $this->assertSame(1.4, $synergy['damageMultiplier']);
    }

    public function testAirEarthSynergyIsBidirectional(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Air, Element::Earth);

        $this->assertNotNull($synergy);
        $this->assertSame('sandstorm', $synergy['name']);
    }

    // --- Tests de synergie lumiere + tenebres => eclipse ---

    public function testLightDarkSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Light, Element::Dark);

        $this->assertNotNull($synergy);
        $this->assertSame('eclipse', $synergy['name']);
        $this->assertSame(1.5, $synergy['damageMultiplier']);
        $this->assertSame(5, $synergy['selfDamagePercent']);
    }

    public function testDarkLightSynergyIsBidirectional(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Dark, Element::Light);

        $this->assertNotNull($synergy);
        $this->assertSame('eclipse', $synergy['name']);
    }

    // --- Tests de synergie feu + terre => explosion florale ---

    public function testFireEarthSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Fire, Element::Earth);

        $this->assertNotNull($synergy);
        $this->assertSame('floral_explosion', $synergy['name']);
        $this->assertSame(1.25, $synergy['damageMultiplier']);
        $this->assertSame('poison', $synergy['statusEffect']);
    }

    public function testEarthFireSynergyIsBidirectional(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Earth, Element::Fire);

        $this->assertNotNull($synergy);
        $this->assertSame('floral_explosion', $synergy['name']);
    }

    // --- Tests synergies metal ---

    public function testMetalFireSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Metal, Element::Fire);

        $this->assertNotNull($synergy);
        $this->assertSame('forge', $synergy['name']);
        $this->assertSame(1.3, $synergy['damageMultiplier']);
        $this->assertSame('burn', $synergy['statusEffect']);
    }

    public function testMetalLightSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Metal, Element::Light);

        $this->assertNotNull($synergy);
        $this->assertSame('holy_blade', $synergy['name']);
        $this->assertSame(1.5, $synergy['damageMultiplier']);
    }

    // --- Tests synergies beast ---

    public function testBeastEarthSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Beast, Element::Earth);

        $this->assertNotNull($synergy);
        $this->assertSame('primal_fury', $synergy['name']);
        $this->assertSame(1.3, $synergy['damageMultiplier']);
        $this->assertSame('berserk', $synergy['statusEffect']);
    }

    public function testBeastDarkSynergyExists(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Beast, Element::Dark);

        $this->assertNotNull($synergy);
        $this->assertSame('venomous_shadow', $synergy['name']);
        $this->assertSame(1.35, $synergy['damageMultiplier']);
        $this->assertSame('poison', $synergy['statusEffect']);
    }

    // --- Tests d'absence de synergie ---

    public function testNoSynergyForSameElement(): void
    {
        $this->assertNull($this->calculator->checkSynergy(Element::Fire, Element::Fire));
        $this->assertNull($this->calculator->checkSynergy(Element::Water, Element::Water));
        $this->assertNull($this->calculator->checkSynergy(Element::Earth, Element::Earth));
        $this->assertNull($this->calculator->checkSynergy(Element::Dark, Element::Dark));
        $this->assertNull($this->calculator->checkSynergy(Element::Metal, Element::Metal));
        $this->assertNull($this->calculator->checkSynergy(Element::Beast, Element::Beast));
    }

    public function testNoSynergyForNoneElement(): void
    {
        $this->assertNull($this->calculator->checkSynergy(Element::None, Element::Fire));
        $this->assertNull($this->calculator->checkSynergy(Element::Fire, Element::None));
        $this->assertNull($this->calculator->checkSynergy(Element::None, Element::None));
    }

    public function testNoSynergyForUnmatchedPair(): void
    {
        $this->assertNull($this->calculator->checkSynergy(Element::Water, Element::Earth));
        $this->assertNull($this->calculator->checkSynergy(Element::Air, Element::Fire));
        $this->assertNull($this->calculator->checkSynergy(Element::Light, Element::Water));
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
        $synergy = $this->calculator->checkSynergy(Element::Light, Element::Dark);

        $result = $this->calculator->applySynergyDamage($baseDamage, $synergy);

        // 100 * 1.5 = 150
        $this->assertSame(150, $result);
    }

    public function testApplySynergyDamageWithSteamMultiplier(): void
    {
        $baseDamage = 50;
        $synergy = $this->calculator->checkSynergy(Element::Water, Element::Fire);

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
        $synergy = $this->calculator->checkSynergy(Element::Light, Element::Dark);

        $selfDamage = $this->calculator->getSelfDamage(200, $synergy);

        // 200 * 5 / 100 = 10
        $this->assertSame(10, $selfDamage);
    }

    public function testGetSelfDamageReturnsZeroWhenNoSelfDamagePercent(): void
    {
        $synergy = $this->calculator->checkSynergy(Element::Water, Element::Fire);

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

        // 4 originales + 2 metal + 2 beast = 8
        $this->assertCount(8, $all);
        $this->assertArrayHasKey('water+fire', $all);
        $this->assertArrayHasKey('earth+air', $all);
        $this->assertArrayHasKey('light+dark', $all);
        $this->assertArrayHasKey('fire+earth', $all);
        $this->assertArrayHasKey('metal+fire', $all);
        $this->assertArrayHasKey('metal+light', $all);
        $this->assertArrayHasKey('beast+earth', $all);
        $this->assertArrayHasKey('beast+dark', $all);
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
