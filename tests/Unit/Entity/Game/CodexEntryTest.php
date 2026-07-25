<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\CodexEntry;
use PHPUnit\Framework\TestCase;

final class CodexEntryTest extends TestCase
{
    public function testDefaults(): void
    {
        $entry = new CodexEntry();

        self::assertNull($entry->getId());
        self::assertSame(CodexEntry::CATEGORY_REGION, $entry->getCategory());
        self::assertSame(CodexEntry::UNLOCK_MANUAL, $entry->getUnlockType());
        self::assertNull($entry->getUnlockKey());
        self::assertNull($entry->getIllustrationPath());
    }

    public function testLocalizedTitleFallsBackToBase(): void
    {
        $entry = (new CodexEntry())->setTitle('La Forêt');

        self::assertSame('La Forêt', $entry->getLocalizedTitle('en'));

        $entry->setTitleTranslations(['en' => 'The Forest']);
        self::assertSame('The Forest', $entry->getLocalizedTitle('en'));
        self::assertSame('La Forêt', $entry->getLocalizedTitle('de'));
        self::assertSame('La Forêt', $entry->getLocalizedTitle(null));
    }

    public function testLocalizedDescriptionFallsBackToBase(): void
    {
        $entry = (new CodexEntry())->setDescription('Description FR');
        $entry->setDescriptionTranslations(['en' => 'Description EN']);

        self::assertSame('Description EN', $entry->getLocalizedDescription('en'));
        self::assertSame('Description FR', $entry->getLocalizedDescription('es'));
    }

    public function testTranslationsNormalisationDropsEmptyValues(): void
    {
        $entry = (new CodexEntry())->setTitle('Base');
        $entry->setTitleTranslations(['en' => '  ', '' => 'x', 'de' => 'Wald']);

        self::assertSame(['de' => 'Wald'], $entry->getTitleTranslations());
    }

    public function testUnlockKeyNormalisesEmptyToNull(): void
    {
        $entry = new CodexEntry();

        $entry->setUnlockKey('  ');
        self::assertNull($entry->getUnlockKey());

        $entry->setUnlockKey('  foret-des-murmures  ');
        self::assertSame('foret-des-murmures', $entry->getUnlockKey());
    }

    public function testUnlockTypeAndKeyRoundTrip(): void
    {
        $entry = (new CodexEntry())
            ->setUnlockType(CodexEntry::UNLOCK_BOSS_KILL)
            ->setUnlockKey('forest_guardian');

        self::assertSame(CodexEntry::UNLOCK_BOSS_KILL, $entry->getUnlockType());
        self::assertSame('forest_guardian', $entry->getUnlockKey());
    }

    public function testIsPublicOnlyForWorldFact(): void
    {
        self::assertFalse((new CodexEntry())->setCategory(CodexEntry::CATEGORY_REGION)->isPublic());
        self::assertFalse((new CodexEntry())->setCategory(CodexEntry::CATEGORY_BESTIARY_LORE)->isPublic());
        self::assertTrue((new CodexEntry())->setCategory(CodexEntry::CATEGORY_WORLD_FACT)->isPublic());
    }

    public function testCreditedGuildNameNormalisesEmptyToNull(): void
    {
        $entry = new CodexEntry();
        self::assertNull($entry->getCreditedGuildName());

        $entry->setCreditedGuildName('   ');
        self::assertNull($entry->getCreditedGuildName());

        $entry->setCreditedGuildName('  Les Gardiens  ');
        self::assertSame('Les Gardiens', $entry->getCreditedGuildName());
    }
}
