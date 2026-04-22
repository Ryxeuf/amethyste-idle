<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\Quest;
use App\Twig\QuestLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class QuestLocalizationExtensionTest extends TestCase
{
    public function testFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $quest = (new Quest())
            ->setName('Chasse aux gobelins')
            ->setNameTranslations(['en' => 'Goblin Hunt']);

        $extension = new QuestLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Goblin Hunt', $extension->localizedQuestName($quest));
    }

    public function testFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $quest = (new Quest())
            ->setName('Chasse aux gobelins')
            ->setNameTranslations(['de' => 'Goblinjagd']);

        $extension = new QuestLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Chasse aux gobelins', $extension->localizedQuestName($quest));
    }

    public function testFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $quest = (new Quest())
            ->setName('Chasse aux gobelins')
            ->setNameTranslations(['en' => 'Goblin Hunt']);

        $extension = new QuestLocalizationExtension(new RequestStack());

        $this->assertSame('Chasse aux gobelins', $extension->localizedQuestName($quest));
    }

    public function testFilterReturnsEmptyStringForNullQuest(): void
    {
        $extension = new QuestLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedQuestName(null));
    }

    public function testFilterIsRegistered(): void
    {
        $extension = new QuestLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('localized_quest_name', $filters[0]->getName());
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
