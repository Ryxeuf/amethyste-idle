<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328GuildColor extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add color column to guild table for prestige cosmetics (GCC-12)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE guild ADD COLUMN IF NOT EXISTS color VARCHAR(7) NOT NULL DEFAULT '#9333EA'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guild DROP COLUMN IF EXISTS color');
    }
}
