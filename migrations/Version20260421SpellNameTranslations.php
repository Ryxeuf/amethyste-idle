<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421SpellNameTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name_translations JSON column to game_spells (task 135 sous-phase 3e.c)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS name_translations');
    }
}
