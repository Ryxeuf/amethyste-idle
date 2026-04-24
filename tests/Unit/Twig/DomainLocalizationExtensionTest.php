<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Dto\Domain\DomainModel;
use App\Entity\Game\Domain;
use App\Twig\DomainLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DomainLocalizationExtensionTest extends TestCase
{
    public function testFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $domain = (new Domain())
            ->setTitle('Guerrier');
        $domain->setTitleTranslations(['en' => 'Warrior']);

        $extension = new DomainLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Warrior', $extension->localizedDomainTitle($domain));
    }

    public function testFilterAcceptsDomainModelAndDelegatesToEntity(): void
    {
        $domain = (new Domain())
            ->setTitle('Guerrier');
        $domain->setTitleTranslations(['en' => 'Warrior']);
        $this->forceDomainId($domain, 1);
        $model = new DomainModel($domain);

        $extension = new DomainLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Warrior', $extension->localizedDomainTitle($model));
    }

    public function testFilterFallsBackToBaseTitleWhenTranslationMissing(): void
    {
        $domain = (new Domain())
            ->setTitle('Guerrier');
        $domain->setTitleTranslations(['de' => 'Krieger']);

        $extension = new DomainLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Guerrier', $extension->localizedDomainTitle($domain));
    }

    public function testFilterFallsBackToBaseTitleWhenRequestStackIsEmpty(): void
    {
        $domain = (new Domain())
            ->setTitle('Guerrier');
        $domain->setTitleTranslations(['en' => 'Warrior']);

        $extension = new DomainLocalizationExtension(new RequestStack());

        $this->assertSame('Guerrier', $extension->localizedDomainTitle($domain));
    }

    public function testFilterReturnsEmptyStringForNullDomain(): void
    {
        $extension = new DomainLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedDomainTitle(null));
    }

    public function testFilterIsRegistered(): void
    {
        $extension = new DomainLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('localized_domain_title', $filters[0]->getName());
    }

    private function forceDomainId(Domain $domain, int $id): void
    {
        $reflection = new \ReflectionProperty(Domain::class, 'id');
        $reflection->setValue($domain, $id);
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
