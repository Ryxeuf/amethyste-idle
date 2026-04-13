<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413GoldSinks extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add custom_name column to player_item for item renaming gold sink';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS custom_name VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS custom_name');
    }
}
