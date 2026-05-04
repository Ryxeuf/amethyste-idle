<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423SpellDescriptionTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description_translations JSON column to game_spells (task 135 sous-phase 3e.c.b.spell.d)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS description_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS description_translations');
    }
}
