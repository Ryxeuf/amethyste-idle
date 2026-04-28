<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\PlayerJournalEntry;
use PHPUnit\Framework\TestCase;

class PlayerJournalEntryLocalizationTest extends TestCase
{
    public function testTypeLabelsAreTranslationKeysForEveryType(): void
    {
        foreach (PlayerJournalEntry::TYPES as $type) {
            $this->assertArrayHasKey($type, PlayerJournalEntry::TYPE_LABELS);
            $this->assertSame(
                'game.journal.type.'.$type,
                PlayerJournalEntry::TYPE_LABELS[$type],
                'Each type label should be the translation key game.journal.type.<type>',
            );
        }
    }

    public function testGetTypeLabelReturnsTranslationKeyForKnownType(): void
    {
        $entry = new PlayerJournalEntry();
        $entry->setType(PlayerJournalEntry::TYPE_COMBAT_VICTORY);

        $this->assertSame('game.journal.type.combat_victory', $entry->getTypeLabel());
    }

    public function testGetTypeLabelFallsBackToRawTypeWhenUnknown(): void
    {
        $entry = new PlayerJournalEntry();
        $entry->setType('unknown_legacy_type');

        $this->assertSame('unknown_legacy_type', $entry->getTypeLabel());
    }

    public function testTranslationKeysExistInFrenchAndEnglishCatalogs(): void
    {
        $fr = json_decode((string) file_get_contents(__DIR__.'/../../../../translations/messages.fr.json'), true);
        $en = json_decode((string) file_get_contents(__DIR__.'/../../../../translations/messages.en.json'), true);

        $this->assertIsArray($fr);
        $this->assertIsArray($en);

        foreach (PlayerJournalEntry::TYPES as $type) {
            $this->assertArrayHasKey($type, $fr['game']['journal']['type'] ?? [], 'FR catalog should expose game.journal.type.'.$type);
            $this->assertArrayHasKey($type, $en['game']['journal']['type'] ?? [], 'EN catalog should expose game.journal.type.'.$type);
            $this->assertNotSame('', trim((string) $fr['game']['journal']['type'][$type]));
            $this->assertNotSame('', trim((string) $en['game']['journal']['type'][$type]));
        }
    }

    public function testJournalUiKeysExistInBothCatalogs(): void
    {
        $fr = json_decode((string) file_get_contents(__DIR__.'/../../../../translations/messages.fr.json'), true);
        $en = json_decode((string) file_get_contents(__DIR__.'/../../../../translations/messages.en.json'), true);

        $required = [
            ['title'],
            ['entries_count'],
            ['filter_all'],
            ['empty', 'heading'],
            ['empty', 'hint'],
            ['pagination', 'previous'],
            ['pagination', 'next'],
            ['pagination', 'page'],
        ];

        foreach ($required as $path) {
            $this->assertNotEmpty($this->lookup($fr, ['game', 'journal', ...$path]), 'FR missing key: '.implode('.', $path));
            $this->assertNotEmpty($this->lookup($en, ['game', 'journal', ...$path]), 'EN missing key: '.implode('.', $path));
        }
    }

    private function lookup(array $catalog, array $path): ?string
    {
        $cursor = $catalog;
        foreach ($path as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }
            $cursor = $cursor[$segment];
        }

        return is_string($cursor) ? $cursor : null;
    }
}
