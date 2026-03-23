<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323ItemElement extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add element column to game_items table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game_items ADD COLUMN IF NOT EXISTS element VARCHAR(25) NOT NULL DEFAULT 'none'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS element');
    }
}
