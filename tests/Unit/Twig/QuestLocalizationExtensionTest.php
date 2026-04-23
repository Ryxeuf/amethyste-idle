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

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $quest = (new Quest())
            ->setDescription('Chasse les gobelins de la foret.')
            ->setDescriptionTranslations(['en' => 'Hunt the goblins in the forest.']);

        $extension = new QuestLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Hunt the goblins in the forest.', $extension->localizedQuestDescription($quest));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $quest = (new Quest())
            ->setDescription('Chasse les gobelins de la foret.')
            ->setDescriptionTranslations(['de' => 'Jage die Goblins im Wald.']);

        $extension = new QuestLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Chasse les gobelins de la foret.', $extension->localizedQuestDescription($quest));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenRequestStackIsEmpty(): void
    {
        $quest = (new Quest())
            ->setDescription('Chasse les gobelins de la foret.')
            ->setDescriptionTranslations(['en' => 'Hunt the goblins in the forest.']);

        $extension = new QuestLocalizationExtension(new RequestStack());

        $this->assertSame('Chasse les gobelins de la foret.', $extension->localizedQuestDescription($quest));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullQuest(): void
    {
        $extension = new QuestLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedQuestDescription(null));
    }

    public function testFilterIsRegistered(): void
    {
        $extension = new QuestLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_quest_name', $filters[0]->getName());
        $this->assertSame('localized_quest_description', $filters[1]->getName());
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
