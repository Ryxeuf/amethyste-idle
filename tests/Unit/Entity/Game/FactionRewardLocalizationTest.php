<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\FactionReward;
use PHPUnit\Framework\TestCase;

class FactionRewardLocalizationTest extends TestCase
{
    public function testGetLocalizedLabelFallsBackToBaseLabelWhenNoTranslations(): void
    {
        $reward = new FactionReward();
        $reward->setLabel('Remise marchande');

        $this->assertSame('Remise marchande', $reward->getLocalizedLabel('en'));
        $this->assertSame('Remise marchande', $reward->getLocalizedLabel('fr'));
        $this->assertSame('Remise marchande', $reward->getLocalizedLabel(null));
        $this->assertSame('Remise marchande', $reward->getLocalizedLabel(''));
    }

    public function testGetLocalizedLabelReturnsMatchingTranslation(): void
    {
        $reward = new FactionReward();
        $reward->setLabel('Remise marchande');
        $reward->setLabelTranslations(['en' => 'Merchant Discount', 'de' => 'Handelsrabatt']);

        $this->assertSame('Merchant Discount', $reward->getLocalizedLabel('en'));
        $this->assertSame('Handelsrabatt', $reward->getLocalizedLabel('de'));
    }

    public function testGetLocalizedLabelFallsBackWhenLocaleMissing(): void
    {
        $reward = new FactionReward();
        $reward->setLabel('Bénédiction du chevalier');
        $reward->setLabelTranslations(['en' => "Knight's Blessing"]);

        $this->assertSame('Bénédiction du chevalier', $reward->getLocalizedLabel('es'));
        $this->assertSame('Bénédiction du chevalier', $reward->getLocalizedLabel('ja'));
    }

    public function testSetLabelTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $reward = new FactionReward();
        $reward->setLabel('Savoir arcanique');
        $reward->setLabelTranslations([
            'en' => 'Arcane Lore',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Arcane Lore'], $reward->getLabelTranslations());
        $this->assertSame('Arcane Lore', $reward->getLocalizedLabel('en'));
        $this->assertSame('Savoir arcanique', $reward->getLocalizedLabel('de'));
        $this->assertSame('Savoir arcanique', $reward->getLocalizedLabel('es'));
        $this->assertSame('Savoir arcanique', $reward->getLocalizedLabel('it'));
    }

    public function testSetLabelTranslationsWithNullResetsStorage(): void
    {
        $reward = new FactionReward();
        $reward->setLabel('Maître assassin');
        $reward->setLabelTranslations(['en' => 'Master Assassin']);
        $reward->setLabelTranslations(null);

        $this->assertSame([], $reward->getLabelTranslations());
        $this->assertSame('Maître assassin', $reward->getLocalizedLabel('en'));
    }

    public function testSetLabelTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $reward = new FactionReward();
        $reward->setLabel('Savoir arcanique');
        $reward->setLabelTranslations(['en' => 'Arcane Lore']);
        $reward->setLabelTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $reward->getLabelTranslations());
        $this->assertSame('Savoir arcanique', $reward->getLocalizedLabel('en'));
    }

    public function testGetLabelTranslationsDefaultsToEmptyArray(): void
    {
        $reward = new FactionReward();
        $reward->setLabel('Remise marchande');

        $this->assertSame([], $reward->getLabelTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $reward = new FactionReward();
        $reward->setDescription('Réduction de 10% dans toutes les boutiques.');

        $this->assertSame('Réduction de 10% dans toutes les boutiques.', $reward->getLocalizedDescription('en'));
        $this->assertSame('Réduction de 10% dans toutes les boutiques.', $reward->getLocalizedDescription('fr'));
        $this->assertSame('Réduction de 10% dans toutes les boutiques.', $reward->getLocalizedDescription(null));
        $this->assertSame('Réduction de 10% dans toutes les boutiques.', $reward->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $reward = new FactionReward();
        $reward->setDescription('Réduction de 10% dans toutes les boutiques.');
        $reward->setDescriptionTranslations([
            'en' => '10% discount in all shops.',
            'de' => '10% Rabatt in allen Geschaeften.',
        ]);

        $this->assertSame('10% discount in all shops.', $reward->getLocalizedDescription('en'));
        $this->assertSame('10% Rabatt in allen Geschaeften.', $reward->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $reward = new FactionReward();
        $reward->setDescription('+5% de dégâts physiques.');
        $reward->setDescriptionTranslations(['en' => '+5% physical damage.']);

        $this->assertSame('+5% de dégâts physiques.', $reward->getLocalizedDescription('es'));
        $this->assertSame('+5% de dégâts physiques.', $reward->getLocalizedDescription('ja'));
    }

    public function testGetLocalizedDescriptionReturnsNullWhenDescriptionIsNullAndNoTranslations(): void
    {
        $reward = new FactionReward();

        $this->assertNull($reward->getLocalizedDescription('en'));
        $this->assertNull($reward->getLocalizedDescription(null));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $reward = new FactionReward();
        $reward->setDescription('+10% d\'efficacité des soins.');
        $reward->setDescriptionTranslations([
            'en' => '+10% healing effectiveness.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => '+10% healing effectiveness.'], $reward->getDescriptionTranslations());
        $this->assertSame('+10% healing effectiveness.', $reward->getLocalizedDescription('en'));
        $this->assertSame('+10% d\'efficacité des soins.', $reward->getLocalizedDescription('de'));
        $this->assertSame('+10% d\'efficacité des soins.', $reward->getLocalizedDescription('es'));
        $this->assertSame('+10% d\'efficacité des soins.', $reward->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $reward = new FactionReward();
        $reward->setDescription('+15% de critique et +10% de précision.');
        $reward->setDescriptionTranslations(['en' => '+15% critical hit chance and +10% accuracy.']);
        $reward->setDescriptionTranslations(null);

        $this->assertSame([], $reward->getDescriptionTranslations());
        $this->assertSame('+15% de critique et +10% de précision.', $reward->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $reward = new FactionReward();
        $reward->setDescription('+5% de dégâts physiques.');
        $reward->setDescriptionTranslations(['en' => '+5% physical damage.']);
        $reward->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $reward->getDescriptionTranslations());
        $this->assertSame('+5% de dégâts physiques.', $reward->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $reward = new FactionReward();
        $reward->setDescription('Description test.');

        $this->assertSame([], $reward->getDescriptionTranslations());
    }
}
