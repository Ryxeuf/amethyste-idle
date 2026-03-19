<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318SpellLevelValueType extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add level and value_type columns to game_spells table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS level INTEGER NOT NULL DEFAULT 1');
        $this->addSql("ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS value_type VARCHAR(10) NOT NULL DEFAULT 'fixed'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS level');
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS value_type');
    }
}
