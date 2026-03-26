<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326FightMetadata extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add metadata JSON column to fight table for quest condition tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fight ADD COLUMN IF NOT EXISTS metadata JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fight DROP COLUMN IF EXISTS metadata');
    }
}
