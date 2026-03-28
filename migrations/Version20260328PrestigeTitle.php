<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328PrestigeTitle extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add prestige_title column to player table for guild city control titles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE player ADD COLUMN IF NOT EXISTS prestige_title VARCHAR(100) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS prestige_title');
    }
}
