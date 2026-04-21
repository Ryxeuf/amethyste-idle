<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\Monster;
use App\Twig\MonsterLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class MonsterLocalizationExtensionTest extends TestCase
{
    public function testFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $monster = (new Monster())
            ->setName('Gobelin')
            ->setNameTranslations(['en' => 'Goblin']);

        $extension = new MonsterLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Goblin', $extension->localizedMonsterName($monster));
    }

    public function testFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $monster = (new Monster())
            ->setName('Gobelin')
            ->setNameTranslations(['de' => 'Kobold']);

        $extension = new MonsterLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Gobelin', $extension->localizedMonsterName($monster));
    }

    public function testFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $monster = (new Monster())
            ->setName('Gobelin')
            ->setNameTranslations(['en' => 'Goblin']);

        $extension = new MonsterLocalizationExtension(new RequestStack());

        $this->assertSame('Gobelin', $extension->localizedMonsterName($monster));
    }

    public function testFilterReturnsEmptyStringForNullMonster(): void
    {
        $extension = new MonsterLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedMonsterName(null));
    }

    public function testFilterIsRegistered(): void
    {
        $extension = new MonsterLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('localized_monster_name', $filters[0]->getName());
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
