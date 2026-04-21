<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421MonsterNameTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name_translations JSON column to game_monsters (task 135 sous-phase 3e.a)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS name_translations');
    }
}
