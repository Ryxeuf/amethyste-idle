<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\EquipmentSet;
use App\Twig\EquipmentSetLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EquipmentSetLocalizationExtensionTest extends TestCase
{
    public function testNameFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $set = new EquipmentSet();
        $set->setName('Set du Gardien');
        $set->setNameTranslations(['en' => 'Guardian Set']);

        $extension = new EquipmentSetLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Guardian Set', $extension->localizedEquipmentSetName($set));
    }

    public function testNameFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $set = new EquipmentSet();
        $set->setName('Set du Gardien');
        $set->setNameTranslations(['de' => 'Wachterset']);

        $extension = new EquipmentSetLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Set du Gardien', $extension->localizedEquipmentSetName($set));
    }

    public function testNameFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $set = new EquipmentSet();
        $set->setName('Set du Gardien');
        $set->setNameTranslations(['en' => 'Guardian Set']);

        $extension = new EquipmentSetLocalizationExtension(new RequestStack());

        $this->assertSame('Set du Gardien', $extension->localizedEquipmentSetName($set));
    }

    public function testNameFilterReturnsEmptyStringForNullSet(): void
    {
        $extension = new EquipmentSetLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedEquipmentSetName(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $set = new EquipmentSet();
        $set->setDescription("L'equipement traditionnel des gardiens.");
        $set->setDescriptionTranslations(['en' => "The traditional equipment of guardians."]);

        $extension = new EquipmentSetLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame("The traditional equipment of guardians.", $extension->localizedEquipmentSetDescription($set));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $set = new EquipmentSet();
        $set->setDescription("L'equipement traditionnel des gardiens.");
        $set->setDescriptionTranslations(['de' => 'Die traditionelle Ausruestung.']);

        $extension = new EquipmentSetLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame("L'equipement traditionnel des gardiens.", $extension->localizedEquipmentSetDescription($set));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenRequestStackIsEmpty(): void
    {
        $set = new EquipmentSet();
        $set->setDescription("L'equipement traditionnel des gardiens.");
        $set->setDescriptionTranslations(['en' => "The traditional equipment of guardians."]);

        $extension = new EquipmentSetLocalizationExtension(new RequestStack());

        $this->assertSame("L'equipement traditionnel des gardiens.", $extension->localizedEquipmentSetDescription($set));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullSet(): void
    {
        $extension = new EquipmentSetLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedEquipmentSetDescription(null));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new EquipmentSetLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_equipment_set_name', $filters[0]->getName());
        $this->assertSame('localized_equipment_set_description', $filters[1]->getName());
    }

    private function stackWithLocale(string $locale): RequestStack
    {
        $request = Request::create('/');
        $request->setLocale($locale);

        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }
}
