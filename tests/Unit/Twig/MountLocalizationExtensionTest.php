<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\Mount;
use App\Twig\MountLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class MountLocalizationExtensionTest extends TestCase
{
    public function testNameFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $mount = (new Mount())
            ->setName('Cheval brun')
            ->setNameTranslations(['en' => 'Brown Horse']);

        $extension = new MountLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Brown Horse', $extension->localizedMountName($mount));
    }

    public function testNameFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $mount = (new Mount())
            ->setName('Cheval brun')
            ->setNameTranslations(['de' => 'Braunes Pferd']);

        $extension = new MountLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Cheval brun', $extension->localizedMountName($mount));
    }

    public function testNameFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $mount = (new Mount())
            ->setName('Cheval brun')
            ->setNameTranslations(['en' => 'Brown Horse']);

        $extension = new MountLocalizationExtension(new RequestStack());

        $this->assertSame('Cheval brun', $extension->localizedMountName($mount));
    }

    public function testNameFilterReturnsEmptyStringForNullMount(): void
    {
        $extension = new MountLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedMountName(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $mount = (new Mount())
            ->setDescription('Une monture commune.')
            ->setDescriptionTranslations(['en' => 'A common mount.']);

        $extension = new MountLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('A common mount.', $extension->localizedMountDescription($mount));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $mount = (new Mount())
            ->setDescription('Une monture commune.')
            ->setDescriptionTranslations(['de' => 'Ein gewoehnliches Reittier.']);

        $extension = new MountLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Une monture commune.', $extension->localizedMountDescription($mount));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenRequestStackIsEmpty(): void
    {
        $mount = (new Mount())
            ->setDescription('Une monture commune.')
            ->setDescriptionTranslations(['en' => 'A common mount.']);

        $extension = new MountLocalizationExtension(new RequestStack());

        $this->assertSame('Une monture commune.', $extension->localizedMountDescription($mount));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullMount(): void
    {
        $extension = new MountLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedMountDescription(null));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new MountLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_mount_name', $filters[0]->getName());
        $this->assertSame('localized_mount_description', $filters[1]->getName());
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
