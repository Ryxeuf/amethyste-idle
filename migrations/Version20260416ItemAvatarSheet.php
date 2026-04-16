<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416ItemAvatarSheet extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar_sheet column to game_items (AVT-16 — visual layer sprite sheet for equipped items)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS avatar_sheet VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS avatar_sheet');
    }
}
