<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327CoopFightTurnKey extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add current_turn_key column to fight table for cooperative combat turn tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE fight ADD COLUMN IF NOT EXISTS current_turn_key VARCHAR(50) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE fight DROP COLUMN IF EXISTS current_turn_key
        SQL);
    }
}
