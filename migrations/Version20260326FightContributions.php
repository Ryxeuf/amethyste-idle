<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326FightContributions extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contributions JSON column to fight table for world boss damage tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fight ADD COLUMN IF NOT EXISTS contributions JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fight DROP COLUMN IF EXISTS contributions');
    }
}
