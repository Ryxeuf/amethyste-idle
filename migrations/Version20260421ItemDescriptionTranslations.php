<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421ItemDescriptionTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description_translations JSON column to game_items (task 135 sous-phase 3d)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS description_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS description_translations');
    }
}
